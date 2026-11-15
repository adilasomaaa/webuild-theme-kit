<?php

namespace Webuild\ThemeKit\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Webuild\ThemeKit\ThemeRegistry;

class ThemeListCommand extends Command
{
    protected $signature = 'theme:list';

    protected $description = 'Daftar tema yang terdaftar di sistem beserta status dan versinya';

    public function handle(ThemeRegistry $registry): int
    {
        $rows = [];

        // Check registry themes
        foreach ($registry->all() as $key => $meta) {
            $manifest = $meta['manifest'];
            $dbRecord = DB::getSchemaBuilder()->hasTable('themes')
                ? DB::table('themes')->where('key', $key)->first()
                : null;

            $rows[] = [
                'key' => $key,
                'name' => $meta['name'],
                'version' => $meta['version'],
                'api_version' => $meta['api_version'],
                'is_active' => $dbRecord ? ($dbRecord->is_active ? 'Ya' : 'Tidak') : 'Registered',
            ];
        }

        if (empty($rows)) {
            $this->warn('Belum ada tema yang terdaftar pada ThemeRegistry.');

            return Command::SUCCESS;
        }

        $this->table(['Key', 'Nama Tema', 'Versi', 'API Version', 'Aktif'], $rows);

        return Command::SUCCESS;
    }
}

