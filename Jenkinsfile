pipeline {
    agent any

    stages {
        stage('Install') {
            steps {
                sh 'composer install --no-interaction --prefer-dist'
            }
        }
        stage('Security audit') {
            steps {
                sh 'composer audit --no-interaction'
                sh 'npm audit --audit-level=moderate'
            }
        }
        stage('PHP style') {
            steps {
                sh 'vendor/bin/pint --test'
            }
        }
        stage('Tests and coverage') {
            steps {
                sh 'php -m | grep -qi xdebug'
                sh 'XDEBUG_MODE=coverage composer test'
            }
        }
        stage('Frontend build') {
            steps {
                sh 'npm ci'
                sh 'npm run build'
            }
        }
    }
}
