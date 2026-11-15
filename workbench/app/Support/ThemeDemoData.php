<?php

namespace App\Support;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Fluent;
use Webuild\ThemeKit\ThemeRegistry;

class ThemeDemoData
{
    private ?array $data = null;

    public function collection(string $key): Collection
    {
        $data = $this->data();

        if ($key === 'contents' && ! array_key_exists('contents', $data)) {
            $rows = [
                ...array_map(fn (array $row) => ['type' => 'news', ...$row], $data['news'] ?? []),
                ...array_map(fn (array $row) => ['type' => 'article', ...$row], $data['articles'] ?? []),
            ];
        } else {
            $rows = $data[$key] ?? [];
        }

        return collect($rows)->map(fn (array $row) => $this->toObject($row));
    }

    public function paginate(string $key, int $perPage): LengthAwarePaginator
    {
        $items = $this->collection($key);
        $page = LengthAwarePaginator::resolveCurrentPage();

        return new LengthAwarePaginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            ['path' => LengthAwarePaginator::resolveCurrentPath(), 'query' => request()->query()],
        );
    }

    /** Struktur organisasi datar dengan relasi parent/children dan activeHolders terisi, seperti model aplikasi. */
    public function organizationStructures(): Collection
    {
        $nodes = $this->collection('organization_structures')->keyBy('id');
        foreach ($nodes as $node) {
            $node->activeHolders = collect($node->holders ?? []);
            $node->children = $nodes->where('parent_id', $node->id)->values();
            $node->parent = $node->parent_id ? $nodes->get($node->parent_id) : null;
        }

        return $nodes->values();
    }

    public function data(): array
    {
        if ($this->data !== null) {
            return $this->data;
        }

        $activeTheme = app(ThemeRegistry::class)->get((string) config('theme.active', ''));
        $themeDataPath = ($activeTheme['base_path'] ?? '') . '/demo/data.json';
        $fallbackPath = dirname(__DIR__, 2) . '/demo/data.json';
        $path = is_file($themeDataPath) ? $themeDataPath : $fallbackPath;
        $contents = is_file($path) ? file_get_contents($path) : false;
        $data = is_string($contents) ? json_decode($contents, true) : null;

        return $this->data = is_array($data) ? $data : [];
    }

    private function toObject(array $values): Fluent
    {
        foreach ($values as $key => $value) {
            if (is_array($value)) {
                $value = array_is_list($value)
                    ? array_map(fn ($item) => is_array($item) ? $this->toObject($item) : $item, $value)
                    : $this->toObject($value);
            } elseif (is_string($value) && str_ends_with($key, '_at')) {
                $value = Carbon::parse($value);
            }

            $values[$key] = $value;
        }

        return new Fluent($values);
    }
}
