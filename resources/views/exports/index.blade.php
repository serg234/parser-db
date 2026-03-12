@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h3 class="mb-0">Экспорт</h3>
            <a class="btn btn-outline-secondary" href="{{ route('dumps.index') }}">К источникам</a>
        </div>

        @include('partials.flash')

        <div class="card mb-4">
            <div class="card-header">Создать экспорт</div>
            <div class="card-body">
                <form method="POST">
                    @csrf

                    <div class="form-row mb-3">
                        <div class="col-md-4">
                            <label for="format">Формат</label>
                            <select class="form-control" name="format" id="format" required>
                                <option value="xml">XML</option>
                                <option value="csv">CSV</option>
                                <option value="txt">TXT</option>
                            </select>
                            <small class="form-text text-muted">
                                Для XML сохраняется базовое форматирование. Для CSV/TXT текст будет преобразован в plain text.
                            </small>
                        </div>
                        <div class="col-md-8 d-flex align-items-end justify-content-end">
                            <div class="btn-group">
                                <button
                                    type="submit"
                                    class="btn btn-primary"
                                    formaction="{{ route('exports.generate') }}"
                                >
                                    Сгенерировать по выбранным
                                </button>
                                <button
                                    type="submit"
                                    class="btn btn-outline-primary"
                                    formaction="{{ route('exports.merge') }}"
                                >
                                    Объединить в один файл
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 36px;">
                                        <input type="checkbox" id="select-all">
                                    </th>
                                    <th>Источник</th>
                                    <th class="text-nowrap">Размер</th>
                                    <th class="text-nowrap">Последний запуск</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($dumps as $dump)
                                    <tr>
                                        <td>
                                            <input type="checkbox" name="dump_ids[]" value="{{ $dump->id }}" class="dump-checkbox">
                                        </td>
                                        <td>
                                            <div class="font-weight-bold">{{ $dump->original_name }}</div>
                                            <div class="text-muted small">{{ $dump->stored_name }}</div>
                                        </td>
                                        <td class="text-nowrap">{{ number_format($dump->size_bytes / 1024 / 1024, 2) }} MB</td>
                                        <td class="text-nowrap">
                                            {{ $dump->last_parsed_at ? $dump->last_parsed_at->format('Y-m-d H:i') : '—' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted p-4">
                                            Нет источников. Сначала добавь файл на странице
                                            <a href="{{ route('dumps.index') }}">Источники</a>.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">Результаты</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Тип</th>
                                <th>Формат</th>
                                <th>Файл</th>
                                <th class="text-nowrap">Размер</th>
                                <th class="text-nowrap">Записей</th>
                                <th class="text-nowrap">Дата</th>
                                <th class="text-right">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($exports as $export)
                                <tr>
                                    <td class="text-muted">{{ $export->id }}</td>
                                    <td>
                                        @if($export->type === \App\Models\ExportFile::TYPE_MERGED)
                                            <span class="badge badge-info">merged</span>
                                            <div class="text-muted small">
                                                @php
                                                    $names = $export->dumps->pluck('original_name')->filter()->values();
                                                @endphp
                                                {{ $names->isNotEmpty() ? $names->implode(', ') : '—' }}
                                            </div>
                                        @else
                                            <span class="badge badge-secondary">single</span>
                                            <div class="text-muted small">{{ optional($export->dump)->original_name ?: '—' }}</div>
                                        @endif
                                    </td>
                                    <td class="text-uppercase">{{ $export->format }}</td>
                                    <td class="text-nowrap">{{ $export->filename }}</td>
                                    <td class="text-nowrap">
                                        {{ $export->size_bytes ? number_format($export->size_bytes / 1024 / 1024, 2) . ' MB' : '—' }}
                                    </td>
                                    <td class="text-nowrap">{{ $export->items_count ?? '—' }}</td>
                                    <td class="text-nowrap">{{ $export->created_at ? $export->created_at->format('Y-m-d H:i') : '—' }}</td>
                                    <td class="text-right text-nowrap">
                                        <a class="btn btn-sm btn-outline-success" href="{{ route('exports.download', $export) }}">Скачать</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted p-4">Экспортов пока нет.</td>
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
            var selectAll = document.getElementById('select-all');
            if (selectAll) {
                var checkboxes = document.querySelectorAll('.dump-checkbox');

                selectAll.addEventListener('change', function () {
                    for (var i = 0; i < checkboxes.length; i++) {
                        checkboxes[i].checked = selectAll.checked;
                    }
                });
            }

            var form = document.querySelector('form');
            if (!form) return;

            form.addEventListener('submit', function (event) {
                // Use AJAX only for the export form (has dump_ids[])
                if (!form.querySelector('input[name="dump_ids[]"]')) {
                    return;
                }

                event.preventDefault();

                var submitter = event.submitter || form.querySelector('button[type="submit"]');
                if (!submitter || !submitter.formAction) {
                    form.submit();
                    return;
                }

                var action = submitter.formAction;
                var method = form.method || 'POST';
                var formData = new FormData(form);

                fetch(action, {
                    method: method,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': formData.get('_token')
                    },
                    body: formData
                })
                    .then(function (response) {
                        if (!response.ok) {
                            return response.json().then(function (data) {
                                throw new Error(data.error || 'Export request failed.');
                            }).catch(function () {
                                throw new Error('Export request failed.');
                            });
                        }
                        return response.json();
                    })
                    .then(function (data) {
                        if (!data.task_id) {
                            // Fallback: just reload if no task id
                            window.location.reload();
                            return;
                        }

                        // Показать сообщение, что задача ушла в очередь
                        var container = document.querySelector('.container');
                        if (container) {
                            var alert = document.createElement('div');
                            alert.className = 'alert alert-info';
                            alert.textContent = 'Задача экспорта отправлена в очередь. Пожалуйста, дождитесь обновления результатов.';
                            container.insertBefore(alert, container.firstChild);
                        }

                        // Poll task status every 3 seconds
                        var attempts = 0;
                        var maxAttempts = 40; // ~2 minutes

                        var intervalId = setInterval(function () {
                            attempts++;
                            fetch("{{ route('exports.taskStatus', ['task' => 'TASK_ID']) }}".replace('TASK_ID', data.task_id), {
                                headers: {
                                    'Accept': 'application/json'
                                }
                            })
                                .then(function (response) { return response.json(); })
                                .then(function (status) {
                                    if (status.status === 'done' || status.status === 'failed') {
                                        clearInterval(intervalId);
                                        window.location.reload();
                                    } else if (attempts >= maxAttempts) {
                                        clearInterval(intervalId);
                                    }
                                })
                                .catch(function () {
                                    clearInterval(intervalId);
                                    window.location.reload();
                                });
                        }, 3000);
                    })
                    .catch(function () {
                        window.location.reload();
                    });
            });
        })();
    </script>
@endsection

