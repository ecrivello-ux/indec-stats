<div
    x-data="{
        chart: null,
        initChart(data) {
            if (this.chart) { this.chart.destroy(); this.chart = null; }
            if (!data || !data.labels || !data.labels.length) return;

            const ctx = document.getElementById('cross-chart').getContext('2d');

            if (data.mode === 'distribucion') {
                const palette = ['#6366f1','#ec4899','#f59e0b','#10b981','#3b82f6','#f97316','#8b5cf6','#14b8a6','#ef4444','#84cc16'];
                this.chart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: data.labels,
                        datasets: [{ label: 'Personas', data: data.values, backgroundColor: palette }]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    afterLabel: (ctx) => {
                                        const pct = data.pct[ctx.dataIndex];
                                        return pct !== undefined ? pct + '%' : '';
                                    }
                                }
                            }
                        },
                        scales: { y: { ticks: { callback: v => v.toLocaleString('es-AR') } } }
                    }
                });
            } else {
                this.chart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: data.labels,
                        datasets: [{ label: '% del grupo', data: data.pct, borderColor: '#6366f1', backgroundColor: 'rgba(99,102,241,0.1)', tension: 0.3, fill: true }]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: { y: { min: 0, ticks: { callback: v => v + '%' } } }
                    }
                });
            }
        }
    }"
    x-on:cross-chart-ready.window="initChart($event.detail.data ?? $event.detail)"
>

{{-- Header --}}
<div class="mb-6">
    <h2 class="text-2xl font-bold text-gray-900">Explorador de cruces</h2>
    <p class="text-gray-500 text-sm mt-1">Cruzá características de personas con características del hogar donde viven.</p>
</div>

{{-- Controls --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-6">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-end">

        {{-- Dimensión individual --}}
        <div>
            <label class="block text-xs font-semibold text-gray-500 mb-1 uppercase tracking-wide">Personas: agrupar por</label>
            <select wire:model.live="dimInd" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                @foreach($config['individual'] as $key => $dim)
                    <option value="{{ $key }}">{{ $dim['label'] }}</option>
                @endforeach
            </select>
        </div>

        {{-- Valor individual --}}
        <div>
            <label class="block text-xs font-semibold text-gray-500 mb-1 uppercase tracking-wide">Valor</label>
            <select wire:model.live="valInd" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                @foreach($config['individual'][$dimInd]['values'] as $val => $label)
                    <option value="{{ $val }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        {{-- Dimensión hogar --}}
        <div>
            <label class="block text-xs font-semibold text-gray-500 mb-1 uppercase tracking-wide">Hogar: ver distribución de</label>
            <select wire:model.live="dimHogar" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                @foreach($config['hogar'] as $key => $dim)
                    <option value="{{ $key }}">{{ $dim['label'] }}</option>
                @endforeach
            </select>
        </div>

        {{-- Modo --}}
        <div>
            <label class="block text-xs font-semibold text-gray-500 mb-1 uppercase tracking-wide">Vista</label>
            <select wire:model.live="mode" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
                <option value="distribucion">Distribución (un período)</option>
                <option value="evolucion">Evolución temporal (%)</option>
            </select>
        </div>

    </div>

    {{-- Valor hogar (solo en modo evolución) --}}
    @if($mode === 'evolucion')
    <div class="mt-4 max-w-xs">
        <label class="block text-xs font-semibold text-gray-500 mb-1 uppercase tracking-wide">¿Qué valor seguir en el tiempo?</label>
        <select wire:model.live="valHog" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-300 focus:outline-none">
            <option value="">— Seleccioná —</option>
            @foreach($config['hogar'][$dimHogar]['values'] as $val => $label)
                <option value="{{ $val }}">{{ $label }}</option>
            @endforeach
        </select>
    </div>
    @endif
</div>

{{-- Chart --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-6">
    <div class="flex items-start justify-between mb-4">
        <div>
            <h3 class="font-semibold text-gray-800">
                @if($mode === 'distribucion')
                    {{ $this->hogLabel() }} — {{ $this->indLabel() }}
                @else
                    Evolución: {{ $this->indLabel() }} → {{ $config['hogar'][$dimHogar]['values'][$valHog] ?? '...' }}
                @endif
            </h3>
            <p class="text-xs text-gray-400 mt-0.5">Todos los períodos disponibles · Total nacional</p>
        </div>
        <div wire:loading class="text-xs text-indigo-400 animate-pulse">Actualizando…</div>
    </div>

    @if(!empty($chartData['labels']))
        <div style="position:relative;height:320px;">
            <canvas id="cross-chart"></canvas>
        </div>
    @else
        <div class="flex items-center justify-center h-48 text-gray-400 text-sm">
            @if($mode === 'evolucion' && !$valHog)
                Seleccioná un valor de hogar para ver la evolución temporal.
            @else
                Sin datos para esta combinación.
            @endif
        </div>
    @endif
</div>

{{-- Tabla de resultados (modo distribución) --}}
@if($mode === 'distribucion' && !empty($chartData['labels']))
<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
                <th class="text-left px-5 py-3 font-semibold text-gray-600">{{ $this->hogLabel() }}</th>
                <th class="text-right px-5 py-3 font-semibold text-gray-600">Personas (pond.)</th>
                <th class="text-right px-5 py-3 font-semibold text-gray-600">%</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @foreach($chartData['labels'] as $i => $label)
            <tr class="hover:bg-gray-50">
                <td class="px-5 py-3 text-gray-700">{{ $label }}</td>
                <td class="px-5 py-3 text-right text-gray-700">{{ number_format($chartData['values'][$i], 0, ',', '.') }}</td>
                <td class="px-5 py-3 text-right font-medium text-indigo-600">{{ $chartData['pct'][$i] }}%</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

</div>
