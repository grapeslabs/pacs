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
</style>

<div class="perm-matrix-container"
     x-data="permissionMatrixComponent(
         @js($element->resolveValue()),
         @js($element->tree()),
         '{{ $element->getColumn() }}',
         @js($rolePermissions),
         '{{ $roleField }}'
     )"
     x-cloak>

    <input type="hidden" name="is_customized_permissions" :value="isCustomized ? '1' : '0'" :disabled="!hasRoleContext">

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
            Сбросить до настроек роли
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
                                        <input type="checkbox"
                                               name="{{ $element->getColumn() }}[{{ $resource['class'] }}][{{ $actionKey }}]"
                                               value="1"
                                               class="form-checkbox"
                                               x-model="values['{{ $resClassEscaped }}']['{{ $actionKey }}']"
                                               @change="checkCustomization()"
                                               id="perm_{{ md5($resource['class']) }}_{{ $actionKey }}">
                                        <label
                                            for="perm_{{ md5($resource['class']) }}_{{ $actionKey }}">{{ $actionLabel }}</label>
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
        window.permissionMatrixComponent = function (initialValues, treeData, inputName, rolePermissions, roleField) {
            return {
                tree: treeData,
                values: {},
                activeTab: null,
                inputName: inputName,
                rolePermissions: rolePermissions,
                currentRoleId: null,
                isCustomized: false,
                roleField: roleField,
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
                                    this.resetToRole();
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

                    if (this.hasRoleContext) {
                        const hasUserOverrides = initial && Object.keys(initial).length > 0;
                        const source = hasUserOverrides ? initial : (this.rolePermissions[this.currentRoleId] || {});

                        for (const cat in this.tree) {
                            this.tree[cat].resources.forEach(res => {
                                newValues[res.class] = {};
                                for (const action in res.actions) {
                                    newValues[res.class][action] = source[res.class]?.[action] === true || source[res.class]?.[action] == 1;
                                }
                            });
                        }
                        this.values = newValues;

                        if (hasUserOverrides) {
                            this.checkCustomization();
                        } else {
                            this.isCustomized = false;
                        }
                    } else {
                        for (const cat in this.tree) {
                            this.tree[cat].resources.forEach(res => {
                                newValues[res.class] = {};
                                for (const action in res.actions) {
                                    newValues[res.class][action] = initial?.[res.class]?.[action] === true || initial?.[res.class]?.[action] == 1;
                                }
                            });
                        }
                        this.values = newValues;
                        this.isCustomized = false;
                    }
                },

                resetToRole() {
                    if (!this.hasRoleContext) return;

                    const rolePerms = this.rolePermissions[this.currentRoleId] || {};
                    let newValues = {};
                    for (const cat in this.tree) {
                        this.tree[cat].resources.forEach(res => {
                            newValues[res.class] = {};
                            for (const action in res.actions) {
                                newValues[res.class][action] = rolePerms[res.class]?.[action] === true || rolePerms[res.class]?.[action] == 1;
                            }
                        });
                    }
                    this.values = newValues;
                    this.isCustomized = false;
                },

                checkCustomization() {
                    if (!this.hasRoleContext) {
                        this.isCustomized = false;
                        return;
                    }

                    if (!this.currentRoleId || !this.rolePermissions[this.currentRoleId]) {
                        this.isCustomized = Object.keys(this.values).length > 0;
                        return;
                    }

                    const rolePerms = this.rolePermissions[this.currentRoleId];
                    let changed = false;

                    for (const cat in this.tree) {
                        this.tree[cat].resources.forEach(res => {
                            for (const action in res.actions) {
                                const currentVal = this.values[res.class]?.[action] === true;
                                const roleVal = rolePerms[res.class]?.[action] === true || rolePerms[res.class]?.[action] == 1;

                                if (currentVal !== roleVal) {
                                    changed = true;
                                }
                            }
                        });
                    }
                    this.isCustomized = changed;
                },

                hasAnyPermission(category) {
                    if (!this.tree[category] || !this.values) return false;
                    return this.tree[category].resources.some(res => {
                        return Object.keys(res.actions).some(action => this.values[res.class]?.[action]);
                    });
                },

                toggleGlobal(event) {
                    const checked = event.target.checked;
                    for (const cat in this.tree) {
                        this.tree[cat].resources.forEach(res => {
                            for (const action in res.actions) {
                                this.values[res.class][action] = checked;
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
                            this.values[res.class][action] = checked;
                        }
                    });
                    this.checkCustomization();
                },

                toggleResource(event, resourceClass, category) {
                    const checked = event.target.checked;
                    const resource = this.tree[category].resources.find(r => r.class === resourceClass);
                    for (const action in resource.actions) {
                        this.values[resourceClass][action] = checked;
                    }
                    this.checkCustomization();
                },

                isCategoryFullyChecked(category) {
                    if (!this.tree[category] || !this.values || Object.keys(this.values).length === 0) return false;
                    let allChecked = true;
                    this.tree[category].resources.forEach(res => {
                        for (const action in res.actions) {
                            if (!this.values[res.class]?.[action]) {
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
                        if (!this.values[resourceClass]?.[action]) {
                            allChecked = false;
                        }
                    }
                    return allChecked;
                }
            }
        }
    }
</script>
