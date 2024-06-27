<x-guest-layout>

    <x-auth-card>

        <x-slot name="logo">
            <a href="/" title="Volver">
                <x-application-logo class="w-20 h-20 fill-current text-gray-500" />
            </a>
        </x-slot>

        <!-- Session Status -->
        <x-auth-session-status class="mb-4" :status="session('status')" />

        <p class="mb-0 text-center">
            En estos momentos no se permite el registro sin invitación.
        </p>

    </x-auth-card>
</x-guest-layout>
<link rel="stylesheet" type="text/css" href="{{asset('vendor/cookie-consent/css/cookie-consent.css')}}">
