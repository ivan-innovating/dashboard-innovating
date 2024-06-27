<x-guest-layout>
    <x-auth-card>
        <x-slot name="logo">
            <a href="/">
                <x-application-logo class="w-20 h-20 fill-current text-gray-500" />
            </a>
        </x-slot>

        <!-- Validation Errors -->
        <x-auth-validation-errors class="mb-4" :errors="$errors" />

        <form method="POST" action="{{ route('register') }}" id="form">
            @csrf
            <x-input type="hidden" name="invitation" :value="1"/>
            <!-- Name -->
            <div>
                <x-label for="nombre" :value="__('Name')" />

                <x-input id="nombre" class="block mt-1 w-full" type="text" name="nombre" :value="old('nombre')" required autofocus />
            </div>

            <!-- Email Address -->
            <div class="mt-4">
                <x-label for="email" :value="__('Email')" />

                <x-input readonly="readonly" id="email" class="block mt-1 w-full" type="email" name="email" :value="request('mail')" required />
            </div>

            <!-- Password -->
            <div class="mt-4">
                <x-label for="password" :value="__('Password')" />

                <x-input id="password" class="block mt-1 w-full"
                                type="password"
                                name="password"
                                required autocomplete="new-password" />
            </div>

            <!-- Confirm Password -->
            <div class="mt-4">
                <x-label for="password_confirmation" :value="__('Confirm Password')" />

                <x-input id="password_confirmation" class="block mt-1 w-full"
                                type="password"
                                name="password_confirmation" required />
            </div>
            <div class="block mt-4">
                <label for="accept_terms" class="inline-flex items-center">
                    <input id="accept_terms" type="checkbox" required class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" name="accept_terms">
                    <span class="ml-2 text-sm text-gray-600">{{ __('Accept Terms & Conditions') }} <a class="txt-azul" href="https://blog.innovating.works/condiciones-de-uso/" target="_blank">Leer más</a></span>
                </label>
            </div>
            <div class="block">
                <label for="accept_rgpd" class="inline-flex items-center">
                    <input id="accept_rgpd" type="checkbox" required class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" name="accept_rgpd">
                    <span class="ml-2 text-sm text-gray-600">{{ __('Accept RGPD') }} <a class="txt-azul" href="https://blog.innovating.works/aviso-legal/" target="_blank">Leer más</a></span>
                </label>
            </div>
            <div class="flex items-center justify-end mt-4">
                <x-button class="ml-4">
                    {{ __('Register') }}
                </x-button>
            </div>
            <div class="flex items-center justify-end mt-4">
                <span class="text-sm text-gray-600">{{ __('Already registered?') }} </span>
                <a class="pl-1 text-sm text-azul hover:text-azul-900" href="{{ route('login') }}">
                    {{ __('Log in') }}
                </a>
            </div>
        </form>
    </x-auth-card>
</x-guest-layout>
<link rel="stylesheet" type="text/css" href="{{asset('vendor/cookie-consent/css/cookie-consent.css')}}">
<script src="https://www.google.com/recaptcha/enterprise.js?render=6LejuTogAAAAAN0VHpYOwcWfomkbmqrm4dbbFYnL"></script>
<script>
grecaptcha.enterprise.ready(function() {
    grecaptcha.enterprise.execute('6LejuTogAAAAAN0VHpYOwcWfomkbmqrm4dbbFYnL', {action: 'login'}).then(function(token) {

    });
});
</script>
