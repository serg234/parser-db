@extends('layouts.app')

@section('content')
    <div class="container mx-auto p-4">
        <h2 class="text-xl mb-4">Публичная комната</h2>
        <div id="public-chat-window" class="border p-2 h-64 overflow-auto mb-4">
            @foreach($publicMessages as $msg)
                <div><strong>{{ $msg->user->name }}:</strong> {{ $msg->message }}</div>
            @endforeach
        </div>
        <input type="text" id="public-input" placeholder="Сообщение..." class="border p-2 w-full mb-2">
        <button id="public-send" data-room="0" class="bg-blue-500 text-white p-2">Отправить</button>

        <hr class="my-6">

        <h2 class="text-xl mb-4">Приватная комната</h2>
        <div id="private-chat-window" class="border p-2 h-64 overflow-auto mb-4">
            @foreach($privateMessages as $msg)
                <div><strong>{{ $msg->user->name }}:</strong> {{ $msg->message }}</div>
            @endforeach
        </div>
        <input type="text" id="private-input" placeholder="Сообщение..." class="border p-2 w-full mb-2">
        <button id="private-send" data-room="{{ Auth::id() }}" class="bg-green-500 text-white p-2">Отправить</button>
    </div>


    <script>

        Pusher.logToConsole = true;

        const userId = {{ Auth::id() }};

        window.Echo = new Echo({

            broadcaster: 'pusher',
            key: '{{ env("PUSHER_APP_KEY") }}',
            wsHost: '{{ env("PUSHER_APP_HOST") }}',
            wsPort: {{ env("PUSHER_APP_PORT") }},
            forceTLS: false,
            encrypted: false,
            disableStats: true,
            authEndpoint: '/broadcasting/auth',
            auth: {
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            }
        });


        // Подписываемся на публичный канал
        window.Echo.channel('public-chat')
            .listen('MessageSent', e => {
                document.querySelector('#public-chat-window')
                    .insertAdjacentHTML('beforeend',
                        `<div><strong>${e.user}:</strong> ${e.message}</div>`);
            });

        // Подписываемся на приватный канал
        window.Echo.private(`private-chat.${userId}`)
            .listen('MessageSent', e => {
                document.querySelector('#private-chat-window')
                    .insertAdjacentHTML('beforeend',
                        `<div><strong>${e.user}:</strong> ${e.message}</div>`);
            });




        // Отправка сообщений
        function sendMessage(buttonSelector, inputSelector) {
            document.querySelector(buttonSelector).addEventListener('click', function(){
                const room = this.dataset.room;
                const msg  = document.querySelector(inputSelector).value;
                if (!msg) return;
                axios.post('{{ route("chat.send") }}', {
                    message: msg,
                    room_id: room
                }).then(() => {
                    document.querySelector(inputSelector).value = '';
                });
            });
        }
        sendMessage('#public-send', '#public-input');
        sendMessage('#private-send', '#private-input');


    </script>

@endsection



