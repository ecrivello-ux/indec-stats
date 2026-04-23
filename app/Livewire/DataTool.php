<?php

namespace App\Livewire;

use App\Models\SavedFilter;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class DataTool extends Component
{
    // CUÁNDO
    public string $selectedAno       = '';
    public string $selectedTrimestre = '';

    // DÓNDE
    public string $selectedRegion      = '';
    public string $selectedAglomerado  = '';

    // QUIÉN
    public string $selectedSexo    = '';
    public string $edadMin         = '';
    public string $edadMax         = '';
    public string $selectedEstado  = '';
    public string $selectedCatOcup = '';
    public string $selectedCatInac = '';
    public string $selectedNivelEd = '';

    // QUÉ
    public string $selectedPP07H = ''; // formalidad (descuento jubilatorio)
    public string $selectedPP04A = ''; // sector (estatal / privado / otro)
    public string $selectedINTENSI = ''; // intensidad horaria
    public string $selectedCaesDivision = ''; // rama CAES Mercosur (letra A-U)

    public int    $page      = 1;
    public int    $perPage   = 50;
    public array  $rows      = [];
    public int    $total     = 0;
    public bool   $hasSearched = false;
    public ?string $error    = null;
    public array  $availableAnos = [];

    public bool   $showSaveModal = false;
    public string $filterName = '';
    public string $filterDescription = '';
    public bool   $showLoadModal = false;
    public array  $savedFilters = [];

    private const REGIONES = [
        1  => 'Gran Buenos Aires',
        40 => 'NOA',
        41 => 'NEA',
        42 => 'Cuyo',
        43 => 'Pampeana',
        44 => 'Patagónica',
    ];

    private const AGLOMERADO_BY_REGION = [
        1  => [32, 33],
        40 => [18, 19, 22, 23, 25, 29],
        41 => [7, 8, 12, 15],
        42 => [10, 26, 27],
        43 => [2, 3, 4, 5, 6, 13, 14, 30, 34, 36, 38],
        44 => [9, 17, 20, 31, 91, 93],
    ];

    private const AGLOMERADOS = [
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

    private const NIVEL_ED = [
        1 => 'Primaria incompleta',
        2 => 'Primaria completa',
        3 => 'Secundaria incompleta',
        4 => 'Secundaria completa',
        5 => 'Superior / Universitario incompleto',
        6 => 'Superior / Universitario completo',
        7 => 'Sin instrucción',
        9 => 'Ns/Nr',
    ];

    private const ESTADOS = [
        1 => 'Ocupado',
        2 => 'Desocupado',
        3 => 'Inactivo',
        4 => 'Menor de 10 años',
    ];

    private const CAT_OCUP = [
        1 => 'Patrón',
        2 => 'Cuenta propia',
        3 => 'Asalariado',
        4 => 'Familiar sin remuneración',
        9 => 'NS/NR',
    ];

    private const CAT_INAC = [
        1 => 'Jubilado / Pensionado',
        2 => 'Rentista',
        3 => 'Estudiante',
        4 => 'Ama de casa',
        5 => 'Discapacitado',
        6 => 'Otro',
    ];

    private const FORMALIDAD = [
        1 => 'Registrado (con descuento jubilatorio)',
        2 => 'No registrado (sin descuento jubilatorio)',
        9 => 'NS / NR',
    ];

    private const SECTOR = [
        1 => 'Estatal',
        2 => 'Privado',
        3 => 'Otro tipo',
    ];

    private const INTENSIDAD = [
        1 => 'Subocupado por insuficiencia horaria',
        2 => 'Ocupado pleno',
        3 => 'Sobreocupado',
        4 => 'Ocupado que no trabajó en la semana',
    ];

    private const PP10C_LABELS = [1 => 'Sí', 2 => 'No', 9 => 'NS/NR'];
    private const PP10D_LABELS = [
        1 => 'Despido / supresión del puesto',
        2 => 'Renuncia voluntaria',
        3 => 'Fin de contrato o temporada',
        4 => 'Negocio cerrado o en quiebra',
        5 => 'Jubilación',
        9 => 'Otros motivos',
    ];
    private const PP10E_LABELS = [1 => 'Sí', 2 => 'No', 9 => 'NS/NR'];
    private const PP11L_LABELS = [1 => 'Patrón', 2 => 'Cuenta propia', 3 => 'Asalariado', 4 => 'Familiar sin rem.', 9 => 'NS/NR'];
    private const PP11N_LABELS = [1 => 'Con desc. jubilatorio', 2 => 'Sin desc. jubilatorio', 9 => 'NS/NR'];
    private const PP11O_LABELS = [1 => 'Estatal', 2 => 'Privado', 3 => 'Otro tipo'];

    private const PP10B_LABELS = [
        'PP10B1'  => 'Avisos en diarios',
        'PP10B2'  => 'Agencia de empleo',
        'PP10B3'  => 'Directamente ante empleadores',
        'PP10B4'  => 'Averiguaciones con amigos/parientes',
        'PP10B5'  => 'Gestiones para cuenta propia',
        'PP10B6'  => 'Trámites ante el Estado',
        'PP10B7'  => 'Otros',
        'PP10B8'  => 'Bolsa de trabajo / red',
        'PP10B9'  => 'Concursos / oposiciones',
        'PP10B10' => 'Otros medios digitales',
    ];

    private const CAES_DIVISION = [
        'A' => 'Agricultura, ganadería, pesca y silvicultura',
        'B' => 'Explotación de minas y canteras',
        'C' => 'Industria manufacturera',
        'D' => 'Electricidad, gas, vapor y aire acondicionado',
        'E' => 'Suministro de agua; saneamiento y gestión de desechos',
        'F' => 'Construcción',
        'G' => 'Comercio; reparación de vehículos automotores',
        'H' => 'Transporte y almacenamiento',
        'I' => 'Servicios de alojamiento y comida',
        'J' => 'Información y comunicaciones',
        'K' => 'Actividades financieras y de seguros',
        'L' => 'Actividades inmobiliarias',
        'M' => 'Actividades profesionales, científicas y técnicas',
        'N' => 'Actividades de servicios administrativos y de apoyo',
        'O' => 'Administración pública y defensa',
        'P' => 'Enseñanza',
        'Q' => 'Servicios de salud y asistencia social',
        'R' => 'Actividades artísticas, de entretenimiento y recreativas',
        'S' => 'Otras actividades de servicios',
        'T' => 'Actividades de los hogares como empleadores',
        'U' => 'Actividades de organizaciones extraterritoriales',
    ];

    // Sección (primeros 2 dígitos de PP04B_COD) por división CAES
    private const CAES_DIV_SECTIONS = [
        'A' => [1, 2, 3],
        'B' => [5, 6, 7, 8, 9],
        'C' => [10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32, 33],
        'D' => [35],
        'E' => [36, 37, 38, 39],
        'F' => [40],
        'G' => [45, 48],
        'H' => [49, 50, 51, 52, 53],
        'I' => [55, 56],
        'J' => [58, 59, 60, 61, 62, 63],
        'K' => [64, 65, 66],
        'L' => [68],
        'M' => [69, 70, 71, 72, 73, 74, 75],
        'N' => [77, 78, 79, 80, 81, 82],
        'O' => [84],
        'P' => [85],
        'Q' => [86, 87, 88],
        'R' => [90, 91, 92, 93],
        'S' => [94, 95, 96],
        'T' => [97, 98],
        'U' => [99],
    ];

    private const CAES_CLASE = [
        '0101' => 'Cultivos agrícolas',
        '0102' => 'Cría de animales',
        '0103' => 'Cultivo agrícola combinado con cría de animales',
        '0104' => 'Servicios agrícolas y pecuarios, excepto veterinarios',
        '0105' => 'Caza y servicios de apoyo',
        '0200' => 'Silvicultura, extracción de madera y actividades de apoyo',
        '0300' => 'Pesca, acuicultura y actividades de apoyo',
        '0500' => 'Extracción de carbón y lignito',
        '0600' => 'Extracción de petróleo crudo y gas natural',
        '0700' => 'Extracción de minerales metalíferos',
        '0800' => 'Explotación de otras minas y canteras',
        '0900' => 'Actividades de apoyo a la explotación de minas y canteras',
        '1001' => 'Producción y procesamiento de carne y pescado',
        '1002' => 'Preparación de frutas, hortalizas y legumbres; aceites y grasas',
        '1003' => 'Elaboración de productos lácteos',
        '1009' => 'Elaboración de productos alimenticios n.c.p.',
        '1100' => 'Elaboración de bebidas',
        '1200' => 'Elaboración de productos de tabaco',
        '1300' => 'Fabricación de productos textiles',
        '1400' => 'Confección de prendas de vestir',
        '1501' => 'Curtido y terminación de cueros; fabricación de marroquinería',
        '1502' => 'Fabricación de calzado y sus partes',
        '1600' => 'Producción de madera y fabricación de productos de madera',
        '1700' => 'Fabricación de papel y productos de papel',
        '1800' => 'Actividades de impresión y reproducción de grabaciones',
        '1901' => 'Fabricación de productos de hornos de coque',
        '1902' => 'Fabricación de productos de la refinación del petróleo',
        '2001' => 'Fabricación de pinturas, barnices, tintas y masillas',
        '2002' => 'Fabricación de jabones, detergentes, perfumes y tocador',
        '2009' => 'Fabricación de otros productos químicos n.c.p.',
        '2100' => 'Fabricación de productos farmacéuticos y medicinales',
        '2201' => 'Fabricación de productos de caucho',
        '2202' => 'Fabricación de productos de plástico',
        '2301' => 'Fabricación de vidrio y productos de vidrio',
        '2309' => 'Fabricación de productos minerales no metálicos n.c.p.',
        '2400' => 'Industrias básicas de hierro y acero',
        '2500' => 'Fabricación de productos metálicos para uso estructural',
        '2601' => 'Fabricación de componentes electrónicos',
        '2602' => 'Fabricación de equipos informáticos y periféricos',
        '2603' => 'Fabricación de equipos de radio, televisión y comunicaciones',
        '2604' => 'Fabricación de equipos electromédicos, ópticos y de precisión',
        '2701' => 'Fabricación de aparatos de uso doméstico',
        '2709' => 'Fabricación de otras maquinarias y equipos eléctricos n.c.p.',
        '2800' => 'Fabricación de maquinarias y equipos n.c.p.',
        '2900' => 'Fabricación de vehículos automotores, remolques y semirremolques',
        '3001' => 'Construcción de buques y otras embarcaciones',
        '3002' => 'Fabricación de locomotoras y material rodante ferroviario',
        '3003' => 'Fabricación de aeronaves',
        '3009' => 'Fabricación de otros equipos de transporte n.c.p.',
        '3100' => 'Fabricación de muebles y colchones',
        '3200' => 'Industrias manufactureras n.c.p.',
        '3300' => 'Mantenimiento, reparación e instalación de máquinas y equipos',
        '3501' => 'Generación, transmisión y distribución de energía eléctrica',
        '3502' => 'Fabricación de gas; distribución de combustibles gaseosos por tuberías',
        '3600' => 'Captación, tratamiento y suministro de agua',
        '3700' => 'Alcantarillado',
        '3800' => 'Recolección, tratamiento y eliminación de desechos',
        '3900' => 'Actividades de saneamiento y gestión de desechos',
        '4000' => 'Construcción',
        '4501' => 'Comercio de vehículos automotores, excepto motocicletas',
        '4502' => 'Mantenimiento y reparación de vehículos automotores',
        '4503' => 'Comercio de partes y accesorios de vehículos automotores',
        '4504' => 'Comercio y reparación de motocicletas',
        '4801' => 'Comercio de intermediación',
        '4802' => 'Comercio de materias primas agropecuarias',
        '4803' => 'Comercio de alimentos, bebidas y tabaco',
        '4804' => 'Comercio de textiles, prendas de vestir y calzado',
        '4805' => 'Comercio de materiales de construcción y ferretería',
        '4806' => 'Comercio de combustibles para vehículos',
        '4807' => 'Comercio de mercaderías n.c.p.',
        '4808' => 'Tiendas no especializadas con predominancia de alimentos',
        '4809' => 'Tiendas no especializadas sin predominancia de alimentos',
        '4810' => 'Comercio en puestos móviles y no en tiendas',
        '4811' => 'Comercio por correo, televisión, internet y otros medios',
        '4901' => 'Transporte ferroviario',
        '4902' => 'Transporte por metro',
        '4903' => 'Transporte automotor de pasajeros',
        '4904' => 'Transporte automotor de cargas',
        '4905' => 'Transporte por tuberías',
        '4909' => 'Transporte terrestre n.c.p.',
        '5000' => 'Transporte por vía acuática',
        '5100' => 'Transporte aéreo',
        '5201' => 'Depósito y almacenamiento',
        '5202' => 'Servicios auxiliares al transporte',
        '5300' => 'Servicio de correo postal',
        '5500' => 'Servicios de alojamiento en hoteles y hospedaje temporal',
        '5601' => 'Servicios de expendio de comidas y bebidas',
        '5602' => 'Servicios de expendio de comidas por vendedores ambulantes',
        '5800' => 'Edición de libros, periódicos y otras publicaciones',
        '5900' => 'Producción y postproducción de filmes y videocintas',
        '6000' => 'Actividades de programación y difusión de radio y televisión',
        '6100' => 'Telecomunicaciones',
        '6200' => 'Servicios de consultoría informática y suministro de software',
        '6300' => 'Procesamiento de datos y actividades conexas',
        '6400' => 'Intermediación financiera y otros servicios financieros',
        '6500' => 'Seguros, reaseguros y fondos de pensiones',
        '6600' => 'Actividades auxiliares a los servicios financieros y seguros',
        '6800' => 'Actividades inmobiliarias',
        '6900' => 'Actividades jurídicas y de contabilidad',
        '7000' => 'Oficinas centrales; actividades de consultoría de gestión',
        '7100' => 'Servicios de arquitectura e ingeniería',
        '7200' => 'Investigación y desarrollo',
        '7301' => 'Actividades publicitarias',
        '7302' => 'Investigación de mercados y encuestas de opinión',
        '7400' => 'Servicios de diseño especializado',
        '7500' => 'Servicios veterinarios',
        '7701' => 'Alquiler de efectos personales y domésticos',
        '7702' => 'Alquiler de vehículos, maquinaria y equipo sin operador',
        '7800' => 'Obtención y dotación de personal',
        '7900' => 'Servicios de agencias de viajes',
        '8000' => 'Servicios de investigación y seguridad',
        '8101' => 'Servicios de limpieza y apoyo a edificios',
        '8102' => 'Servicios de paisajismo y jardinería',
        '8200' => 'Actividades administrativas de oficinas y servicios auxiliares',
        '8401' => 'Servicios de administración pública y prestación de servicios a la comunidad',
        '8402' => 'Servicios de la seguridad social obligatoria',
        '8501' => 'Enseñanza inicial, primaria, secundaria, terciaria y universitaria',
        '8509' => 'Otros tipos de enseñanza n.c.p.',
        '8600' => 'Actividades de atención a la salud humana',
        '8700' => 'Asistencia social relacionada con la atención a la salud',
        '8800' => 'Servicios sociales sin alojamiento',
        '9000' => 'Actividades artísticas y de espectáculos',
        '9100' => 'Actividades de bibliotecas, archivos, museos y otras actividades culturales',
        '9200' => 'Actividades de juegos de azar y apuestas',
        '9301' => 'Actividades para la práctica deportiva',
        '9302' => 'Actividades de entretenimiento n.c.p.',
        '9401' => 'Actividades de organizaciones empresariales y profesionales',
        '9402' => 'Actividades de sindicatos',
        '9409' => 'Actividades de asociaciones n.c.p.',
        '9501' => 'Reparación de equipos informáticos',
        '9502' => 'Reparación de equipos de comunicación',
        '9503' => 'Reparación de efectos de uso personal y doméstico',
        '9601' => 'Lavado y limpieza de artículos de tela y cuero',
        '9602' => 'Servicios de peluquería y tratamientos de belleza',
        '9603' => 'Pompas fúnebres y servicios conexos',
        '9609' => 'Servicios personales n.c.p.',
        '9700' => 'Actividades de los hogares como empleadores de personal doméstico',
        '9800' => 'Actividades de los hogares como productores para uso propio',
        '9900' => 'Actividades de organizaciones y organismos extraterritoriales',
    ];

    public function mount(): void
    {
        try {
            $rows = DB::connection('indec')
                ->select("SELECT TABLE_NAME FROM information_schema.TABLES
                          WHERE TABLE_SCHEMA = 'indec'
                          AND TABLE_NAME REGEXP '^usu_individual_t[1-4][0-9]{2}$'");
            $anos = [];
            foreach ($rows as $row) {
                if (preg_match('/t[1-4](\d{2})$/', $row->TABLE_NAME, $m)) {
                    $anos[2000 + (int)$m[1]] = true;
                }
            }
            krsort($anos);
            $this->availableAnos = array_keys($anos);
        } catch (\Exception $e) {
            $this->availableAnos = [];
            $this->error = 'No se pudo conectar con la base de datos: ' . $e->getMessage();
        }

        $this->loadSavedFilters();
    }

    public function updatedSelectedRegion(): void
    {
        $this->selectedAglomerado = '';
    }

    public function updatedSelectedEstado(): void
    {
        $this->selectedCatOcup  = '';
        $this->selectedCatInac  = '';
        $this->selectedPP07H    = '';
        $this->selectedPP04A    = '';
        $this->selectedINTENSI  = '';
    }

    public function search(): void
    {
        $this->page = 1;
        $this->load();
    }

    public function goToPage(int $p): void
    {
        $this->page = max(1, min($p, $this->totalPages()));
        $this->load();
    }

    public function clearFilters(): void
    {
        $this->selectedAno       = '';
        $this->selectedTrimestre = '';
        $this->selectedRegion    = '';
        $this->selectedAglomerado = '';
        $this->selectedSexo      = '';
        $this->edadMin           = '';
        $this->edadMax           = '';
        $this->selectedEstado    = '';
        $this->selectedCatOcup   = '';
        $this->selectedCatInac   = '';
        $this->selectedNivelEd   = '';
        $this->selectedPP07H          = '';
        $this->selectedPP04A          = '';
        $this->selectedINTENSI        = '';
        $this->selectedCaesDivision   = '';
        $this->page                   = 1;
        $this->rows              = [];
        $this->total             = 0;
        $this->hasSearched       = false;
        $this->error             = null;
    }

    private const COUNT_CAP = 5000;

    // Devuelve la tabla específica si hay año+trimestre, o la vista general.
    // Consultar la tabla directa es órdenes de magnitud más rápido que la vista UNION ALL.
    private function resolveSource(): string
    {
        if ($this->selectedAno && $this->selectedTrimestre) {
            $suffix = sprintf('t%d%02d', (int)$this->selectedTrimestre, (int)$this->selectedAno % 100);
            $table  = "usu_individual_{$suffix}";
            $exists = DB::connection('indec')->selectOne(
                "SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA='indec' AND TABLE_NAME=? LIMIT 1",
                [$table]
            );
            if ($exists) return $table;
        }
        return 'usu_individual_all';
    }

    private function buildWhere(string $source): array
    {
        $where    = [];
        $bindings = [];

        // ANO4/TRIMESTRE solo se aplican en la vista general; la tabla específica ya los tiene implícitos
        if ($source === 'usu_individual_all') {
            if ($this->selectedAno)       { $where[] = 'ANO4 = ?';      $bindings[] = (int)$this->selectedAno; }
            if ($this->selectedTrimestre) { $where[] = 'TRIMESTRE = ?'; $bindings[] = (int)$this->selectedTrimestre; }
        }

        if ($this->selectedRegion)     { $where[] = 'REGION = ?';    $bindings[] = (int)$this->selectedRegion; }
        if ($this->selectedAglomerado) { $where[] = 'AGLOMERADO = ?';$bindings[] = (int)$this->selectedAglomerado; }
        if ($this->selectedSexo)       { $where[] = 'CH04 = ?';      $bindings[] = (int)$this->selectedSexo; }
        if ($this->edadMin !== '')     { $where[] = 'CH06 >= ?';     $bindings[] = (int)$this->edadMin; }
        if ($this->edadMax !== '')     { $where[] = 'CH06 <= ?';     $bindings[] = (int)$this->edadMax; }
        if ($this->selectedEstado)     { $where[] = 'ESTADO = ?';    $bindings[] = (int)$this->selectedEstado; }
        if ($this->selectedCatOcup)    { $where[] = 'CAT_OCUP = ?'; $bindings[] = (int)$this->selectedCatOcup; }
        if ($this->selectedCatInac)    { $where[] = 'CAT_INAC = ?'; $bindings[] = (int)$this->selectedCatInac; }
        if ($this->selectedNivelEd)    { $where[] = 'NIVEL_ED = ?'; $bindings[] = (int)$this->selectedNivelEd; }
        if ($this->selectedPP07H)      { $where[] = 'PP07H = ?';    $bindings[] = (int)$this->selectedPP07H; }
        if ($this->selectedPP04A)      { $where[] = 'PP04A = ?';    $bindings[] = (int)$this->selectedPP04A; }
        if ($this->selectedINTENSI)    { $where[] = 'INTENSI = ?';  $bindings[] = (int)$this->selectedINTENSI; }
        if ($this->selectedCaesDivision) {
            $sections = self::CAES_DIV_SECTIONS[$this->selectedCaesDivision] ?? [];
            if ($sections) {
                $ph = implode(',', array_fill(0, count($sections), '?'));
                $where[] = "FLOOR(PP04B_COD / 100) IN ({$ph})";
                $bindings = array_merge($bindings, $sections);
            }
        }

        $clause = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
        return [$clause, $bindings];
    }

    // Conteo acotado: escanea hasta COUNT_CAP+1 filas y se detiene.
    private function countCapped(string $source, string $where, array $bind): int
    {
        $cap = self::COUNT_CAP + 1;
        $row = DB::connection('indec')
            ->selectOne(
                "SELECT COUNT(*) AS n FROM (SELECT 1 FROM `{$source}` {$where} LIMIT {$cap}) AS sub",
                $bind
            );
        return (int)($row->n ?? 0);
    }

    private function load(): void
    {
        try {
            $this->error    = null;
            $source         = $this->resolveSource();
            [$where, $bind] = $this->buildWhere($source);
            $offset         = ($this->page - 1) * $this->perPage;

            $this->total  = $this->countCapped($source, $where, $bind);

            $cols = 'ANO4, TRIMESTRE, REGION, AGLOMERADO, CH04, CH06, NIVEL_ED, ESTADO, CAT_OCUP, CAT_INAC, INTENSI, PP07H, PP04A, PP04B_COD, P21, P47T, T_VI';
            if ($this->selectedEstado === '2') {
                $cols .= ', PP10A, PP10B1, PP10B2, PP10B3, PP10B4, PP10B5, PP10B6, PP10B7, PP10B8, PP10B9, PP10B10, PP10C, PP10D, PP10E, PP11L, PP11N, PP11O';
            }
            $rows = DB::connection('indec')
                ->select(
                    "SELECT {$cols} FROM `{$source}` {$where} LIMIT ? OFFSET ?",
                    array_merge($bind, [$this->perPage, $offset])
                );

            $this->rows       = array_map(fn($r) => (array)$r, $rows);
            $this->hasSearched = true;
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
            $this->rows  = [];
            $this->total = 0;
        }
    }

    public function exportCsv(): mixed
    {
        if (!$this->hasSearched) return null;

        try {
            set_time_limit(300);
            $source         = $this->resolveSource();
            [$where, $bind] = $this->buildWhere($source);
            $cols = 'ANO4, TRIMESTRE, REGION, AGLOMERADO, CH04, CH06, NIVEL_ED, ESTADO, CAT_OCUP, CAT_INAC, INTENSI, PP07H, PP04A, PP04B_COD, P21, P47T, T_VI';
            if ($this->selectedEstado === '2') {
                $cols .= ', PP10A, PP10B1, PP10B2, PP10B3, PP10B4, PP10B5, PP10B6, PP10B7, PP10B8, PP10B9, PP10B10, PP10C, PP10D, PP10E, PP11L, PP11N, PP11O';
            }
            $data = DB::connection('indec')
                ->select("SELECT {$cols} FROM `{$source}` {$where} LIMIT 200000", $bind);

            $filename = 'eph_datos_' . now()->format('Ymd_His') . '.csv';

            $isDesocupado = $this->selectedEstado === '2';
            return response()->streamDownload(function () use ($data, $isDesocupado) {
                $fp = fopen('php://output', 'w');
                fprintf($fp, "\xEF\xBB\xBF");
                $headers = [
                    'Año', 'Trimestre', 'Región', 'Aglomerado',
                    'Sexo', 'Edad', 'Nivel educativo',
                    'Estado laboral', 'Cat. ocupacional', 'Cat. inactividad',
                    'Intensidad horaria', 'Formalidad', 'Sector',
                    'Rama CAES (cód.)', 'Rama CAES (descripción)',
                ];
                if ($isDesocupado) {
                    array_push($headers,
                        'Semanas buscando (PP10A)', 'Cómo buscó (PP10B)', 'Trabajo p/empezar (PP10C)',
                        'Razón desocupación (PP10D)', '¿Trabajó antes? (PP10E)',
                        'Cat. últ. trabajo (PP11L)', 'Registro últ. trab. (PP11N)', 'Sector últ. trab. (PP11O)'
                    );
                }
                array_push($headers, 'Ingreso ocup. ppal. (P21)', 'Ingreso total (P47T)', 'Ing. no laborales (T_VI)');
                fputcsv($fp, $headers, ';');
                foreach ($data as $r) {
                    $caesCode = $r->PP04B_COD ? str_pad((string)(int)$r->PP04B_COD, 4, '0', STR_PAD_LEFT) : '';
                    $row = [
                        $r->ANO4,
                        $r->TRIMESTRE,
                        self::REGIONES[$r->REGION]        ?? $r->REGION,
                        self::AGLOMERADOS[$r->AGLOMERADO] ?? $r->AGLOMERADO,
                        match((int)$r->CH04) { 1 => 'Varón', 2 => 'Mujer', default => '' },
                        $r->CH06,
                        self::NIVEL_ED[$r->NIVEL_ED]      ?? '',
                        self::ESTADOS[$r->ESTADO]         ?? '',
                        self::CAT_OCUP[$r->CAT_OCUP]      ?? '',
                        self::CAT_INAC[$r->CAT_INAC]      ?? '',
                        self::INTENSIDAD[$r->INTENSI]     ?? '',
                        self::FORMALIDAD[$r->PP07H]       ?? '',
                        self::SECTOR[$r->PP04A]           ?? '',
                        $caesCode,
                        $caesCode ? (self::CAES_CLASE[$caesCode] ?? '') : '',
                    ];
                    if ($isDesocupado) {
                        $rArr = (array)$r;
                        $methods = [];
                        foreach (self::PP10B_LABELS as $col => $label) {
                            if (!empty($rArr[$col]) && (int)$rArr[$col] === 1) $methods[] = $label;
                        }
                        array_push($row,
                            $r->PP10A ?? '',
                            implode(', ', $methods),
                            self::PP10C_LABELS[(int)($r->PP10C ?? 0)] ?? '',
                            self::PP10D_LABELS[(int)($r->PP10D ?? 0)] ?? '',
                            self::PP10E_LABELS[(int)($r->PP10E ?? 0)] ?? '',
                            self::PP11L_LABELS[(int)($r->PP11L ?? 0)] ?? '',
                            self::PP11N_LABELS[(int)($r->PP11N ?? 0)] ?? '',
                            self::PP11O_LABELS[(int)($r->PP11O ?? 0)] ?? ''
                        );
                    }
                    array_push($row, $r->P21, $r->P47T, $r->T_VI);
                    fputcsv($fp, $row, ';');
                }
                fclose($fp);
            }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
            return null;
        }
    }

    private function currentFilters(): array
    {
        return [
            'ano'             => $this->selectedAno,
            'trimestre'       => $this->selectedTrimestre,
            'region'          => $this->selectedRegion,
            'aglomerado'      => $this->selectedAglomerado,
            'sexo'            => $this->selectedSexo,
            'edad_min'        => $this->edadMin,
            'edad_max'        => $this->edadMax,
            'estado'          => $this->selectedEstado,
            'cat_ocup'        => $this->selectedCatOcup,
            'cat_inac'        => $this->selectedCatInac,
            'nivel_ed'        => $this->selectedNivelEd,
            'pp07h'           => $this->selectedPP07H,
            'pp04a'           => $this->selectedPP04A,
            'intensi'         => $this->selectedINTENSI,
            'caes_division'   => $this->selectedCaesDivision,
        ];
    }

    public function saveFilter(): void
    {
        $this->validate([
            'filterName'        => 'required|min:2|max:100',
            'filterDescription' => 'nullable|max:255',
        ]);
        SavedFilter::create([
            'name'        => $this->filterName,
            'description' => $this->filterDescription,
            'table_type'  => 'datatool',
            'filters'     => $this->currentFilters(),
        ]);
        $this->filterName        = '';
        $this->filterDescription = '';
        $this->showSaveModal     = false;
        $this->loadSavedFilters();
    }

    public function loadFilter(int $id): void
    {
        $saved = SavedFilter::findOrFail($id);
        $f = $saved->filters;
        $this->selectedAno          = $f['ano']           ?? '';
        $this->selectedTrimestre    = $f['trimestre']     ?? '';
        $this->selectedRegion       = $f['region']        ?? '';
        $this->selectedAglomerado   = $f['aglomerado']    ?? '';
        $this->selectedSexo         = $f['sexo']          ?? '';
        $this->edadMin              = $f['edad_min']      ?? '';
        $this->edadMax              = $f['edad_max']      ?? '';
        $this->selectedEstado       = $f['estado']        ?? '';
        $this->selectedCatOcup      = $f['cat_ocup']      ?? '';
        $this->selectedCatInac      = $f['cat_inac']      ?? '';
        $this->selectedNivelEd      = $f['nivel_ed']      ?? '';
        $this->selectedPP07H        = $f['pp07h']         ?? '';
        $this->selectedPP04A        = $f['pp04a']         ?? '';
        $this->selectedINTENSI      = $f['intensi']       ?? '';
        $this->selectedCaesDivision = $f['caes_division'] ?? '';
        $this->showLoadModal        = false;
        $this->page                 = 1;
        $this->load();
    }

    public function deleteFilter(int $id): void
    {
        SavedFilter::destroy($id);
        $this->loadSavedFilters();
    }

    private function loadSavedFilters(): void
    {
        $this->savedFilters = SavedFilter::where('table_type', 'datatool')
            ->orderByDesc('created_at')
            ->get(['id', 'name', 'description', 'table_type', 'created_at'])
            ->toArray();
    }

    public function filteredAglomerados(): array
    {
        if (!$this->selectedRegion) return self::AGLOMERADOS;
        $ids = self::AGLOMERADO_BY_REGION[(int)$this->selectedRegion] ?? [];
        return array_filter(self::AGLOMERADOS, fn($id) => in_array($id, $ids), ARRAY_FILTER_USE_KEY);
    }

    public function totalPages(): int
    {
        return max(1, (int)ceil($this->total / $this->perPage));
    }

    public function totalIsCapped(): bool
    {
        return $this->total > self::COUNT_CAP;
    }

    // Label helpers para la vista
    public function regionLabel(mixed $v): string     { return self::REGIONES[(int)$v]    ?? "Región $v"; }
    public function aglomeradoLabel(mixed $v): string { return self::AGLOMERADOS[(int)$v] ?? "Aglo. $v"; }
    public function nivelEdLabel(mixed $v): string    { return self::NIVEL_ED[(int)$v]    ?? ''; }
    public function estadoLabel(mixed $v): string     { return self::ESTADOS[(int)$v]     ?? ''; }
    public function catOcupLabel(mixed $v): string    { return self::CAT_OCUP[(int)$v]    ?? ''; }
    public function catInacLabel(mixed $v): string    { return self::CAT_INAC[(int)$v]    ?? ''; }
    public function intensidadLabel(mixed $v): string { return self::INTENSIDAD[(int)$v]  ?? ''; }
    public function formalidadLabel(mixed $v): string { return self::FORMALIDAD[(int)$v]  ?? ''; }
    public function sectorLabel(mixed $v): string     { return self::SECTOR[(int)$v]      ?? ''; }
    public function sexoLabel(mixed $v): string       { return match((int)$v) { 1 => 'Varón', 2 => 'Mujer', default => '' }; }
    public function caesLabel(mixed $v): string {
        if (!$v) return '';
        $code = str_pad((string)(int)$v, 4, '0', STR_PAD_LEFT);
        $label = self::CAES_CLASE[$code] ?? '';
        return $label ? "{$code} — {$label}" : $code;
    }
    public function pp10cLabel(mixed $v): string  { return self::PP10C_LABELS[(int)$v]  ?? ''; }
    public function pp10dLabel(mixed $v): string  { return self::PP10D_LABELS[(int)$v]  ?? ''; }
    public function pp10eLabel(mixed $v): string  { return self::PP10E_LABELS[(int)$v]  ?? ''; }
    public function pp11lLabel(mixed $v): string  { return self::PP11L_LABELS[(int)$v]  ?? ''; }
    public function pp11nLabel(mixed $v): string  { return self::PP11N_LABELS[(int)$v]  ?? ''; }
    public function pp11oLabel(mixed $v): string  { return self::PP11O_LABELS[(int)$v]  ?? ''; }

    public function pp10bLabel(array $row): string {
        $used = [];
        foreach (self::PP10B_LABELS as $col => $label) {
            if (!empty($row[$col]) && (int)$row[$col] === 1) {
                $used[] = $label;
            }
        }
        return $used ? implode(', ', $used) : '—';
    }

    public function render()
    {
        return view('livewire.data-tool')->layout('layouts.app');
    }
}
