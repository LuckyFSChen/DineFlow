<?php

namespace App\Helpers;

use Illuminate\Support\Str;
use Illuminate\Database\QueryException;

class SlugHelper
{
    /**
     * 產生唯一 slug，支援多次 DB 寫入競爭。
     *
     * @param string $name
     * @param callable $existsCallback function($slug): bool
     * @param int $maxAttempts
     * @return string
     */
    public static function generateUniqueSlug(string $name, callable $existsCallback, int $maxAttempts = 20): string
    {
        $baseSlug = Str::slug($name) ?: 'store';
        $slug = $baseSlug;
        $i = 1;
        while ($existsCallback($slug) && $i <= $maxAttempts) {
            $slug = $baseSlug . '-' . $i;
            $i++;
        }
        return $slug;
    }

    /**
     * 嘗試 DB 寫入，遇唯一鍵衝突自動遞增 slug 並重試。
     *
     * @param callable $createCallback function($slug): mixed
     * @param string $name
     * @param callable $existsCallback function($slug): bool
     * @param int $maxAttempts
     * @return mixed
     * @throws \Exception
     */
    public static function createWithUniqueSlug(callable $createCallback, string $name, callable $existsCallback, int $maxAttempts = 20)
    {
        $baseSlug = Str::slug($name) ?: 'store';
        $slug = $baseSlug;
        $i = 1;
        $lastException = null;
        while ($i <= $maxAttempts) {
            try {
                return $createCallback($slug);
            } catch (QueryException $e) {
                if (str_contains($e->getMessage(), 'slug') && str_contains($e->getMessage(), 'unique')) {
                    // slug 衝突，遞增
                    $slug = $baseSlug . '-' . $i;
                    $i++;
                    $lastException = $e;
                    continue;
                }
                throw $e;
            }
        }
        throw $lastException ?: new \Exception('無法產生唯一 slug');
    }
}
