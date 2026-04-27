<?php

namespace App\Livewire;

use App\Models\SavedFilter;
use App\Services\IndecService;
use Livewire\Attributes\On;
use Livewire\Component;

class Dashboard extends Component
{
    public string $activeTab = 'individual';

    public array $selectedAnos = [];
    public array $selectedTrimestres = [];
    public array $selectedRegiones = [];
    public array $selectedAglomerados = [];

    public bool $showSaveModal = false;
    public string $filterName = '';
    public string $filterDescription = '';
    public bool $showLoadModal = false;

    public array $availablePeriods = [];
    public array $availableRegions = [];
    public array $availableAglomerados = [];
    public array $savedFilters = [];

    public array $chartData = [];
    public array $summary = [];
    public ?string $error = null;
    public bool $isComputed = false;
    public ?string $lastComputed = null;
    public bool $isPrecomputing = false;

    protected IndecService $service;

    public function boot(IndecService $service): void
    {
        $this->service = $service;
    }

    public function mount(): void
    {
        $this->availableRegions = $this->service->availableRegions();
        $this->isComputed = $this->service->isComputed();
        $this->lastComputed = $this->service->lastComputed();
        $this->loadSavedFilters();

        if ($this->isComputed) {
            $this->availablePeriods = $this->service->availableYearsAndQuarters();
            $this->availableAglomerados = $this->service->availableAglomerados();
            if (!empty($this->availablePeriods)) {
                $latest = $this->availablePeriods[0];
                $this->selectedAnos = [(string)$latest->ano4];
                $this->selectedTrimestres = [(string)$latest->trimestre];
            }
            $this->loadData();
        }
        // loadData() already dispatches eph-charts-ready; no extra dispatch needed here
    }

    public function updatedActiveTab(): void { $this->loadData(); }
    public function updatedSelectedAnos(): void { $this->loadData(); }
    public function updatedSelectedTrimestres(): void { $this->loadData(); }
    public function updatedSelectedAglomerados(): void { $this->loadData(); }

    public function updatedSelectedRegiones(): void
    {
        $regionIds = array_map('intval', $this->selectedRegiones);
        $this->availableAglomerados = $this->service->availableAglomerados($regionIds);
        // Drop selections that are no longer available
        $validIds = array_column($this->availableAglomerados, 'id');
        $this->selectedAglomerados = array_values(
            array_filter($this->selectedAglomerados, fn($a) => in_array((int)$a, $validIds))
        );
        $this->loadData();
    }

    private function currentFilters(): array
    {
        return [
            'anos'        => array_map('intval', $this->selectedAnos),
            'trimestres'  => array_map('intval', $this->selectedTrimestres),
            'regiones'    => array_map('intval', $this->selectedRegiones),
            'aglomerados' => array_map('intval', $this->selectedAglomerados),
        ];
    }

    private function loadData(): void
    {
        if (!$this->isComputed) return;

        try {
            $this->error = null;
            $filters = $this->currentFilters();
            $this->activeTab === 'individual'
                ? $this->loadIndividualData($filters)
                : $this->loadHogarData($filters);
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
            $this->chartData = [];
            $this->summary = [];
        }

        $this->dispatch('eph-charts-ready', tab: $this->activeTab, chartData: $this->chartData);
    }

