<?php

namespace App\Features\Ai\Contracts;

interface AiClientInterface
{
    /** @return list<array{name:string,sku:?string,description:?string,quantity:int,unit:string,price:?float,min_stock:int,status:string}> */
    public function generateStockItems(string $prompt, int $count): array;
}
