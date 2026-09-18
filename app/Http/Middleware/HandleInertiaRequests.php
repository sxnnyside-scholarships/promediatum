<?php

namespace App\Http\Middleware;

use App\Models\SmtpSetting;
use App\Services\UI\FabActionResolver;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'full_name' => $user->full_name,
                    'email' => $user->email,
                    'pronoun' => $user->pronoun,
                    'institution' => $user->institution,
                    'educational_area' => $user->educational_area,
                    'educational_level' => $user->educational_level,
                    'greeting' => $user->greeting,
                    'localized_greeting' => $user->localized_greeting,
                    'is_locked' => $user->is_locked,
                    'locale' => $user->locale,
                    'settings' => $user->settings ?? [],
                ] : null,
            ],
            'locale' => fn () => app()->getLocale(),
            'flash' => [
                'status' => fn () => $request->session()->get('status'),
                'smtp_status' => fn () => $request->session()->get('smtp_status'),
            ],
            // FAB contextual actions (resolved server-side)
            'fab' => fn () => $this->resolveFabActions($request),
            // Whether the user has SMTP configured (for showing email option)
            'smtp_configured' => fn () => $user ? SmtpSetting::where('user_id', $user->id)->where('verified', true)->exists() : false,
        ];
    }

    /**
     * Resolve intelligent FAB actions based on current route and app state.
     */
    protected function resolveFabActions(Request $request): array
    {
        if (! $request->user()) {
            return [];
        }

        $resolver = app(FabActionResolver::class);
        $routeName = $request->route()?->getName() ?? 'workspace';
        $params = $request->route()?->parameters() ?? [];
        $state = $resolver->gatherState();

        return $resolver->resolve($routeName, $params, $state);
    }
}
