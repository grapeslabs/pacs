<style>
    .perm-matrix-container {
        display: flex;
        flex-direction: column;
        gap: 1rem;
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

    .perm-tab:hover {
        background: #f1f5f9;
        color: #0f172a;
    }

    .perm-tab.active {
        background: #eef2ff;
        color: #4f46e5;
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

    .dark .perm-tab.active {
        background: #312e81;
        color: #818cf8;
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
</style>

<div class="perm-matrix-container">
    <div class="perm-matrix-wrapper"
         x-data="permissionMatrixComponent(@js($element->resolveValue()), @js($element->tree()), '{{ $element->getColumn() }}')"
         x-cloak>

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

                    <div class="perm-checkbox-group mb-6">
                        <input type="checkbox" :id="'toggle_cat_{{ md5($categoryName) }}'" class="form-checkbox"
                               @change="toggleCategory($event, '{{ $categoryName }}')"
                               :checked="isCategoryFullyChecked('{{ $categoryName }}')">
                        <label :for="'toggle_cat_{{ md5($categoryName) }}'"
                               class="font-medium text-black dark:text-white">Включить все</label>
                    </div>

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
        window.permissionMatrixComponent = function (initialValues, treeData, inputName) {
            return {
                tree: treeData,
                values: {},
                activeTab: null,
                inputName: inputName,

                init() {
                    this.activeTab = Object.keys(this.tree)[0] || null;
                    this.initValues(initialValues);
                },

                initValues(initial) {
                    let newValues = {};
                    for (const cat in this.tree) {
                        this.tree[cat].resources.forEach(res => {
                            newValues[res.class] = {};
                            for (const action in res.actions) {
                                newValues[res.class][action] = initial?.[res.class]?.[action] === true || initial?.[res.class]?.[action] == 1;
                            }
                        });
                    }
                    this.values = newValues;
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
                },

                toggleResource(event, resourceClass, category) {
                    const checked = event.target.checked;
                    const resource = this.tree[category].resources.find(r => r.class === resourceClass);
                    for (const action in resource.actions) {
                        this.values[resourceClass][action] = checked;
                    }
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
