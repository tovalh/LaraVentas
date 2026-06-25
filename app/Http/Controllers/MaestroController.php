<?php

namespace App\Http\Controllers;

use App\Support\Maestro\Maestro;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador base de los maestros (Tipo A / CRUD).
 *
 * Equivale al `tpt::_init()` + flujo lfn_list/lfn_form/lfn_save del ERP original:
 * el módulo concreto solo implementa maestro() (la declaración), y aquí viven
 * listar + filtrar + guardar (con auditoría/empresa/unicidad) + soft-delete.
 *
 * Toda la UI la pinta una sola página Vue genérica: resources/js/Pages/Maestro/Index.vue.
 */
abstract class MaestroController extends Controller
{
    /** Cada módulo declara su maestro acá. */
    abstract protected function maestro(): Maestro;

    public function index(Request $request): Response
    {
        $m = $this->maestro();

        $perPage = min(500, max(10, (int) $request->input('perPage', 25)));

        $query = $m->modelo::query()->with($m->with);
        $this->aplicarFiltros($query, $m, $request);

        // Paginación del lado del servidor: soporta miles de registros sin inflar
        // el payload (solo viaja la página actual).
        $page = $query->orderByDesc($this->keyName($m))
            ->paginate($perPage)
            ->withQueryString();

        $page->getCollection()->transform(
            fn ($model) => array_merge($model->toArray(), ['_id' => $model->getKey()])
        );

        return Inertia::render('Maestro/Index', [
            'maestro' => $m->toArray(),
            'page'    => $page,
            'filtros' => (object) $request->input('f', []),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        return $this->guardar($request, null);
    }

    public function update(Request $request, $id): RedirectResponse
    {
        return $this->guardar($request, $id);
    }

    public function destroy(Request $request): RedirectResponse
    {
        $m   = $this->maestro();
        $ids = (array) $request->input('ids', []);

        $n = 0;
        foreach ($ids as $id) {
            $model = $m->modelo::find($id);
            if ($model) {
                $model->habilitado = false;   // soft-delete por estado, nunca DELETE
                $model->save();
                $n++;
            }
        }

        return back()->with('success', $n === 1 ? 'Registro eliminado.' : "$n registros eliminados.");
    }

    // -------------------------------------------------------------------------

    private function guardar(Request $request, $id): RedirectResponse
    {
        $m = $this->maestro();

        $this->validar($m, $request, $id);

        $model = $id ? $m->modelo::findOrFail($id) : new $m->modelo;

        foreach ($m->fields as $f) {
            $model->{$f['name']} = $this->valorCampo($f, $request);
        }

        $model->save();

        return back()->with('success', $id ? 'Registro modificado.' : 'Registro creado.');
    }

    private function validar(Maestro $m, Request $request, $id): void
    {
        $rules = [];
        $messages = [];

        foreach ($m->fields as $f) {
            $r = [];
            if ($f['type'] === 'switch') {
                continue; // booleano: ausencia = false
            }
            $r[] = $f['required'] ? 'required' : 'nullable';
            if ($f['type'] === 'number') {
                $r[] = 'integer';
            }
            $rules[$f['name']] = $r;
            $messages["{$f['name']}.required"] = "El campo \"{$f['label']}\" es obligatorio.";
        }

        $validator = validator($request->all(), $rules, $messages);

        // Unicidad (estilo lfn_check_unicidad del original), dentro de la empresa.
        $validator->after(function ($v) use ($m, $request, $id) {
            foreach ($m->fields as $f) {
                if (empty($f['unique'])) {
                    continue;
                }
                $valor = $request->input($f['name']);
                if ($valor === null || $valor === '') {
                    continue;
                }
                $existe = $m->modelo::where($f['name'], $valor)
                    ->when($id, fn ($q) => $q->where($this->keyName($m), '!=', $id))
                    ->exists();
                if ($existe) {
                    $v->errors()->add($f['name'], "Ya existe un registro con {$f['label']} \"$valor\".");
                }
            }
        });

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    private function valorCampo(array $f, Request $request)
    {
        return match ($f['type']) {
            'switch' => $request->boolean($f['name']),
            'number' => (int) $request->input($f['name'], 0),
            default  => $request->input($f['name']),
        };
    }

    private function aplicarFiltros($query, Maestro $m, Request $request): void
    {
        $f = (array) $request->input('f', []);

        foreach ($m->filters as $filtro) {
            $valor = $f[$filtro['field']] ?? null;
            if ($valor === null || $valor === '') {
                continue;
            }
            if ($filtro['type'] === 'text') {
                $query->where($filtro['field'], 'like', "%$valor%");
            } else {
                $query->where($filtro['field'], $valor);
            }
        }
    }

    private function keyName(Maestro $m): string
    {
        return (new $m->modelo)->getKeyName();
    }
}
