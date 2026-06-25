<script setup>
/**
 * Campanita de notificaciones — réplica de uso del ERP original (`notif`):
 *  - cargar(): lee la bandeja (badge = no leídas, lista = últimas 20).
 *  - al abrir: marca como leídas (badge a 0) dejando el resaltado verde visible.
 *  - al cerrar: quita el resaltado.
 *  - clic en una notificación: navega vía SPA (Inertia) a su url.
 *  - tiempo real: WebSocket al servicio Go; si no hay Go configurado, cae a polling.
 */
import { onMounted, onUnmounted, ref } from 'vue';
import { router } from '@inertiajs/vue3';

const items = ref([]);
const count = ref(0);
const abierto = ref(false);

let ws = null;
let reintento = 0;
let pollTimer = null;

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

const getJson = async (url, options = {}) => {
    const r = await fetch(url, {
        credentials: 'same-origin',
        headers: { Accept: 'application/json', ...(options.headers || {}) },
        ...options,
    });
    if (!r.ok) throw new Error('HTTP ' + r.status);
    return r.json();
};

// --- API ---------------------------------------------------------------------
const cargar = async () => {
    try {
        const r = await getJson('/api/notificaciones');
        if (r?.status !== 'ok') return;
        items.value = r.items || [];
        count.value = r.count || 0;
    } catch (e) {
        /* silencioso: la campanita no debe romper la app */
    }
};

const marcarLeidas = async (id = null) => {
    try {
        await fetch('/api/notificaciones/marcar-leidas', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf(),
            },
            body: JSON.stringify(id ? { id } : {}),
        });
    } catch (e) { /* best-effort */ }
};

// --- Interacción -------------------------------------------------------------
const toggle = async () => {
    abierto.value = !abierto.value;
    if (abierto.value && count.value > 0) {
        await marcarLeidas();      // marca en BD, apaga el badge
        count.value = 0;           // pero el resaltado queda hasta cerrar
    } else if (!abierto.value) {
        quitarResaltado();
    }
};

const cerrar = () => {
    if (!abierto.value) return;
    abierto.value = false;
    quitarResaltado();
};

const quitarResaltado = () => {
    items.value = items.value.map((n) => ({ ...n, leido: 1 }));
};

const onItem = (n) => {
    cerrar();
    if (n.url && n.url !== '#') router.visit(n.url);
};

// --- Tiempo real (WebSocket Go) + fallback polling ---------------------------
const conectarWs = async () => {
    if (ws && (ws.readyState === WebSocket.OPEN || ws.readyState === WebSocket.CONNECTING)) return;

    let token, wsUrl;
    try {
        const r = await getJson('/api/notificaciones/token');
        if (r?.status !== 'ok' || !r.token || !r.ws_url) {
            iniciarPolling();   // sin Go configurado: caemos a polling
            return;
        }
        token = r.token;
        wsUrl = r.ws_url;
    } catch (e) {
        iniciarPolling();
        return;
    }

    try {
        ws = new WebSocket(wsUrl + '?token=' + encodeURIComponent(token));
        ws.onopen = () => { reintento = 0; };
        ws.onmessage = (ev) => {
            try {
                const data = JSON.parse(ev.data);
                if (data?.tipo === 'notif') cargar();
            } catch (e) { /* mensaje no-JSON: ignorar */ }
        };
        ws.onclose = () => { ws = null; reconectar(); };
        ws.onerror = () => { try { ws.close(); } catch (e) {} };
    } catch (e) {
        reconectar();
    }
};

const reconectar = () => {
    const espera = Math.min(30000, 1000 * 2 ** reintento++);
    setTimeout(conectarWs, espera);
};

const iniciarPolling = () => {
    if (!pollTimer) pollTimer = setInterval(cargar, 20000);
};

onMounted(() => {
    cargar();
    conectarWs();
});

onUnmounted(() => {
    if (pollTimer) clearInterval(pollTimer);
    if (ws) { try { ws.close(); } catch (e) {} }
});
</script>

<template>
    <div class="relative">
        <button class="relative p-2 rounded-lg hover:bg-slate-100 text-slate-600" @click="toggle">
            <i class="pi pi-bell text-lg"></i>
            <span v-if="count > 0"
                  class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 rounded-full bg-red-500 text-white text-[10px] font-bold flex items-center justify-center">
                {{ count > 99 ? '99+' : count }}
            </span>
        </button>

        <!-- backdrop para cerrar al hacer clic fuera -->
        <div v-if="abierto" class="fixed inset-0 z-40" @click="cerrar"></div>

        <div v-if="abierto"
             class="absolute right-0 mt-2 w-80 bg-white rounded-xl shadow-xl border border-slate-200 z-50 overflow-hidden">
            <div class="px-4 py-2.5 border-b border-slate-100 flex items-center justify-between">
                <span class="text-sm font-semibold text-slate-700">Notificaciones</span>
                <button v-if="items.length" class="text-xs text-blue-600 hover:underline"
                        @click.stop="marcarLeidas().then(() => { count = 0; quitarResaltado(); })">
                    Marcar leídas
                </button>
            </div>

            <div class="max-h-80 overflow-y-auto">
                <div v-if="!items.length" class="px-4 py-6 text-sm text-slate-400 text-center">
                    Sin notificaciones.
                </div>
                <button v-for="n in items" :key="n.id" type="button"
                        class="w-full text-left px-4 py-2.5 border-b border-slate-50 hover:bg-slate-50 transition"
                        :style="{ borderLeft: '3px solid ' + (n.leido ? 'transparent' : '#22c55e') }"
                        @click="onItem(n)">
                    <div class="text-sm text-slate-700" :class="{ 'font-semibold': !n.leido }">{{ n.titulo }}</div>
                    <div class="text-xs text-slate-500 mt-0.5">{{ n.mensaje }}</div>
                    <div class="text-[11px] text-slate-400 text-right mt-1">{{ n.fecha }}</div>
                </button>
            </div>
        </div>
    </div>
</template>