    private function loadIndividualData(array $filters): void
    {
        $employment   = $this->service->individualEmploymentSeries($filters);
        $income       = $this->service->individualIncomeSeries($filters);
        $education    = $this->service->individualByEducation($filters);
        $gender       = $this->service->individualByGender($filters);
        $category     = $this->service->individualByCategory($filters);
        $informalidad = $this->service->individualInformalidadSeries($filters);
        $intensidad   = $this->service->individualIntensidadSeries($filters);
        $salud        = $this->service->individualBySalud($filters);
        $ingrGenero   = $this->service->individualIngresoGeneroBySeries($filters);
        $decindr      = $this->service->individualByDecindr($filters);
        $catInac      = $this->service->individualByCatInac($filters);
        $this->summary = $this->service->individualSummary($filters);

        $periods = array_map(fn($r) => "T{$r->TRIMESTRE} {$r->ANO4}", $employment);

        // Build gender income series (varón and mujer as separate arrays keyed by period)
        $ingrByPeriodGenero = [];
        foreach ($ingrGenero as $r) {
            $key = "T{$r->TRIMESTRE} {$r->ANO4}";
            $ingrByPeriodGenero[$key][$r->CH04] = (int)$r->ingreso_prom;
        }
        $ingrPeriods = array_keys($ingrByPeriodGenero);
        $ingrVaron   = array_map(fn($p) => $ingrByPeriodGenero[$p][1] ?? null, $ingrPeriods);
        $ingrMujer   = array_map(fn($p) => $ingrByPeriodGenero[$p][2] ?? null, $ingrPeriods);

        $this->chartData = [
            'employment' => [
                'labels'           => $periods,
                'tasa_actividad'   => array_map(fn($r) => $r->tasa_actividad, $employment),
                'tasa_empleo'      => array_map(fn($r) => $r->tasa_empleo, $employment),
                'tasa_desocupacion'=> array_map(fn($r) => $r->tasa_desocupacion, $employment),
                'tasa_subocupacion'=> array_map(fn($r) => $r->tasa_subocupacion, $employment),
            ],
            'income' => [
                'labels'           => array_map(fn($r) => "T{$r->TRIMESTRE} {$r->ANO4}", $income),
                'ingreso_total'    => array_map(fn($r) => $r->ingreso_total_prom, $income),
                'ingreso_ocupacion'=> array_map(fn($r) => $r->ingreso_ocupacion_prom, $income),
            ],
            'education' => [
                'labels' => $this->educationLabels($education),
                'values' => array_map(fn($r) => (int)$r->total, $education),
            ],
            'gender' => [
                'labels' => array_map(fn($r) => $r->CH04 == 1 ? 'Varón' : 'Mujer', $gender),
                'values' => array_map(fn($r) => (int)$r->total, $gender),
            ],
            'category' => [
                'labels' => $this->categoryLabels($category),
                'values' => array_map(fn($r) => (int)$r->total, $category),
            ],
            'informalidad' => [
                'labels'          => array_map(fn($r) => "T{$r->TRIMESTRE} {$r->ANO4}", $informalidad),
                'pct_informalidad'=> array_map(fn($r) => $r->pct_informalidad, $informalidad),
            ],
            'intensidad' => [
                'labels'                  => array_map(fn($r) => "T{$r->TRIMESTRE} {$r->ANO4}", $intensidad),
                'pct_sobreocupados'       => array_map(fn($r) => $r->pct_sobreocupados, $intensidad),
                'pct_subocu_demandante'   => array_map(fn($r) => $r->pct_subocu_demandante, $intensidad),
                'pct_subocu_no_demandante'=> array_map(fn($r) => $r->pct_subocu_no_demandante, $intensidad),
            ],
            'salud' => [
                'labels' => $this->saludLabels($salud),
                'values' => array_map(fn($r) => (int)$r->total, $salud),
            ],
            'ingreso_genero' => [
                'labels' => $ingrPeriods,
                'varon'  => $ingrVaron,
                'mujer'  => $ingrMujer,
            ],
            'decindr' => [
                'labels' => array_map(fn($r) => "Decil {$r->DECINDR}", $decindr),
                'values' => array_map(fn($r) => (int)$r->total, $decindr),
            ],
            'cat_inac' => [
                'labels' => $this->catInacLabels($catInac),
                'values' => array_map(fn($r) => (int)$r->total, $catInac),
            ],
        ];
    }

    private function loadHogarData(array $filters): void
    {
        $income   = $this->service->hogarIncomeSeries($filters);
        $decil    = $this->service->hogarByDecil($filters);
        $vivienda = $this->service->hogarByVivienda($filters);
        $tenencia = $this->service->hogarByTenencia($filters);
        $this->summary = $this->service->hogarSummary($filters);

        $periods = array_map(fn($r) => "T{$r->TRIMESTRE} {$r->ANO4}", $income);

        $this->chartData = [
            'income'  => [
                'labels'  => $periods,
                'itf'     => array_map(fn($r) => (int)$r->itf_promedio, $income),
                'ipcf'    => array_map(fn($r) => (int)$r->ipcf_promedio, $income),
            ],
            'members' => [
                'labels'  => $periods,
                'miembros'=> array_map(fn($r) => (float)$r->miembros_promedio, $income),
            ],
            'decil'   => [
                'labels' => array_map(fn($r) => "Decil {$r->DECIFR}", $decil),
                'values' => array_map(fn($r) => (int)$r->total, $decil),
            ],
            'vivienda'=> [
                'labels' => $this->viviendaLabels($vivienda),
                'values' => array_map(fn($r) => (int)$r->total, $vivienda),
            ],
            'tenencia'=> [
                'labels' => $this->tenenciaLabels($tenencia),
                'values' => array_map(fn($r) => (int)$r->total, $tenencia),
            ],
        ];
    }

    // ── Label helpers ──────────────────────────────────────────────────────────

    private function educationLabels(array $rows): array
    {
        $map = [1=>'Primaria inc.',2=>'Primaria comp.',3=>'Secundaria inc.',4=>'Secundaria comp.',5=>'Superior inc.',6=>'Superior comp.',7=>'Sin instrucción',9=>'Sin especificar'];
        return array_map(fn($r) => $map[$r->NIVEL_ED] ?? "Nivel {$r->NIVEL_ED}", $rows);
    }

