<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\StoreTenantRequest;
use App\Http\Resources\Platform\TenantResource;
use App\Platform\Actions\ProvisionTenant;
use App\Platform\Exceptions\TenantProvisioningFailed;
use App\Platform\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

/**
 * Catálogo de curadurías para el panel de plataforma. Controlador delgado: el
 * alta es `ProvisionTenant`, la misma Action que usa `tenants:provision`.
 */
final class TenantController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::forUser($request->user('platform'))->authorize('viewAny', Tenant::class);

        return TenantResource::collection(
            Tenant::query()->with('domains')->latest()->get(),
        )->additional([
            // El formulario de alta muestra "<subdominio>.<dominio base>".
            'meta' => ['base_domain' => config('platform.tenant_base_domain')],
        ]);
    }

    public function store(StoreTenantRequest $request, ProvisionTenant $provision): JsonResponse
    {
        try {
            $tenant = $provision->handle($request->toData());
        } catch (TenantProvisioningFailed) {
            // La Action ya compensó (sin base, sin dominio) y dejó el tenant en
            // `failed` con el motivo. El mensaje de la excepción lleva detalle
            // interno (SQL, rutas): no se devuelve al panel.
            return response()->json([
                'message' => 'No se pudo crear la curaduría. El intento quedó registrado como fallido.',
            ], 500);
        }

        return (new TenantResource($tenant->load('domains')))
            ->response()
            ->setStatusCode(201);
    }
}
