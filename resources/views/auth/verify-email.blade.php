<x-guest-layout>
    <x-auth-card>
        <x-slot name="logo">
            <a href="/">
                <x-application-logo class="w-20 h-20 fill-current text-gray-500" />
            </a>
        </x-slot>
        <div class="mb-4 text-lg">
            <b>{{ __('Tu e-mail no esta verificado.') }}</b>
            {{ __('Haz click en el siguiente enlace y te re-enviaremos un e-mail para vertificar tu cuenta.') }}
        </div>
        @if(session()->has('status'))
            <div class="mb-4 text-lg">
                <small><i>{{ __('Te hemos enviado un e-mail de verificación, revisa tu bandeja de correo.') }}</i></small>
            </div>
        @endif
        <div class="mt-4 flex items-center justify-between">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf

                <div>
                    @if(session()->has('status'))
                        <button type="submit" class="btn-primary inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150" disabled>
                            {{ __('Reenviar correo de verificación') }}
                        </button>
                    @else
                        <x-button>
                            {{ __('Reenviar correo de verificación') }}
                        </x-button>
                    @endif
                </div>
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="underline text-sm text-gray-600 hover:text-gray-900">
                    {{ __('Cerrar sesión') }}
                </button>
            </form>
        </div>
        <p class="text-left text-danger">Recuerda revisar tu carpeta de spam.</p>
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
