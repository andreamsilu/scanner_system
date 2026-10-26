<?php

namespace App\Support;

use Illuminate\Foundation\Vite;

class FrontendAssets
{
    public static function url(string $file): string
    {
        $vite = app(Vite::class);

        if ($vite->isRunningHot()) {
            return rtrim(trim(file_get_contents($vite->hotFile())), '/').'/legacy/'.$file;
        }

        return asset('build/legacy/'.$file);
    }
}
