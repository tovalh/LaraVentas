<?php

namespace App\Support\Maestro;

/**
 * Builder declarativo de un maestro (Tipo A / CRUD).
 *
 * Es el equivalente a `tpt::var()` del ERP original: el módulo describe su tabla,
 * columnas, filtros y campos de formulario, y el resto (listar, filtrar, guardar,
 * eliminar, auditoría, multi-empresa, botonera, footer) lo aporta el framework.
 *
 * Ejemplo:
 *   Maestro::make('Unidades de Medida', Unidadmedida::class, '/unidades')
 *       ->with('tipo')
 *       ->column('tipo.descripcion', 'Tipo')
 *       ->column('descripcion', 'Descripción')
 *       ->column('abreviatura', 'Abreviatura')
 *       ->filterSelect('idgen_unidadmedidatipo', 'Tipo', $tipos)
 *       ->filterText('descripcion', 'Descripción')
 *       ->fieldSwitch('habilitado', 'Habilitado')
 *       ->fieldText('descripcion', 'Descripción', required: true, unique: true)
 *       ->fieldSelect('idgen_unidadmedidatipo', 'Tipo', $tipos, required: true);
 */
class Maestro
{
    public array $columns = [];
    public array $filters = [];
    public array $fields  = [];
    public array $with    = [];

    public function __construct(
        public string $titulo,
        public string $modelo,
        public string $baseUrl,
    ) {}

    public static function make(string $titulo, string $modelo, string $baseUrl): static
    {
        return new static($titulo, $modelo, $baseUrl);
    }

    /** Helper: arma opciones [{value,label}] para selects desde una colección de modelos. */
    public static function opciones(iterable $items, string $valueKey, string $labelKey): array
    {
        $out = [];
        foreach ($items as $it) {
            $out[] = ['value' => $it->{$valueKey}, 'label' => $it->{$labelKey}];
        }

        return $out;
    }

    /** Relaciones a cargar para mostrar columnas anidadas (tipo.descripcion). */
    public function with(string ...$relations): static
    {
        $this->with = array_merge($this->with, $relations);

        return $this;
    }

    // --- Columnas del listado -------------------------------------------------

    /** $format: text | clp | entero | fecha | bool */
    public function column(string $field, string $label, string $format = 'text', string $align = 'left'): static
    {
        $this->columns[] = compact('field', 'label', 'format', 'align');

        return $this;
    }

    // --- Buscadores (filtros colapsables) ------------------------------------

    public function filterText(string $field, string $label): static
    {
        $this->filters[] = ['field' => $field, 'label' => $label, 'type' => 'text', 'options' => []];

        return $this;
    }

    public function filterSelect(string $field, string $label, array $options): static
    {
        $this->filters[] = ['field' => $field, 'label' => $label, 'type' => 'select', 'options' => $options];

        return $this;
    }

    // --- Campos del formulario -----------------------------------------------

    private function addField(array $field): static
    {
        $this->fields[] = array_merge([
            'name' => '', 'label' => '', 'type' => 'text', 'col' => 6,
            'required' => false, 'unique' => false, 'options' => [], 'suffix' => null,
        ], $field);

        return $this;
    }

    public function fieldSwitch(string $name, string $label, int $col = 12): static
    {
        return $this->addField(compact('name', 'label', 'col') + ['type' => 'switch']);
    }

    public function fieldText(string $name, string $label, int $col = 6, bool $required = false, bool $unique = false): static
    {
        return $this->addField(compact('name', 'label', 'col', 'required', 'unique') + ['type' => 'text']);
    }

    public function fieldTextarea(string $name, string $label, int $col = 12, bool $required = false): static
    {
        return $this->addField(compact('name', 'label', 'col', 'required') + ['type' => 'textarea']);
    }

    /** $format: entero | clp */
    public function fieldNumber(string $name, string $label, int $col = 4, bool $required = false, string $format = 'entero', ?string $suffix = null): static
    {
        return $this->addField(compact('name', 'label', 'col', 'required', 'suffix') + ['type' => 'number', 'format' => $format]);
    }

    public function fieldSelect(string $name, string $label, array $options, int $col = 6, bool $required = false): static
    {
        return $this->addField(compact('name', 'label', 'col', 'required', 'options') + ['type' => 'select']);
    }

    public function fieldDate(string $name, string $label, int $col = 4, bool $required = false): static
    {
        return $this->addField(compact('name', 'label', 'col', 'required') + ['type' => 'date']);
    }

    // --- Serialización para el front -----------------------------------------

    public function toArray(): array
    {
        return [
            'titulo'  => $this->titulo,
            'baseUrl' => $this->baseUrl,
            'columns' => $this->columns,
            'filters' => $this->filters,
            'fields'  => $this->fields,
        ];
    }
}
