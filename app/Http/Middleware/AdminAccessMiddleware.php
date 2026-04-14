<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminAccessMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $adminAccess = $request->session()->get('admin_access');

        if (!is_array($adminAccess) || empty($adminAccess['user_id']) || empty($adminAccess['granted_at'])) {
            return redirect()->route('dashboard.public')->with('error', 'Silakan login admin terlebih dahulu.');
        }

        if ((now()->getTimestamp() - (int) $adminAccess['granted_at']) > 60 * 60 * 8) {
            $request->session()->forget('admin_access');

            return redirect()->route('dashboard.public')->with('error', 'Sesi admin sudah berakhir. Silakan login ulang.');
        }

        $isValidAdmin = User::query()
            ->whereKey((int) $adminAccess['user_id'])
            ->where('role', 'admin')
            ->where('is_active', true)
            ->exists();

        if (!$isValidAdmin) {
            $request->session()->forget('admin_access');

            return redirect()->route('dashboard.public')->with('error', 'Akses admin tidak valid.');
        }

        return $next($request);
    }
}
