@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h3 class="mb-0">Источники</h3>
            <a class="btn btn-outline-primary" href="{{ route('exports.index') }}">Перейти к экспорту</a>
        </div>

        @include('partials.flash')

        <div class="card mb-4">
            <div class="card-header">Добавить файл</div>
            <div class="card-body">
                <form id="dump-upload-form" method="POST" action="{{ route('dumps.store') }}" enctype="multipart/form-data">
                    @csrf

                    <div class="form-row align-items-end">
                        <div class="col-md-8">
                            <label for="files">SQL-файлы</label>
                            <input
                                type="file"
                                class="form-control @error('files') is-invalid @enderror"
                                id="files"
                                name="files[]"
                                accept=".sql"
                                multiple
                            >
                            @error('files')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">Формат: .sql, можно выбрать несколько файлов сразу</small>
                        </div>
                        <div class="col-md-4">
                            <button class="btn btn-primary btn-block" type="submit">Загрузить</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">Список</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Имя</th>
                                <th class="text-nowrap">Размер</th>
                                <th class="text-nowrap">Последний запуск</th>
                                <th>Ошибка</th>
                                <th class="text-right">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($dumps as $dump)
                                <tr>
                                    <td class="text-muted">{{ $dump->id }}</td>
                                    <td>
                                        <div class="font-weight-bold">{{ $dump->original_name }}</div>
                                        <div class="text-muted small">{{ $dump->stored_name }}</div>
                                    </td>
                                    <td class="text-nowrap">{{ number_format($dump->size_bytes / 1024 / 1024, 2) }} MB</td>
                                    <td class="text-nowrap">
                                        {{ $dump->last_parsed_at ? $dump->last_parsed_at->format('Y-m-d H:i') : '—' }}
                                    </td>
                                    <td style="max-width: 420px;">
                                        @if($dump->last_error)
                                            <div class="text-danger small" title="{{ $dump->last_error }}">
                                                {{ \Illuminate\Support\Str::limit($dump->last_error, 140) }}
                                            </div>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="text-right text-nowrap">
                                        <form method="POST" action="{{ route('dumps.destroy', $dump) }}" onsubmit="return confirm('Удалить файл?');" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger" type="submit">Удалить</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted p-4">Файлы не добавлены.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            var form = document.getElementById('dump-upload-form');
            if (!form) return;

            form.addEventListener('submit', function (event) {
                event.preventDefault();

                var formData = new FormData(form);

                // Индикатор начала загрузки
                var container = document.querySelector('.container');
                var uploadAlert = null;
                if (container) {
                    uploadAlert = document.createElement('div');
                    uploadAlert.className = 'alert alert-warning mb-3';
                    uploadAlert.textContent = 'Загрузка файлов... Пожалуйста, подождите.';
                    container.insertBefore(uploadAlert, container.firstChild);
                }
                var submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Загружаем...';
                }

                fetch(form.action, {
                    method: form.method || 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': formData.get('_token')
                    },
                    body: formData
                })
                    .then(function (response) {
                        if (!response.ok) {
                            throw new Error('Upload failed.');
                        }
                        return response.json();
                    })
                    .then(function (data) {
                        var container = document.querySelector('.container');
                        if (container) {
                            var alert = document.createElement('div');
                            alert.className = 'alert alert-info';
                            alert.textContent = 'Файлы загружены. Обновляем список источников...';
                            container.insertBefore(alert, container.firstChild);
                        }

                        // Сбросить форму (очистить выбранные файлы)
                        form.reset();

                        // Небольшая задержка для наглядности и затем обновляем страницу.
                        setTimeout(function () {
                            window.location.reload();
                        }, 500);
                    })
                    .catch(function () {
                        window.location.reload();
                    });
            });
        })();
    </script>
@endsection