    private function categoryLabels(array $rows): array
    {
        $map = [1=>'Patrón',2=>'Cuenta propia',3=>'Asalariado',4=>'Familiar s/rem.'];
        return array_map(fn($r) => $map[$r->CAT_OCUP] ?? "Cat {$r->CAT_OCUP}", $rows);
    }

    private function viviendaLabels(array $rows): array
    {
        $map = [1=>'Casa',2=>'Departamento',3=>'Inquilinato',4=>'Hotel/pensión',5=>'Local no habitable',6=>'Vivienda móvil',7=>'Otro'];
        return array_map(fn($r) => $map[$r->IV1] ?? "Tipo {$r->IV1}", $rows);
    }

    private function tenenciaLabels(array $rows): array
    {
        $map = [1=>'Propietario terreno+viv.',2=>'Propietario solo viv.',3=>'Inquilino',4=>'Ocupante c/permiso',5=>'Ocupante de hecho',6=>'Otra'];
        return array_map(fn($r) => $map[$r->II7] ?? "Ten. {$r->II7}", $rows);
    }

    private function saludLabels(array $rows): array
    {
        $map = [1=>'Obra social / PAMI',2=>'Mutual / Prepaga',3=>'Pago en el momento',4=>'No paga (pública)',9=>'Sin especificar'];
        return array_map(fn($r) => $map[$r->CH08] ?? "Cobertura {$r->CH08}", $rows);
    }

    private function catInacLabels(array $rows): array
    {
        $map = [1=>'Jubilado/Pensionado',2=>'Rentista',3=>'Estudiante',4=>'Ama de casa',5=>'Discapacitado',6=>'Otro'];
        return array_map(fn($r) => $map[$r->CAT_INAC] ?? "Cat {$r->CAT_INAC}", $rows);
    }

    // ── Saved filters ──────────────────────────────────────────────────────────

    public function saveFilter(): void
    {
        $this->validate(['filterName' => 'required|min:2|max:100', 'filterDescription' => 'nullable|max:255']);
        SavedFilter::create(['name' => $this->filterName, 'description' => $this->filterDescription, 'table_type' => $this->activeTab, 'filters' => $this->currentFilters()]);
        $this->filterName = '';
        $this->filterDescription = '';
        $this->showSaveModal = false;
        $this->loadSavedFilters();
    }

    public function loadFilter(int $id): void
    {
        $saved = SavedFilter::findOrFail($id);
        $this->activeTab = $saved->table_type;
        $f = $saved->filters;
        $this->selectedAnos        = array_map('strval', $f['anos'] ?? []);
        $this->selectedTrimestres  = array_map('strval', $f['trimestres'] ?? []);
        $this->selectedRegiones    = array_map('strval', $f['regiones'] ?? []);
        $this->selectedAglomerados = array_map('strval', $f['aglomerados'] ?? []);
        $regionIds = array_map('intval', $this->selectedRegiones);
        $this->availableAglomerados = $this->service->availableAglomerados($regionIds ?: []);
        $this->showLoadModal = false;
        $this->loadData();
    }

    public function deleteFilter(int $id): void
    {
        SavedFilter::destroy($id);
        $this->loadSavedFilters();
    }

    public function clearFilters(): void
    {
        $this->selectedAnos = [];
        $this->selectedTrimestres = [];
        $this->selectedRegiones = [];
        $this->selectedAglomerados = [];
        $this->availableAglomerados = $this->service->availableAglomerados();
        $this->loadData();
    }

    private function loadSavedFilters(): void
    {
        $this->savedFilters = SavedFilter::whereIn('table_type', ['individual', 'hogar'])->orderByDesc('created_at')->get(['id','name','description','table_type','created_at'])->toArray();
    }

