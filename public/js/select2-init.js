document.addEventListener('alpine:init', () => {
    Alpine.data('select2', () => ({
        select: null,

        init() {
            this.select = this.$refs.select;
            const isTags = this.select.getAttribute('data-tags') === 'true';
            const createUrl = this.select.getAttribute('data-create-url');

            const t = this;

            this.$nextTick(function() {
                // Инициализируем Select2
                $(t.select).select2({
                    tags: isTags,
                    multiple: t.select.multiple,
                    width: '100%',
                    placeholder: 'Выберите теги или введите новый',
                    allowClear: true,
                    language: {
                        noResults: function() {
                            return isTags ? 'Введите значение для нового тега' : 'Ничего не найдено';
                        }
                    },
                    createTag: function (params) {
                        if (!isTags) return null;
                        
                        const term = params.term.trim();
                        if (term === '') return null;

                        return {
                            id: term,
                            text: term,
                            isNew: true
                        };
                    }
                });

                // Обработка создания новых тегов
                if (isTags && createUrl) {
                    $(t.select).on('select2:select', function (e) {
                        const data = e.params.data;
                        
                        if (data.isNew) {
                            // Создаем новый тег через AJAX
                            fetch(createUrl, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                },
                                body: JSON.stringify({ name: data.text })
                            })
                            .then(response => response.json())
                            .then(result => {
                                if (result.success) {
                                    // Заменяем временный ID на настоящий
                                    const option = new Option(data.text, result.tag.id, true, true);
                                    $(t.select).append(option).trigger('change');
                                }
                            })
                            .catch(error => {
                                console.error('Error creating tag:', error);
                            });
                        }
                    });
                }

                // Синхронизация с оригинальным select
                $(t.select).on('change', function() {
                    t.select.dispatchEvent(new Event('change', { bubbles: true }));
                });
            });
        },
    }))
});