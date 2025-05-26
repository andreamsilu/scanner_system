<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Response;

class PDFServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton('pdf', function ($app) {
            return new class {
                public function loadView($view, $data = [])
                {
                    $html = View::make($view, $data)->render();
                    
                    // Create a temporary file
                    $tempFile = tempnam(sys_get_temp_dir(), 'pdf_');
                    
                    // Write HTML to file
                    file_put_contents($tempFile, $html);
                    
                    return new class($tempFile) {
                        protected $file;
                        
                        public function __construct($file)
                        {
                            $this->file = $file;
                        }
                        
                        public function download($filename)
                        {
                            $headers = [
                                'Content-Type' => 'application/pdf',
                                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                            ];
                            
                            return Response::download($this->file, $filename, $headers)->deleteFileAfterSend(true);
                        }
                    };
                }
            };
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
} 