    public function exportCsv(): mixed
    {
        if (!$this->isComputed || empty($this->chartData)) return null;

        $rows = [];
        $tab  = $this->activeTab;

        if ($tab === 'individual') {
            $emp = $this->chartData['employment'];
            $rows[] = ['Período','Tasa Actividad','Tasa Empleo','Tasa Desocupación','Tasa Subocupación'];
            foreach ($emp['labels'] as $i => $label) {
                $rows[] = [
                    $label,
                    $emp['tasa_actividad'][$i]    ?? '',
                    $emp['tasa_empleo'][$i]        ?? '',
                    $emp['tasa_desocupacion'][$i]  ?? '',
                    $emp['tasa_subocupacion'][$i]  ?? '',
                ];
            }
            $rows[] = [];
            $inc = $this->chartData['income'];
            $rows[] = ['Período','Ingreso Total Prom.','Ingreso Ocupación Prom.'];
            foreach ($inc['labels'] as $i => $label) {
                $rows[] = [$label, $inc['ingreso_total'][$i] ?? '', $inc['ingreso_ocupacion'][$i] ?? ''];
            }
            $rows[] = [];
            $rows[] = ['Nivel Educativo','Total'];
            foreach ($this->chartData['education']['labels'] as $i => $label) {
                $rows[] = [$label, $this->chartData['education']['values'][$i] ?? ''];
            }
            $rows[] = [];
            $rows[] = ['Género','Total'];
            foreach ($this->chartData['gender']['labels'] as $i => $label) {
                $rows[] = [$label, $this->chartData['gender']['values'][$i] ?? ''];
            }
            $rows[] = [];
            $rows[] = ['Categoría Ocupacional','Total'];
            foreach ($this->chartData['category']['labels'] as $i => $label) {
                $rows[] = [$label, $this->chartData['category']['values'][$i] ?? ''];
            }
            if (!empty($this->chartData['informalidad']['labels'])) {
                $rows[] = [];
                $rows[] = ['Período','% Informalidad (asalariados no registrados)'];
                foreach ($this->chartData['informalidad']['labels'] as $i => $label) {
                    $rows[] = [$label, $this->chartData['informalidad']['pct_informalidad'][$i] ?? ''];
                }
            }
            if (!empty($this->chartData['intensidad']['labels'])) {
                $rows[] = [];
                $rows[] = ['Período','Sobreocupados','Suboc. demandante','Suboc. no demandante'];
                foreach ($this->chartData['intensidad']['labels'] as $i => $label) {
                    $rows[] = [
                        $label,
                        $this->chartData['intensidad']['sobreocupados'][$i] ?? '',
                        $this->chartData['intensidad']['subocu_demandante'][$i] ?? '',
                        $this->chartData['intensidad']['subocu_no_demandante'][$i] ?? '',
                    ];
                }
            }
            if (!empty($this->chartData['ingreso_genero']['labels'])) {
                $rows[] = [];
                $rows[] = ['Período','Ingreso Varón','Ingreso Mujer'];
                foreach ($this->chartData['ingreso_genero']['labels'] as $i => $label) {
                    $rows[] = [$label, $this->chartData['ingreso_genero']['varon'][$i] ?? '', $this->chartData['ingreso_genero']['mujer'][$i] ?? ''];
                }
            }
            if (!empty($this->chartData['decindr']['labels'])) {
                $rows[] = [];
                $rows[] = ['Decil Individual','Total'];
                foreach ($this->chartData['decindr']['labels'] as $i => $label) {
                    $rows[] = [$label, $this->chartData['decindr']['values'][$i] ?? ''];
                }
            }
            if (!empty($this->chartData['salud']['labels'])) {
                $rows[] = [];
                $rows[] = ['Cobertura de Salud','Total'];
                foreach ($this->chartData['salud']['labels'] as $i => $label) {
                    $rows[] = [$label, $this->chartData['salud']['values'][$i] ?? ''];
                }
            }
            if (!empty($this->chartData['cat_inac']['labels'])) {
                $rows[] = [];
                $rows[] = ['Tipo Inactividad','Total'];
                foreach ($this->chartData['cat_inac']['labels'] as $i => $label) {
                    $rows[] = [$label, $this->chartData['cat_inac']['values'][$i] ?? ''];
                }
            }
        } else {
            $inc = $this->chartData['income'];
            $rows[] = ['Período','ITF Promedio','IPCF Promedio','Miembros Promedio'];
            foreach ($inc['labels'] as $i => $label) {
                $rows[] = [
                    $label,
                    $inc['itf'][$i]  ?? '',
                    $inc['ipcf'][$i] ?? '',
                    $this->chartData['members']['miembros'][$i] ?? '',
                ];
            }
            $rows[] = [];
            $rows[] = ['Decil','Total Hogares'];
            foreach ($this->chartData['decil']['labels'] as $i => $label) {
                $rows[] = [$label, $this->chartData['decil']['values'][$i] ?? ''];
            }
            $rows[] = [];
            $rows[] = ['Tipo Vivienda','Total'];
            foreach ($this->chartData['vivienda']['labels'] as $i => $label) {
                $rows[] = [$label, $this->chartData['vivienda']['values'][$i] ?? ''];
            }
            $rows[] = [];
            $rows[] = ['Tenencia','Total'];
            foreach ($this->chartData['tenencia']['labels'] as $i => $label) {
                $rows[] = [$label, $this->chartData['tenencia']['values'][$i] ?? ''];
            }
        }

        $filename = 'eph_' . $tab . '_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $fp = fopen('php://output', 'w');
            fprintf($fp, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel
            foreach ($rows as $row) {
                fputcsv($fp, $row, ';');
            }
            fclose($fp);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function render()
    {
        return view('livewire.dashboard')->layout('layouts.app');
    }
}
