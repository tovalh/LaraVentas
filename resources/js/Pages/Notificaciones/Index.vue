<script setup>
/**
 * Bandeja de notificaciones del usuario. Incluye un botón "Enviar notificación
 * Live" que se manda una notificación a uno mismo: el servicio Go la empuja por
 * WebSocket y la campana de arriba se actualiza sola (prueba del realtime).
 */
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from 'primevue/button';

defineProps({
    notificaciones: { type: Array, default: () => [] },
});

const enviando = ref(false);

const enviarLive = () => {
    router.post('/notificaciones/enviar', {}, {
        preserveScroll: true,
        onStart: () => (enviando.value = true),
        onFinish: () => (enviando.value = false),
    });
};

const csrf = () =>
    document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

const marcarLeidas = async () => {
    try {
        await fetch('/api/notificaciones/marcar-leidas', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf() },
        });
    } catch { /* no crítico */ }
    router.reload({ only: ['notificaciones'], preserveScroll: true });
};
</script>

<template>
    <Head title="Notificaciones" />
    <AppLayout>
        <header class="mb-6 flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-slate-800">Notificaciones</h1>
                <p class="text-sm text-slate-500 mt-1">
                    Bandeja completa. Usá el botón para probar el WebSocket en vivo.
                </p>
            </div>
            <div class="flex items-center gap-2">
                <Button
                    label="Marcar todas como leídas"
                    icon="pi pi-check"
                    severity="secondary"
                    outlined
                    size="small"
                    @click="marcarLeidas"
                />
                <Button
                    label="Enviar notificación Live"
                    icon="pi pi-bolt"
                    :loading="enviando"
                    @click="enviarLive"
                />
            </div>
        </header>

        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <!-- Vacío -->
            <div v-if="!notificaciones.length" class="px-6 py-16 text-center">
                <i class="pi pi-bell-slash text-3xl text-slate-300"></i>
                <p class="text-sm text-slate-400 mt-3">Sin notificaciones todavía.</p>
                <p class="text-xs text-slate-400 mt-1">
                    Tocá "Enviar notificación Live" y mirá la campana de arriba.
                </p>
            </div>

            <!-- Lista -->
            <ul v-else class="divide-y divide-slate-100">
                <li
                    v-for="n in notificaciones"
                    :key="n.id"
                    class="flex gap-3 px-5 py-4 hover:bg-slate-50 transition"
                    :style="{ borderLeft: '3px solid ' + (n.leido ? 'transparent' : '#22c55e') }"
                >
                    <div class="mt-0.5">
                        <i
                            class="pi pi-bell text-base"
                            :class="n.leido ? 'text-slate-300' : 'text-emerald-500'"
                        ></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <span
                                class="text-sm text-slate-800"
                                :class="{ 'font-semibold': !n.leido }"
                            >{{ n.titulo }}</span>
                            <span
                                v-if="!n.leido"
                                class="text-[10px] font-semibold text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded-full"
                            >NUEVA</span>
                        </div>
                        <p class="text-sm text-slate-500 mt-0.5">{{ n.mensaje }}</p>
                    </div>
                    <div class="text-[11px] text-slate-400 whitespace-nowrap mt-0.5">
                        {{ n.fecha }}
                    </div>
                </li>
            </ul>
        </div>
    </AppLayout>
</template>
