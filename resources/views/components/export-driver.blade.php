<script>
    (function () {
        if (window.pacsExportDriverReady) {
            return;
        }
        window.pacsExportDriverReady = true;

        function toast(message, type) {
            window.dispatchEvent(new CustomEvent('toast', {
                detail: { type: type || 'info', message: message }
            }));
        }

        async function poll(statusUrl, downloadUrl) {
            for (let attempt = 0; attempt < 300; attempt++) {
                await new Promise(resolve => setTimeout(resolve, 2000));

                const response = await fetch(statusUrl, {
                    headers: { 'Accept': 'application/json' },
                    credentials: 'same-origin',
                });

                if (!response.ok) {
                    toast('Не удалось получить статус выгрузки', 'error');
                    return;
                }

                const data = await response.json();

                if (data.status === 'done') {
                    window.location.assign(downloadUrl);
                    return;
                }

                if (data.status === 'failed') {
                    toast(data.error || 'Не удалось сформировать выгрузку', 'error');
                    return;
                }
            }

            toast('Выгрузка ещё готовится — ссылка придёт в уведомления', 'info');
        }

        document.addEventListener('click', async function (event) {
            const link = event.target.closest('.split-export-wrapper a[data-role]');

            if (!link) {
                return;
            }

            const url = link.getAttribute('href') || '';

            if (!url.includes('/export-tasks')) {
                return;
            }

            event.preventDefault();

            const wrapper = link.closest('.split-export-wrapper');

            if (wrapper.dataset.busy === '1') {
                return;
            }
            wrapper.dataset.busy = '1';

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        query: Object.fromEntries(new URLSearchParams(window.location.search).entries()),
                    }),
                });

                if (!response.ok) {
                    toast('Не удалось поставить выгрузку в очередь', 'error');
                    return;
                }

                const data = await response.json();
                toast('Выгрузка формируется, файл скачается автоматически', 'info');

                await poll(data.status_url, data.download_url);
            } catch (e) {
                toast('Ошибка при запуске выгрузки', 'error');
            } finally {
                wrapper.dataset.busy = '0';
            }
        });
    })();
</script>
