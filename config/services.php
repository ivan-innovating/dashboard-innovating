<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'elastic_ayudas' => [ //Dev por ahora no se va a usar 11/09/2023, pasamos a entorno activo entorno pasivo
        'environment' => env('ELASTIC_AYUDAS_ENVIRONMENT'),
        'servers' => [
            'dev' => [
                'endpoint' => 'https://pvu1udend4.execute-api.eu-west-2.amazonaws.com/dev/',
                'api_key' => '742MPgt0qu9dQbkxrw9Rga6yWLAFhmGK51kyN557',
            ],
            'prod' => [
                'endpoint' => 'https://ww7vgfpua1.execute-api.eu-west-2.amazonaws.com/pro/',
                'api_key' => 'fzwHVEgM9w2aB0EWOv6Pi2TLUwMNhmb7adz9gwH1',
            ],
            'prod-2' => [
                //'endpoint' => 'https://60mv9caulc.execute-api.eu-west-2.amazonaws.com/pro-2/', OLD ENDPOINT
                'endpoint' => 'https://7osmuoci42.execute-api.eu-west-2.amazonaws.com/pro-2/',                
                'api_key' => 'TRMbLOIY498wA6af6JAPk975isFFzhdpNCnrHbVe',
            ],
        ],
    ],

    'elastic_grafos' => [ //Dev por ahora no se va a usar 11/09/2023, pasamos a entorno activo entorno pasivo
        'environment' => env('ELASTIC_AYUDAS_ENVIRONMENT'),
        'servers' => [
            'dev' => [
                'endpoint' => 'https://pvu1udend4.execute-api.eu-west-2.amazonaws.com/dev/',
                'api_key' => '742MPgt0qu9dQbkxrw9Rga6yWLAFhmGK51kyN557',
            ],
            'prod' => [
                ###OLDENDPOINT
                //'endpoint' => 'https://i3enr0bra5.execute-api.eu-west-2.amazonaws.com/pro/',
                //'api_key' => 'fzwHVEgM9w2aB0EWOv6Pi2TLUwMNhmb7adz9gwH1',
                ###NEWENDPOINT 19/01/2024
                'endpoint' => 'https://dwfckz8ljj.execute-api.eu-west-2.amazonaws.com/pro/',
                'api_key' => 'tlMiQbwqe01kR5tL1kbH1ahONR3cNhk26kMciLIj',
            ],
            'prod-2' => [                
                ###ENDPOINT nuevo 19/12/2023
                'endpoint' => 'https://j5ibony954.execute-api.eu-west-2.amazonaws.com/pro-2/',
                'api_key' => 'XDVJckctwXJ6GDlNyUxa5lnYQdIIH8P1HUqBYwO1',
            ],
        ],
    ],

];
