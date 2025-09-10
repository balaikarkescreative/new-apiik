<?php

namespace App\Http\Middleware;

use App\Models\PaktaIntegritas;
use Closure;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPaktaIntegritas
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            if (auth()->user()->hasRole(['admin', 'verifikator1', 'verifikator2'])) {
                return $next($request);
            }
        } else {
            return $next($request);

        }

        // Skip check for pakta integritas related routes to prevent redirect loop
        $exemptRoutes = [
            'filament.admin.resources.pakta-integritas.index',
            'filament.admin.resources.pakta-integritas.create',
            'filament.admin.resources.pakta-integritas.edit',
            'filament.admin.resources.pakta-integritas.view',
            'filament.admin.auth.logout',
        ];

        if (in_array($request->route()->getName(), $exemptRoutes)) {
            return $next($request);
        }

        // Check if user has uploaded pakta integritas
        $paktaIntegritas = PaktaIntegritas::where('user_id', auth()->id())->first();

        if (! $paktaIntegritas || ! $paktaIntegritas->file) {
            Notification::make()
                ->title('Pakta Integritas Belum Ada')
                ->body('Anda harus mengupload pakta integritas terlebih dahulu sebelum dapat mengakses halaman ini.')
                ->danger()
                ->persistent()
                ->send();

            // Redirect to pakta integritas create page
            return redirect()->route('filament.admin.resources.pakta-integritas.create');
        }

        return $next($request);
    }
}
