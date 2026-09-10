<template>
    <span
        :class="config.badge"
        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold tracking-wide"
    >
        <span :class="config.dot" class="w-1.5 h-1.5 rounded-full"></span>
        {{ config.label }}
    </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    status: {
        type: String,
        required: true,
    },
});

const STATUS_CONFIG = {
    success: {
        label: 'Sukces',
        badge: 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20',
        dot: 'bg-emerald-500',
    },
    partial: {
        label: 'Częściowy',
        badge: 'bg-yellow-50 text-yellow-800 ring-1 ring-yellow-600/30',
        dot: 'bg-yellow-500',
    },
    failed: {
        label: 'Nieudany',
        badge: 'bg-rose-50 text-rose-700 ring-1 ring-rose-600/20',
        dot: 'bg-rose-500',
    },
};

const config = computed(() => STATUS_CONFIG[props.status] ?? {
    label: props.status,
    badge: 'bg-neutral-100 text-neutral-700 ring-1 ring-neutral-600/20',
    dot: 'bg-neutral-400',
});
</script>
