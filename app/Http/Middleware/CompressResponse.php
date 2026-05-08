<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CompressResponse
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Solo comprimir respuestas exitosas y de tipo JSON
        if ($response->getStatusCode() === 200 && 
            $response->headers->get('Content-Type') === 'application/json') {
            
            $content = $response->getContent();
            
            // Verificar si el cliente acepta compresión gzip
            $acceptEncoding = $request->header('Accept-Encoding', '');
            
            // No comprimir si supera 8MB para evitar memory exhaustion (límite PHP: 128MB)
            if (strpos($acceptEncoding, 'gzip') !== false && function_exists('gzencode') && strlen($content) <= 8 * 1024 * 1024) {
                $compressedContent = gzencode($content, 6);

                if ($compressedContent !== false) {
                    $response->setContent($compressedContent);
                    $response->headers->set('Content-Encoding', 'gzip');
                    $response->headers->set('Content-Length', strlen($compressedContent));
                }
            }
        }

        return $response;
    }
}