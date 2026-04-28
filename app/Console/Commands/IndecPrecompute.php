<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class IndecPrecompute extends Command
{
    protected $signature = 'indec:precompute {--fresh : Truncate before recomputing}';
    protected $description = 'Pre-compute EPH statistics from INDEC MySQL into local SQLite cache';

    private \Illuminate\Database\Connection $indec;
    private \Illuminate\Database\Connection $local;

    public function handle(): int
    {
        $this->indec = DB::connection('indec');
        $this->local = DB::connection('sqlite');

        if ($this->option('fresh')) {
            $this->truncateAll();
            $this->info('Tablas vaciadas.');
        }

        $indTables = $this->getTables('individual');
        $hogTables = $this->getTables('hogar');

        $this->info('Procesando ' . count($indTables) . ' tablas de individuos...');
        $bar = $this->output->createProgressBar(count($indTables));
        $bar->start();

        foreach ($indTables as $table) {
            $this->processIndividualTable($table);
            $bar->advance();
        }
        $bar->finish();
        $this->newLine();

        $this->info('Procesando ' . count($hogTables) . ' tablas de hogares...');
        $bar = $this->output->createProgressBar(count($hogTables));
        $bar->start();

        foreach ($hogTables as $table) {
            $this->processHogarTable($table);
            $bar->advance();
        }
        $bar->finish();
        $this->newLine();

        $this->info('Procesando cruces individuo-hogar...');
        $bar = $this->output->createProgressBar(count($indTables));
        $bar->start();
        foreach ($indTables as $indTable) {
            $hogTable = str_replace('individual', 'hogar', $indTable);
            if (in_array($hogTable, $hogTables)) {
                $this->processCrossTable($indTable, $hogTable);
            }
            $bar->advance();
        }
        $bar->finish();
        $this->newLine();

        $this->info('Repoblando eph_cross_hogar...');
        $this->local->table('eph_cross_hogar')->truncate();
        foreach (['ii7', 'iv1', 'iv3', 'hacinamiento', 'decifr', 'v17'] as $dim) {
            $this->local->statement("
                INSERT OR IGNORE INTO eph_cross_hogar (ano4, trimestre, region, aglomerado, dim_key, dim_val, total)
                SELECT ano4, trimestre, region, aglomerado, '{$dim}', {$dim}, SUM(total)
                FROM eph_cross WHERE {$dim} > 0
                GROUP BY ano4, trimestre, region, aglomerado, {$dim}
            ");
        }

        $this->local->table('eph_meta')->updateOrInsert(
            ['key' => 'last_computed'],
            ['value' => now()->toIso8601String()]
        );

        $this->info('Pre-cómputo completado.');
        return Command::SUCCESS;
    }

    private function getTables(string $type): array
    {
        return array_column(
            $this->indec->select(
                "SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA='indec' AND TABLE_NAME LIKE ? ORDER BY TABLE_NAME",
                ["usu_{$type}_t%"]
            ),
            'TABLE_NAME'
        );
    }

    private function truncateAll(): void
    {
        foreach ([
            'eph_individual_period', 'eph_individual_edu', 'eph_individual_cat_ocup',
            'eph_individual_gender', 'eph_individual_informalidad', 'eph_individual_intensidad',
            'eph_individual_salud', 'eph_individual_ingreso_genero', 'eph_individual_decindr',
            'eph_individual_cat_inac',
            'eph_hogar_period', 'eph_hogar_decil', 'eph_hogar_vivienda', 'eph_hogar_tenencia',
            'eph_cross', 'eph_cross_hogar',
        ] as $t) {
            $this->local->table($t)->truncate();
        }
    }

    private function tableCols(string $table): array
    {
        static $cache = [];
        if (!isset($cache[$table])) {
            $cache[$table] = array_column(
                $this->indec->select("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='indec' AND TABLE_NAME=?", [$table]),
                'COLUMN_NAME'
            );
        }
        return $cache[$table];
    }

    private function col(string $table, string $col, string $fallback = 'NULL'): string
    {
        return in_array($col, $this->tableCols($table)) ? "`{$col}`" : $fallback;
    }

    private function processIndividualTable(string $table): void
    {
        $hasCh06 = in_array('CH06', $this->tableCols($table));
        $hasIntens = in_array('INTENSI', $this->tableCols($table));
        $hasPondii = in_array('PONDII', $this->tableCols($table));
        $hasPondiio = in_array('PONDIIO', $this->tableCols($table));

        $ch06Filter = $hasCh06 ? 'AND CH06 >= 10' : '';
        $ch06Total = $hasCh06 ? 'SUM(CASE WHEN CH06 >= 10 THEN PONDERA ELSE 0 END)' : 'SUM(PONDERA)';
        $ch06Act = $hasCh06 ? 'SUM(CASE WHEN ESTADO IN (1,2) AND CH06 >= 10 THEN PONDERA ELSE 0 END)' : 'SUM(CASE WHEN ESTADO IN (1,2) THEN PONDERA ELSE 0 END)';
        $ch06Ocu = $hasCh06 ? 'SUM(CASE WHEN ESTADO = 1 AND CH06 >= 10 THEN PONDERA ELSE 0 END)' : 'SUM(CASE WHEN ESTADO = 1 THEN PONDERA ELSE 0 END)';
        $subocuExpr = $hasIntens ? 'SUM(CASE WHEN INTENSI IN (1,2) AND ESTADO = 1 THEN PONDERA ELSE 0 END)' : '0';
        $p47tExpr = $hasPondii
            ? 'SUM(CASE WHEN ESTADO=1 AND P47T>0 THEN P47T*PONDII ELSE 0 END) / NULLIF(SUM(CASE WHEN ESTADO=1 AND P47T>0 THEN PONDII ELSE 0 END), 0)'
            : 'AVG(CASE WHEN ESTADO=1 AND P47T>0 THEN P47T END)';
        $p21Expr = $hasPondiio
            ? 'SUM(CASE WHEN ESTADO=1 AND P21>0 THEN P21*PONDIIO ELSE 0 END) / NULLIF(SUM(CASE WHEN ESTADO=1 AND P21>0 THEN PONDIIO ELSE 0 END), 0)'
            : 'AVG(CASE WHEN ESTADO=1 AND P21>0 THEN P21 END)';
        $edadExpr = $hasCh06 ? 'AVG(CH06)' : 'NULL';

        // Period stats per region
        $periodRows = $this->indec->select("
            SELECT ANO4, TRIMESTRE, REGION, AGLOMERADO,
                COUNT(DISTINCT CONCAT(CODUSU, COMPONENTE)) total_personas,
                COUNT(DISTINCT CASE WHEN ESTADO=1 THEN CONCAT(CODUSU, COMPONENTE) END) ocupados,
                COUNT(DISTINCT CASE WHEN ESTADO=2 THEN CONCAT(CODUSU, COMPONENTE) END) desocupados,
                COUNT(DISTINCT CASE WHEN ESTADO=3 THEN CONCAT(CODUSU, COMPONENTE) END) inactivos,
                ROUND({$ch06Act} / NULLIF({$ch06Total},0)*100, 2) tasa_actividad,
                ROUND({$ch06Ocu} / NULLIF({$ch06Total},0)*100, 2) tasa_empleo,
                ROUND(SUM(CASE WHEN ESTADO=2 THEN PONDERA ELSE 0 END) / NULLIF(SUM(CASE WHEN ESTADO IN(1,2) THEN PONDERA ELSE 0 END),0)*100, 2) tasa_desocupacion,
                ROUND({$subocuExpr} / NULLIF(SUM(CASE WHEN ESTADO=1 THEN PONDERA ELSE 0 END),0)*100, 2) tasa_subocupacion,
                ROUND({$p47tExpr}, 0) ingreso_p47t_prom,
                ROUND({$p21Expr}, 0) ingreso_p21_prom,
                ROUND({$edadExpr}, 1) edad_promedio,
                SUM(CASE WHEN CH04=1 THEN PONDERA ELSE 0 END) varones,
                SUM(CASE WHEN CH04=2 THEN PONDERA ELSE 0 END) mujeres
            FROM `indec`.`{$table}`
            WHERE REGION IS NOT NULL AND AGLOMERADO IS NOT NULL
            GROUP BY ANO4, TRIMESTRE, REGION, AGLOMERADO
        ");

        foreach ($periodRows as $row) {
            $this->local->table('eph_individual_period')->updateOrInsert(
                ['ano4' => $row->ANO4, 'trimestre' => $row->TRIMESTRE, 'region' => $row->REGION, 'aglomerado' => $row->AGLOMERADO],
                (array)$row + ['ano4' => $row->ANO4, 'trimestre' => $row->TRIMESTRE, 'region' => $row->REGION, 'aglomerado' => $row->AGLOMERADO]
            );
        }

        // Education distribution per region/aglomerado
        if (in_array('NIVEL_ED', $this->tableCols($table))) {
            $eduRows = $this->indec->select("
                SELECT ANO4, TRIMESTRE, REGION, AGLOMERADO, NIVEL_ED, SUM(PONDERA) total
                FROM `indec`.`{$table}`
                WHERE NIVEL_ED IS NOT NULL AND NIVEL_ED > 0 AND REGION IS NOT NULL AND AGLOMERADO IS NOT NULL
                GROUP BY ANO4, TRIMESTRE, REGION, AGLOMERADO, NIVEL_ED
            ");
            foreach ($eduRows as $row) {
                $this->local->table('eph_individual_edu')->updateOrInsert(
                    ['ano4' => $row->ANO4, 'trimestre' => $row->TRIMESTRE, 'region' => $row->REGION, 'aglomerado' => $row->AGLOMERADO, 'nivel_ed' => $row->NIVEL_ED],
                    ['total' => $row->total]
                );
            }
        }

        // Cat ocupacional per region/aglomerado
        if (in_array('CAT_OCUP', $this->tableCols($table))) {
            $catRows = $this->indec->select("
                SELECT ANO4, TRIMESTRE, REGION, AGLOMERADO, CAT_OCUP, SUM(PONDERA) total
                FROM `indec`.`{$table}`
                WHERE ESTADO=1 AND CAT_OCUP IS NOT NULL AND CAT_OCUP > 0 AND REGION IS NOT NULL AND AGLOMERADO IS NOT NULL
                GROUP BY ANO4, TRIMESTRE, REGION, AGLOMERADO, CAT_OCUP
            ");
            foreach ($catRows as $row) {
                $this->local->table('eph_individual_cat_ocup')->updateOrInsert(
                    ['ano4' => $row->ANO4, 'trimestre' => $row->TRIMESTRE, 'region' => $row->REGION, 'aglomerado' => $row->AGLOMERADO, 'cat_ocup' => $row->CAT_OCUP],
                    ['total' => $row->total]
                );
            }
        }

        // Gender per region/aglomerado
        $genRows = $this->indec->select("
            SELECT ANO4, TRIMESTRE, REGION, AGLOMERADO, CH04, SUM(PONDERA) total
            FROM `indec`.`{$table}`
            WHERE CH04 IN (1,2) AND REGION IS NOT NULL AND AGLOMERADO IS NOT NULL
            GROUP BY ANO4, TRIMESTRE, REGION, AGLOMERADO, CH04
        ");
        foreach ($genRows as $row) {
            $this->local->table('eph_individual_gender')->updateOrInsert(
                ['ano4' => $row->ANO4, 'trimestre' => $row->TRIMESTRE, 'region' => $row->REGION, 'aglomerado' => $row->AGLOMERADO, 'ch04' => $row->CH04],
                ['total' => $row->total]
            );
        }

        // Informalidad (asalariados sin descuento jubilatorio, PP07H=2)
        if (in_array('PP07H', $this->tableCols($table))) {
            $infRows = $this->indec->select("
                SELECT ANO4, TRIMESTRE, REGION, AGLOMERADO,
                    SUM(CASE WHEN ESTADO=1 AND CAT_OCUP=3 THEN PONDERA ELSE 0 END) total_asalariados,
                    SUM(CASE WHEN ESTADO=1 AND CAT_OCUP=3 AND PP07H=2 THEN PONDERA ELSE 0 END) no_registrados
                FROM `indec`.`{$table}`
                WHERE REGION IS NOT NULL AND AGLOMERADO IS NOT NULL
                GROUP BY ANO4, TRIMESTRE, REGION, AGLOMERADO
            ");
            foreach ($infRows as $row) {
                $this->local->table('eph_individual_informalidad')->updateOrInsert(
                    ['ano4' => $row->ANO4, 'trimestre' => $row->TRIMESTRE, 'region' => $row->REGION, 'aglomerado' => $row->AGLOMERADO],
                    ['total_asalariados' => $row->total_asalariados, 'no_registrados' => $row->no_registrados]
                );
            }
        }

        // Intensidad laboral (sobreocupación + subocupación demandante/no demandante)
        if ($hasIntens) {
            $intRows = $this->indec->select("
                SELECT ANO4, TRIMESTRE, REGION, AGLOMERADO,
                    SUM(CASE WHEN ESTADO=1 AND INTENSI=3 THEN PONDERA ELSE 0 END) sobreocupados,
                    SUM(CASE WHEN ESTADO=1 AND INTENSI=1 THEN PONDERA ELSE 0 END) subocu_demandante,
                    SUM(CASE WHEN ESTADO=1 AND INTENSI=2 THEN PONDERA ELSE 0 END) subocu_no_demandante,
                    SUM(CASE WHEN ESTADO=1 THEN PONDERA ELSE 0 END) ocupados_pond
                FROM `indec`.`{$table}`
                WHERE REGION IS NOT NULL AND AGLOMERADO IS NOT NULL
                GROUP BY ANO4, TRIMESTRE, REGION, AGLOMERADO
            ");
            foreach ($intRows as $row) {
                $this->local->table('eph_individual_intensidad')->updateOrInsert(
                    ['ano4' => $row->ANO4, 'trimestre' => $row->TRIMESTRE, 'region' => $row->REGION, 'aglomerado' => $row->AGLOMERADO],
                    ['sobreocupados' => $row->sobreocupados, 'subocu_demandante' => $row->subocu_demandante, 'subocu_no_demandante' => $row->subocu_no_demandante, 'ocupados_pond' => $row->ocupados_pond]
                );
            }
        }

        // Cobertura de salud (CH08)
        if (in_array('CH08', $this->tableCols($table))) {
            $saludRows = $this->indec->select("
                SELECT ANO4, TRIMESTRE, REGION, AGLOMERADO, CH08, SUM(PONDERA) total
                FROM `indec`.`{$table}`
                WHERE CH08 IS NOT NULL AND CH08 > 0 AND REGION IS NOT NULL AND AGLOMERADO IS NOT NULL
                GROUP BY ANO4, TRIMESTRE, REGION, AGLOMERADO, CH08
            ");
            foreach ($saludRows as $row) {
                $this->local->table('eph_individual_salud')->updateOrInsert(
                    ['ano4' => $row->ANO4, 'trimestre' => $row->TRIMESTRE, 'region' => $row->REGION, 'aglomerado' => $row->AGLOMERADO, 'ch08' => $row->CH08],
                    ['total' => $row->total]
                );
            }
        }

        // Ingreso promedio por género (brecha)
        if ($hasPondiio && in_array('CH04', $this->tableCols($table))) {
            $ingrGenRows = $this->indec->select("
                SELECT ANO4, TRIMESTRE, REGION, AGLOMERADO, CH04,
                    SUM(CASE WHEN ESTADO=1 AND P21>0 THEN P21*PONDIIO ELSE 0 END) / NULLIF(SUM(CASE WHEN ESTADO=1 AND P21>0 THEN PONDIIO ELSE 0 END),0) ingreso_prom,
                    SUM(CASE WHEN ESTADO=1 AND P21>0 THEN PONDIIO ELSE 0 END) total
                FROM `indec`.`{$table}`
                WHERE CH04 IN (1,2) AND REGION IS NOT NULL AND AGLOMERADO IS NOT NULL
                GROUP BY ANO4, TRIMESTRE, REGION, AGLOMERADO, CH04
            ");
            foreach ($ingrGenRows as $row) {
                $this->local->table('eph_individual_ingreso_genero')->updateOrInsert(
                    ['ano4' => $row->ANO4, 'trimestre' => $row->TRIMESTRE, 'region' => $row->REGION, 'aglomerado' => $row->AGLOMERADO, 'ch04' => $row->CH04],
                    ['ingreso_prom' => $row->ingreso_prom ? (int)round($row->ingreso_prom) : null, 'total' => (int)$row->total]
                );
            }
        }

        // Decil individual (DECINDR)
        if (in_array('DECINDR', $this->tableCols($table))) {
            $decindrRows = $this->indec->select("
                SELECT ANO4, TRIMESTRE, REGION, AGLOMERADO, DECINDR, SUM(PONDERA) total
                FROM `indec`.`{$table}`
                WHERE DECINDR IS NOT NULL AND DECINDR > 0 AND REGION IS NOT NULL AND AGLOMERADO IS NOT NULL
                GROUP BY ANO4, TRIMESTRE, REGION, AGLOMERADO, DECINDR
            ");
            foreach ($decindrRows as $row) {
                $this->local->table('eph_individual_decindr')->updateOrInsert(
                    ['ano4' => $row->ANO4, 'trimestre' => $row->TRIMESTRE, 'region' => $row->REGION, 'aglomerado' => $row->AGLOMERADO, 'decindr' => $row->DECINDR],
                    ['total' => $row->total]
                );
            }
        }

        // Categoría de inactividad (CAT_INAC)
        if (in_array('CAT_INAC', $this->tableCols($table))) {
            $catInacRows = $this->indec->select("
                SELECT ANO4, TRIMESTRE, REGION, AGLOMERADO, CAT_INAC, SUM(PONDERA) total
                FROM `indec`.`{$table}`
                WHERE ESTADO=3 AND CAT_INAC IS NOT NULL AND CAT_INAC > 0 AND REGION IS NOT NULL AND AGLOMERADO IS NOT NULL
                GROUP BY ANO4, TRIMESTRE, REGION, AGLOMERADO, CAT_INAC
            ");
            foreach ($catInacRows as $row) {
                $this->local->table('eph_individual_cat_inac')->updateOrInsert(
                    ['ano4' => $row->ANO4, 'trimestre' => $row->TRIMESTRE, 'region' => $row->REGION, 'aglomerado' => $row->AGLOMERADO, 'cat_inac' => $row->CAT_INAC],
                    ['total' => $row->total]
                );
            }
        }
    }

    private function processHogarTable(string $table): void
    {
        $cols = $this->tableCols($table);
        $hasPondih = in_array('PONDIH', $cols);
        $hasIv8 = in_array('IV8', $cols);
        $hasIi7 = in_array('II7', $cols);

        $hacExpr = $hasIv8
            ? 'COUNT(DISTINCT CASE WHEN IX_TOT > 0 AND IV8 > 0 AND (IX_TOT / IV8) > 3 THEN CODUSU END) / NULLIF(COUNT(DISTINCT CODUSU),0)*100'
            : 'NULL';
        $pondihExpr = $hasPondih ? 'PONDIH' : 'PONDERA';

        $periodRows = $this->indec->select("
            SELECT ANO4, TRIMESTRE, REGION, AGLOMERADO,
                COUNT(DISTINCT CODUSU) total_hogares,
                ROUND(AVG(CASE WHEN ITF > 0 THEN ITF END), 0) itf_promedio,
                ROUND(AVG(CASE WHEN IPCF > 0 THEN IPCF END), 0) ipcf_promedio,
                ROUND(AVG(IX_TOT), 2) miembros_promedio,
                ROUND({$hacExpr}, 2) pct_hacinamiento
            FROM `indec`.`{$table}`
            WHERE REGION IS NOT NULL AND AGLOMERADO IS NOT NULL
            GROUP BY ANO4, TRIMESTRE, REGION, AGLOMERADO
        ");

        foreach ($periodRows as $row) {
            $this->local->table('eph_hogar_period')->updateOrInsert(
                ['ano4' => $row->ANO4, 'trimestre' => $row->TRIMESTRE, 'region' => $row->REGION, 'aglomerado' => $row->AGLOMERADO],
                (array)$row + ['ano4' => $row->ANO4, 'trimestre' => $row->TRIMESTRE, 'region' => $row->REGION, 'aglomerado' => $row->AGLOMERADO]
            );
        }

        // Deciles
        if (in_array('DECIFR', $cols)) {
            $decRows = $this->indec->select("
                SELECT ANO4, TRIMESTRE, REGION, AGLOMERADO, DECIFR, SUM({$pondihExpr}) total
                FROM `indec`.`{$table}`
                WHERE DECIFR IS NOT NULL AND DECIFR > 0 AND REGION IS NOT NULL AND AGLOMERADO IS NOT NULL
                GROUP BY ANO4, TRIMESTRE, REGION, AGLOMERADO, DECIFR
            ");
            foreach ($decRows as $row) {
                $this->local->table('eph_hogar_decil')->updateOrInsert(
                    ['ano4' => $row->ANO4, 'trimestre' => $row->TRIMESTRE, 'region' => $row->REGION, 'aglomerado' => $row->AGLOMERADO, 'decifr' => $row->DECIFR],
                    ['total' => $row->total]
                );
            }
        }

        // Tipo vivienda
        if (in_array('IV1', $cols)) {
            $vivRows = $this->indec->select("
                SELECT ANO4, TRIMESTRE, REGION, AGLOMERADO, IV1, SUM(PONDERA) total
                FROM `indec`.`{$table}`
                WHERE IV1 IS NOT NULL AND IV1 > 0 AND REGION IS NOT NULL AND AGLOMERADO IS NOT NULL
                GROUP BY ANO4, TRIMESTRE, REGION, AGLOMERADO, IV1
            ");
            foreach ($vivRows as $row) {
                $this->local->table('eph_hogar_vivienda')->updateOrInsert(
                    ['ano4' => $row->ANO4, 'trimestre' => $row->TRIMESTRE, 'region' => $row->REGION, 'aglomerado' => $row->AGLOMERADO, 'iv1' => $row->IV1],
                    ['total' => $row->total]
                );
            }
        }

        // Tenencia
        if ($hasIi7) {
            $tenRows = $this->indec->select("
                SELECT ANO4, TRIMESTRE, REGION, AGLOMERADO, II7, SUM(PONDERA) total
                FROM `indec`.`{$table}`
                WHERE II7 IS NOT NULL AND II7 > 0 AND REGION IS NOT NULL AND AGLOMERADO IS NOT NULL
                GROUP BY ANO4, TRIMESTRE, REGION, AGLOMERADO, II7
            ");
            foreach ($tenRows as $row) {
                $this->local->table('eph_hogar_tenencia')->updateOrInsert(
                    ['ano4' => $row->ANO4, 'trimestre' => $row->TRIMESTRE, 'region' => $row->REGION, 'aglomerado' => $row->AGLOMERADO, 'ii7' => $row->II7],
                    ['total' => $row->total]
                );
            }
        }
    }

    private function processCrossTable(string $indTable, string $hogTable): void
    {
        $iCols = $this->tableCols($indTable);
        $hCols = $this->tableCols($hogTable);

        $catInac = in_array('CAT_INAC', $iCols) ? 'COALESCE(i.CAT_INAC,0)' : '0';
        $catOcup = in_array('CAT_OCUP', $iCols) ? 'COALESCE(i.CAT_OCUP,0)' : '0';
        $nivelEd = in_array('NIVEL_ED', $iCols) ? 'COALESCE(i.NIVEL_ED,0)' : '0';
        $ch08    = in_array('CH08',    $iCols) ? 'COALESCE(i.CH08,0)'    : '0';
        $pp07h   = in_array('PP07H',   $iCols) ? 'COALESCE(i.PP07H,0)'   : '0';

        $iv1  = in_array('IV1',   $hCols) ? 'COALESCE(h.IV1,0)'  : '0';
        $iv3  = in_array('IV3',   $hCols) ? 'COALESCE(h.IV3,0)'  : '0';
        $ii7  = in_array('II7',   $hCols) ? 'COALESCE(h.II7,0)'  : '0';
        $decifr = in_array('DECIFR', $hCols) ? 'COALESCE(h.DECIFR,0)' : '0';
        $v17  = in_array('V17',   $hCols) ? 'COALESCE(h.V17,0)'  : '0';

        $hacin = (in_array('IX_TOT', $hCols) && in_array('IV8', $hCols))
            ? 'CASE WHEN h.IX_TOT > 0 AND h.IV8 > 0 AND (h.IX_TOT / h.IV8) > 3 THEN 1 ELSE 0 END'
            : '0';

        $rows = $this->indec->select("
            SELECT
                i.ANO4, i.TRIMESTRE, i.REGION, i.AGLOMERADO,
                COALESCE(i.ESTADO,0)  estado,
                {$catInac}            cat_inac,
                {$catOcup}            cat_ocup,
                COALESCE(i.CH04,0)    ch04,
                {$nivelEd}            nivel_ed,
                {$ch08}               ch08,
                {$pp07h}              pp07h,
                {$iv1}                iv1,
                {$iv3}                iv3,
                {$ii7}                ii7,
                {$hacin}              hacinamiento,
                {$decifr}             decifr,
                {$v17}                v17,
                SUM(i.PONDERA)        total
            FROM `indec`.`{$indTable}` i
            JOIN `indec`.`{$hogTable}` h ON i.CODUSU = h.CODUSU AND i.NRO_HOGAR = h.NRO_HOGAR
            WHERE i.REGION IS NOT NULL AND i.AGLOMERADO IS NOT NULL
            GROUP BY i.ANO4, i.TRIMESTRE, i.REGION, i.AGLOMERADO,
                estado, cat_inac, cat_ocup, ch04, nivel_ed, ch08, pp07h,
                iv1, iv3, ii7, hacinamiento, decifr, v17
        ");

        if (empty($rows)) return;

        // Delete existing rows for this period before bulk insert
        $this->local->table('eph_cross')
            ->where('ano4', $rows[0]->ANO4)
            ->where('trimestre', $rows[0]->TRIMESTRE)
            ->delete();

        foreach (array_chunk($rows, 500) as $chunk) {
            $this->local->table('eph_cross')->insertOrIgnore(array_map(fn($r) => [
                'ano4'         => $r->ANO4,
                'trimestre'    => $r->TRIMESTRE,
                'region'       => $r->REGION,
                'aglomerado'   => $r->AGLOMERADO,
                'estado'       => $r->estado,
                'cat_inac'     => $r->cat_inac,
                'cat_ocup'     => $r->cat_ocup,
                'ch04'         => $r->ch04,
                'nivel_ed'     => $r->nivel_ed,
                'ch08'         => $r->ch08,
                'pp07h'        => $r->pp07h,
                'iv1'          => $r->iv1,
                'iv3'          => $r->iv3,
                'ii7'          => $r->ii7,
                'hacinamiento' => $r->hacinamiento,
                'decifr'       => $r->decifr,
                'v17'          => $r->v17,
                'total'        => $r->total,
            ], $chunk));
        }
    }
}
