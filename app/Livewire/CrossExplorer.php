<?php

namespace App\Livewire;

use App\Services\IndecService;
use Livewire\Component;

class CrossExplorer extends Component
{
    public string $dimInd   = 'cat_inac';
    public int    $valInd   = 1;
    public string $dimHogar = 'ii7';
    public ?int   $valHog   = null;
    public string $mode     = 'distribucion'; // 'distribucion' | 'evolucion'

    public array $chartData = [];
    public array $config    = [];

    private IndecService $service;

    public function boot(IndecService $service): void
    {
        $this->service = $service;
    }

    public function mount(): void
    {
        $this->config = IndecService::crossDimConfig();
        $this->loadData();
    }

    public function updatedDimInd(): void
    {
        // Reset to first valid value for new dimension
        $vals = array_keys($this->config['individual'][$this->dimInd]['values'] ?? []);
        $this->valInd = $vals[0] ?? 1;
        $this->valHog = null;
        $this->loadData();
    }

    public function updatedValInd(): void
    {
        $this->valHog = null;
        $this->loadData();
    }

    public function updatedDimHogar(): void
    {
        $this->valHog = null;
        $this->loadData();
    }

    public function updatedValHog(): void
    {
        $this->loadData();
    }

    public function updatedMode(): void
    {
        $this->loadData();
    }

    public function loadData(): void
    {
        $this->config = IndecService::crossDimConfig();

        if ($this->mode === 'distribucion') {
            $rows = $this->service->crossDistribution($this->dimInd, $this->valInd, $this->dimHogar, []);
            $labels = $this->config['hogar'][$this->dimHogar]['values'] ?? [];
            $total  = array_sum(array_column((array) $rows, 'n'));

            $this->chartData = [
                'mode'   => 'distribucion',
                'labels' => array_map(fn($r) => $labels[$r->dim_val] ?? "Código {$r->dim_val}", $rows),
                'values' => array_map(fn($r) => (int)$r->n, $rows),
                'pct'    => array_map(fn($r) => $total > 0 ? round($r->n / $total * 100, 1) : 0, $rows),
            ];
        } else {
            if (!$this->valHog) {
                $this->chartData = ['mode' => 'evolucion', 'labels' => [], 'pct' => []];
                $this->dispatch('cross-chart-ready', data: $this->chartData);
                return;
            }
            $rows = $this->service->crossTimeSeries($this->dimInd, $this->valInd, $this->dimHogar, $this->valHog, []);
            $this->chartData = [
                'mode'   => 'evolucion',
                'labels' => array_map(fn($r) => "T{$r->TRIMESTRE} {$r->ANO4}", $rows),
                'pct'    => array_map(fn($r) => $r->pct, $rows),
            ];
        }

        $this->dispatch('cross-chart-ready', data: $this->chartData);
    }

    public function indLabel(): string
    {
        $dim = $this->config['individual'][$this->dimInd] ?? [];
        $val = $dim['values'][$this->valInd] ?? "Código {$this->valInd}";
        return "{$dim['label']}: {$val}";
    }

    public function hogLabel(): string
    {
        return $this->config['hogar'][$this->dimHogar]['label'] ?? $this->dimHogar;
    }

    public function render()
    {
        return view('livewire.cross-explorer')
            ->layout('layouts.app');
    }
}
