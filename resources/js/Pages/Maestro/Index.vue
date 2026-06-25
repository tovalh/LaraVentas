<script setup>
/**
 * Página genérica de Maestro (Tipo A / CRUD).
 *
 * NO se programa por módulo: recibe la config declarada en el backend (Maestro.php)
 * y arma sola la botonera fija, los filtros, la tabla (con soft-delete en rojo) y el
 * formulario con footer de auditoría. Crear un maestro nuevo = declararlo en su
 * controller; esta vista ya le da toda la UI.
 */
import { computed, reactive, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Botonera from '@/components/Botonera.vue';
import { getPath, formatCol, fechaHora } from '@/lib/format';

import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Select from 'primevue/select';
import Textarea from 'primevue/textarea';
import ToggleSwitch from 'primevue/toggleswitch';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import ConfirmDialog from 'primevue/confirmdialog';
import { useConfirm } from 'primevue/useconfirm';

const props = defineProps({
    maestro: Object,   // { titulo, baseUrl, columns, filters, fields }
    page: Object,      // paginador Laravel { data, total, current_page, per_page, ... }
    filtros: Object,
});

const confirm = useConfirm();
const cfg = props.maestro;
const perPage = ref(props.page.per_page || 25);

// --- Paginación (lado servidor) ----------------------------------------------
const recargar = (extra = {}) => {
    router.get(cfg.baseUrl, { f: filtrosLocal, perPage: perPage.value, ...extra },
        { preserveState: true, preserveScroll: true, only: ['page', 'filtros'] });
};
const onPage = (e) => {
    perPage.value = e.rows;
    recargar({ page: e.page + 1 });
};

// --- Modo lista / formulario --------------------------------------------------
const modo = ref('list');         // 'list' | 'form'
const editando = ref(null);       // fila en edición (null = nuevo)
const seleccion = ref([]);        // filas marcadas para eliminar
const verFiltros = ref(false);

// --- Filtros ------------------------------------------------------------------
const filtrosLocal = reactive({ ...(props.filtros || {}) });
const hayFiltros = computed(() => Object.values(filtrosLocal).some((v) => v !== '' && v != null));

const aplicarFiltros = () => recargar({ page: 1 });
const limpiarFiltros = () => {
    Object.keys(filtrosLocal).forEach((k) => (filtrosLocal[k] = ''));
    recargar({ page: 1 });
};

// --- Formulario ---------------------------------------------------------------
const blank = () => {
    const o = {};
    for (const f of cfg.fields) o[f.name] = f.type === 'switch' ? true : f.type === 'number' ? null : '';
    return o;
};
const form = useForm(blank());

const nuevo = () => {
    editando.value = null;
    Object.assign(form, blank());
    form.clearErrors();
    modo.value = 'form';
};

const editar = (row) => {
    editando.value = row;
    for (const f of cfg.fields) form[f.name] = row[f.name] ?? (f.type === 'switch' ? false : f.type === 'number' ? null : '');
    form.clearErrors();
    modo.value = 'form';
};

const volver = () => {
    modo.value = 'list';
    editando.value = null;
};

const guardar = () => {
    const opts = { preserveScroll: true, onSuccess: () => volver() };
    if (editando.value) {
        form.put(`${cfg.baseUrl}/${editando.value._id}`, opts);
    } else {
        form.post(cfg.baseUrl, opts);
    }
};

// --- Eliminar (soft-delete) ---------------------------------------------------
const eliminar = () => {
    if (!seleccion.value.length) return;
    confirm.require({
        message: `¿Eliminar ${seleccion.value.length} registro(s)? Quedarán deshabilitados.`,
        header: 'Confirmar',
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: 'Cancelar',
        acceptLabel: 'Eliminar',
        acceptClass: 'p-button-danger',
        accept: () => {
            const ids = seleccion.value.map((r) => r._id);
            router.delete(cfg.baseUrl, {
                data: { ids },
                preserveScroll: true,
                onSuccess: () => (seleccion.value = []),
            });
        },
    });
};

// span inline (Tailwind no genera col-span-N dinámicos en build)
const spanStyle = (col) => ({ gridColumn: `span ${col} / span ${col}` });
</script>

<template>
    <Head :title="cfg.titulo" />
    <AppLayout>
        <ConfirmDialog />

        <!-- Encabezado + botonera fija -->
        <div class="sticky top-0 z-20 -mx-6 -mt-3 px-6 pb-3 bg-slate-100/95 backdrop-blur border-b border-slate-200">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h1 class="text-xl font-semibold text-slate-800">{{ cfg.titulo }}</h1>
                    <p class="text-xs text-slate-500">
                        {{ modo === 'list' ? `${page.total} registro(s)` : (editando ? 'Editando registro' : 'Nuevo registro') }}
                    </p>
                </div>

                <Botonera v-if="modo === 'list'">
                    <button class="kd-btn kd-btn-filtro" :class="{ 'kd-active': verFiltros || hayFiltros }"
                            @click="verFiltros = !verFiltros">
                        <i class="pi pi-filter" /> Filtros
                    </button>
                    <button class="kd-btn kd-btn-nuevo" @click="nuevo">
                        <i class="pi pi-plus" /> Nuevo
                    </button>
                    <button class="kd-btn kd-btn-eliminar" :disabled="!seleccion.length" @click="eliminar">
                        <i class="pi pi-trash" /> Eliminar
                    </button>
                </Botonera>

                <Botonera v-else>
                    <button class="kd-btn kd-btn-volver" @click="volver">
                        <i class="pi pi-arrow-left" /> Volver
                    </button>
                    <button class="kd-btn kd-btn-guardar" :disabled="form.processing" @click="guardar">
                        <i class="pi pi-save" /> Guardar
                    </button>
                </Botonera>
            </div>
        </div>

        <!-- LISTA -->
        <div v-show="modo === 'list'" class="pt-6">
            <!-- Filtros colapsables -->
            <transition name="fade">
                <div v-if="verFiltros" class="bg-white border border-slate-200 rounded-xl p-4 mb-4">
                    <div class="grid grid-cols-12 gap-3 items-end">
                        <div v-for="f in cfg.filters" :key="f.field" class="col-span-12 sm:col-span-4">
                            <label class="block text-xs font-medium text-slate-500 mb-1">{{ f.label }}</label>
                            <Select v-if="f.type === 'select'" v-model="filtrosLocal[f.field]" :options="f.options"
                                    optionLabel="label" optionValue="value" placeholder="Todos"
                                    class="w-full" showClear @change="aplicarFiltros" />
                            <InputText v-else v-model="filtrosLocal[f.field]" class="w-full"
                                       @keyup.enter="aplicarFiltros" />
                        </div>
                        <div class="col-span-12 sm:col-span-4 flex gap-2">
                            <Button label="Aplicar" icon="pi pi-search" size="small" @click="aplicarFiltros" />
                            <Button label="Limpiar" icon="pi pi-times" size="small" severity="secondary" outlined
                                    @click="limpiarFiltros" />
                        </div>
                    </div>
                </div>
            </transition>

            <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
                <DataTable :value="page.data" v-model:selection="seleccion" dataKey="_id"
                           lazy paginator :rows="page.per_page" :totalRecords="page.total"
                           :first="(page.current_page - 1) * page.per_page"
                           :rowsPerPageOptions="[25, 50, 100, 200]"
                           paginatorTemplate="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
                           currentPageReportTemplate="{first}–{last} de {totalRecords}"
                           stripedRows size="small" rowHover :rowClass="() => 'cursor-pointer'"
                           @page="onPage" @row-click="(e) => editar(e.data)">
                    <template #empty>
                        <div class="text-center text-slate-400 py-8 text-sm">Sin registros.</div>
                    </template>

                    <Column selectionMode="multiple" headerStyle="width:3rem" />
                    <Column v-for="c in cfg.columns" :key="c.field" :field="c.field" :header="c.label"
                            :bodyClass="c.align === 'right' ? 'text-right tabular-nums' : ''"
                            :headerClass="c.align === 'right' ? 'text-right' : ''">
                        <template #body="{ data }">
                            <span :class="{ 'text-red-500 line-through': data.habilitado === false }">
                                {{ formatCol(getPath(data, c.field), c.format) }}
                            </span>
                        </template>
                    </Column>
                    <Column headerStyle="width:8rem" header="Estado">
                        <template #body="{ data }">
                            <Tag :severity="data.habilitado === false ? 'danger' : 'success'"
                                 :value="data.habilitado === false ? 'Inhabilitado' : 'Activo'" />
                        </template>
                    </Column>
                    <Column headerStyle="width:5rem" bodyStyle="text-align:center">
                        <template #body="{ data }">
                            <Button icon="pi pi-pencil" size="small" text rounded @click="editar(data)"
                                    v-tooltip.left="'Editar'" />
                        </template>
                    </Column>
                </DataTable>
            </div>
            <p class="text-xs text-slate-400 mt-2">Clic en una fila para abrir el registro.</p>
        </div>

        <!-- FORMULARIO -->
        <div v-show="modo === 'form'" class="pt-6">
            <div class="bg-white border border-slate-200 rounded-xl p-6">
                <div class="grid grid-cols-12 gap-4">
                    <div v-for="f in cfg.fields" :key="f.name" :style="spanStyle(f.col)">
                        <label class="block text-sm font-medium text-slate-600 mb-1">
                            {{ f.label }} <span v-if="f.required" class="text-red-500">*</span>
                        </label>

                        <ToggleSwitch v-if="f.type === 'switch'" v-model="form[f.name]" />

                        <Select v-else-if="f.type === 'select'" v-model="form[f.name]" :options="f.options"
                                optionLabel="label" optionValue="value" placeholder="Seleccione..."
                                class="w-full" filter showClear />

                        <InputNumber v-else-if="f.type === 'number'" v-model="form[f.name]" class="w-full"
                                     locale="es-CL" :useGrouping="true" :minFractionDigits="0" :maxFractionDigits="0"
                                     :prefix="f.format === 'clp' ? '$' : ''" :suffix="f.suffix || ''" />

                        <Textarea v-else-if="f.type === 'textarea'" v-model="form[f.name]" class="w-full" rows="3" autoResize />

                        <InputText v-else v-model="form[f.name]" class="w-full" />

                        <small v-if="form.errors[f.name]" class="text-red-500">{{ form.errors[f.name] }}</small>
                    </div>
                </div>

                <!-- Footer de auditoría (estilo original) -->
                <div v-if="editando" class="mt-6 pt-4 border-t border-slate-100 grid grid-cols-1 md:grid-cols-3 gap-2 text-xs text-slate-400">
                    <div>Id registro: <span class="text-slate-600">{{ editando._id }}</span></div>
                    <div>Creación: <span class="text-slate-600">{{ editando.userins || '—' }} · {{ fechaHora(editando.fechains) }}</span></div>
                    <div>Modificación: <span class="text-slate-600">{{ editando.usermod || '—' }} · {{ fechaHora(editando.fechamod) }}</span></div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity .15s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
