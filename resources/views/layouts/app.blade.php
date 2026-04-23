<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>INDEC EPH — Estadísticas</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        .chart-container { position: relative; height: 280px; }
    </style>
    @livewireStyles
</head>
<body class="bg-gray-50 min-h-screen">
    <header class="bg-indigo-700 text-white shadow-md">
        <div class="max-w-screen-xl mx-auto px-6 py-4 flex items-center gap-6">
            <div class="flex-shrink-0">
                <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center font-bold text-xl">E</div>
            </div>
            <div class="flex-1">
                <h1 class="text-xl font-bold tracking-tight">INDEC EPH — Estadísticas</h1>
                <p class="text-indigo-200 text-sm">Encuesta Permanente de Hogares</p>
            </div>
            <nav class="flex items-center gap-1">
                <a href="{{ url('/') }}"
                   class="px-4 py-2 rounded-lg text-sm font-medium transition-colors
                          {{ request()->is('/') ? 'bg-white/20 text-white' : 'text-indigo-200 hover:bg-white/10 hover:text-white' }}">
                    Dashboard
                </a>
                <a href="{{ url('/explorador') }}"
                   class="px-4 py-2 rounded-lg text-sm font-medium transition-colors
                          {{ request()->is('explorador') ? 'bg-white/20 text-white' : 'text-indigo-200 hover:bg-white/10 hover:text-white' }}">
                    Explorador de cruces
                </a>
                <a href="{{ url('/datos') }}"
                   class="px-4 py-2 rounded-lg text-sm font-medium transition-colors
                          {{ request()->is('datos') ? 'bg-white/20 text-white' : 'text-indigo-200 hover:bg-white/10 hover:text-white' }}">
                    Herramienta de datos
                </a>
            </nav>
        </div>
    </header>

    <main class="max-w-screen-xl mx-auto px-4 py-6">
        {{ $slot }}
    </main>

    @livewireScripts
    <script>
        // Chart registry to destroy before re-render
        window.chartInstances = {};

        Chart.defaults.animation = false;
        Chart.defaults.responsive = true;
        Chart.defaults.maintainAspectRatio = false;

        function renderChart(id, config) {
            if (window.chartInstances[id]) {
                window.chartInstances[id].destroy();
                delete window.chartInstances[id];
            }
            const ctx = document.getElementById(id);
            if (!ctx) return;
            window.chartInstances[id] = new Chart(ctx, config);
        }

        document.addEventListener('livewire:navigated', () => {
            Object.values(window.chartInstances).forEach(c => c.destroy());
            window.chartInstances = {};
        });
    </script>
</body>
</html>
