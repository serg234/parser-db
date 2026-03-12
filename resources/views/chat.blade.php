<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Public Chat</title>

    <!-- Scripts -->
    {{--<script src="{{ asset('js/app.js') }}"></script>--}}

    <!-- Pusher JS -->
    <script src="https://js.pusher.com/7.2/pusher.min.js"></script>
    <!-- Laravel Echo IIFE -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/laravel-echo/1.11.2/echo.iife.js"></script>
    <!-- (Опционально) Axios для AJAX -->
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

    {{--<style>--}}
        {{--#messages { list-style: none; padding: 0; max-height: 300px; overflow-y: auto; }--}}
        {{--#messages li { margin-bottom: 5px; }--}}
    {{--</style>--}}

<!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet">

    <!-- Styles -->
    {{--<link href="{{ asset('css/app.css') }}" rel="stylesheet">--}}

</head>
<body>
<h1>Public Chat</h1>

<ul id="messages"></ul>

<form id="message-form">
    <input id="message-input" autocomplete="off" placeholder="Введите сообщение" />
    <button>Отправить</button>
</form>

<script>
    // CSRF для axios
    axios.defaults.headers.common['X-CSRF-TOKEN'] = document
        .querySelector('meta[name="csrf-token"]').getAttribute('content');

    // Инициализация Echo
    const echo = new Echo({
        broadcaster: 'pusher',
        key: '{{ env('PUSHER_APP_KEY') }}',
        wsHost: '{{ env('PUSHER_APP_HOST') }}',
        wsPort: {{ env('PUSHER_APP_PORT') }},
        wssPort: {{ env('PUSHER_APP_PORT') }},
        forceTLS: false,
        encrypted: false,
        disableStats: true,
        enabledTransports: ['ws', 'wss']
    });

    // Подписываемся на канал
    echo.channel('public-chat')
        .listen('MessageSent', e => {
            const li = document.createElement('li');
            li.textContent = `[${e.time}] ${e.user}: ${e.message}`;
            document.getElementById('messages').appendChild(li);
        });

    // Отправка формы
    document.getElementById('message-form')
        .addEventListener('submit', e => {
            e.preventDefault();
            const input = document.getElementById('message-input');
            if (!input.value.trim()) return;
            axios.post('/chat/message', { message: input.value })
                .then(() => input.value = '')
                .catch(console.error);
        });
</script>
</body>
</html>
