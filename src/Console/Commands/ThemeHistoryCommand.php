<?php

namespace Webuild\ThemeKit\Console\Commands;

use Illuminate\Console\Command;
use Webuild\ThemeKit\Services\ThemeManagerService;

class ThemeHistoryCommand extends Command
{
    protected $signature = 'theme:history {key : Key tema}';

    protected $description = 'Tampilkan riwayat rilis dan catatan pembaruan tema';

    public function handle(ThemeManagerService $service): int
    {
        $key = $this->argument('key');

        $history = $service->getHistory($key);

        $this->info("=== RIWAYAT RILIS TEMA: {$key} ===");
        if (empty($history['releases'])) {
            $this->line('  Belum ada catatan rilis.');
        } else {
            $rows = [];
            foreach ($history['releases'] as $rel) {
                $rows[] = [
                    'Versi' => $rel->version,
                    'Channel' => $rel->channel,
                    'Changelog' => $rel->changelog,
                    'Dirilis' => $rel->released_at?->format('Y-m-d H:i') ?? $rel->created_at->format('Y-m-d H:i'),
                ];
            }
            $this->table(['Versi', 'Channel', 'Changelog', 'Dirilis'], $rows);
        }

        $this->newLine();
        $this->info("=== CATATAN PEMBARUAN & ROLLBACK (AUDIT) ===");
        if (empty($history['logs'])) {
            $this->line('  Belum ada catatan log pembaruan.');
        } else {
            $logRows = [];
            foreach ($history['logs'] as $log) {
                $logRows[] = [
                    'ID' => $log->id,
                    'Aksi' => strtoupper($log->action),
                    'Dari' => $log->from_version ?? '-',
                    'Ke' => $log->to_version,
                    'Status' => strtoupper($log->status),
                    'Aktor' => $log->actor_name ?? '-',
                    'Waktu' => $log->started_at?->format('Y-m-d H:i:s') ?? '-',
                ];
            }
            $this->table(['ID', 'Aksi', 'Dari', 'Ke', 'Status', 'Aktor', 'Waktu'], $logRows);
        }

        return Command::SUCCESS;
    }
}

