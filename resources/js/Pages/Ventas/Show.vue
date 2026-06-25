<script setup>
/** Ventas — detalle de una venta (solo lectura). */
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Botonera from '@/components/Botonera.vue';
import { clp, fecha, fechaHora } from '@/lib/format';

const props = defineProps({ venta: Object });

const anular = () => {
    if (!confirm('¿Anular esta venta? Quedará deshabilitada.')) return;
    router.delete(`/ventas/${props.venta.idven_venta}`, {
        onSuccess: () => router.get('/ventas'),
    });
};
</script>

<template>
    <Head :title="`Venta ${venta.numero}`" />
    <AppLayout>
        <div class="sticky top-0 z-20 -mx-6 -mt-3 px-6 pb-3 bg-slate-100/95 backdrop-blur border-b border-slate-200">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h1 class="text-xl font-semibold text-slate-800">Venta {{ venta.numero }}</h1>
                    <p class="text-xs text-slate-500">{{ fecha(venta.fecha) }}</p>
                </div>
                <Botonera>
                    <button class="kd-btn kd-btn-volver" @click="router.get('/ventas')">
                        <i class="pi pi-arrow-left" /> Volver
                    </button>
                    <button v-if="venta.habilitado" class="kd-btn kd-btn-eliminar" @click="anular">
                        <i class="pi pi-ban" /> Anular
                    </button>
                </Botonera>
            </div>
        </div>

        <div class="pt-6 space-y-4">
            <div class="bg-white border border-slate-200 rounded-xl p-6 grid grid-cols-1 sm:grid-cols-3 gap-3 text-sm">
                <div><div class="text-xs text-slate-400">Cliente</div><div class="text-slate-700 font-medium">{{ venta.cliente?.razon_social || '—' }}</div></div>
                <div><div class="text-xs text-slate-400">RUT</div><div class="text-slate-700">{{ venta.cliente?.rut || '—' }}</div></div>
                <div><div class="text-xs text-slate-400">Estado</div><div class="text-slate-700">{{ venta.habilitado ? 'Activa' : 'Anulada' }}</div></div>
            </div>

            <div class="bg-white border border-slate-200 rounded-xl p-6">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500 mb-3">Detalle</h2>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs text-slate-400 border-b border-slate-100">
                            <th class="py-2 font-medium">Descripción</th>
                            <th class="py-2 font-medium text-right">Cantidad</th>
                            <th class="py-2 font-medium text-right">Precio</th>
                            <th class="py-2 font-medium text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="d in venta.detalles" :key="d.idven_venta_detalle" class="border-b border-slate-50">
                            <td class="py-2 text-slate-700">{{ d.descripcion }}</td>
                            <td class="py-2 text-right tabular-nums">{{ d.cantidad }}</td>
                            <td class="py-2 text-right tabular-nums">{{ clp(d.precio) }}</td>
                            <td class="py-2 text-right tabular-nums font-medium">{{ clp(d.subtotal) }}</td>
                        </tr>
                    </tbody>
                </table>

                <div class="flex justify-end mt-4">
                    <div class="w-64 space-y-1.5 text-sm">
                        <div class="flex justify-between text-slate-500"><span>Neto</span><span class="tabular-nums">{{ clp(venta.neto) }}</span></div>
                        <div class="flex justify-between text-slate-500"><span>IVA (19%)</span><span class="tabular-nums">{{ clp(venta.iva) }}</span></div>
                        <div class="flex justify-between text-base font-bold text-slate-800 border-t border-slate-200 pt-1.5">
                            <span>Total</span><span class="tabular-nums">{{ clp(venta.total) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <p class="text-xs text-slate-400">
                Creación: {{ venta.userins || '—' }} · {{ fechaHora(venta.fechains) }}
            </p>
        </div>
    </AppLayout>
</template>
