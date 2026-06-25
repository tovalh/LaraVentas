<script setup>
/**
 * Botonera fija del módulo. Es solo el contenedor: los botones (Nuevo, Guardar,
 * Eliminar, etc.) se pasan por slot. Centraliza el look de la barra de acciones
 * para que ningún módulo la arme a mano (igual que en el ERP original).
 */
</script>

<template>
    <div class="flex items-center gap-2">
        <slot />
    </div>
</template>
