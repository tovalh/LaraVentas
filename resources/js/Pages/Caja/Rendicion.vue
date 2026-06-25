<script setup>
/** Rendición de caja — reporte de cierre (solo lectura). */
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Botonera from '@/components/Botonera.vue';
import { clp, fecha, fechaHora } from '@/lib/format';
import Tag from 'primevue/tag';

defineProps({
    rendicion: Object,
    movimientos: Array,
    ventas: Array,
});

const tipoTag = { apertura: 'info', ingreso: 'success', egreso: 'warn' };
const tipoLabel = { apertura: 'Apertura', ingreso: 'Ingreso', egreso: 'Egreso' };
</script>

<template>
    <Head :title="`Rendición #${rendicion.rendicion}`" />
    <AppLayout>
        <div class="sticky top-0 z-20 -mx-6 -mt-3 px-6 pb-3 bg-slate-100/95 backdrop-blur border-b border-slate-200">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h1 class="text-xl font-semibold text-slate-800">Rendición #{{ rendicion.rendicion }}</h1>
                    <p class="text-xs text-slate-500">{{ fecha(rendicion.fecha) }}</p>
                </div>
                <Botonera>
                    <button class="kd-btn kd-btn-volver" @click="router.get('/caja')"><i class="pi pi-arrow-left" /> Volver</button>
                </Botonera>
            </div>
        </div>

        <div class="pt-6 space-y-4">
            <!-- Cabecera -->
            <div class="bg-white border border-slate-200 rounded-xl p-6 grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                <div><div class="text-xs text-slate-400">Apertura</div><div class="text-slate-700">{{ fechaHora(rendicion.apertura_fecha) }}</div><div class="text-xs text-slate-500">{{ rendicion.apertura_usuario }}</div></div>
                <div><div class="text-xs text-slate-400">Cierre</div><div class="text-slate-700">{{ fechaHora(rendicion.cierre_fecha) }}</div><div class="text-xs text-slate-500">{{ rendicion.cierre_usuario }}</div></div>
                <div><div class="text-xs text-slate-400">Caja</div><div class="text-slate-700">#{{ rendicion.idven_caja }}</div></div>
                <div><div class="text-xs text-slate-400">Saldo final</div><div class="text-lg font-bold text-slate-800 tabular-nums">{{ clp(rendicion.saldo_final) }}</div></div>
            </div>

            <!-- Totales -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white border border-slate-200 rounded-xl p-4"><div class="text-xs text-slate-500">Monto inicial</div><div class="text-lg font-semibold text-slate-800 tabular-nums">{{ clp(rendicion.monto_inicial) }}</div></div>
                <div class="bg-white border border-slate-200 rounded-xl p-4"><div class="text-xs text-slate-500">Ingresos</div><div class="text-lg font-semibold text-emerald-600 tabular-nums">{{ clp(rendicion.total_ingresos) }}</div></div>
                <div class="bg-white border border-slate-200 rounded-xl p-4"><div class="text-xs text-slate-500">Ventas</div><div class="text-lg font-semibold text-blue-600 tabular-nums">{{ clp(rendicion.total_ventas) }}</div></div>
                <div class="bg-white border border-slate-200 rounded-xl p-4"><div class="text-xs text-slate-500">Egresos</div><div class="text-lg font-semibold text-rose-600 tabular-nums">{{ clp(rendicion.total_egresos) }}</div></div>
            </div>

            <!-- Movimientos -->
            <div class="bg-white border border-slate-200 rounded-xl p-5">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500 mb-3">Movimientos</h2>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs text-slate-400 border-b border-slate-100">
                            <th class="py-2 font-medium">Tipo</th>
                            <th class="py-2 font-medium">Descripción</th>
                            <th class="py-2 font-medium">Fecha</th>
                            <th class="py-2 font-medium text-right">Monto</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="m in movimientos" :key="m.idven_cajamovimiento" class="border-b border-slate-50" :class="{ 'opacity-50 line-through': m.anulado }">
                            <td class="py-2"><Tag :severity="tipoTag[m.tipo]" :value="tipoLabel[m.tipo]" /></td>
                            <td class="py-2 text-slate-700">{{ m.descripcion }}</td>
                            <td class="py-2 text-slate-500">{{ fecha(m.fecha) }}</td>
                            <td class="py-2 text-right tabular-nums" :class="m.tipo === 'egreso' ? 'text-rose-600' : 'text-emerald-600'">
                                {{ m.tipo === 'egreso' ? '-' : '+' }}{{ clp(m.monto) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Ventas -->
            <div v-if="ventas.length" class="bg-white border border-slate-200 rounded-xl p-5">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500 mb-3">Ventas ({{ ventas.length }})</h2>
                <ul class="divide-y divide-slate-100">
                    <li v-for="v in ventas" :key="v.idven_venta" class="flex items-center justify-between py-2 text-sm">
                        <span class="text-slate-700">{{ v.numero }}</span>
                        <span class="text-slate-500">{{ fecha(v.fecha) }}</span>
                        <span class="tabular-nums font-semibold text-slate-700">{{ clp(v.total) }}</span>
                    </li>
                </ul>
            </div>
        </div>
    </AppLayout>
</template>
