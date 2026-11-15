<?php

namespace App\Http\Middleware;

use App\Support\WorkbenchCustomizer;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menyisipkan drawer customizer ke halaman HTML workbench.
 * Drawer tidak berada di view tema sehingga tidak ikut terpasang ke aplikasi Webuild.
 */
class InjectCustomizerDrawer
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $this->shouldInject($request, $response)) {
            return $response;
        }

        $content = (string) $response->getContent();
        $position = strripos($content, '</body>');
        if ($position === false) {
            return $response;
        }

        $customizer = app(WorkbenchCustomizer::class);
        $routeName = $request->route()?->getName();
        $drawer = view('customizer.drawer', [
            'customizerPayload' => [
                'routeName' => $routeName,
                'pageKey' => $customizer->pageKeyForRoute($routeName),
                'schema' => $customizer->schema(),
                'state' => $customizer->state(),
                'endpoint' => url('/__workbench/customizer'),
                'csrfToken' => csrf_token(),
            ],
        ])->render();

        $response->setContent(substr_replace($content, $drawer, $position, 0));

        return $response;
    }

    private function shouldInject(Request $request, Response $response): bool
    {
        return filter_var(env('THEME_CUSTOMIZER', true), FILTER_VALIDATE_BOOL)
            && $request->isMethod('GET')
            && ! $request->is('__workbench/*', 'theme-assets/*')
            && $response->getStatusCode() === 200
            && str_contains((string) $response->headers->get('Content-Type'), 'text/html');
    }
}
