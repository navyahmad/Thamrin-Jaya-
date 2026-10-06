<?php

namespace App\Actions\Cms;

use App\Models\Company;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\SectionItem;
use App\Models\Site;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use LogicException;
use RuntimeException;
use Throwable;

class MediaManager
{
    public const COLUMNS = [
        'companies' => ['banner_path', 'about_image_path'],
        'products' => ['image_path'],
        'sites' => ['logo_path', 'favicon_path'],
        'pages' => ['og_image_path'],
        'page_sections' => ['image_path'],
        'section_items' => ['image_path', 'file_path'],
        'services' => ['image_path'],
    ];

    /** @return array<int, string> */
    public static function imageRules(): array
    {
        return ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'];
    }

    public static function uploadPath(?string $path): ?string
    {
        if (! $path) {
            return null;
        }
        $path = preg_replace('#^/?storage/#', '', $path);
        if (! preg_match('#^(companies|products|media)/[a-zA-Z0-9/_\-.]+$#D', $path)
            || preg_match('#(^|/)[.]{1,2}(/|$)#', $path) || str_contains($path, '//')) {
            return null;
        }

        return $path;
    }

    public static function url(?string $path): ?string
    {
        if ($path && preg_match('#^images/[a-zA-Z0-9_-]+\.(webp|png|jpe?g|svg)$#D', $path)) {
            return asset($path);
        }
        $upload = self::uploadPath($path);

        return $upload ? Storage::disk('public')->url($upload) : null;
    }

    /**
     * Owns the transaction; callers must not wrap this operation in another transaction.
     *
     * @param  array<string, mixed>  $attributes
     * @param  array<string, UploadedFile|null>  $uploads  Null explicitly removes the existing media.
     * @param  \Closure(Model): void|null  $afterSave  Database-only relation writes inside this transaction; exceptions roll back data and new uploads.
     */
    public function save(Model $record, array $attributes, array $uploads = [], ?\Closure $afterSave = null): void
    {
        $this->assertOwnTransaction($record);
        $newFiles = [];
        $obsolete = [];
        try {
            DB::transaction(function () use ($record, $attributes, $uploads, $afterSave, &$newFiles, &$obsolete): void {
                if ($record->exists) {
                    $record->setRawAttributes($record->newQuery()->lockForUpdate()->findOrFail($record->getKey())->getAttributes(), true);
                }
                foreach ($uploads as $column => $file) {
                    if (! in_array($column, self::COLUMNS[$record->getTable()] ?? [], true)) {
                        throw new LogicException('Kolom media tidak terdaftar.');
                    }
                    $obsolete[] = $record->getAttribute($column);
                    $path = null;
                    if (is_string($file)) {
                        if (! preg_match('#^images/[a-zA-Z0-9_-]+\.(webp|png|jpe?g|svg)$#D', $file) || ! is_file(public_path($file)) || $column === 'file_path') {
                            throw new LogicException('Aset bawaan tidak valid.');
                        }
                        $path = $file;
                    } elseif ($file !== null) {
                        $pdf = $column === 'file_path';
                        if ($pdf && (! $record instanceof SectionItem || $record->section()->firstOrFail()->type !== 'certifications')) {
                            throw new LogicException('Dokumen PDF hanya untuk item sertifikasi.');
                        }
                        Validator::make(['file' => $file], ['file' => $pdf ? ['required', 'file', 'mimes:pdf', 'max:10240'] : self::imageRules()])->validate();
                        $directory = $pdf ? 'media/documents' : 'media/images';
                        $newFiles[] = $directory.'/'.$file->hashName();
                        $path = $file->store($directory, 'public');
                        if (! is_string($path) || $path === '') {
                            throw new RuntimeException('Upload gagal disimpan.');
                        }
                    }
                    $attributes[$column] = $path;
                }
                foreach (self::COLUMNS[$record->getTable()] ?? [] as $column) {
                    if (array_key_exists($column, $attributes) && ! array_key_exists($column, $uploads)) {
                        throw new LogicException('Perubahan media harus melalui parameter uploads.');
                    }
                }
                $record->fill($attributes);
                if (! $record->save()) {
                    throw new RuntimeException('Penyimpanan data dibatalkan.');
                }
                $afterSave?->__invoke($record);
            });
        } catch (Throwable $exception) {
            foreach ($newFiles as $path) {
                $this->cleanupUnused($path);
            }
            throw $exception;
        }
        foreach (array_filter($obsolete) as $path) {
            $this->cleanupUnused($path);
        }
    }

    public function delete(Model $record, ?\Closure $guard = null): void
    {
        $this->assertOwnTransaction($record);
        $paths = DB::transaction(function () use ($record, $guard): array {
            $record = $record->newQuery()->lockForUpdate()->findOrFail($record->getKey());
            $guard?->__invoke($record);
            $paths = $this->treePaths($record);
            if (! $record->delete()) {
                throw new RuntimeException('Penghapusan data dibatalkan.');
            }

            return $paths;
        });
        foreach (array_unique($paths) as $path) {
            $this->cleanupUnused($path);
        }
    }

    public function referenced(string $path): bool
    {
        $path = self::uploadPath($path);
        if ($path === null) {
            return true;
        }
        foreach (self::COLUMNS as $table => $columns) {
            foreach ($columns as $column) {
                if (DB::table($table)->whereIn($column, [$path, 'storage/'.$path, '/storage/'.$path])->exists()) {
                    return true;
                }
            }
        }

        return false;
    }

    public function cleanupUnused(string $path): bool
    {
        if (DB::transactionLevel() !== 0) {
            throw new LogicException('Cleanup hanya boleh dilakukan setelah transaksi selesai.');
        }
        $path = self::uploadPath($path);
        if ($path === null) {
            return false;
        }
        try {
            if ($this->referenced($path)) {
                return false;
            }
            if (Storage::disk('public')->delete($path)) {
                return true;
            }
            Log::warning('Media cleanup gagal; file dipertahankan.', ['path' => $path]);
        } catch (Throwable $exception) {
            Log::warning('Media cleanup perlu diulang.', ['path' => $path, 'error' => $exception::class]);
        }

        return false;
    }

    /** @return list<string> */
    private function treePaths(Model $record): array
    {
        $paths = [];
        foreach (self::COLUMNS[$record->getTable()] ?? [] as $column) {
            if ($record->getAttribute($column)) {
                $paths[] = $record->getAttribute($column);
            }
        }
        $relations = match (true) {
            $record instanceof Company => ['site', 'products'],
            $record instanceof Site => ['pages'],
            $record instanceof Page => ['sections'],
            $record instanceof PageSection => ['items'],
            default => [],
        };
        foreach ($relations as $relation) {
            foreach ($record->$relation()->get() as $child) {
                array_push($paths, ...$this->treePaths($child));
            }
        }

        return $paths;
    }

    private function assertOwnTransaction(Model $record): void
    {
        if ($record->getConnection()->getName() !== DB::connection()->getName() || DB::transactionLevel() !== 0) {
            throw new LogicException('MediaManager harus memiliki transaksi terluar pada koneksi default.');
        }
    }
}
