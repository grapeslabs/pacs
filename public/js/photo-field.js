(function() {
    // Определяем компонент
    var photoFieldComponent = function() {
        return {
            items: [],
            columnName: 'photo',
            isDragOver: false,

            initField(existingValues, colName) {
                this.columnName = colName;

                if (Array.isArray(existingValues)) {
                    existingValues.forEach((pathRaw, index) => {
                        let path = String(pathRaw).trim();
                        let url = path;

                        if (path.toLowerCase().startsWith('http')) {
                            try {
                                const urlObj = new URL(path);
                                path = urlObj.pathname.replace(/^\/storage\//, '').replace(/^\//, '');
                            } catch (e) {
                                console.error("Invalid URL", path);
                            }
                        } else {
                            const cleanPath = path.replace(/^\/+/, '');
                            if (cleanPath.startsWith('storage/')) {
                                url = '/' + cleanPath;
                                path = cleanPath.replace('storage/', '');
                            } else {
                                url = '/storage/' + cleanPath;
                            }
                        }

                        this.items.push({
                            id: 'old-' + index,
                            url: url,
                            value: path,
                            file: null,
                            isNew: false
                        });
                    });
                }
            },

            syncInput() {
                const dt = new DataTransfer();
                this.items.forEach(item => {
                    if (item.isNew && item.file) {
                        dt.items.add(item.file);
                    }
                });
                this.$refs.fileInput.files = dt.files;
            },

            addFile(file) {
                const existingFileNames = this.items
                    .filter(item => item.isNew && item.file)
                    .map(item => item.file.name);

                if (!existingFileNames.includes(file.name)) {
                    this.items.push({
                        id: 'new-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9),
                        url: URL.createObjectURL(file),
                        value: null,
                        file: file,
                        isNew: true
                    });
                    this.syncInput();
                }
            },

            removeFile(index) {
                const item = this.items[index];
                if (item.isNew && item.url) {
                    URL.revokeObjectURL(item.url);
                }
                this.items.splice(index, 1);
                this.syncInput();
            },

            handleManualFileSelect(event) {
                const files = Array.from(event.target.files);
                files.forEach(file => {
                    if (file.type.startsWith('image/')) this.addFile(file);
                });
                event.target.value = '';
            },

            handleGlobalPaste(event) {
                if (['INPUT', 'TEXTAREA'].includes(event.target.tagName)) return;
                const items = (event.clipboardData || event.originalEvent.clipboardData).items;
                for (let index in items) {
                    const item = items[index];
                    if (item.kind === 'file' && item.type.startsWith('image/')) {
                        const blob = item.getAsFile();
                        const file = new File([blob], `pasted_${Date.now()}.png`, { type: blob.type });
                        this.addFile(file);
                    }
                }
            },

            handleDrop(event) {
                this.isDragOver = false;
                const files = Array.from(event.dataTransfer.files);
                files.forEach(file => {
                    if (file.type.startsWith('image/')) this.addFile(file);
                });
            }
        };
    };

    if (typeof Alpine !== 'undefined') {
        Alpine.data('photoField', photoFieldComponent);
    } else {
        document.addEventListener('alpine:init', function() {
            Alpine.data('photoField', photoFieldComponent);
        });
    }
})();
