<script setup>
/**
 * Ventas — Tipo B (maestro-detalle).
 * Lista de ventas + formulario de cabecera (cliente, fecha) con líneas de producto.
 * Totales en CLP, IVA 19%. Misma botonera fija que los maestros.
 */
import { computed, reactive, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Botonera from '@/components/Botonera.vue';
import { clp, fecha } from '@/lib/format';

import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Select from 'primevue/select';
import InputNumber from 'primevue/inputnumber';
import DatePicker from 'primevue/datepicker';
import Button from 'primevue/button';

const props = defineProps({
    page: Object,      // paginador Laravel de ventas
    clientes: Array,
    productos: Array,
});

const IVA = 0.19;
const modo = ref('list');
const perPage = ref(props.page.per_page || 25);

const onPage = (e) => {
    perPage.value = e.rows;
    router.get('/ventas', { perPage: e.rows, page: e.page + 1 },
        { preserveState: true, preserveScroll: true, only: ['page'] });
};

const form = useForm({
    idgen_cliente: null,
    fecha: new Date(),
    detalles: [],
});

const lineas = reactive([]);

const nueva = () => {
    form.reset();
    form.fecha = new Date();
    lineas.splice(0, lineas.length);
    agregarLinea();
    form.clearErrors();
    modo.value = 'form';
};

const volver = () => { modo.value = 'list'; };

const agregarLinea = () => {
    lineas.push({ idgen_producto: null, descripcion: '', cantidad: 1, precio: 0 });
};
const quitarLinea = (i) => { lineas.splice(i, 1); };

const onProducto = (linea) => {
    const p = props.productos.find((x) => x.idgen_producto === linea.idgen_producto);
    if (p) {
        linea.descripcion = p.descripcion;
        linea.precio = p.precio;
    }
};

const subtotal = (l) => (Number(l.cantidad) || 0) * (Number(l.precio) || 0);
const neto  = computed(() => lineas.reduce((s, l) => s + subtotal(l), 0));
const iva   = computed(() => Math.round(neto.value * IVA));
const total = computed(() => neto.value + iva.value);

const guardar = () => {
    form.detalles = lineas.map((l) => ({
        idgen_producto: l.idgen_producto,
        descripcion: l.descripcion,
        cantidad: Number(l.cantidad) || 0,
        precio: Number(l.precio) || 0,
    }));
    form.transform((data) => ({
        ...data,
        fecha: data.fecha instanceof Date ? data.fecha.toISOString().slice(0, 10) : data.fecha,
    })).post('/ventas', {
        preserveScroll: true,
        onSuccess: () => volver(),
    });
};

const verVenta = (row) => router.get(`/ventas/${row.idven_venta}`);

const clienteLabel = (c) => `${c.razon_social}${c.rut ? ' · ' + c.rut : ''}`;
const productoLabel = (p) => `${p.codigo ? p.codigo + ' · ' : ''}${p.descripcion}`;
</script>

<template>
    <Head title="Ventas" />
    <AppLayout>
        <!-- Encabezado + botonera fija -->
        <div class="sticky top-0 z-20 -mx-6 -mt-3 px-6 pb-3 bg-slate-100/95 backdrop-blur border-b border-slate-200">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h1 class="text-xl font-semibold text-slate-800">Ventas</h1>
                    <p class="text-xs text-slate-500">
                        {{ modo === 'list' ? `${page.total} venta(s)` : 'Nueva venta' }}
                    </p>
                </div>

                <Botonera v-if="modo === 'list'">
                    <button class="kd-btn kd-btn-nuevo" @click="nueva">
                        <i class="pi pi-plus" /> Nueva venta
                    </button>
                </Botonera>
                <Botonera v-else>
                    <button class="kd-btn kd-btn-volver" @click="volver">
                        <i class="pi pi-arrow-left" /> Volver
                    </button>
                    <button class="kd-btn kd-btn-guardar" :disabled="form.processing || !lineas.length" @click="guardar">
                        <i class="pi pi-save" /> Guardar
                    </button>
                </Botonera>
            </div>
        </div>

        <!-- LISTA -->
        <div v-show="modo === 'list'" class="pt-6">
            <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
                <DataTable :value="page.data" dataKey="idven_venta"
                           lazy paginator :rows="page.per_page" :totalRecords="page.total"
                           :first="(page.current_page - 1) * page.per_page"
                           :rowsPerPageOptions="[25, 50, 100, 200]"
                           paginatorTemplate="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
                           currentPageReportTemplate="{first}–{last} de {totalRecords}"
                           stripedRows size="small" rowHover :rowClass="() => 'cursor-pointer'"
                           @page="onPage" @row-click="(e) => verVenta(e.data)">
                    <template #empty><div class="text-center text-slate-400 py-8 text-sm">Sin ventas.</div></template>
                    <Column field="numero" header="Número" />
                    <Column header="Cliente">
                        <template #body="{ data }">{{ data.cliente?.razon_social || '—' }}</template>
                    </Column>
                    <Column header="Fecha" field="fecha">
                        <template #body="{ data }">{{ fecha(data.fecha) }}</template>
                    </Column>
                    <Column header="Líneas" bodyStyle="text-align:center">
                        <template #body="{ data }">{{ data.detalles_count }}</template>
                    </Column>
                    <Column header="Neto" headerClass="text-right">
                        <template #body="{ data }"><div class="text-right tabular-nums">{{ clp(data.neto) }}</div></template>
                    </Column>
                    <Column header="Total" headerClass="text-right">
                        <template #body="{ data }"><div class="text-right tabular-nums font-semibold">{{ clp(data.total) }}</div></template>
                    </Column>
                    <Column headerStyle="width:5rem" bodyStyle="text-align:center">
                        <template #body="{ data }">
                            <Button icon="pi pi-eye" size="small" text rounded @click="verVenta(data)" v-tooltip.left="'Ver'" />
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>

        <!-- FORMULARIO -->
        <div v-show="modo === 'form'" class="pt-6 space-y-4">
            <!-- Cabecera -->
            <div class="bg-white border border-slate-200 rounded-xl p-6">
                <div class="grid grid-cols-12 gap-4">
                    <div class="col-span-12 sm:col-span-6">
                        <label class="block text-sm font-medium text-slate-600 mb-1">Cliente</label>
                        <Select v-model="form.idgen_cliente" :options="clientes" :optionLabel="clienteLabel"
                                optionValue="idgen_cliente" placeholder="Seleccione cliente..." class="w-full" filter showClear />
                    </div>
                    <div class="col-span-12 sm:col-span-3">
                        <label class="block text-sm font-medium text-slate-600 mb-1">Fecha</label>
                        <DatePicker v-model="form.fecha" dateFormat="dd/mm/yy" class="w-full" />
                        <small v-if="form.errors.fecha" class="text-red-500">{{ form.errors.fecha }}</small>
                    </div>
                </div>
            </div>

            <!-- Detalle -->
            <div class="bg-white border border-slate-200 rounded-xl p-6">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Detalle</h2>
                    <Button label="Agregar línea" icon="pi pi-plus" size="small" outlined @click="agregarLinea" />
                </div>
                <small v-if="form.errors.detalles" class="text-red-500">{{ form.errors.detalles }}</small>

                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs text-slate-400 border-b border-slate-100">
                            <th class="py-2 font-medium" style="width:34%">Producto</th>
                            <th class="py-2 font-medium">Descripción</th>
                            <th class="py-2 font-medium text-right" style="width:90px">Cantidad</th>
                            <th class="py-2 font-medium text-right" style="width:130px">Precio</th>
                            <th class="py-2 font-medium text-right" style="width:140px">Subtotal</th>
                            <th style="width:40px"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(l, i) in lineas" :key="i" class="border-b border-slate-50">
                            <td class="py-1.5 pr-2">
                                <Select v-model="l.idgen_producto" :options="productos" :optionLabel="productoLabel"
                                        optionValue="idgen_producto" placeholder="Producto..." class="w-full" filter
                                        @change="onProducto(l)" />
                            </td>
                            <td class="py-1.5 pr-2 text-slate-600">{{ l.descripcion || '—' }}</td>
                            <td class="py-1.5 pr-2">
                                <InputNumber v-model="l.cantidad" :min="1" locale="es-CL" :maxFractionDigits="0"
                                             class="w-full" inputClass="text-right" />
                            </td>
                            <td class="py-1.5 pr-2">
                                <InputNumber v-model="l.precio" :min="0" prefix="$" locale="es-CL" :maxFractionDigits="0"
                                             class="w-full" inputClass="text-right" />
                            </td>
                            <td class="py-1.5 text-right tabular-nums font-medium">{{ clp(subtotal(l)) }}</td>
                            <td class="py-1.5 text-center">
                                <Button icon="pi pi-trash" size="small" text rounded severity="danger" @click="quitarLinea(i)" />
                            </td>
                        </tr>
                        <tr v-if="!lineas.length">
                            <td colspan="6" class="py-6 text-center text-slate-400">Agrega al menos una línea.</td>
                        </tr>
                    </tbody>
                </table>

                <!-- Totales -->
                <div class="flex justify-end mt-4">
                    <div class="w-64 space-y-1.5 text-sm">
                        <div class="flex justify-between text-slate-500"><span>Neto</span><span class="tabular-nums">{{ clp(neto) }}</span></div>
                        <div class="flex justify-between text-slate-500"><span>IVA (19%)</span><span class="tabular-nums">{{ clp(iva) }}</span></div>
                        <div class="flex justify-between text-base font-bold text-slate-800 border-t border-slate-200 pt-1.5">
                            <span>Total</span><span class="tabular-nums">{{ clp(total) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
