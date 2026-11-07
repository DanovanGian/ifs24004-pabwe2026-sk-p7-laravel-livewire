<?php

namespace App\Features\Stock\Services;

use App\Features\Stock\Contracts\StockRepositoryInterface;
use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Models\Stock;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StockExcelService
{
    public function __construct(private StockRepositoryInterface $stocks, private StockService $stockService) {}

    public function exportFor(User $user, array $filters = []): BinaryFileResponse
    {
        Gate::forUser($user)->authorize('viewAny', Stock::class);

        $query = $this->stocks->queryFor($user)
            ->when(trim((string) ($filters['search'] ?? '')) !== '', function ($query) use ($filters): void {
                $term = '%'.trim((string) $filters['search']).'%';
                $query->where(function ($subQuery) use ($term): void {
                    $subQuery->where('name', 'like', $term)
                        ->orWhere('sku', 'like', $term);
                });
            })
            ->when(in_array($filters['status'] ?? '', ['available', 'low_stock', 'out_of_stock'], true), fn ($query) => $query->where('status', $filters['status'])
            );

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([[
            'name', 'sku', 'description', 'qty', 'unit', 'price', 'min_stock', 'status',
        ]], null, 'A1');

        $row = 2;
        foreach ($query->orderBy('name')->orderBy('id')->cursor() as $stock) {
            $status = StockStatus::tryFrom($stock->status);
            $sheet->fromArray([[
                $stock->name,
                $stock->sku,
                $stock->description,
                $stock->quantity,
                $stock->unit,
                $stock->price,
                $stock->min_stock,
                $status?->value ?? $stock->status,
            ]], null, 'A'.$row++);
        }

        $sheet->getStyle('A1:H1')->getFont()->setBold(true);
        foreach (range('A', 'H') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $filePath = tempnam(sys_get_temp_dir(), 'stock-export-');
        (new Xlsx($spreadsheet))->save($filePath);
        $spreadsheet->disconnectWorksheets();

        return response()->download($filePath, 'stocks.xlsx')->deleteFileAfterSend(true);
    }

    public function importFor(User $user, UploadedFile $file): int
    {
        Gate::forUser($user)->authorize('create', Stock::class);

        $spreadsheet = IOFactory::load($file->getRealPath());
        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
        $spreadsheet->disconnectWorksheets();

        if (count($rows) < 2) {
            throw ValidationException::withMessages([
                'excelFile' => 'File Excel belum berisi baris data.',
            ]);
        }

        $headers = array_map(
            fn ($header): string => strtolower(trim((string) $header)),
            array_shift($rows),
        );

        foreach (['name', 'qty'] as $requiredHeader) {
            if (! in_array($requiredHeader, $headers, true)) {
                throw ValidationException::withMessages([
                    'excelFile' => "Kolom {$requiredHeader} wajib tersedia di baris pertama.",
                ]);
            }
        }

        return DB::transaction(function () use ($headers, $rows, $user): int {
            $imported = 0;

            foreach ($rows as $index => $row) {
                if (count(array_filter($row, fn ($value): bool => $value !== null && trim((string) $value) !== '')) === 0) {
                    continue;
                }

                $values = array_pad(array_slice($row, 0, count($headers)), count($headers), null);
                $record = array_combine($headers, $values);
                $record['quantity'] = $record['qty'] ?? null;
                $record['unit'] = filled($record['unit'] ?? null) ? $record['unit'] : 'pcs';
                $record['min_stock'] = filled($record['min_stock'] ?? null) ? $record['min_stock'] : 5;
                $record['sku'] = filled($record['sku'] ?? null) ? trim((string) $record['sku']) : null;
                $record['price'] = filled($record['price'] ?? null) ? $record['price'] : null;
                unset($record['qty'], $record['status']);

                $validator = Validator::make($record, [
                    'name' => ['required', 'string', 'max:255'],
                    'sku' => ['nullable', 'string', 'max:255', 'unique:stocks,sku'],
                    'description' => ['nullable', 'string', 'max:5000'],
                    'quantity' => ['required', 'integer', 'min:0', 'max:'.Stock::MAX_QUANTITY],
                    'unit' => ['required', 'string', 'max:30'],
                    'price' => ['nullable', 'numeric', 'min:0', 'max:'.Stock::MAX_PRICE],
                    'min_stock' => ['required', 'integer', 'min:0', 'max:'.Stock::MAX_QUANTITY],
                ]);

                if ($validator->fails()) {
                    throw ValidationException::withMessages([
                        'excelFile' => 'Baris '.($index + 2).': '.$validator->errors()->first(),
                    ]);
                }

                $this->stockService->createFor($user, $validator->validated());
                $imported++;
            }

            return $imported;
        });
    }
}
