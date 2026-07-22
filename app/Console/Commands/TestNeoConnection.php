<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use NeoLms\NeoSync\Domain\Ports\NeoLmsApiContract;

class TestNeoConnection extends Command
{
    protected $signature = 'neo:test';

    protected $description = 'Prueba la conexión con NEO LMS API v3';

    public function handle(NeoLmsApiContract $neo): int
    {
        $this->info('=== NEO LMS API Test ===');

        $this->info('[1] Health check...');
        $neo->healthCheck() ? $this->info('OK') : $this->error('FAIL');

        $this->info('[2] GET /users ($limit=3)...');
        $users = $neo->listUsers(['$limit' => 3]);
        $this->info('Recibidos: '.count($users));
        foreach ($users as $u) {
            $this->line("  [{$u['id']}] {$u['first_name']} {$u['last_name']} sis_id={$u['sis_id']}");
        }

        $this->info('[3] getUserBySisId("Super Admin")...');
        $u = $neo->getUserBySisId('Super Admin');
        $u ? $this->info("Encontrado: [{$u['id']}]") : $this->warn('No encontrado');


        $this->info('[3] getUserByUserId("Super Admin")...');
        $user = $neo->getUserByUserId('Super Admin');
        $user ? $this->info("Encontrado: [{$user['id']}]") : $this->warn('No encontrado');

        $this->info('[4] GET /classes ($limit=3)...');
        $classes = $neo->listClasses(['$limit' => 3]);
        $this->info('Recibidas: '.count($classes));
        foreach ($classes as $c) {
            $this->line("  [{$c['id']}] {$c['name']} sis_id=".($c['sis_id'] ?? 'N/A'));
        }

        $this->info('[5] GET /class_templates ($limit=3)...');
        $templates = $neo->listClassTemplates(['$limit' => 3]);
        $this->info('Recibidos: '.count($templates));

        $this->info('[6] GET /batches ($limit=3)...');
        $batches = $neo->listBatches(['$limit' => 3]);
        foreach ($batches as $b) {
            $this->line("  Batch[{$b['id']}] {$b['status']} processed={$b['processed']}");
        }

        $this->info('=== Test completado ===');

        return self::SUCCESS;
    }
}
