<script setup>
/**
 * Inicio / Dashboard — réplica de inicio.php: grilla de widgets (KPIs + gráfico +
 * mini-tablas + accesos rápidos). Montos en CLP.
 */
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { clp, entero, fecha } from '@/lib/format';

const props = defineProps({
    kpis: Object,
    grafico: Array,
    stockBajo: Array,
    ultimasVentas: Array,
});

const page = usePage();
const user = computed(() => page.props.auth.user);
const hoy = new Date().toLocaleDateString('es-CL', { weekday: 'long', day: 'numeric', month: 'long' });

const maxGrafico = computed(() => Math.max(1, ...props.grafico.map((g) => g.total)));
// Altura de cada barra como % del alto fijo del contenedor.
const barAltura = (total) => `${Math.max(2, (total / maxGrafico.value) * 100)}%`;

const accesos = [
    { label: 'Nueva venta', href: '/ventas',   color: 'bg-emerald-500', icon: 'pi pi-shopping-cart' },
    { label: 'Productos',   href: '/productos', color: 'bg-indigo-500',  icon: 'pi pi-box' },
    { label: 'Clientes',    href: '/clientes',  color: 'bg-violet-500',  icon: 'pi pi-users' },
    { label: 'Familias',    href: '/familias',  color: 'bg-amber-500',   icon: 'pi pi-sitemap' },
    { label: 'Unidades',    href: '/unidades',  color: 'bg-sky-500',     icon: 'pi pi-tags' },
];
</script>

<template>
    <Head title="Inicio" />
    <AppLayout>
        <header class="mb-6">
            <h1 class="text-2xl font-semibold text-slate-800">Hola, {{ user?.name }}</h1>
            <p class="text-sm text-slate-500 mt-1 capitalize">{{ user?.empresa }} · {{ hoy }}</p>
        </header>

        <!-- Fila 1: KPIs -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Ventas de hoy</div>
                        <div class="text-2xl font-bold text-slate-800 mt-2">{{ clp(kpis.ventasHoy.monto) }}</div>
                        <div class="text-xs text-slate-400 mt-1">{{ kpis.ventasHoy.docs }} documento(s)</div>
                    </div>
                    <span class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <i class="pi pi-chart-line" />
                    </span>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Ventas del mes</div>
                        <div class="text-2xl font-bold text-slate-800 mt-2">{{ clp(kpis.ventasMes) }}</div>
                        <div class="text-xs text-slate-400 mt-1">Acumulado mensual</div>
                    </div>
                    <span class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                        <i class="pi pi-dollar" />
                    </span>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Stock crítico</div>
                        <div class="text-2xl font-bold text-slate-800 mt-2">{{ kpis.stockCritico }}</div>
                        <div class="text-xs text-slate-400 mt-1">Productos por reponer</div>
                    </div>
                    <span class="w-10 h-10 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center">
                        <i class="pi pi-exclamation-triangle" />
                    </span>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Clientes</div>
                        <div class="text-2xl font-bold text-slate-800 mt-2">{{ kpis.clientes }}</div>
                        <div class="text-xs text-slate-400 mt-1">Activos</div>
                    </div>
                    <span class="w-10 h-10 rounded-lg bg-violet-50 text-violet-600 flex items-center justify-center">
                        <i class="pi pi-users" />
                    </span>
                </div>
            </div>
        </div>

        <!-- Fila 2: gráfico ancho + stock bajo -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mt-4">
            <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-slate-200 p-5">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500 mb-4">Ventas últimos 14 días</h2>
                <!-- Barras: hijas directas de un contenedor con altura fija (h-44),
                     así el height en % se calcula correctamente. -->
                <div class="flex items-end gap-1.5 h-44">
                    <div v-for="g in grafico" :key="g.fecha"
                         class="flex-1 rounded-t bg-blue-500/80 hover:bg-blue-600 transition-all min-h-[2px]"
                         :style="{ height: barAltura(g.total) }"
                         v-tooltip.top="`${g.fecha}: ${clp(g.total)}`" />
                </div>
                <!-- Etiquetas alineadas con las barras -->
                <div class="flex gap-1.5 mt-1">
                    <span v-for="g in grafico" :key="g.fecha" class="flex-1 text-center text-[9px] text-slate-400">
                        {{ g.fecha }}
                    </span>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500 mb-4">Stock por reponer</h2>
                <div v-if="!stockBajo.length" class="text-sm text-slate-400 py-6 text-center">Todo en orden.</div>
                <ul v-else class="space-y-2.5">
                    <li v-for="p in stockBajo" :key="p.idgen_producto" class="flex items-center justify-between text-sm">
                        <span class="text-slate-600 truncate">{{ p.descripcion }}</span>
                        <span class="text-rose-600 font-semibold tabular-nums">{{ entero(p.stock) }}</span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Fila 3: últimas ventas -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 mt-4">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500 mb-3">Últimas ventas</h2>
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs text-slate-400 border-b border-slate-100">
                        <th class="py-2 font-medium">Número</th>
                        <th class="py-2 font-medium">Cliente</th>
                        <th class="py-2 font-medium">Fecha</th>
                        <th class="py-2 font-medium text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="v in ultimasVentas" :key="v.idven_venta" class="border-b border-slate-50 hover:bg-slate-50">
                        <td class="py-2 font-medium text-slate-700">{{ v.numero }}</td>
                        <td class="py-2 text-slate-600">{{ v.cliente?.razon_social || '—' }}</td>
                        <td class="py-2 text-slate-500">{{ fecha(v.fecha) }}</td>
                        <td class="py-2 text-right tabular-nums font-semibold text-slate-700">{{ clp(v.total) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Fila 4: accesos rápidos -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 mt-4">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500 mb-4">Accesos rápidos</h2>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
                <Link v-for="a in accesos" :key="a.href" :href="a.href"
                      class="group flex flex-col items-center justify-center p-4 rounded-lg border border-slate-200 hover:border-slate-300 hover:shadow-sm transition">
                    <span class="w-11 h-11 rounded-lg text-white flex items-center justify-center mb-2 group-hover:scale-105 transition"
                          :class="a.color">
                        <i :class="a.icon" class="text-lg" />
                    </span>
                    <span class="text-xs text-slate-700 text-center">{{ a.label }}</span>
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
