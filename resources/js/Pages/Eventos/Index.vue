<script setup>
/**
 * Log de eventos de dominio (timeline) — la capa reusable sys_evento.
 * Cada acción de negocio (caja, ventas, ...) deja un evento inmutable acá.
 */
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { fechaHora } from '@/lib/format';

import Select from 'primevue/select';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';

const props = defineProps({
    page: Object,
    tipos: Array,
    filtro: Object,
});

const tipoSel = ref(props.filtro?.tipo ?? null);
const perPage = ref(props.page.per_page || 30);

const recargar = (extra = {}) => {
    router.get('/eventos', { tipo: tipoSel.value, perPage: perPage.value, ...extra },
        { preserveState: true, preserveScroll: true, only: ['page', 'filtro'] });
};
const onPage = (e) => { perPage.value = e.rows; recargar({ page: e.page + 1 }); };

// Color del tag según el prefijo del tipo de evento.
const tagSeverity = (tipo) => {
    if (tipo.startsWith('caja.cerrada')) return 'danger';
    if (tipo.startsWith('caja.anulacion')) return 'warn';
    if (tipo.startsWith('caja')) return 'info';
    if (tipo.startsWith('venta')) return 'success';
    return 'secondary';
};
const resumenDetalle = (detalle) => {
    if (!detalle || typeof detalle !== 'object') return '';
    return Object.entries(detalle).map(([k, v]) => `${k}: ${v}`).join(' · ');
};
</script>

<template>
    <Head title="Eventos" />
    <AppLayout>
        <div class="sticky top-0 z-20 -mx-6 -mt-3 px-6 pb-3 bg-slate-100/95 backdrop-blur border-b border-slate-200">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h1 class="text-xl font-semibold text-slate-800">Eventos</h1>
                    <p class="text-xs text-slate-500">{{ page.total }} evento(s) · log de dominio</p>
                </div>
                <div class="w-56">
                    <Select v-model="tipoSel" :options="tipos" placeholder="Todos los tipos" class="w-full" showClear
                            @change="recargar({ page: 1 })" />
                </div>
            </div>
        </div>

        <div class="pt-6">
            <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
                <DataTable :value="page.data" dataKey="idsys_evento"
                           lazy paginator :rows="page.per_page" :totalRecords="page.total"
                           :first="(page.current_page - 1) * page.per_page"
                           :rowsPerPageOptions="[30, 50, 100]"
                           paginatorTemplate="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
                           currentPageReportTemplate="{first}–{last} de {totalRecords}"
                           stripedRows size="small" @page="onPage">
                    <template #empty><div class="text-center text-slate-400 py-8 text-sm">Sin eventos.</div></template>
                    <Column header="Tipo" headerStyle="width:11rem">
                        <template #body="{ data }"><Tag :severity="tagSeverity(data.tipo)" :value="data.tipo" /></template>
                    </Column>
                    <Column field="titulo" header="Título" />
                    <Column header="Detalle">
                        <template #body="{ data }"><span class="text-xs text-slate-500">{{ resumenDetalle(data.detalle) }}</span></template>
                    </Column>
                    <Column header="Usuario" field="userins" headerStyle="width:8rem" />
                    <Column header="Fecha" headerStyle="width:11rem">
                        <template #body="{ data }"><span class="text-slate-500">{{ fechaHora(data.fechains) }}</span></template>
                    </Column>
                </DataTable>
            </div>
        </div>
    </AppLayout>
</template>
