<script setup>
import { computed, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import Toast from 'primevue/toast';
import Button from 'primevue/button';
import NotificacionesBell from '@/components/NotificacionesBell.vue';

const page = usePage();
const toast = useToast();

const user = computed(() => page.props.auth.user);
const empresaColor = computed(() => user.value?.empresa_color || '#2563eb');

const nav = [
    {
        seccion: null,
        items: [
            { label: 'Inicio', href: '/',       icon: 'pi pi-home' },
            { label: 'Ventas', href: '/ventas', icon: 'pi pi-shopping-cart' },
            { label: 'Caja',   href: '/caja',   icon: 'pi pi-wallet' },
            { label: 'Eventos', href: '/eventos', icon: 'pi pi-bolt' },
        ],
    },
    {
        seccion: 'Mantenedores',
        items: [
            { label: 'Productos',          href: '/productos',         icon: 'pi pi-box' },
            { label: 'Familias',           href: '/familias',          icon: 'pi pi-sitemap' },
            { label: 'Unidades de medida', href: '/unidades',          icon: 'pi pi-tags' },
            { label: 'Tipos de unidad',    href: '/unidad-tipos',      icon: 'pi pi-list' },
            { label: 'Clientes',           href: '/clientes',          icon: 'pi pi-users' },
            { label: 'Tipos de egreso',    href: '/caja-egreso-tipos', icon: 'pi pi-arrow-up-right' },
        ],
    },
];

const isActive = (href) =>
    href === '/' ? page.url === '/' : (page.url === href || page.url.startsWith(href + '/'));

// ---- Flash -> toast ----
watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.success) toast.add({ severity: 'success', summary: 'OK', detail: flash.success, life: 3500 });
        if (flash?.error) toast.add({ severity: 'error', summary: 'Error', detail: flash.error, life: 4500 });
    },
    { deep: true, immediate: true }
);

const logout = () => router.post('/logout');
</script>

<template>
    <div class="min-h-screen flex">
        <Toast position="top-right" />

        <!-- Sidebar -->
        <aside class="w-64 shrink-0 text-slate-200 flex flex-col"
               style="background: var(--kd-sidebar);">
            <div class="h-16 flex items-center gap-3 px-5 border-b border-white/10">
                <div class="w-9 h-9 rounded-lg flex items-center justify-center font-bold text-white"
                     :style="{ background: empresaColor }">K</div>
                <div>
                    <div class="font-semibold leading-tight">KUDOS</div>
                    <div class="text-[11px] text-slate-400">Demo ERP</div>
                </div>
            </div>

            <nav class="flex-1 p-3 space-y-1 overflow-y-auto">
                <template v-for="(grupo, gi) in nav" :key="gi">
                    <div v-if="grupo.seccion"
                         class="px-3 pt-4 pb-1 text-[10px] font-semibold uppercase tracking-wider text-slate-500">
                        {{ grupo.seccion }}
                    </div>
                    <Link v-for="item in grupo.items" :key="item.href" :href="item.href"
                          class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-colors"
                          :class="isActive(item.href)
                            ? 'bg-white/10 text-white font-medium'
                            : 'text-slate-300 hover:bg-white/5 hover:text-white'">
                        <i :class="item.icon" class="text-base"></i>
                        {{ item.label }}
                    </Link>
                </template>
            </nav>

            <div class="p-4 border-t border-white/10 text-xs text-slate-400">
                Multi-empresa · Demo portafolio
            </div>
        </aside>

        <!-- Main -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Topbar -->
            <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-6">
                <div class="flex items-center gap-2 text-sm text-slate-500">
                    <i class="pi pi-building" :style="{ color: empresaColor }"></i>
                    <span class="font-medium text-slate-700">{{ user?.empresa }}</span>
                </div>

                <div class="flex items-center gap-4">
                    <!-- Notificaciones (campanita real con WebSocket + polling) -->
                    <NotificacionesBell />

                    <div class="flex items-center gap-3 pl-4 border-l border-slate-200">
                        <div class="text-right leading-tight">
                            <div class="text-sm font-medium text-slate-700">{{ user?.name }}</div>
                            <div class="text-xs text-slate-400">{{ user?.email }}</div>
                        </div>
                        <Button icon="pi pi-sign-out" severity="secondary" text rounded
                                @click="logout" v-tooltip.bottom="'Salir'" />
                    </div>
                </div>
            </header>

            <!-- Page content -->
            <main class="flex-1 px-6 pb-6 pt-3 overflow-auto">
                <slot />
            </main>
        </div>
    </div>
</template>
