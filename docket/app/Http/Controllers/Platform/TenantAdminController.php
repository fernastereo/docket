<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\SetTenantAdminPasswordRequest;
use App\Platform\Actions\SendTenantAdminLink;
use App\Platform\Actions\SetTenantAdminPassword;
use App\Platform\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Acceso del administrador de una curaduría desde el panel de plataforma:
 * enviarle su enlace (invitación o recuperación) o fijarle la contraseña.
 */
final class TenantAdminController extends Controller
{
    public function sendLink(Request $request, Tenant $tenant, SendTenantAdminLink $send): JsonResponse
    {
        Gate::forUser($request->user('platform'))->authorize('update', $tenant);

        $send->handle($tenant);

        return response()->json(status: 204);
    }

    public function setPassword(SetTenantAdminPasswordRequest $request, Tenant $tenant, SetTenantAdminPassword $set): JsonResponse
    {
        $set->handle($tenant, $request->password(), (string) $request->user('platform')->getAuthIdentifier());

        return response()->json(status: 204);
    }
}
