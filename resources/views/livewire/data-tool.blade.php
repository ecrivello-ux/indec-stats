<div x-data="{ filtersOpen: true }">

    {{-- Error --}}
    @if($error)
    <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg flex items-start gap-2">
        <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
        <div><p class="font-medium">Error</p><p class="text-sm font-mono">{{ $error }}</p></div>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">

        {{-- ── SIDEBAR ──────────────────────────────────────────── --}}
        <aside class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 sticky top-4">
                <div class="overflow-y-auto max-h-[calc(100vh-2rem)] p-4">

                <div class="flex items-center justify-between mb-4">
                    <h2 class="font-semibold text-gray-800">Filtros</h2>
                    <button wire:click="clearFilters" class="text-xs text-indigo-600 hover:text-indigo-800">Limpiar todo</button>
                </div>

                {{-- CUÁNDO --}}
                <div class="mb-5">
                    <p class="text-xs font-bold text-indigo-600 uppercase tracking-widest mb-2">Cuándo</p>

                    <label class="block text-xs font-medium text-gray-500 mb-1">Año</label>
                    <select wire:model="selectedAno" class="w-full text-sm border border-gray-200 rounded-lg px-3 py-1.5 mb-3 focus:outline-none focus:ring-2 focus:ring-indigo-300">
                        <option value="">Todos</option>
                        @foreach($availableAnos as $ano)
                            <option value="{{ $ano }}">{{ $ano }}</option>
                        @endforeach
                    </select>

                    <label class="block text-xs font-medium text-gray-500 mb-1">Trimestre</label>
                    <select wire:model="selectedTrimestre" class="w-full text-sm border border-gray-200 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-indigo-300">
                        <option value="">Todos</option>
                        <option value="1">1° trimestre</option>
                        <option value="2">2° trimestre</option>
                        <option value="3">3° trimestre</option>
                        <option value="4">4° trimestre</option>
                    </select>
                </div>

                {{-- DÓNDE --}}
                <div class="mb-5">
                    <p class="text-xs font-bold text-indigo-600 uppercase tracking-widest mb-2">Dónde</p>

                    <label class="block text-xs font-medium text-gray-500 mb-1">Región</label>
                    <select wire:model.live="selectedRegion" class="w-full text-sm border border-gray-200 rounded-lg px-3 py-1.5 mb-3 focus:outline-none focus:ring-2 focus:ring-indigo-300">
                        <option value="">Todas</option>
                        <option value="1">Gran Buenos Aires</option>
                        <option value="40">NOA</option>
                        <option value="41">NEA</option>
                        <option value="42">Cuyo</option>
                        <option value="43">Pampeana</option>
                        <option value="44">Patagónica</option>
                    </select>

                    <label class="block text-xs font-medium text-gray-500 mb-1">Aglomerado</label>
                    <select wire:model="selectedAglomerado" class="w-full text-sm border border-gray-200 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-indigo-300">
                        <option value="">Todos</option>
                        @foreach($this->filteredAglomerados() as $id => $nombre)
                            <option value="{{ $id }}">{{ $nombre }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- QUIÉN --}}
                <div class="mb-5">
                    <p class="text-xs font-bold text-indigo-600 uppercase tracking-widest mb-2">Quién</p>

                    <label class="block text-xs font-medium text-gray-500 mb-1">Sexo</label>
                    <select wire:model="selectedSexo" class="w-full text-sm border border-gray-200 rounded-lg px-3 py-1.5 mb-3 focus:outline-none focus:ring-2 focus:ring-indigo-300">
                        <option value="">Todos</option>
                        <option value="1">Varón</option>
                        <option value="2">Mujer</option>
                    </select>

                    <label class="block text-xs font-medium text-gray-500 mb-1">Edad</label>
                    <div class="flex gap-2 mb-3">
                        <input wire:model="edadMin" type="number" min="0" max="120" placeholder="Desde"
                               class="w-1/2 text-sm border border-gray-200 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-indigo-300">
                        <input wire:model="edadMax" type="number" min="0" max="120" placeholder="Hasta"
                               class="w-1/2 text-sm border border-gray-200 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-indigo-300">
                    </div>

                    <label class="block text-xs font-medium text-gray-500 mb-1">Nivel educativo</label>
                    <select wire:model="selectedNivelEd" class="w-full text-sm border border-gray-200 rounded-lg px-3 py-1.5 mb-3 focus:outline-none focus:ring-2 focus:ring-indigo-300">
                        <option value="">Todos</option>
                        <option value="1">Primaria incompleta</option>
                        <option value="2">Primaria completa</option>
                        <option value="3">Secundaria incompleta</option>
                        <option value="4">Secundaria completa</option>
                        <option value="5">Superior / Univ. incompleto</option>
                        <option value="6">Superior / Univ. completo</option>
                        <option value="7">Sin instrucción</option>
                        <option value="9">Sin especificar</option>
                    </select>

                    <label class="block text-xs font-medium text-gray-500 mb-1">Estado laboral</label>
                    <select wire:model.live="selectedEstado" class="w-full text-sm border border-gray-200 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-indigo-300">
                        <option value="">Todos</option>
                        <option value="1">Ocupado</option>
                        <option value="2">Desocupado</option>
                        <option value="3">Inactivo</option>
                        <option value="4">Menor de 10 años</option>
                    </select>

                    @if($selectedEstado === '1')
                    <div class="mt-3 pl-3 border-l-2 border-indigo-100 space-y-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Categoría ocupacional</label>
                            <select wire:model="selectedCatOcup" class="w-full text-sm border border-gray-200 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-indigo-300">
                                <option value="">Todas</option>
                                <option value="1">Patrón</option>
                                <option value="2">Cuenta propia</option>
                                <option value="3">Asalariado</option>
                                <option value="4">Familiar sin remuneración</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Intensidad horaria</label>
                            <select wire:model="selectedINTENSI" class="w-full text-sm border border-gray-200 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-indigo-300">
                                <option value="">Todas</option>
                                <option value="1">Subocupado por insuf. horaria</option>
                                <option value="2">Ocupado pleno</option>
                                <option value="3">Sobreocupado</option>
                                <option value="4">No trabajó la semana</option>
                            </select>
                        </div>
                    </div>
                    @endif

                    @if($selectedEstado === '3')
                    <div class="mt-3 pl-3 border-l-2 border-indigo-100">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Tipo de inactividad</label>
                        <select wire:model="selectedCatInac" class="w-full text-sm border border-gray-200 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-indigo-300">
                            <option value="">Todos</option>
                            <option value="1">Jubilado / Pensionado</option>
                            <option value="2">Rentista</option>
                            <option value="3">Estudiante</option>
                            <option value="4">Ama de casa</option>
                            <option value="5">Discapacitado</option>
                            <option value="6">Otro</option>
                        </select>
                    </div>
                    @endif
                </div>

                {{-- QUÉ --}}
                <div class="mb-5">
                    <p class="text-xs font-bold text-indigo-600 uppercase tracking-widest mb-2">Qué</p>

                    <label class="block text-xs font-medium text-gray-500 mb-1">Formalidad (asalariados)</label>
                    <select wire:model="selectedPP07H" class="w-full text-sm border border-gray-200 rounded-lg px-3 py-1.5 mb-3 focus:outline-none focus:ring-2 focus:ring-indigo-300">
                        <option value="">Todas</option>
                        <option value="1">Registrado</option>
                        <option value="2">No registrado</option>
                        <option value="9">NS / NR</option>
                    </select>

                    <label class="block text-xs font-medium text-gray-500 mb-1">Sector</label>
                    <select wire:model="selectedPP04A" class="w-full text-sm border border-gray-200 rounded-lg px-3 py-1.5 mb-3 focus:outline-none focus:ring-2 focus:ring-indigo-300">
                        <option value="">Todos</option>
                        <option value="1">Estatal</option>
                        <option value="2">Privado</option>
                        <option value="3">Otro tipo</option>
                    </select>

                    <label class="block text-xs font-medium text-gray-500 mb-1">Rama de actividad (CAES)</label>
                    <select wire:model="selectedCaesDivision" class="w-full text-sm border border-gray-200 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-indigo-300">
                        <option value="">Todas</option>
                        <option value="A">A — Agricultura, ganadería, pesca y silvicultura</option>
                        <option value="B">B — Explotación de minas y canteras</option>
                        <option value="C">C — Industria manufacturera</option>
                        <option value="D">D — Electricidad, gas, vapor</option>
                        <option value="E">E — Suministro de agua; saneamiento</option>
                        <option value="F">F — Construcción</option>
                        <option value="G">G — Comercio; reparación de vehículos</option>
                        <option value="H">H — Transporte y almacenamiento</option>
                        <option value="I">I — Alojamiento y servicios de comida</option>
                        <option value="J">J — Información y comunicaciones</option>
                        <option value="K">K — Actividades financieras y de seguros</option>
                        <option value="L">L — Actividades inmobiliarias</option>
                        <option value="M">M — Actividades profesionales y científicas</option>
                        <option value="N">N — Servicios administrativos y de apoyo</option>
                        <option value="O">O — Administración pública y defensa</option>
                        <option value="P">P — Enseñanza</option>
                        <option value="Q">Q — Salud y asistencia social</option>
                        <option value="R">R — Actividades artísticas y recreativas</option>
                        <option value="S">S — Otras actividades de servicios</option>
                        <option value="T">T — Hogares como empleadores</option>
                        <option value="U">U — Organizaciones extraterritoriales</option>
                    </select>
                </div>

                <button wire:click="search"
                        class="w-full bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold py-2.5 rounded-lg transition-colors flex items-center justify-center gap-2 mb-3">
                    <span wire:loading wire:target="search" class="inline-block w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                    Buscar
                </button>

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

            </div>
            </div>
        </aside>

        {{-- ── TABLA ────────────────────────────────────────────── --}}
        <section class="lg:col-span-3">

            {{-- Header de resultados --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-4 flex items-center justify-between">
                <div>
                    <h2 class="font-semibold text-gray-800">Resultados</h2>
                    @if($hasSearched)
                        <p class="text-sm text-gray-500 mt-0.5">
                            @if($this->totalIsCapped())
                                Más de {{ number_format(5000, 0, ',', '.') }} registros
                            @else
                                {{ number_format($total, 0, ',', '.') }} registros
                            @endif
                            @if($total > $perPage)
                                — página {{ $page }}@if(!$this->totalIsCapped()) de {{ $this->totalPages() }}@endif
                            @endif
                        </p>
                    @else
                        <p class="text-sm text-gray-400 mt-0.5">Seleccioná filtros y presioná Buscar</p>
                    @endif
                </div>
                @if($hasSearched && $total > 0)
                <button wire:click="exportCsv"
                        class="flex items-center gap-1.5 text-sm text-emerald-700 border border-emerald-200 bg-emerald-50 hover:bg-emerald-100 px-3 py-1.5 rounded-lg transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Exportar CSV
                </button>
                @endif
            </div>

            @if($hasSearched && count($rows) > 0)
            {{-- Tabla --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden"
                 x-data="{
                     init() {
                         this.$nextTick(() => {
                             const top = this.$refs.topScroll;
                             const tbl = this.$refs.tblScroll;
                             const inner = this.$refs.topInner;
                             const updateWidth = () => { inner.style.width = tbl.scrollWidth + 'px'; };
                             updateWidth();
                             top.addEventListener('scroll', () => tbl.scrollLeft = top.scrollLeft);
                             tbl.addEventListener('scroll', () => top.scrollLeft = tbl.scrollLeft);
                             new ResizeObserver(updateWidth).observe(tbl);
                         });
                     }
                 }">
                <div x-ref="topScroll" class="overflow-x-scroll border-b border-gray-100 h-4">
                    <div x-ref="topInner" style="height:1px;width:100%"></div>
                </div>
                <div x-ref="tblScroll" class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 border-b border-gray-100">
                            <tr>
                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide whitespace-nowrap">Período</th>
                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide whitespace-nowrap">Aglomerado</th>
                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide whitespace-nowrap">Sexo</th>
                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide whitespace-nowrap">Edad</th>
                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide whitespace-nowrap">Nivel ed.</th>
                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide whitespace-nowrap">Estado</th>
                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide whitespace-nowrap">Cat. ocup. / inac.</th>
                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide whitespace-nowrap">Intensidad</th>
                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide whitespace-nowrap">Formalidad</th>
                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide whitespace-nowrap">Sector</th>
                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide whitespace-nowrap">Rama CAES</th>
                                @if($selectedEstado === '2')
                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide whitespace-nowrap bg-amber-50">Semanas buscando</th>
                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide whitespace-nowrap bg-amber-50">Cómo buscó</th>
                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide whitespace-nowrap bg-amber-50">Trabajo p/empezar</th>
                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide whitespace-nowrap bg-amber-50">Razón desocupación</th>
                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide whitespace-nowrap bg-amber-50">¿Trabajó antes?</th>
                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide whitespace-nowrap bg-amber-50">Cat. últ. trabajo</th>
                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide whitespace-nowrap bg-amber-50">Registro últ. trab.</th>
                                <th class="px-3 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide whitespace-nowrap bg-amber-50">Sector últ. trab.</th>
                                @endif
                                <th class="px-3 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide whitespace-nowrap">Ing. ocup. ppal.</th>
                                <th class="px-3 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide whitespace-nowrap">Ing. total</th>
                                <th class="px-3 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide whitespace-nowrap">Ing. no lab.</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach($rows as $row)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-3 py-2.5 text-gray-700 whitespace-nowrap font-medium">
                                    T{{ $row['TRIMESTRE'] }} {{ $row['ANO4'] }}
                                </td>
                                <td class="px-3 py-2.5 text-gray-600 whitespace-nowrap">
                                    {{ $this->aglomeradoLabel($row['AGLOMERADO']) }}
                                </td>
                                <td class="px-3 py-2.5 text-gray-600 whitespace-nowrap">
                                    {{ $this->sexoLabel($row['CH04']) }}
                                </td>
                                <td class="px-3 py-2.5 text-gray-600 text-center">
                                    {{ $row['CH06'] }}
                                </td>
                                <td class="px-3 py-2.5 text-gray-600">
                                    {{ $this->nivelEdLabel($row['NIVEL_ED']) }}
                                </td>
                                <td class="px-3 py-2.5 whitespace-nowrap">
                                    @php $estado = (int)$row['ESTADO']; @endphp
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                        {{ $estado === 1 ? 'bg-green-100 text-green-800' : ($estado === 2 ? 'bg-red-100 text-red-800' : ($estado === 3 ? 'bg-gray-100 text-gray-600' : 'bg-yellow-100 text-yellow-800')) }}">
                                        {{ $this->estadoLabel($row['ESTADO']) }}
                                    </span>
                                </td>
                                <td class="px-3 py-2.5 text-gray-600 whitespace-nowrap">
                                    @if((int)$row['ESTADO'] === 1 && $row['CAT_OCUP'])
                                        {{ $this->catOcupLabel($row['CAT_OCUP']) }}
                                    @elseif((int)$row['ESTADO'] === 3 && $row['CAT_INAC'])
                                        {{ $this->catInacLabel($row['CAT_INAC']) }}
                                    @else
                                        <span class="text-gray-300">—</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2.5 text-gray-600 text-xs">
                                    {{ $this->intensidadLabel($row['INTENSI']) ?: '—' }}
                                </td>
                                <td class="px-3 py-2.5 text-gray-600 whitespace-nowrap text-xs">
                                    {{ $this->formalidadLabel($row['PP07H']) ?: '—' }}
                                </td>
                                <td class="px-3 py-2.5 text-gray-600 whitespace-nowrap">
                                    {{ $this->sectorLabel($row['PP04A']) ?: '—' }}
                                </td>
                                <td class="px-3 py-2.5 text-gray-600 text-xs max-w-[180px]">
                                    @if($row['PP04B_COD'] ?? null)
                                        <span title="{{ $this->caesLabel($row['PP04B_COD']) }}" class="truncate block">
                                            {{ $this->caesLabel($row['PP04B_COD']) }}
                                        </span>
                                    @else
                                        <span class="text-gray-300">—</span>
                                    @endif
                                </td>
                                @if($selectedEstado === '2')
                                <td class="px-3 py-2.5 text-gray-600 text-center bg-amber-50/50 text-xs">{{ $row['PP10A'] ?? '—' }}</td>
                                <td class="px-3 py-2.5 text-gray-600 bg-amber-50/50 text-xs max-w-[200px]">
                                    <span class="truncate block" title="{{ $this->pp10bLabel($row) }}">{{ $this->pp10bLabel($row) }}</span>
                                </td>
                                <td class="px-3 py-2.5 text-gray-600 bg-amber-50/50 text-xs whitespace-nowrap">{{ $this->pp10cLabel($row['PP10C'] ?? null) ?: '—' }}</td>
                                <td class="px-3 py-2.5 text-gray-600 bg-amber-50/50 text-xs max-w-[180px]">
                                    <span class="truncate block" title="{{ $this->pp10dLabel($row['PP10D'] ?? null) }}">{{ $this->pp10dLabel($row['PP10D'] ?? null) ?: '—' }}</span>
                                </td>
                                <td class="px-3 py-2.5 text-gray-600 bg-amber-50/50 text-xs whitespace-nowrap">{{ $this->pp10eLabel($row['PP10E'] ?? null) ?: '—' }}</td>
                                <td class="px-3 py-2.5 text-gray-600 bg-amber-50/50 text-xs whitespace-nowrap">{{ $this->pp11lLabel($row['PP11L'] ?? null) ?: '—' }}</td>
                                <td class="px-3 py-2.5 text-gray-600 bg-amber-50/50 text-xs whitespace-nowrap">{{ $this->pp11nLabel($row['PP11N'] ?? null) ?: '—' }}</td>
                                <td class="px-3 py-2.5 text-gray-600 bg-amber-50/50 text-xs whitespace-nowrap">{{ $this->pp11oLabel($row['PP11O'] ?? null) ?: '—' }}</td>
                                @endif
                                <td class="px-3 py-2.5 text-right font-mono text-gray-700">
                                    @if($row['P21'])
                                        ${{ number_format($row['P21'], 0, ',', '.') }}
                                    @else
                                        <span class="text-gray-300">—</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2.5 text-right font-mono text-gray-700">
                                    @if($row['P47T'])
                                        ${{ number_format($row['P47T'], 0, ',', '.') }}
                                    @else
                                        <span class="text-gray-300">—</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2.5 text-right font-mono text-gray-700">
                                    @if($row['T_VI'])
                                        ${{ number_format($row['T_VI'], 0, ',', '.') }}
                                    @else
                                        <span class="text-gray-300">—</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Paginación --}}
                @if($this->totalPages() > 1)
                <div class="px-4 py-3 border-t border-gray-100 flex items-center justify-between bg-gray-50">
                    <p class="text-xs text-gray-500">
                        Mostrando {{ ($page - 1) * $perPage + 1 }}–{{ min($page * $perPage, $total) }} de {{ number_format($total, 0, ',', '.') }}
                    </p>
                    <div class="flex items-center gap-1">
                        <button wire:click="goToPage({{ $page - 1 }})" @if($page <= 1) disabled @endif
                                class="px-3 py-1.5 text-sm border border-gray-200 rounded-lg text-gray-600 hover:bg-gray-100 disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
                            ← Anterior
                        </button>

                        @php
                            $start = max(1, $page - 2);
                            $end   = min($this->totalPages(), $page + 2);
                        @endphp
                        @for($p = $start; $p <= $end; $p++)
                            <button wire:click="goToPage({{ $p }})"
                                    class="px-3 py-1.5 text-sm border rounded-lg transition-colors
                                           {{ $p === $page ? 'bg-indigo-600 text-white border-indigo-600' : 'border-gray-200 text-gray-600 hover:bg-gray-100' }}">
                                {{ $p }}
                            </button>
                        @endfor

                        <button wire:click="goToPage({{ $page + 1 }})" @if($page >= $this->totalPages()) disabled @endif
                                class="px-3 py-1.5 text-sm border border-gray-200 rounded-lg text-gray-600 hover:bg-gray-100 disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
                            Siguiente →
                        </button>
                    </div>
                </div>
                @endif
            </div>

            @elseif($hasSearched && $total === 0)
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center text-gray-400">
                <svg class="w-10 h-10 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <p class="text-sm">Ningún registro coincide con los filtros seleccionados.</p>
            </div>

            @else
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center text-gray-400">
                <svg class="w-10 h-10 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/></svg>
                <p class="text-sm">Seleccioná filtros y presioná <strong>Buscar</strong> para ver los datos.</p>
                <p class="text-xs mt-1 text-gray-300">Recomendamos filtrar al menos por año y trimestre.</p>
            </div>
            @endif

        </section>
    </div>

    {{-- ── MODAL GUARDAR ─────────────────────────────────────────── --}}
    @if($showSaveModal)
    <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Guardar filtro</h2>
            <form wire:submit="saveFilter" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                    <input wire:model="filterName" type="text" placeholder="Ej: Asalariados GBA 2024"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    @error('filterName') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Descripción (opcional)</label>
                    <textarea wire:model="filterDescription" rows="2" placeholder="Descripción breve..."
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent"></textarea>
                </div>
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

    {{-- ── MODAL CARGAR ──────────────────────────────────────────── --}}
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
                        <span class="font-medium text-sm text-gray-800">{{ $sf['name'] }}</span>
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
