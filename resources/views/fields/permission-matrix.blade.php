<style>
    .perm-matrix-container {
        display: flex;
        flex-direction: column;
    }

    .perm-main-label {
        font-size: 1.125rem;
        font-weight: 600;
        color: #0f172a;
    }

    .perm-matrix-wrapper {
        display: flex;
        min-height: 500px;
        border: 1px solid #e2e8f0;
        border-radius: 0.75rem;
        overflow: hidden;
        background: #fff;
    }

    .perm-sidebar {
        width: 280px;
        border-right: 1px solid #e2e8f0;
        background: #f8fafc;
        padding: 1rem 0.5rem;
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }

    .perm-sidebar-global {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.5rem 1rem;
        margin-bottom: 0.5rem;
    }

    .perm-tab {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.6rem 1rem;
        cursor: pointer;
        color: #64748b;
        font-weight: 500;
        border-radius: 0.5rem;
        transition: all 0.2s;
    }

    .perm-tab svg {
        width: 1.25rem;
        height: 1.25rem;
        flex-shrink: 0;
    }

    .perm-tab svg path {
        fill: #7F7F9D;
        transition: fill 0.2s;
    }

    .perm-tab:hover {
        background: #f1f5f9;
        color: #0f172a;
    }

    .perm-tab.active {
        background: #eef2ff;
        color: #4f46e5;
    }

    .perm-tab.active svg path {
        fill: #4f46e5;
    }

    .perm-dot {
        width: 6px;
        height: 6px;
        background-color: #4f46e5;
        border-radius: 50%;
        margin-left: auto;
    }

    .perm-content {
        flex: 1;
        padding: 2rem;
        background: #fff;
    }

    .perm-content-header {
        font-size: 0.9rem;
        color: #64748b;
        margin-bottom: 1rem;
    }

    .perm-content-header strong {
        color: #0f172a;
        font-weight: 600;
    }

    .perm-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 1.5rem;
        margin-top: 1.5rem;
    }

    .perm-resource-col {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        min-width: 0;
    }

    .perm-resource-title {
        font-weight: 600;
        color: #0f172a;
        margin-bottom: 0.25rem;
        font-size: 0.95rem;
    }

    .perm-checkbox-group {
        display: flex;
        align-items: flex-start;
        gap: 0.5rem;
    }

    .perm-checkbox-group input {
        margin-top: 0.15rem;
        cursor: pointer;
        width: 1.25rem;
        height: 1.25rem;
    }

    .perm-checkbox-group label {
        cursor: pointer;
        font-size: 0.875rem;
        line-height: 1.25rem;
        color: #334155;
    }

    .dark .perm-main-label {
        color: #f8fafc;
    }

    .dark .perm-matrix-wrapper {
        border-color: #334155;
        background: #1e293b;
    }

    .dark .perm-sidebar {
        border-color: #334155;
        background: #0f172a;
    }

    .dark .perm-content {
        background: #1e293b;
    }

    .dark .perm-tab {
        color: #94a3b8;
    }

    .dark .perm-tab:hover {
        background: #1e293b;
        color: #f8fafc;
    }

    .dark .perm-tab svg path {
        fill: #7F7F9D;
    }

    .dark .perm-tab.active {
        background: #312e81;
        color: #818cf8;
    }

    .dark .perm-tab.active svg path {
        fill: #818cf8;
    }

    .dark .perm-dot {
        background-color: #818cf8;
    }

    .dark .perm-resource-title, .dark .perm-content-header strong {
        color: #f8fafc;
    }

    .dark .perm-checkbox-group label {
        color: #cbd5e1;
    }

    .perm-header-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1rem;
    }

    .perm-header-left {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .perm-badge {
        background-color: #fef08a;
        color: #854d0e;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .perm-reset-btn {
        display: flex;
        gap: 0.5rem;
        align-items: center;
        border: 1px solid #e2e8f0;
        padding: 0.4rem 0.75rem;
        border-radius: 0.5rem;
        font-size: 0.875rem;
        color: #64748b;
        background: white;
        cursor: pointer;
        transition: all 0.2s;
    }

    .perm-reset-btn:hover {
        background: #f8fafc;
        color: #0f172a;
    }

    .dark .perm-reset-btn {
        background: transparent;
        border-color: #334155;
        color: #cbd5e1;
    }

    .dark .perm-reset-btn:hover {
        background: #334155;
        color: #f8fafc;
    }

    .perm-tri {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 1.25rem;
        height: 1.25rem;
        margin-top: 0.15rem;
        flex-shrink: 0;
        border: 1px solid #cbd5e1;
        border-radius: 0.35rem;
        background: #fff;
        font-size: 0.8rem;
        font-weight: 700;
        line-height: 1;
        color: #94a3b8;
        cursor: pointer;
        transition: all 0.2s;
    }

    .perm-tri.is-allow {
        background: #4f46e5;
        border-color: #4f46e5;
        color: #fff;
    }

    .perm-tri.is-deny {
        background: #dc2626;
        border-color: #dc2626;
        color: #fff;
    }

    .perm-tri-hint {
        display: block;
        font-size: 0.75rem;
        color: #94a3b8;
    }

    .dark .perm-tri {
        background: transparent;
        border-color: #475569;
        color: #64748b;
    }

    .dark .perm-tri.is-allow {
        background: #6366f1;
        border-color: #6366f1;
        color: #fff;
    }

    .dark .perm-tri.is-deny {
        background: #ef4444;
        border-color: #ef4444;
        color: #fff;
    }
</style>

<div class="perm-matrix-container"
     x-data="permissionMatrixComponent(
         @js($element->resolveValue()),
         @js($element->tree()),
         '{{ $element->getColumn() }}',
         @js($rolePermissions),
         '{{ $roleField }}',
         @js($inheritable)
     )"
     x-cloak>

    <div class="perm-header-bar" x-show="hasRoleContext && isCustomized" style="display: none;">
        <div class="perm-header-left">
            <span x-show="isCustomized" x-transition class="perm-badge" style="display: none;">
                Индивидуальные настройки
            </span>
        </div>

        <button type="button" x-show="isCustomized" x-transition @click="resetToRole()" class="perm-reset-btn" style="display: none;">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
            Наследовать всё от роли
        </button>
    </div>

    <div class="perm-matrix-wrapper">
        <div class="perm-sidebar">
            <div class="perm-sidebar-global">
                <input type="checkbox" id="global_toggle" class="form-checkbox" @change="toggleGlobal($event)"
                       :checked="isGlobalFullyChecked()">
                <label for="global_toggle" class="cursor-pointer text-sm text-gray-500 font-medium">Включить все</label>
            </div>

            @foreach($element->tree() as $categoryName => $categoryData)
                <div class="perm-tab"
                     :class="{ 'active': activeTab === '{{ $categoryName }}' }"
                     @click="activeTab = '{{ $categoryName }}'">

                    @if(str_contains(trim($categoryData['icon']), '<svg'))
                        {!! $categoryData['icon'] !!}
                    @else
                        <x-moonshine::icon :icon="$categoryData['icon']"/>
                    @endif

                    <span>{{ $categoryName }}</span>
                    <div class="perm-dot" x-show="hasAnyPermission('{{ $categoryName }}')" style="display: none;"></div>
                </div>
            @endforeach
        </div>

        <div class="perm-content">
            @foreach($element->tree() as $categoryName => $categoryData)
                <div x-show="activeTab === '{{ $categoryName }}'" style="display: none;">

                    <div class="perm-content-header">
                        Настройка прав для <strong x-text="activeTab"></strong>
                    </div>
                    @if(count($categoryData['resources']) > 1)
                        <div class="perm-checkbox-group mb-6">
                            <input type="checkbox" :id="'toggle_cat_{{ md5($categoryName) }}'" class="form-checkbox"
                                   @change="toggleCategory($event, '{{ $categoryName }}')"
                                   :checked="isCategoryFullyChecked('{{ $categoryName }}')">
                                <label :for="'toggle_cat_{{ md5($categoryName) }}'"
                                       class="font-medium text-black dark:text-white">Включить все</label>
                        </div>
                    @endif

                    <div class="perm-grid">
                        @foreach($categoryData['resources'] as $resource)
                            @php
                                $resClassEscaped = addslashes($resource['class']);
                            @endphp

                            <div class="perm-resource-col">
                                @if(count($categoryData['resources']) > 1)
                                    <div class="perm-resource-title">{{ $resource['name'] }}</div>
                                @endif

                                @if(count($resource['actions']) > 1)
                                    <div class="perm-checkbox-group mb-2">
                                        <input type="checkbox" :id="'toggle_res_{{ md5($resource['class']) }}'"
                                               class="form-checkbox"
                                               @change="toggleResource($event, '{{ $resClassEscaped }}', '{{ $categoryName }}')"
                                               :checked="isResourceFullyChecked('{{ $resClassEscaped }}', '{{ $categoryName }}')">
                                        <label :for="'toggle_res_{{ md5($resource['class']) }}'"
                                               class="text-sm text-indigo-500 font-medium">Включить все</label>
                                    </div>
                                @endif

                                @foreach($resource['actions'] as $actionKey => $actionLabel)
                                    <div class="perm-checkbox-group">
                                        @if($inheritable)
                                            <input type="hidden"
                                                   name="{{ $element->getColumn() }}[{{ $resource['class'] }}][{{ $actionKey }}]"
                                                   x-model="values['{{ $resClassEscaped }}']['{{ $actionKey }}']">
                                            <button type="button"
                                                    class="perm-tri"
                                                    :class="triClass('{{ $resClassEscaped }}', '{{ $actionKey }}')"
                                                    :title="triTitle('{{ $resClassEscaped }}', '{{ $actionKey }}')"
                                                    @click="cycle('{{ $resClassEscaped }}', '{{ $actionKey }}')"
                                                    x-text="triGlyph('{{ $resClassEscaped }}', '{{ $actionKey }}')"></button>
                                            <label @click="cycle('{{ $resClassEscaped }}', '{{ $actionKey }}')">
                                                {{ $actionLabel }}
                                                <span class="perm-tri-hint"
                                                      x-show="isInherited('{{ $resClassEscaped }}', '{{ $actionKey }}')"
                                                      x-text="roleHint('{{ $resClassEscaped }}', '{{ $actionKey }}')"
                                                      style="display: none;"></span>
                                            </label>
                                        @else
                                            <input type="checkbox"
                                                   name="{{ $element->getColumn() }}[{{ $resource['class'] }}][{{ $actionKey }}]"
                                                   value="1"
                                                   class="form-checkbox"
                                                   x-model="values['{{ $resClassEscaped }}']['{{ $actionKey }}']"
                                                   @change="checkCustomization()"
                                                   id="perm_{{ md5($resource['class']) }}_{{ $actionKey }}">
                                            <label
                                                for="perm_{{ md5($resource['class']) }}_{{ $actionKey }}">{{ $actionLabel }}</label>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<script>
    if (typeof window.permissionMatrixComponent === 'undefined') {
        window.permissionMatrixComponent = function (initialValues, treeData, inputName, rolePermissions, roleField, inheritable) {
            return {
                tree: treeData,
                values: {},
                activeTab: null,
                inputName: inputName,
                rolePermissions: rolePermissions,
                currentRoleId: null,
                isCustomized: false,
                roleField: roleField,
                inheritable: inheritable,
                hasRoleContext: false,

                init() {
                    this.activeTab = Object.keys(this.tree)[0] || null;
                    this.$nextTick(() => {
                        const roleSelect = document.querySelector(`select[name="${this.roleField}"], input[name="${this.roleField}"]`);

                        if (roleSelect) {
                            this.hasRoleContext = true;
                            this.currentRoleId = roleSelect.value;

                            document.body.addEventListener('change', (e) => {
                                if (e.target.name === this.roleField) {
                                    this.currentRoleId = e.target.value;
                                    this.checkCustomization();
                                }
                            });
                        } else {
                            this.hasRoleContext = false;
                        }

                        this.initValues(initialValues);
                    });
                },

                initValues(initial) {
                    let newValues = {};

                    for (const cat in this.tree) {
                        this.tree[cat].resources.forEach(res => {
                            newValues[res.class] = {};
                            for (const action in res.actions) {
                                newValues[res.class][action] = this.encode(initial?.[res.class], action);
                            }
                        });
                    }

                    this.values = newValues;
                    this.checkCustomization();
                },

                encode(stored, action) {
                    if (this.inheritable) {
                        if (!stored || !Object.prototype.hasOwnProperty.call(stored, action)) {
                            return '';
                        }
                        return stored[action] ? '1' : '0';
                    }
                    return stored?.[action] === true || stored?.[action] == 1;
                },

                isAllowed(resourceClass, action) {
                    const value = this.values[resourceClass]?.[action];
                    return value === true || value === '1';
                },

                isDenied(resourceClass, action) {
                    return this.values[resourceClass]?.[action] === '0';
                },

                isInherited(resourceClass, action) {
                    return this.inheritable && (this.values[resourceClass]?.[action] ?? '') === '';
                },

                setAllowed(resourceClass, action, allowed) {
                    if (!this.values[resourceClass]) return;
                    this.values[resourceClass][action] = this.inheritable ? (allowed ? '1' : '') : allowed;
                },

                cycle(resourceClass, action) {
                    if (!this.values[resourceClass]) return;
                    const value = this.values[resourceClass][action] ?? '';
                    this.values[resourceClass][action] = value === '' ? '1' : (value === '1' ? '0' : '');
                    this.checkCustomization();
                },

                triClass(resourceClass, action) {
                    return {
                        'is-allow': this.isAllowed(resourceClass, action),
                        'is-deny': this.isDenied(resourceClass, action),
                    };
                },

                triGlyph(resourceClass, action) {
                    if (this.isAllowed(resourceClass, action)) return '✓';
                    if (this.isDenied(resourceClass, action)) return '✕';
                    return '–';
                },

                roleAllows(resourceClass, action) {
                    const rolePerms = this.rolePermissions[this.currentRoleId] || {};
                    return rolePerms[resourceClass]?.[action] === true || rolePerms[resourceClass]?.[action] == 1;
                },

                roleHint(resourceClass, action) {
                    return this.roleAllows(resourceClass, action)
                        ? 'наследуется от роли: разрешено'
                        : 'наследуется от роли: запрещено';
                },

                triTitle(resourceClass, action) {
                    if (this.isAllowed(resourceClass, action)) return 'Разрешено';
                    if (this.isDenied(resourceClass, action)) return 'Запрещено';
                    return this.roleHint(resourceClass, action);
                },

                resetToRole() {
                    for (const cat in this.tree) {
                        this.tree[cat].resources.forEach(res => {
                            for (const action in res.actions) {
                                this.values[res.class][action] = this.inheritable ? '' : false;
                            }
                        });
                    }
                    this.checkCustomization();
                },

                checkCustomization() {
                    if (!this.inheritable) {
                        this.isCustomized = false;
                        return;
                    }

                    this.isCustomized = Object.values(this.values).some(actions =>
                        Object.values(actions).some(value => value !== '')
                    );
                },

                hasAnyPermission(category) {
                    if (!this.tree[category] || !this.values) return false;
                    return this.tree[category].resources.some(res => {
                        return Object.keys(res.actions).some(action => this.inheritable
                            ? (this.values[res.class]?.[action] ?? '') !== ''
                            : this.isAllowed(res.class, action));
                    });
                },

                toggleGlobal(event) {
                    const checked = event.target.checked;
                    for (const cat in this.tree) {
                        this.tree[cat].resources.forEach(res => {
                            for (const action in res.actions) {
                                this.setAllowed(res.class, action, checked);
                            }
                        });
                    }
                    this.checkCustomization();
                },

                isGlobalFullyChecked() {
                    if (!this.tree || Object.keys(this.values).length === 0) return false;
                    for (const cat in this.tree) {
                        if (!this.isCategoryFullyChecked(cat)) return false;
                    }
                    return true;
                },

                toggleCategory(event, category) {
                    const checked = event.target.checked;
                    this.tree[category].resources.forEach(res => {
                        for (const action in res.actions) {
                            this.setAllowed(res.class, action, checked);
                        }
                    });
                    this.checkCustomization();
                },

                toggleResource(event, resourceClass, category) {
                    const checked = event.target.checked;
                    const resource = this.tree[category].resources.find(r => r.class === resourceClass);
                    for (const action in resource.actions) {
                        this.setAllowed(resourceClass, action, checked);
                    }
                    this.checkCustomization();
                },

                isCategoryFullyChecked(category) {
                    if (!this.tree[category] || !this.values || Object.keys(this.values).length === 0) return false;
                    let allChecked = true;
                    this.tree[category].resources.forEach(res => {
                        for (const action in res.actions) {
                            if (!this.isAllowed(res.class, action)) {
                                allChecked = false;
                            }
                        }
                    });
                    return allChecked;
                },

                isResourceFullyChecked(resourceClass, category) {
                    if (!this.values || Object.keys(this.values).length === 0) return false;
                    const resource = this.tree[category].resources.find(r => r.class === resourceClass);
                    if (!resource) return false;
                    let allChecked = true;
                    for (const action in resource.actions) {
                        if (!this.isAllowed(resourceClass, action)) {
                            allChecked = false;
                        }
                    }
                    return allChecked;
                }
            }
        }
    }
</script>
