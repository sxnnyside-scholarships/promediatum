<?php

namespace App\Http\Middleware;

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
            ],
        ];
    }
}
