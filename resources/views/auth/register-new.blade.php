<!doctype html>
<html lang="{{$app->getLocale()}}">
    <head>
    <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />        
        <meta charset="utf-8">
        <meta name="csrf_token" content="{{ csrf_token() }}">
        <meta name="robots" content="index, follow">
        <link rel="shortcut icon" href="{{asset('img/favicon.png')}}" type="image/x-icon" />
		<link rel="apple-touch-icon" href="{{asset('img/favicon.png')}}">
		<link rel="apple-touch-icon" sizes="120x120" href="{{asset('img/favicon.png')}}">
		<link rel="apple-touch-icon" sizes="76x76" href="{{asset('img/favicon.png')}}">
		<link rel="apple-touch-icon" sizes="152x152" href="{{asset('img/favicon.png')}}">
        <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&amp;display=fallback">
        <!-- Bootstrap CSS -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
        <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.1/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.1/js/bootstrap.bundle.min.js"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css"
            integrity="sha512-KfkfwYDsLkIlwQp6LFnl8zNdLGxu9YAA1QvwINks4PhcElQSvqcyVLLD9aMhXd13uQjoXtEKNosOWaZqXgel0g=="
            crossorigin="anonymous" referrerpolicy="no-referrer" />
        <link rel="stylesheet" type="text/css" href="{{asset('vendor/cookie-consent/css/cookie-consent.css')}}">
        <link href="{{asset('dist/style.css')}}" rel="stylesheet" />
        <link href="{{asset('dist/custom.css')}}" rel="stylesheet" />
        @if(empty(metaTitleDynamic()))
        <title>{{metaTitle()}}</title>
        @else
        <title>{{metaTitleDynamic()}}</title>
        @endif
        @meta('description')
        @meta('keywords')
        @meta('title')
        @meta('og_data')
        <meta property='og:image' content="{{asset('img/logo-sm.jpg')}}" />            
        <link rel="canonical" href="{{url()->current()}}"/>        
        <!-- Google tag (gtag.js) -->
        <script async src="https://www.googletagmanager.com/gtag/js?id=G-TVKYN80SLE"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());

            gtag('config', 'G-TVKYN80SLE');
        </script>
    </head>
    <body class="o-none bg-white dark:bg-dim-900">
        <div class="container mx-auto h-login-screen">         
            <div class="flex  align-items-center justify-content-center">
                <div class="flex flex-row justify-center">
                    <div class="w-full h-login-screen flex align-items-center justify-content-center">
                        <div class="flex justify-between align-items-center justify-content-center items-center px-4 py-3 bg-white dark:bg-dim-900">                                                
                            <h2 class="text-gray-800 dark:text-gray-100 font-bold-xl font-sm mr-5"> 
                                <!-- Logo -->
                                <a href="{{route('index')}}">
                                    <img src="{{asset('img/logo-login.png')}}" alt="Innovating Works" width="200%">
                                </a>
                                <!-- /Logo -->                                            
                            </h2>                        
                            <div class="flex flex-col ml-5 w-100">                                     
                                <h2 class="text-xl font-bold-xl pl-2 pt-3 mb-3">{{ __('Conecta tu I+D') }}</h2>
                                <form method="POST" action="{{ route('register') }}" id="form">
                                    @csrf
                                    <x-input type="hidden" name="idzb" :value="request('id')" />
                                    <x-input type="hidden" name="accept_rgpd" :value="1" />
                                    <!-- Name -->
                                    <div>
                                        <x-label for="nombre" :value="__('Name')" />

                                        <x-input id="nombre" class="block mt-1 w-full rounded-2xl pl-2 pt-1 pb-1 border-l border-r border-b border-t border-gray-200 dark:border-gray-700 mb-3" type="text" name="nombre" :value="old('nombre')" required autofocus />
                                    </div>

                                    <!-- Email Address -->
                                    <div class="mt-4">
                                        <x-label for="email" :value="__('Email')" />
                                        @if(request()->query('mail') !== null && request()->query('id') !== null)
                                        <x-input readonly="readonly" id="email" class="block mt-1 w-full rounded-2xl pl-2 pt-1 pb-1 border-l border-r border-b border-t border-gray-200 dark:border-gray-700 mb-3 opacity-75" type="email" name="email" :value="request('mail')" required />
                                        @elseif(request()->query('mail') !== null && request()->query('utm_campaign') == "ayudapublicada" && request()->query('utm_content') == "footer")
                                        <x-input readonly="readonly" id="email" class="block mt-1 w-full rounded-2xl pl-2 pt-1 pb-1 border-l border-r border-b border-t border-gray-200 dark:border-gray-700 mb-3 opacity-75" type="email" name="email" :value="request('mail')" required />
                                        @elseif(request()->get('mail') !== null)
                                        <x-input readonly="readonly" id="email" class="block mt-1 w-full rounded-2xl pl-2 pt-1 pb-1 border-l border-r border-b border-t border-gray-200 dark:border-gray-700 mb-3 opacity-75" type="email" name="email" :value="request()->get('mail')" required />
                                        @else
                                        <x-input id="email" class="block mt-1 w-full rounded-2xl pl-2 pt-1 pb-1 border-l border-r border-b border-t border-gray-200 dark:border-gray-700 mb-3" type="email" name="email" :value="old('email')" required />
                                        @endif
                                    </div>

                                    <!-- Password -->
                                    <div class="mt-4">
                                        <x-label for="password" :value="__('Password')" />

                                        <x-input id="password" class="block mt-1 w-full rounded-2xl pl-2 pt-1 pb-1 border-l border-r border-b border-t border-gray-200 dark:border-gray-700 mb-3"
                                                        type="password"
                                                        name="password"
                                                        required autocomplete="new-password" />
                                    </div>

                                    <!-- Confirm Password -->
                                    <div class="mt-4">
                                        <x-label for="password_confirmation" :value="__('Confirm Password')" />

                                        <x-input id="password_confirmation" class="block mt-1 w-full rounded-2xl pl-2 pt-1 pb-1 border-l border-r border-b border-t border-gray-200 dark:border-gray-700 mb-3"
                                                        type="password"
                                                        name="password_confirmation" required />
                                    </div>
                                    <div class="block mt-4">
                                        <label for="accept_terms" class="inline-flex items-center">
                                            <input id="accept_terms" type="checkbox" required class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" name="accept_terms">
                                            <span class="ml-2 text-sm text-gray-600">
                                                {{__('Acepto los ')}}
                                                <a class="txt-azul" href="https://blog.innovating.works/condiciones-de-uso/" target="_blank">{{ __('Terms & Conditions') }}</a>
                                            {{__('y acepto la ')}}<a class="txt-azul" href="https://blog.innovating.works/politica-privacidad/" target="_blank">{{ __('Privacy Policy') }}</a></span>
                                        </label>
                                    </div>
                                    <div class="flex items-center justify-end mt-4">
                                        <button type="submit"  class="text-left mx-auto w-100 h-11 xl:w-auto flex items-center justify-center bg-blue-400 hover:bg-blue-500 py-3 rounded-full text-white font-bold font-sm transition duration-350 ease-in-out mb-3">
                                            {{ __('Regístrate') }}
                                        </button>
                                    </div>
                                    <hr/>
                                    <p class="pl-2 pt-3">{{ __('¿Tienes cuenta?') }}</p>
                                    <div class="flex items-center justify-end mt-4">
                                        
                                        <a class="text-left mx-auto w-100 h-11 xl:w-auto flex items-center justify-center bg-blue-400 hover:bg-blue-500 py-3 rounded-full text-white font-bold font-sm transition duration-350 ease-in-out mb-3" href="{{ route('login') }}">
                                            {{ __('Log in') }}
                                        </a>
                                    </div>
                                </form>
                            </div>                        
                        </div>
                    </div>
                </div>
            </div>  
        </div>
    </body>
</html>