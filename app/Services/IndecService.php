<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class IndecService
{
    private const MIN_ANO4 = 2016;

    private const AGLOMERADO_NAMES = [
        2  => 'Gran La Plata',
        3  => 'Bahía Blanca - Cerri',
        4  => 'Gran Rosario',
        5  => 'Gran Santa Fe',
        6  => 'Gran Paraná',
        7  => 'Posadas',
        8  => 'Gran Resistencia',
        9  => 'Comodoro Rivadavia - Rada Tilly',
        10 => 'Gran Mendoza',
        12 => 'Corrientes',
        13 => 'Gran Córdoba',
        14 => 'Concordia',
        15 => 'Formosa',
        17 => 'Neuquén - Plottier',
        18 => 'Santiago del Estero - La Banda',
        19 => 'Jujuy - Palpalá',
        20 => 'Río Gallegos',
        22 => 'Gran Catamarca',
        23 => 'Gran Salta',
        25 => 'La Rioja',
        26 => 'Gran San Luis',
        27 => 'Gran San Juan',
        29 => 'Gran Tucumán - Tafí Viejo',
        30 => 'Santa Rosa - Toay',
        31 => 'Ushuaia - Río Grande',
        32 => 'Ciudad Autónoma de Buenos Aires',
        33 => 'Partidos del Gran Buenos Aires',
        34 => 'Mar del Plata',
        36 => 'Río Cuarto',
        38 => 'San Nicolás - Villa Constitución',
        91 => 'Rawson - Trelew',
        93 => 'Viedma - Carmen de Patagones',
    ];

    private function local(): \Illuminate\Database\Connection
    {
        return DB::connection('sqlite');
    }

    private function buildLocalWhere(array $filters): array
    {
        $where = ['ano4 >= ' . self::MIN_ANO4];
        $bindings = [];

        if (!empty($filters['anos'])) {
            $ph = implode(',', array_fill(0, count($filters['anos']), '?'));
            $where[] = "ano4 IN ({$ph})";
            $bindings = array_merge($bindings, $filters['anos']);
        }
        if (!empty($filters['trimestres'])) {
            $ph = implode(',', array_fill(0, count($filters['trimestres']), '?'));
            $where[] = "trimestre IN ({$ph})";
            $bindings = array_merge($bindings, $filters['trimestres']);
        }
        if (!empty($filters['regiones'])) {
            $ph = implode(',', array_fill(0, count($filters['regiones']), '?'));
            $where[] = "region IN ({$ph})";
            $bindings = array_merge($bindings, $filters['regiones']);
        }
        if (!empty($filters['aglomerados'])) {
            $ph = implode(',', array_fill(0, count($filters['aglomerados']), '?'));
            $where[] = "aglomerado IN ({$ph})";
            $bindings = array_merge($bindings, $filters['aglomerados']);
        }

        $sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        return [$sql, $bindings];
    }

    public function isComputed(): bool
    {
        return $this->local()->table('eph_meta')->where('key', 'last_computed')->exists()
            && $this->local()->table('eph_individual_period')->exists();
    }

    public function lastComputed(): ?string
    {
        $row = $this->local()->table('eph_meta')->where('key', 'last_computed')->first();
        return $row?->value;
    }

    public function availableYearsAndQuarters(): array
    {
        return $this->local()
            ->select('SELECT DISTINCT ano4 as ano4, trimestre FROM eph_individual_period WHERE ano4 >= ' . self::MIN_ANO4 . ' ORDER BY ano4 DESC, trimestre DESC')
            ?: [];
    }

    public function availableAglomerados(array $regionIds = []): array
    {
        $query = $this->local()
            ->table('eph_individual_period')
            ->selectRaw('DISTINCT aglomerado, region')
            ->where('ano4', '>=', self::MIN_ANO4);

        if (!empty($regionIds)) {
            $query->whereIn('region', $regionIds);
        }

        $rows = $query->orderBy('region')->orderBy('aglomerado')->get();

        return $rows->map(fn($r) => [
            'id'     => $r->aglomerado,
            'region' => $r->region,
            'name'   => self::AGLOMERADO_NAMES[$r->aglomerado] ?? "Aglomerado {$r->aglomerado}",
        ])->all();
    }

    public function availableRegions(): array
    {
        return [
            1 => 'Gran Buenos Aires',
            40 => 'NOA',
            41 => 'NEA',
            42 => 'Cuyo',
            43 => 'Pampeana',
            44 => 'Patagónica',
        ];
    }

    // ── INDIVIDUAL ────────────────────────────────────────────────────────────

    public function individualEmploymentSeries(array $filters): array
    {
        [$where, $bindings] = $this->buildLocalWhere($filters);

        return $this->local()->select("
            SELECT ano4 AS ANO4, trimestre AS TRIMESTRE,
                ROUND(SUM(CAST(total_personas AS REAL) * tasa_actividad / 100) / NULLIF(SUM(total_personas),0) * 100, 2) AS tasa_actividad,
                ROUND(SUM(CAST(total_personas AS REAL) * tasa_empleo / 100) / NULLIF(SUM(total_personas),0) * 100, 2) AS tasa_empleo,
                ROUND(CAST(SUM(desocupados) AS REAL) / NULLIF(SUM(ocupados)+SUM(desocupados),0) * 100, 2) AS tasa_desocupacion,
                ROUND(SUM(CAST(total_personas AS REAL) * COALESCE(tasa_subocupacion,0) / 100) / NULLIF(SUM(total_personas),0) * 100, 2) AS tasa_subocupacion
            FROM eph_individual_period {$where}
            GROUP BY ano4, trimestre ORDER BY ano4, trimestre
        ", $bindings);
    }

    public function individualIncomeSeries(array $filters): array
    {
        [$where, $bindings] = $this->buildLocalWhere($filters);

        return $this->local()->select("
            SELECT ano4 AS ANO4, trimestre AS TRIMESTRE,
                ROUND(SUM(CAST(ocupados AS REAL) * COALESCE(ingreso_p47t_prom,0)) / NULLIF(SUM(CASE WHEN ingreso_p47t_prom IS NOT NULL THEN ocupados ELSE 0 END),0), 0) AS ingreso_total_prom,
                ROUND(SUM(CAST(ocupados AS REAL) * COALESCE(ingreso_p21_prom,0)) / NULLIF(SUM(CASE WHEN ingreso_p21_prom IS NOT NULL THEN ocupados ELSE 0 END),0), 0) AS ingreso_ocupacion_prom
            FROM eph_individual_period {$where}
            GROUP BY ano4, trimestre ORDER BY ano4, trimestre
        ", $bindings);
    }

    public function individualByEducation(array $filters): array
    {
        [$where, $bindings] = $this->buildLocalWhere($filters);
        $and = $where ? $where . ' AND nivel_ed > 0' : 'WHERE nivel_ed > 0';

        return $this->local()->select("
            SELECT nivel_ed AS NIVEL_ED, SUM(total) as total
            FROM eph_individual_edu {$and}
            GROUP BY nivel_ed ORDER BY nivel_ed
        ", $bindings);
    }

    public function individualByGender(array $filters): array
    {
        [$where, $bindings] = $this->buildLocalWhere($filters);

        return $this->local()->select("
            SELECT ch04 AS CH04, SUM(total) as total
            FROM eph_individual_gender {$where}
            GROUP BY ch04
        ", $bindings);
    }

    public function individualByCategory(array $filters): array
    {
        [$where, $bindings] = $this->buildLocalWhere($filters);
        $and = $where ? $where . ' AND cat_ocup > 0' : 'WHERE cat_ocup > 0';

        return $this->local()->select("
            SELECT cat_ocup AS CAT_OCUP, SUM(total) as total
            FROM eph_individual_cat_ocup {$and}
            GROUP BY cat_ocup ORDER BY cat_ocup
        ", $bindings);
    }

    public function individualSummary(array $filters): array
    {
        [$where, $bindings] = $this->buildLocalWhere($filters);

        $row = $this->local()->selectOne("
            SELECT
                SUM(total_personas) as total_personas,
                SUM(ocupados) as ocupados,
                SUM(desocupados) as desocupados,
                SUM(inactivos) as inactivos,
                ROUND(SUM(CAST(total_personas AS REAL) * COALESCE(edad_promedio,0)) / NULLIF(SUM(CASE WHEN edad_promedio IS NOT NULL THEN total_personas ELSE 0 END),0), 1) as edad_promedio,
                ROUND(SUM(CAST(ocupados AS REAL) * COALESCE(ingreso_p47t_prom,0)) / NULLIF(SUM(CASE WHEN ingreso_p47t_prom IS NOT NULL THEN ocupados ELSE 0 END),0), 0) as ingreso_promedio
            FROM eph_individual_period {$where}
        ", $bindings);

        return $row ? (array)$row : [];
    }

    public function individualInformalidadSeries(array $filters): array
    {
        [$where, $bindings] = $this->buildLocalWhere($filters);

        return $this->local()->select("
            SELECT ano4 AS ANO4, trimestre AS TRIMESTRE,
                SUM(total_asalariados) AS total_asalariados,
                SUM(no_registrados) AS no_registrados,
                ROUND(CAST(SUM(no_registrados) AS REAL) / NULLIF(SUM(total_asalariados),0) * 100, 2) AS pct_informalidad
            FROM eph_individual_informalidad {$where}
            GROUP BY ano4, trimestre ORDER BY ano4, trimestre
        ", $bindings);
    }

    public function individualIntensidadSeries(array $filters): array
    {
        [$where, $bindings] = $this->buildLocalWhere($filters);

        return $this->local()->select("
            SELECT ano4 AS ANO4, trimestre AS TRIMESTRE,
                SUM(sobreocupados)        AS sobreocupados,
                SUM(subocu_demandante)    AS subocu_demandante,
                SUM(subocu_no_demandante) AS subocu_no_demandante,
                SUM(ocupados_pond)        AS ocupados_pond,
                ROUND(CAST(SUM(sobreocupados)        AS REAL) / NULLIF(SUM(ocupados_pond),0) * 100, 2) AS pct_sobreocupados,
                ROUND(CAST(SUM(subocu_demandante)    AS REAL) / NULLIF(SUM(ocupados_pond),0) * 100, 2) AS pct_subocu_demandante,
                ROUND(CAST(SUM(subocu_no_demandante) AS REAL) / NULLIF(SUM(ocupados_pond),0) * 100, 2) AS pct_subocu_no_demandante
            FROM eph_individual_intensidad {$where}
            GROUP BY ano4, trimestre ORDER BY ano4, trimestre
        ", $bindings);
    }

    public function individualBySalud(array $filters): array
    {
        [$where, $bindings] = $this->buildLocalWhere($filters);
        $and = $where ? $where . ' AND ch08 > 0' : 'WHERE ch08 > 0';

        return $this->local()->select("
            SELECT ch08 AS CH08, SUM(total) as total
            FROM eph_individual_salud {$and}
            GROUP BY ch08 ORDER BY ch08
        ", $bindings);
    }

    public function individualIngresoGeneroBySeries(array $filters): array
    {
        [$where, $bindings] = $this->buildLocalWhere($filters);

        return $this->local()->select("
            SELECT ano4 AS ANO4, trimestre AS TRIMESTRE, ch04 AS CH04,
                ROUND(SUM(CAST(total AS REAL) * COALESCE(ingreso_prom,0)) / NULLIF(SUM(CASE WHEN ingreso_prom IS NOT NULL THEN total ELSE 0 END),0), 0) AS ingreso_prom
            FROM eph_individual_ingreso_genero {$where}
            GROUP BY ano4, trimestre, ch04 ORDER BY ano4, trimestre, ch04
        ", $bindings);
    }

    public function individualByDecindr(array $filters): array
    {
        [$where, $bindings] = $this->buildLocalWhere($filters);
        $and = $where ? $where . ' AND decindr > 0' : 'WHERE decindr > 0';

        return $this->local()->select("
            SELECT decindr AS DECINDR, SUM(total) as total
            FROM eph_individual_decindr {$and}
            GROUP BY decindr ORDER BY decindr
        ", $bindings);
    }

    public function individualByCatInac(array $filters): array
    {
        [$where, $bindings] = $this->buildLocalWhere($filters);
        $and = $where ? $where . ' AND cat_inac > 0' : 'WHERE cat_inac > 0';

        return $this->local()->select("
            SELECT cat_inac AS CAT_INAC, SUM(total) as total
            FROM eph_individual_cat_inac {$and}
            GROUP BY cat_inac ORDER BY cat_inac
        ", $bindings);
    }

    // ── HOGAR ─────────────────────────────────────────────────────────────────

    public function hogarIncomeSeries(array $filters): array
    {
        [$where, $bindings] = $this->buildLocalWhere($filters);

        return $this->local()->select("
            SELECT ano4 AS ANO4, trimestre AS TRIMESTRE,
                ROUND(SUM(CAST(total_hogares AS REAL) * COALESCE(itf_promedio,0)) / NULLIF(SUM(CASE WHEN itf_promedio IS NOT NULL THEN total_hogares ELSE 0 END),0), 0) AS itf_promedio,
                ROUND(SUM(CAST(total_hogares AS REAL) * COALESCE(ipcf_promedio,0)) / NULLIF(SUM(CASE WHEN ipcf_promedio IS NOT NULL THEN total_hogares ELSE 0 END),0), 0) AS ipcf_promedio,
                ROUND(SUM(CAST(total_hogares AS REAL) * COALESCE(miembros_promedio,0)) / NULLIF(SUM(total_hogares),0), 2) AS miembros_promedio
            FROM eph_hogar_period {$where}
            GROUP BY ano4, trimestre ORDER BY ano4, trimestre
        ", $bindings);
    }

    public function hogarByDecil(array $filters): array
    {
        [$where, $bindings] = $this->buildLocalWhere($filters);
        $and = $where ? $where . ' AND decifr > 0' : 'WHERE decifr > 0';

        return $this->local()->select("
            SELECT decifr AS DECIFR, SUM(total) as total
            FROM eph_hogar_decil {$and}
            GROUP BY decifr ORDER BY decifr
        ", $bindings);
    }

    public function hogarByVivienda(array $filters): array
    {
        [$where, $bindings] = $this->buildLocalWhere($filters);
        $and = $where ? $where . ' AND iv1 > 0' : 'WHERE iv1 > 0';

        return $this->local()->select("
            SELECT iv1 AS IV1, SUM(total) as total
            FROM eph_hogar_vivienda {$and}
            GROUP BY iv1 ORDER BY iv1
        ", $bindings);
    }

    public function hogarByTenencia(array $filters): array
    {
        [$where, $bindings] = $this->buildLocalWhere($filters);
        $and = $where ? $where . ' AND ii7 > 0' : 'WHERE ii7 > 0';

        return $this->local()->select("
            SELECT ii7 AS II7, SUM(total) as total
            FROM eph_hogar_tenencia {$and}
            GROUP BY ii7 ORDER BY ii7
        ", $bindings);
    }

    public function hogarSummary(array $filters): array
    {
        [$where, $bindings] = $this->buildLocalWhere($filters);

        $row = $this->local()->selectOne("
            SELECT
                SUM(total_hogares) as total_hogares,
                ROUND(SUM(CAST(total_hogares AS REAL) * COALESCE(itf_promedio,0)) / NULLIF(SUM(CASE WHEN itf_promedio IS NOT NULL THEN total_hogares ELSE 0 END),0), 0) as itf_promedio,
                ROUND(SUM(CAST(total_hogares AS REAL) * COALESCE(ipcf_promedio,0)) / NULLIF(SUM(CASE WHEN ipcf_promedio IS NOT NULL THEN total_hogares ELSE 0 END),0), 0) as ipcf_promedio,
                ROUND(SUM(CAST(total_hogares AS REAL) * COALESCE(miembros_promedio,0)) / NULLIF(SUM(total_hogares),0), 2) as miembros_promedio,
                ROUND(SUM(CAST(total_hogares AS REAL) * COALESCE(pct_hacinamiento,0)) / NULLIF(SUM(total_hogares),0), 2) as pct_hacinamiento
            FROM eph_hogar_period {$where}
        ", $bindings);

        return $row ? (array)$row : [];
    }

    // ── Cross explorer ──────────────────────────────────────────────────────

    public static function crossDimConfig(): array
    {
        return [
            'individual' => [
                'estado'   => ['label' => 'Estado laboral',         'values' => [1 => 'Ocupado', 2 => 'Desocupado', 3 => 'Inactivo', 4 => 'Menor de 10 años']],
                'cat_inac' => ['label' => 'Tipo de inactividad',    'values' => [1 => 'Jubilado/pensionado', 2 => 'Rentista', 3 => 'Estudiante', 4 => 'Ama/o de casa', 5 => 'Discapacitado', 6 => 'Otra inactividad']],
                'cat_ocup' => ['label' => 'Categoría ocupacional',  'values' => [1 => 'Patrón/empleador', 2 => 'Cuenta propia', 3 => 'Asalariado', 4 => 'Familiar sin remuneración']],
                'ch04'     => ['label' => 'Sexo',                   'values' => [1 => 'Varón', 2 => 'Mujer']],
                'nivel_ed' => ['label' => 'Nivel educativo',        'values' => [1 => 'Primaria incompleta', 2 => 'Primaria completa', 3 => 'Secundaria incompleta', 4 => 'Secundaria completa', 5 => 'Superior incompleta', 6 => 'Superior completa', 7 => 'Sin instrucción']],
                'ch08'     => ['label' => 'Cobertura de salud',     'values' => [1 => 'Obra social / PAMI', 2 => 'Mutual / Prepaga', 3 => 'Pago en el momento', 4 => 'No paga (pública)', 9 => 'Sin cobertura']],
                'pp07h'    => ['label' => 'Registro laboral',       'values' => [1 => 'Registrado (con aportes)', 2 => 'No registrado (informal)']],
            ],
            'hogar' => [
                'iv1'         => ['label' => 'Tipo de vivienda',        'values' => [1 => 'Casa', 2 => 'Departamento', 3 => 'Inquilinato/conventillo', 4 => 'Hotel/pensión', 5 => 'Local no construido p/vivienda', 6 => 'Vivienda móvil/irregular']],
                'iv3'         => ['label' => 'Material del piso',       'values' => [1 => 'Cerámica/mármol/madera', 2 => 'Cemento/ladrillo', 3 => 'Tierra/ladrillo suelto']],
                'ii7'         => ['label' => 'Tenencia de vivienda',    'values' => [1 => 'Propietario (vivienda y terreno)', 2 => 'Propietario (solo vivienda)', 3 => 'Inquilino/arrendatario', 4 => 'Ocupante (relación laboral)', 5 => 'Ocupante gratuito', 6 => 'Otra situación']],
                'hacinamiento'=> ['label' => 'Hacinamiento crítico',    'values' => [0 => 'Sin hacinamiento', 1 => 'Con hacinamiento crítico']],
                'decifr'      => ['label' => 'Decil de ingreso familiar','values' => array_combine(range(1,10), array_map(fn($i) => "Decil $i", range(1,10)))],
                'v17'         => ['label' => 'Estrategias del hogar (vendió bienes)', 'values' => [1 => 'Sí vendió bienes', 2 => 'No vendió bienes', 9 => 'NS/NR']],
            ],
        ];
    }

    public function crossDistribution(string $dimInd, int $valInd, string $dimHogar, array $filters): array
    {
        if (!$this->crossValidate($dimInd, $dimHogar)) return [];
        [$where, $bindings] = $this->buildLocalWhere($filters);
        $validHog = implode(',', $this->crossValidKeys('hogar', $dimHogar));

        if ($valInd === 0) {
            $and = str_replace('WHERE ', 'WHERE dim_key = ? AND ', $where) . " AND dim_val IN ({$validHog})";
            array_unshift($bindings, $dimHogar);
            return $this->local()->select("
                SELECT dim_val, SUM(total) AS n
                FROM eph_cross_hogar {$and}
                GROUP BY dim_val ORDER BY dim_val
            ", $bindings);
        }

        $base = $where ?: 'WHERE 1=1';
        $and = "{$base} AND {$dimInd} = ? AND {$dimInd} > 0 AND {$dimHogar} IN ({$validHog})";
        $bindings[] = $valInd;

        return $this->local()->select("
            SELECT {$dimHogar} AS dim_val, SUM(total) AS n
            FROM eph_cross {$and}
            GROUP BY {$dimHogar} ORDER BY {$dimHogar}
        ", $bindings);
    }

    public function crossTimeSeries(string $dimInd, int $valInd, string $dimHogar, int $valHog, array $filters): array
    {
        if (!$this->crossValidate($dimInd, $dimHogar)) return [];
        [$where, $bindings] = $this->buildLocalWhere($filters);
        $validHog = implode(',', $this->crossValidKeys('hogar', $dimHogar));
        $base = $where ?: 'WHERE 1=1';
        if ($valInd === 0) {
            $and = str_replace('WHERE ', 'WHERE dim_key = ? AND ', $where);
            array_unshift($bindings, $dimHogar);
            return $this->local()->select("
                SELECT ano4 AS ANO4, trimestre AS TRIMESTRE,
                    SUM(CASE WHEN dim_val = ? THEN total ELSE 0 END) AS match_n,
                    SUM(total) AS total_n,
                    ROUND(CAST(SUM(CASE WHEN dim_val = ? THEN total ELSE 0 END) AS REAL) / NULLIF(SUM(total),0) * 100, 1) AS pct
                FROM eph_cross_hogar {$and}
                GROUP BY ano4, trimestre ORDER BY ano4, trimestre
            ", array_merge([$valHog, $valHog], $bindings));
        }
        $and = "{$base} AND {$dimInd} = ? AND {$dimInd} > 0 AND {$dimHogar} IN ({$validHog})";
        $bindings[] = $valInd;

        return $this->local()->select("
            SELECT ano4 AS ANO4, trimestre AS TRIMESTRE,
                SUM(CASE WHEN {$dimHogar} = ? THEN total ELSE 0 END) AS match_n,
                SUM(total) AS total_n,
                ROUND(CAST(SUM(CASE WHEN {$dimHogar} = ? THEN total ELSE 0 END) AS REAL) / NULLIF(SUM(total),0) * 100, 1) AS pct
            FROM eph_cross {$and}
            GROUP BY ano4, trimestre ORDER BY ano4, trimestre
        ", array_merge([$valHog, $valHog], $bindings));
    }

    private function crossValidate(string $dimInd, string $dimHogar): bool
    {
        $config = self::crossDimConfig();
        return isset($config['individual'][$dimInd]) && isset($config['hogar'][$dimHogar]);
    }

    private function crossValidKeys(string $group, string $dim): array
    {
        return array_keys(self::crossDimConfig()[$group][$dim]['values'] ?? []);
    }
}
