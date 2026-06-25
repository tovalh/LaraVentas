<script setup>
/**
 * Gestión de Caja — modernización del gestion_caja del ERP original.
 *
 * Sin caja abierta → tarjeta para abrirla (monto inicial).
 * Con caja abierta → saldo (DERIVADO del stream de eventos) + movimientos
 * (apertura/ingreso/egreso) + ventas vinculadas, con acciones Ingreso/Egreso/Cerrar.
 * Cada acción registra un evento de dominio (ver /eventos).
 */
import { ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Botonera from '@/components/Botonera.vue';
import { clp, fecha, fechaHora } from '@/lib/format';

import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Select from 'primevue/select';
import Textarea from 'primevue/textarea';
import Button from 'primevue/button';
import Tag from 'primevue/tag';

const props = defineProps({
    caja: Object,         // null si no hay caja abierta
    egresoTipos: Array,
    rendiciones: Array,
});

// --- Abrir caja ---------------------------------------------------------------
const formAbrir = useForm({ monto_inicial: 0 });
const abrir = () => formAbrir.post('/caja/abrir', { preserveScroll: true });

// --- Ingreso ------------------------------------------------------------------
const dlgIngreso = ref(false);
const formIngreso = useForm({ descripcion: '', monto: null });
const guardarIngreso = () => formIngreso.post('/caja/ingreso', {
    preserveScroll: true,
    onSuccess: () => { dlgIngreso.value = false; formIngreso.reset(); },
});

// --- Egreso -------------------------------------------------------------------
const dlgEgreso = ref(false);
const formEgreso = useForm({ idven_cajarendicionegreso_tipo: null, descripcion: '', monto: null });
const guardarEgreso = () => formEgreso.post('/caja/egreso', {
    preserveScroll: true,
    onSuccess: () => { dlgEgreso.value = false; formEgreso.reset(); },
});

// --- Anular movimiento --------------------------------------------------------
const dlgAnular = ref(false);
const formAnular = useForm({ anulamotivo: '' });
const movAnular = ref(null);
const abrirAnular = (mov) => { movAnular.value = mov; formAnular.reset(); dlgAnular.value = true; };
const confirmarAnular = () => formAnular.post(`/caja/movimiento/${movAnular.value.idven_cajamovimiento}/anular`, {
    preserveScroll: true,
    onSuccess: () => { dlgAnular.value = false; },
});

// --- Cerrar caja --------------------------------------------------------------
const dlgCerrar = ref(false);
const cerrar = () => router.post('/caja/cerrar', {}, { preserveScroll: true });

const tipoTag = { apertura: 'info', ingreso: 'success', egreso: 'warn' };
const tipoLabel = { apertura: 'Apertura', ingreso: 'Ingreso', egreso: 'Egreso' };
</script>

<template>
    <Head title="Caja" />
    <AppLayout>
        <!-- Encabezado + botonera fija -->
        <div class="sticky top-0 z-20 -mx-6 -mt-3 px-6 pb-3 bg-slate-100/95 backdrop-blur border-b border-slate-200">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h1 class="text-xl font-semibold text-slate-800">Caja</h1>
                    <p class="text-xs text-slate-500">
                        {{ caja ? `Caja #${caja.idven_caja} · abierta ${fechaHora(caja.fecha_apertura)}` : 'Sin caja abierta' }}
                    </p>
                </div>
                <Botonera v-if="caja">
                    <button class="kd-btn kd-btn-ingreso" @click="dlgIngreso = true"><i class="pi pi-arrow-down-left" /> Ingreso</button>
                    <button class="kd-btn kd-btn-guardar" @click="dlgEgreso = true"><i class="pi pi-arrow-up-right" /> Egreso</button>
                    <button class="kd-btn kd-btn-eliminar" @click="dlgCerrar = true"><i class="pi pi-lock" /> Cerrar caja</button>
                </Botonera>
            </div>
        </div>

        <!-- SIN CAJA ABIERTA -->
        <div v-if="!caja" class="pt-6">
            <div class="bg-white border border-slate-200 rounded-xl p-8 max-w-md mx-auto text-center">
                <div class="w-14 h-14 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center mx-auto mb-4">
                    <i class="pi pi-wallet text-2xl" />
                </div>
                <h2 class="text-lg font-semibold text-slate-800">No tienes una caja abierta</h2>
                <p class="text-sm text-slate-500 mt-1 mb-5">Ingresa el monto inicial para abrir la caja del día.</p>
                <label class="block text-sm font-medium text-slate-600 mb-1 text-left">Monto inicial</label>
                <InputNumber v-model="formAbrir.monto_inicial" prefix="$" locale="es-CL" :maxFractionDigits="0"
                             :min="0" class="w-full mb-1" inputClass="text-right" />
                <small v-if="formAbrir.errors.monto_inicial" class="text-red-500 block text-left mb-2">{{ formAbrir.errors.monto_inicial }}</small>
                <button class="kd-btn kd-btn-nuevo w-full justify-center mt-3" :disabled="formAbrir.processing" @click="abrir">
                    <i class="pi pi-unlock" /> Abrir caja
                </button>
            </div>

            <!-- Rendiciones pasadas -->
            <div v-if="rendiciones.length" class="bg-white border border-slate-200 rounded-xl p-5 mt-6 max-w-3xl mx-auto">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500 mb-3">Rendiciones recientes</h2>
                <ul class="divide-y divide-slate-100">
                    <li v-for="r in rendiciones" :key="r.idven_cajarendicion">
                        <Link :href="`/caja/rendicion/${r.idven_cajarendicion}`"
                              class="flex items-center justify-between py-2.5 text-sm hover:bg-slate-50 px-2 -mx-2 rounded">
                            <span class="text-slate-700 font-medium">Rendición #{{ r.rendicion }}</span>
                            <span class="text-slate-500">{{ fecha(r.fecha) }}</span>
                            <span class="tabular-nums font-semibold text-slate-700">{{ clp(r.saldo_final) }}</span>
                        </Link>
                    </li>
                </ul>
            </div>
        </div>

        <!-- CON CAJA ABIERTA -->
        <div v-else class="pt-6 space-y-4">
            <!-- Resumen / saldo -->
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
                <div class="lg:col-span-1 bg-slate-900 text-white rounded-xl p-5 flex flex-col justify-center">
                    <div class="text-xs uppercase tracking-wide text-slate-400">Saldo en caja</div>
                    <div class="text-3xl font-bold mt-1 tabular-nums">{{ clp(caja.totales.saldo) }}</div>
                </div>
                <div class="lg:col-span-3 grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-white border border-slate-200 rounded-xl p-4">
                        <div class="text-xs text-slate-500">Monto inicial</div>
                        <div class="text-lg font-semibold text-slate-800 tabular-nums">{{ clp(caja.totales.monto_inicial) }}</div>
                    </div>
                    <div class="bg-white border border-slate-200 rounded-xl p-4">
                        <div class="text-xs text-slate-500">Ingresos</div>
                        <div class="text-lg font-semibold text-emerald-600 tabular-nums">{{ clp(caja.totales.ingresos) }}</div>
                    </div>
                    <div class="bg-white border border-slate-200 rounded-xl p-4">
                        <div class="text-xs text-slate-500">Ventas</div>
                        <div class="text-lg font-semibold text-blue-600 tabular-nums">{{ clp(caja.totales.ventas) }}</div>
                    </div>
                    <div class="bg-white border border-slate-200 rounded-xl p-4">
                        <div class="text-xs text-slate-500">Egresos</div>
                        <div class="text-lg font-semibold text-rose-600 tabular-nums">{{ clp(caja.totales.egresos) }}</div>
                    </div>
                </div>
            </div>

            <!-- Movimientos (eventos del ledger) -->
            <div class="bg-white border border-slate-200 rounded-xl p-5">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500 mb-3">Movimientos</h2>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs text-slate-400 border-b border-slate-100">
                            <th class="py-2 font-medium">Tipo</th>
                            <th class="py-2 font-medium">Descripción</th>
                            <th class="py-2 font-medium">Fecha</th>
                            <th class="py-2 font-medium text-right">Monto</th>
                            <th class="py-2 font-medium text-center w-20">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="m in caja.movimientos" :key="m.idven_cajamovimiento"
                            class="border-b border-slate-50" :class="{ 'opacity-50': m.anulado }">
                            <td class="py-2"><Tag :severity="tipoTag[m.tipo]" :value="tipoLabel[m.tipo]" /></td>
                            <td class="py-2 text-slate-700" :class="{ 'line-through': m.anulado }">
                                {{ m.descripcion }}
                                <span v-if="m.egreso_tipo" class="text-xs text-slate-400">· {{ m.egreso_tipo.descripcion }}</span>
                                <span v-if="m.anulado" class="text-xs text-rose-500">· anulado: {{ m.anulamotivo }}</span>
                            </td>
                            <td class="py-2 text-slate-500">{{ fecha(m.fecha) }}</td>
                            <td class="py-2 text-right tabular-nums font-medium"
                                :class="m.tipo === 'egreso' ? 'text-rose-600' : 'text-emerald-600'">
                                {{ m.tipo === 'egreso' ? '-' : '+' }}{{ clp(m.monto) }}
                            </td>
                            <td class="py-2 text-center">
                                <Button v-if="!m.anulado && m.tipo !== 'apertura'" icon="pi pi-ban" size="small" text rounded
                                        severity="danger" @click="abrirAnular(m)" v-tooltip.left="'Anular'" />
                            </td>
                        </tr>
                        <tr v-if="!caja.movimientos.length"><td colspan="5" class="py-6 text-center text-slate-400">Sin movimientos.</td></tr>
                    </tbody>
                </table>
            </div>

            <!-- Ventas vinculadas -->
            <div class="bg-white border border-slate-200 rounded-xl p-5">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500 mb-3">
                    Ventas de esta caja ({{ caja.ventas.length }})
                </h2>
                <div v-if="!caja.ventas.length" class="text-sm text-slate-400 py-2">Aún no hay ventas en esta caja.</div>
                <ul v-else class="divide-y divide-slate-100">
                    <li v-for="v in caja.ventas" :key="v.idven_venta" class="flex items-center justify-between py-2 text-sm">
                        <Link :href="`/ventas/${v.idven_venta}`" class="text-blue-600 hover:underline">{{ v.numero }}</Link>
                        <span class="text-slate-500">{{ fecha(v.fecha) }}</span>
                        <span class="tabular-nums font-semibold text-slate-700">{{ clp(v.total) }}</span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- DIÁLOGOS -->
        <Dialog v-model:visible="dlgIngreso" modal header="Registrar ingreso" :style="{ width: '420px' }">
            <div class="space-y-3">
                <div>
                    <label class="block text-sm font-medium text-slate-600 mb-1">Descripción</label>
                    <InputText v-model="formIngreso.descripcion" class="w-full" />
                    <small v-if="formIngreso.errors.descripcion" class="text-red-500">{{ formIngreso.errors.descripcion }}</small>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-600 mb-1">Monto</label>
                    <InputNumber v-model="formIngreso.monto" prefix="$" locale="es-CL" :maxFractionDigits="0" :min="1"
                                 class="w-full" inputClass="text-right" />
                    <small v-if="formIngreso.errors.monto" class="text-red-500">{{ formIngreso.errors.monto }}</small>
                </div>
            </div>
            <template #footer>
                <Button label="Cancelar" text @click="dlgIngreso = false" />
                <button class="kd-btn kd-btn-ingreso" :disabled="formIngreso.processing" @click="guardarIngreso">Guardar ingreso</button>
            </template>
        </Dialog>

        <Dialog v-model:visible="dlgEgreso" modal header="Registrar egreso" :style="{ width: '420px' }">
            <div class="space-y-3">
                <div>
                    <label class="block text-sm font-medium text-slate-600 mb-1">Tipo de egreso</label>
                    <Select v-model="formEgreso.idven_cajarendicionegreso_tipo" :options="egresoTipos"
                            optionLabel="descripcion" optionValue="idven_cajarendicionegreso_tipo"
                            placeholder="Seleccione..." class="w-full" />
                    <small v-if="formEgreso.errors.idven_cajarendicionegreso_tipo" class="text-red-500">{{ formEgreso.errors.idven_cajarendicionegreso_tipo }}</small>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-600 mb-1">Descripción</label>
                    <InputText v-model="formEgreso.descripcion" class="w-full" />
                    <small v-if="formEgreso.errors.descripcion" class="text-red-500">{{ formEgreso.errors.descripcion }}</small>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-600 mb-1">Monto</label>
                    <InputNumber v-model="formEgreso.monto" prefix="$" locale="es-CL" :maxFractionDigits="0" :min="1"
                                 class="w-full" inputClass="text-right" />
                    <small v-if="formEgreso.errors.monto" class="text-red-500">{{ formEgreso.errors.monto }}</small>
                </div>
            </div>
            <template #footer>
                <Button label="Cancelar" text @click="dlgEgreso = false" />
                <button class="kd-btn kd-btn-guardar" :disabled="formEgreso.processing" @click="guardarEgreso">Guardar egreso</button>
            </template>
        </Dialog>

        <Dialog v-model:visible="dlgAnular" modal header="Anular movimiento" :style="{ width: '420px' }">
            <p class="text-sm text-slate-600 mb-3">Indica el motivo de la anulación. El movimiento no se borra (queda como anulado).</p>
            <Textarea v-model="formAnular.anulamotivo" class="w-full" rows="3" autoResize />
            <small v-if="formAnular.errors.anulamotivo" class="text-red-500">{{ formAnular.errors.anulamotivo }}</small>
            <template #footer>
                <Button label="Cancelar" text @click="dlgAnular = false" />
                <button class="kd-btn kd-btn-eliminar" :disabled="formAnular.processing" @click="confirmarAnular">Anular</button>
            </template>
        </Dialog>

        <Dialog v-model:visible="dlgCerrar" modal header="Cerrar caja" :style="{ width: '420px' }">
            <p class="text-sm text-slate-600">
                Se generará la rendición con el saldo final
                <span class="font-semibold">{{ caja ? clp(caja.totales.saldo) : '' }}</span>.
                La caja quedará cerrada.
            </p>
            <template #footer>
                <Button label="Cancelar" text @click="dlgCerrar = false" />
                <button class="kd-btn kd-btn-eliminar" @click="cerrar">Cerrar caja y generar rendición</button>
            </template>
        </Dialog>
    </AppLayout>
</template>
