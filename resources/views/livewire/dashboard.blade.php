<div x-data="iphDashboard(@js($chartData), @js($activeTab))" x-init="$nextTick(() => initCharts())" @eph-charts-ready.window="data = $event.detail.chartData; tab = $event.detail.tab; $nextTick(() => initCharts())">

    {{-- Not computed banner --}}
    @if(!$isComputed)
    <div class="mb-6 bg-amber-50 border border-amber-200 rounded-xl p-6 text-center">
        <div class="text-amber-700 mb-2">
            <svg class="w-12 h-12 mx-auto mb-3 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <h2 class="text-lg font-semibold">Datos no pre-computados</h2>
            <p class="text-sm mt-1">Para usar la aplicación, primero es necesario pre-computar las estadísticas desde la base de datos INDEC. Esto se hace una sola vez y toma algunos minutos.</p>
        </div>
        <div class="mt-4 bg-amber-100 rounded-lg p-3 text-left text-sm font-mono text-amber-900 inline-block">
            php artisan indec:precompute --fresh
        </div>
        <p class="text-xs text-amber-600 mt-2">Ejecutá este comando en la terminal dentro de la carpeta del proyecto.</p>
    </div>
    @endif

    {{-- Error Banner --}}
    @if($error)
    <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg flex items-start gap-2">
        <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
        <div><p class="font-medium">Error de conexión</p><p class="text-sm">{{ $error }}</p></div>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">

        {{-- ── SIDEBAR FILTERS ─────────────────────────────────── --}}
        <aside class="lg:col-span-1 space-y-4">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="font-semibold text-gray-800">Filtros</h2>
                    <button wire:click="clearFilters" class="text-xs text-indigo-600 hover:text-indigo-800">Limpiar</button>
                </div>

                {{-- Anos --}}
                <div class="mb-4">
                    <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">Año</label>
                    <div class="space-y-1 max-h-36 overflow-y-auto">
                        @foreach(array_unique(array_column($availablePeriods, 'ano4')) as $ano)
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" wire:model.live.debounce.400ms="selectedAnos" value="{{ $ano }}"
                                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <span class="text-sm text-gray-700">{{ $ano }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>

                {{-- Trimestres --}}
                <div class="mb-4">
                    <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">Trimestre</label>
                    <div class="grid grid-cols-2 gap-1">
                        @foreach([1 => '1° trim.', 2 => '2° trim.', 3 => '3° trim.', 4 => '4° trim.'] as $t => $label)
                        <label class="flex items-center gap-1 cursor-pointer">
                            <input type="checkbox" wire:model.live.debounce.400ms="selectedTrimestres" value="{{ $t }}"
                                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <span class="text-xs text-gray-700">{{ $label }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>

                {{-- Regiones --}}
                <div class="mb-4">
                    <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">Región</label>
                    <div class="space-y-1">
                        @foreach($availableRegions as $id => $name)
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" wire:model.live.debounce.400ms="selectedRegiones" value="{{ $id }}"
                                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <span class="text-xs text-gray-700">{{ $name }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>

                {{-- Conglomerados --}}
                @if(!empty($availableAglomerados))
                <div class="mb-4">
                    <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">Conglomerado</label>
                    <div class="space-y-1 max-h-40 overflow-y-auto">
                        @foreach($availableAglomerados as $aglo)
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" wire:model.live.debounce.400ms="selectedAglomerados" value="{{ $aglo['id'] }}"
                                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <span class="text-xs text-gray-700">{{ $aglo['name'] }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Filter actions --}}
                <div class="flex gap-2 pt-2 border-t border-gray-100">
                    <button wire:click="$set('showSaveModal', true)"
                        class="flex-1 text-xs bg-indigo-600 text-white px-3 py-2 rounded-lg hover:bg-indigo-700 transition">
                        Guardar filtro
                    </button>
                    <button wire:click="$set('showLoadModal', true)"
                        class="flex-1 text-xs bg-white text-indigo-600 border border-indigo-300 px-3 py-2 rounded-lg hover:bg-indigo-50 transition">
                        Cargar ({{ count($savedFilters) }})
                    </button>
                </div>

                {{-- Last computed --}}
                @if($lastComputed)
                <div class="pt-3 border-t border-gray-100">
                    <p class="text-xs text-gray-400">
                        Datos: {{ \Carbon\Carbon::parse($lastComputed)->format('d/m/Y H:i') }}
                    </p>
                    @unless(app()->isProduction())
                    <p class="text-xs text-gray-400 mt-1">Para actualizar:</p>
                    <code class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded block mt-1 break-all">php artisan indec:precompute</code>
                    @endunless
                </div>
                @endif
            </div>

            {{-- Active filter indicator --}}
            @if(count($selectedAnos) || count($selectedTrimestres) || count($selectedRegiones) || count($selectedAglomerados))
            <div class="bg-indigo-50 rounded-lg border border-indigo-200 p-3 text-xs text-indigo-700">
                <p class="font-medium mb-1">Filtros activos:</p>
                @if(count($selectedAnos)) <p>Años: {{ implode(', ', $selectedAnos) }}</p> @endif
                @if(count($selectedTrimestres)) <p>Trimestres: {{ implode(', ', $selectedTrimestres) }}</p> @endif
                @if(count($selectedRegiones))
                    <p>Regiones: {{ implode(', ', array_intersect_key($availableRegions, array_flip($selectedRegiones))) }}</p>
                @endif
                @if(count($selectedAglomerados))
                    @php $agloNames = array_column($availableAglomerados, 'name', 'id'); @endphp
                    <p>Conglomerados: {{ implode(', ', array_map(fn($id) => $agloNames[$id] ?? $id, $selectedAglomerados)) }}</p>
                @endif
            </div>
            @endif
        </aside>

        {{-- ── MAIN CONTENT ─────────────────────────────────────── --}}
        <div class="lg:col-span-3 space-y-5">

            {{-- Tabs --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-1 flex gap-1">
                <button wire:click="$set('activeTab', 'individual')"
                    class="flex-1 py-2 px-4 rounded-lg text-sm font-medium transition {{ $activeTab === 'individual' ? 'bg-indigo-600 text-white shadow-sm' : 'text-gray-600 hover:text-gray-800 hover:bg-gray-50' }}">
                    Individuos
                </button>
                <button wire:click="$set('activeTab', 'hogar')"
                    class="flex-1 py-2 px-4 rounded-lg text-sm font-medium transition {{ $activeTab === 'hogar' ? 'bg-indigo-600 text-white shadow-sm' : 'text-gray-600 hover:text-gray-800 hover:bg-gray-50' }}">
                    Hogares
                </button>
                @if($isComputed && !empty($chartData))
                <button wire:click="exportCsv"
                    class="py-2 px-4 rounded-lg text-sm font-medium text-emerald-700 border border-emerald-300 hover:bg-emerald-50 transition flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    CSV
                </button>
                @endif
            </div>

            {{-- Loading indicator --}}
            <div wire:loading wire:target="selectedAnos,selectedTrimestres,selectedRegiones,selectedAglomerados,activeTab,clearFilters,loadFilter" class="flex items-center gap-2 text-indigo-600 text-sm">
                <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                Cargando datos...
            </div>

            {{-- ── INDIVIDUAL TAB ──────────────────────────────────── --}}
            @if($activeTab === 'individual')

            {{-- KPI Cards --}}
            @if(!empty($summary))
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                @php
                    $ocupados = $summary['ocupados'] ?? 0;
                    $desocupados = $summary['desocupados'] ?? 0;
                    $pea = $ocupados + $desocupados;
                    $tasaDesocupacion = $pea > 0 ? round($desocupados / $pea * 100, 1) : 0;
                @endphp
                <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Personas</p>
                    <p class="text-2xl font-bold text-gray-800">{{ number_format($summary['total_personas'] ?? 0, 0, ',', '.') }}</p>
                    <p class="text-xs text-gray-400">ponderadas</p>
                </div>
                <div class="bg-white rounded-xl p-4 shadow-sm border border-green-100">
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Ocupados</p>
                    <p class="text-2xl font-bold text-green-600">{{ number_format($summary['ocupados'] ?? 0, 0, ',', '.') }}</p>
                    <p class="text-xs text-gray-400">ESTADO = 1</p>
                </div>
                <div class="bg-white rounded-xl p-4 shadow-sm border border-red-100">
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Desocupados</p>
                    <p class="text-2xl font-bold text-red-600">{{ number_format($summary['desocupados'] ?? 0, 0, ',', '.') }}</p>
                    <p class="text-xs text-gray-400">ESTADO = 2</p>
                </div>
                <div class="bg-white rounded-xl p-4 shadow-sm border border-orange-100">
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Tasa desocupación</p>
                    <p class="text-2xl font-bold text-orange-600">{{ $tasaDesocupacion }}%</p>
                    <p class="text-xs text-gray-400">desoc. / PEA</p>
                </div>
                <div class="bg-white rounded-xl p-4 shadow-sm border border-blue-100">
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Ingreso promedio</p>
                    <p class="text-2xl font-bold text-blue-600">$ {{ number_format($summary['ingreso_promedio'] ?? 0, 0, ',', '.') }}</p>
                    <p class="text-xs text-gray-400">P47T (ocupados)</p>
                </div>
                <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Edad promedio</p>
                    <p class="text-2xl font-bold text-gray-700">{{ $summary['edad_promedio'] ?? '—' }}</p>
                    <p class="text-xs text-gray-400">años</p>
                </div>
            </div>
            @endif

            {{-- Employment Series Chart --}}
            @if(!empty($chartData['employment']['labels']))
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <h3 class="font-semibold text-gray-800 mb-4">Tasas de actividad, empleo y desocupación (%)</h3>
                <div class="chart-container">
                    <canvas id="chart-employment"></canvas>
                </div>
            </div>

            {{-- Income Series Chart --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <h3 class="font-semibold text-gray-800 mb-4">Ingreso promedio por período ($)</h3>
                <div class="chart-container">
                    <canvas id="chart-income-ind"></canvas>
                </div>
            </div>

            {{-- Informalidad laboral --}}
            @if(!empty($chartData['informalidad']['labels']))
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <h3 class="font-semibold text-gray-800 mb-4">Informalidad laboral — asalariados sin descuento jubilatorio (%)</h3>
                <div class="chart-container">
                    <canvas id="chart-informalidad"></canvas>
                </div>
            </div>
            @endif

            {{-- Intensidad laboral --}}
            @if(!empty($chartData['intensidad']['labels']))
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <h3 class="font-semibold text-gray-800 mb-1">Intensidad laboral — sobreocupación y subocupación</h3>
                <p class="text-xs text-gray-500 mb-4">% sobre total de ocupados. Sobreocupados: más de 45h semanales. Subocupados: menos de 35h.</p>
                <div class="chart-container">
                    <canvas id="chart-intensidad"></canvas>
                </div>
            </div>
            @endif

            {{-- Brecha de género en ingresos --}}
            @if(!empty($chartData['ingreso_genero']['labels']))
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <h3 class="font-semibold text-gray-800 mb-1">Brecha de género en ingresos de ocupación</h3>
                <p class="text-xs text-gray-500 mb-4">Ingreso promedio (eje izq.) y brecha porcentual = cuánto menos gana la mujer respecto al varón (eje der.)</p>
                <div class="chart-container">
                    <canvas id="chart-ingreso-genero"></canvas>
                </div>
            </div>
            @endif

            {{-- Distribution charts (2 col) --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                    <h3 class="font-semibold text-gray-800 mb-4">Nivel educativo</h3>
                    <div class="chart-container" style="height:240px">
                        <canvas id="chart-education"></canvas>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                    <h3 class="font-semibold text-gray-800 mb-4">Categoría ocupacional</h3>
                    <div class="chart-container" style="height:240px">
                        <canvas id="chart-category"></canvas>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                    <h3 class="font-semibold text-gray-800 mb-4">Distribución por sexo</h3>
                    <div class="chart-container" style="height:240px">
                        <canvas id="chart-gender"></canvas>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                    <h3 class="font-semibold text-gray-800 mb-4">Decil individual de ingreso</h3>
                    <div class="chart-container" style="height:240px">
                        <canvas id="chart-decindr"></canvas>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                    <h3 class="font-semibold text-gray-800 mb-4">Cobertura de salud</h3>
                    <div class="chart-container" style="height:240px">
                        <canvas id="chart-salud"></canvas>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                    <h3 class="font-semibold text-gray-800 mb-4">Inactivos por tipo</h3>
                    <div class="chart-container" style="height:240px">
                        <canvas id="chart-cat-inac"></canvas>
                    </div>
                </div>
            </div>
            @else
            <div class="bg-gray-50 rounded-xl p-8 text-center text-gray-400">
                <p>No hay datos disponibles para los filtros seleccionados.</p>
            </div>
            @endif

            {{-- ── HOGAR TAB ────────────────────────────────────────── --}}
            @elseif($activeTab === 'hogar')

            {{-- KPI Cards --}}
            @if(!empty($summary))
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Hogares</p>
                    <p class="text-2xl font-bold text-gray-800">{{ number_format($summary['total_hogares'] ?? 0, 0, ',', '.') }}</p>
                    <p class="text-xs text-gray-400">ponderados</p>
                </div>
                <div class="bg-white rounded-xl p-4 shadow-sm border border-indigo-100">
                    <p class="text-xs text-gray-500 uppercase tracking-wide">ITF promedio</p>
                    <p class="text-2xl font-bold text-indigo-600">$ {{ number_format($summary['itf_promedio'] ?? 0, 0, ',', '.') }}</p>
                    <p class="text-xs text-gray-400">Ingreso total familiar</p>
                </div>
                <div class="bg-white rounded-xl p-4 shadow-sm border border-blue-100">
                    <p class="text-xs text-gray-500 uppercase tracking-wide">IPCF promedio</p>
                    <p class="text-2xl font-bold text-blue-600">$ {{ number_format($summary['ipcf_promedio'] ?? 0, 0, ',', '.') }}</p>
                    <p class="text-xs text-gray-400">Ingreso per cápita</p>
                </div>
                <div class="bg-white rounded-xl p-4 shadow-sm border border-green-100">
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Miembros promedio</p>
                    <p class="text-2xl font-bold text-green-600">{{ number_format($summary['miembros_promedio'] ?? 0, 1, ',', '.') }}</p>
                    <p class="text-xs text-gray-400">por hogar</p>
                </div>
                <div class="bg-white rounded-xl p-4 shadow-sm border border-orange-100">
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Hacinamiento</p>
                    <p class="text-2xl font-bold text-orange-600">{{ number_format($summary['pct_hacinamiento'] ?? 0, 1, ',', '.') }}%</p>
                    <p class="text-xs text-gray-400">&gt; 3 pers./cuarto</p>
                </div>
            </div>
            @endif

            @if(!empty($chartData['income']['labels']))
            {{-- Income series --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                <h3 class="font-semibold text-gray-800 mb-4">Ingreso familiar promedio por período ($)</h3>
                <div class="chart-container">
                    <canvas id="chart-hogar-income"></canvas>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                {{-- Decil --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                    <h3 class="font-semibold text-gray-800 mb-4">Distribución por decil (ITF)</h3>
                    <div class="chart-container" style="height:240px">
                        <canvas id="chart-decil"></canvas>
                    </div>
                </div>

                {{-- Vivienda --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                    <h3 class="font-semibold text-gray-800 mb-4">Tipo de vivienda</h3>
                    <div class="chart-container" style="height:240px">
                        <canvas id="chart-vivienda"></canvas>
                    </div>
                </div>

                {{-- Tenencia --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                    <h3 class="font-semibold text-gray-800 mb-4">Régimen de tenencia</h3>
                    <div class="chart-container" style="height:240px">
                        <canvas id="chart-tenencia"></canvas>
                    </div>
                </div>

                {{-- Members --}}
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                    <h3 class="font-semibold text-gray-800 mb-4">Miembros promedio por período</h3>
                    <div class="chart-container" style="height:240px">
                        <canvas id="chart-members"></canvas>
                    </div>
                </div>
            </div>
            @else
            <div class="bg-gray-50 rounded-xl p-8 text-center text-gray-400">
                <p>No hay datos disponibles para los filtros seleccionados.</p>
            </div>
            @endif

            @endif
        </div>
    </div>

    {{-- ── SAVE FILTER MODAL ─────────────────────────────────────────── --}}
    @if($showSaveModal)
    <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Guardar filtro</h2>
            <form wire:submit="saveFilter" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                    <input wire:model="filterName" type="text" placeholder="Ej: GBA 2024"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    @error('filterName') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Descripción (opcional)</label>
                    <textarea wire:model="filterDescription" rows="2" placeholder="Descripción breve..."
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent"></textarea>
                </div>
                <p class="text-xs text-gray-500">Se guardará el filtro actual de la tabla <strong>{{ $activeTab }}</strong>.</p>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" wire:click="$set('showSaveModal', false)"
                        class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">
                        Cancelar
                    </button>
                    <button type="submit"
                        class="px-4 py-2 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                        Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- ── LOAD FILTER MODAL ─────────────────────────────────────────── --}}
    @if($showLoadModal)
    <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-lg p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Filtros guardados</h2>
            @if(empty($savedFilters))
            <p class="text-gray-500 text-sm text-center py-8">No hay filtros guardados aún.</p>
            @else
            <div class="space-y-2 max-h-80 overflow-y-auto">
                @foreach($savedFilters as $sf)
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-100">
                    <div class="flex-1 min-w-0 mr-3">
                        <div class="flex items-center gap-2">
                            <span class="font-medium text-sm text-gray-800">{{ $sf['name'] }}</span>
                            <span class="text-xs px-2 py-0.5 rounded-full {{ $sf['table_type'] === 'individual' ? 'bg-indigo-100 text-indigo-700' : 'bg-green-100 text-green-700' }}">
                                {{ $sf['table_type'] }}
                            </span>
                        </div>
                        @if($sf['description'])
                        <p class="text-xs text-gray-500 mt-0.5 truncate">{{ $sf['description'] }}</p>
                        @endif
                        <p class="text-xs text-gray-400 mt-0.5">{{ \Carbon\Carbon::parse($sf['created_at'])->format('d/m/Y H:i') }}</p>
                    </div>
                    <div class="flex gap-1 flex-shrink-0">
                        <button wire:click="loadFilter({{ $sf['id'] }})"
                            class="px-3 py-1.5 text-xs bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                            Cargar
                        </button>
                        <button wire:click="deleteFilter({{ $sf['id'] }})"
                            wire:confirm="¿Eliminar este filtro?"
                            class="px-3 py-1.5 text-xs text-red-600 border border-red-200 rounded-lg hover:bg-red-50">
                            Borrar
                        </button>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
            <div class="flex justify-end mt-4">
                <button wire:click="$set('showLoadModal', false)"
                    class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">
                    Cerrar
                </button>
            </div>
        </div>
    </div>
    @endif

</div>

<script>
function iphDashboard(chartData, activeTab) {
    return {
        data: chartData,
        tab: activeTab,
        palette: ['#6366f1','#22c55e','#f59e0b','#ef4444','#3b82f6','#8b5cf6','#10b981','#f97316','#ec4899','#06b6d4'],

        initCharts() {
            try {
                Object.values(window.chartInstances).forEach(c => { try { c.destroy(); } catch(e) {} });
                window.chartInstances = {};
                if (this.tab === 'individual') {
                    this.renderIndividualCharts();
                } else {
                    this.renderHogarCharts();
                }
            } catch(e) {
                console.error('initCharts error:', e);
            }
        },

        renderIndividualCharts() {
            const d = this.data;
            if (!d || !d.employment) return;

            renderChart('chart-employment', {
                type: 'line',
                data: {
                    labels: d.employment.labels,
                    datasets: [
                        { label: 'Tasa actividad', data: d.employment.tasa_actividad, borderColor: '#6366f1', backgroundColor: 'rgba(99,102,241,0.08)', tension: 0.3, fill: false },
                        { label: 'Tasa empleo', data: d.employment.tasa_empleo, borderColor: '#22c55e', backgroundColor: 'rgba(34,197,94,0.08)', tension: 0.3, fill: false },
                        { label: 'Tasa desocupación', data: d.employment.tasa_desocupacion, borderColor: '#ef4444', backgroundColor: 'rgba(239,68,68,0.08)', tension: 0.3, fill: false },
                        { label: 'Subocupación', data: d.employment.tasa_subocupacion, borderColor: '#f59e0b', backgroundColor: 'rgba(245,158,11,0.08)', tension: 0.3, fill: false, borderDash: [4,4] },
                    ]
                },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } }, scales: { y: { ticks: { callback: v => v + '%' } } } }
            });

            if (d.income && d.income.labels.length) {
                renderChart('chart-income-ind', {
                    type: 'bar',
                    data: {
                        labels: d.income.labels,
                        datasets: [
                            { label: 'Ingreso total (P47T)', data: d.income.ingreso_total, backgroundColor: '#6366f1' },
                            { label: 'Ingreso ocupación (P21)', data: d.income.ingreso_ocupacion, backgroundColor: '#a5b4fc' },
                        ]
                    },
                    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } }, scales: { y: { ticks: { callback: v => '$' + v.toLocaleString('es-AR') } } } }
                });
            }

            if (d.education && d.education.labels.length) {
                renderChart('chart-education', {
                    type: 'bar',
                    data: { labels: d.education.labels, datasets: [{ data: d.education.values, backgroundColor: this.palette }] },
                    options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
                });
            }

            if (d.category && d.category.labels.length) {
                renderChart('chart-category', {
                    type: 'doughnut',
                    data: { labels: d.category.labels, datasets: [{ data: d.category.values, backgroundColor: this.palette }] },
                    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
                });
            }

            if (d.gender && d.gender.labels.length) {
                renderChart('chart-gender', {
                    type: 'pie',
                    data: { labels: d.gender.labels, datasets: [{ data: d.gender.values, backgroundColor: ['#6366f1','#ec4899'] }] },
                    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
                });
            }

            if (d.informalidad && d.informalidad.labels && d.informalidad.labels.length) {
                renderChart('chart-informalidad', {
                    type: 'line',
                    data: {
                        labels: d.informalidad.labels,
                        datasets: [{ label: '% No registrados', data: d.informalidad.pct_informalidad, borderColor: '#dc2626', backgroundColor: 'rgba(220,38,38,0.1)', tension: 0.3, fill: true }]
                    },
                    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } }, scales: { y: { ticks: { callback: v => v + '%' }, min: 0 } } }
                });
            }

            if (d.intensidad && d.intensidad.labels && d.intensidad.labels.length) {
                renderChart('chart-intensidad', {
                    type: 'line',
                    data: {
                        labels: d.intensidad.labels,
                        datasets: [
                            { label: 'Sobreocupados (>45h)', data: d.intensidad.pct_sobreocupados, borderColor: '#f97316', backgroundColor: 'rgba(249,115,22,0.08)', tension: 0.3, fill: true },
                            { label: 'Suboc. demandante', data: d.intensidad.pct_subocu_demandante, borderColor: '#f59e0b', backgroundColor: 'rgba(245,158,11,0.08)', tension: 0.3, fill: true },
                            { label: 'Suboc. no demandante', data: d.intensidad.pct_subocu_no_demandante, borderColor: '#fcd34d', backgroundColor: 'rgba(252,211,77,0.08)', tension: 0.3, fill: true },
                        ]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        plugins: { legend: { position: 'bottom' } },
                        scales: { y: { ticks: { callback: v => v + '%' }, min: 0 } }
                    }
                });
            }

            if (d.ingreso_genero && d.ingreso_genero.labels && d.ingreso_genero.labels.length) {
                const brecha = d.ingreso_genero.labels.map((_, i) => {
                    const v = d.ingreso_genero.varon[i], m = d.ingreso_genero.mujer[i];
                    return (v && m) ? parseFloat(((v - m) / v * 100).toFixed(1)) : null;
                });
                renderChart('chart-ingreso-genero', {
                    type: 'line',
                    data: {
                        labels: d.ingreso_genero.labels,
                        datasets: [
                            { label: 'Varón ($)', data: d.ingreso_genero.varon, borderColor: '#6366f1', backgroundColor: 'rgba(99,102,241,0.05)', tension: 0.3, fill: false, yAxisID: 'yIngreso' },
                            { label: 'Mujer ($)', data: d.ingreso_genero.mujer, borderColor: '#ec4899', backgroundColor: 'rgba(236,72,153,0.05)', tension: 0.3, fill: false, yAxisID: 'yIngreso' },
                            { label: 'Brecha (%)', data: brecha, borderColor: '#f59e0b', borderDash: [5,3], tension: 0.3, fill: false, yAxisID: 'yBrecha', pointRadius: 2 },
                        ]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        plugins: { legend: { position: 'bottom' } },
                        scales: {
                            yIngreso: { type: 'linear', position: 'left', ticks: { callback: v => '$' + v.toLocaleString('es-AR') } },
                            yBrecha:  { type: 'linear', position: 'right', grid: { drawOnChartArea: false }, ticks: { callback: v => v + '%' }, min: 0 }
                        }
                    }
                });
            }

            if (d.decindr && d.decindr.labels.length) {
                renderChart('chart-decindr', {
                    type: 'bar',
                    data: { labels: d.decindr.labels, datasets: [{ label: 'Personas', data: d.decindr.values, backgroundColor: this.palette }] },
                    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
                });
            }

            if (d.salud && d.salud.labels.length) {
                renderChart('chart-salud', {
                    type: 'doughnut',
                    data: { labels: d.salud.labels, datasets: [{ data: d.salud.values, backgroundColor: this.palette }] },
                    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { font: { size: 10 } } } } }
                });
            }

            if (d.cat_inac && d.cat_inac.labels.length) {
                renderChart('chart-cat-inac', {
                    type: 'doughnut',
                    data: { labels: d.cat_inac.labels, datasets: [{ data: d.cat_inac.values, backgroundColor: this.palette }] },
                    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { font: { size: 10 } } } } }
                });
            }
        },

        renderHogarCharts() {
            const d = this.data;
            if (!d || !d.income || !d.income.labels || !d.income.labels.length) return;

            renderChart('chart-hogar-income', {
                type: 'line',
                data: {
                    labels: d.income.labels,
                    datasets: [
                        { label: 'ITF promedio', data: d.income.itf, borderColor: '#6366f1', tension: 0.3, fill: false },
                        { label: 'IPCF promedio', data: d.income.ipcf, borderColor: '#22c55e', tension: 0.3, fill: false },
                    ]
                },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } }, scales: { y: { ticks: { callback: v => '$' + v.toLocaleString('es-AR') } } } }
            });

            if (d.decil && d.decil.labels.length) {
                renderChart('chart-decil', {
                    type: 'bar',
                    data: { labels: d.decil.labels, datasets: [{ label: 'Hogares', data: d.decil.values, backgroundColor: this.palette }] },
                    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
                });
            }

            if (d.vivienda && d.vivienda.labels.length) {
                renderChart('chart-vivienda', {
                    type: 'doughnut',
                    data: { labels: d.vivienda.labels, datasets: [{ data: d.vivienda.values, backgroundColor: this.palette }] },
                    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { font: { size: 10 } } } } }
                });
            }

            if (d.tenencia && d.tenencia.labels.length) {
                renderChart('chart-tenencia', {
                    type: 'doughnut',
                    data: { labels: d.tenencia.labels, datasets: [{ data: d.tenencia.values, backgroundColor: this.palette }] },
                    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { font: { size: 10 } } } } }
                });
            }

            if (d.members && d.members.labels.length) {
                renderChart('chart-members', {
                    type: 'line',
                    data: { labels: d.members.labels, datasets: [{ label: 'Miembros promedio', data: d.members.miembros, borderColor: '#f59e0b', backgroundColor: 'rgba(245,158,11,0.1)', tension: 0.3, fill: true }] },
                    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
                });
            }
        }
    };
}

</script>
