<template>
    <div class="max-w-6xl mx-auto py-6 sm:py-8 px-3 sm:px-6 lg:px-8">
        <!-- Breadcrumb / Back Link -->
        <div class="mb-6 flex items-center justify-between">
            <router-link
                to="/imports"
                class="inline-flex items-center gap-1.5 text-sm font-medium text-neutral-500 hover:text-neutral-800 transition"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
                <span>Wróć do listy importów</span>
            </router-link>

            <router-link
                to="/"
                class="text-xs font-semibold text-orange-700 hover:text-orange-900 transition"
            >
                + Nowy import
            </router-link>
        </div>

        <!-- Error State -->
        <div v-if="error" class="bg-white rounded-xl shadow-xs border border-neutral-200 p-8 text-center max-w-lg mx-auto">
            <div class="w-12 h-12 rounded-full bg-rose-50 text-rose-600 flex items-center justify-center mx-auto mb-3">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                </svg>
            </div>
            <h2 class="text-lg font-bold text-neutral-900">{{ error }}</h2>
            <router-link
                to="/imports"
                class="mt-4 inline-block px-4 py-2 rounded-lg bg-orange-700 text-white text-sm font-medium hover:bg-orange-800 transition"
            >
                Wróć do historii
            </router-link>
        </div>

        <!-- Loading State -->
        <div v-else-if="loading" class="bg-white rounded-xl shadow-xs border border-neutral-200 p-12 text-center">
            <svg class="animate-spin h-8 w-8 text-orange-600 mx-auto" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p class="mt-3 text-sm text-neutral-500">Pobieranie szczegółów importu...</p>
        </div>

        <!-- Detail View -->
        <div v-else-if="importItem" class="space-y-8">
            <!-- Summary Card -->
            <div class="bg-white rounded-xl shadow-xs border border-neutral-200 p-5 sm:p-8">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-6 border-b border-neutral-100">
                    <div>
                        <div class="flex items-center gap-3 flex-wrap">
                            <h1 class="text-lg sm:text-2xl font-bold text-neutral-900 tracking-tight break-all">{{ importItem.file_name }}</h1>
                            <StatusBadge :status="importItem.status" class="shrink-0" />
                        </div>
                        <p class="text-xs text-neutral-500 mt-1.5">
                            Zaimportowano: <span class="font-medium text-neutral-700">{{ formatDate(importItem.created_at) }}</span> &bull; ID: #{{ importItem.id }}
                        </p>
                    </div>
                </div>

                <!-- Stats Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-6">
                    <div class="bg-neutral-50 rounded-lg p-4 border border-neutral-100">
                        <span class="text-xs font-medium text-neutral-500 uppercase tracking-wider block">Łącznie rekordów</span>
                        <span class="text-2xl font-bold text-neutral-900 mt-1 block">{{ importItem.total_records }}</span>
                    </div>
                    <div class="bg-emerald-50 rounded-lg p-4 border border-emerald-100">
                        <span class="text-xs font-medium text-emerald-700 uppercase tracking-wider block">Poprawnie zapisane</span>
                        <span class="text-2xl font-bold text-emerald-700 mt-1 block">{{ importItem.successful_records }}</span>
                    </div>
                    <div class="bg-rose-50 rounded-lg p-4 border border-rose-100">
                        <span class="text-xs font-medium text-rose-700 uppercase tracking-wider block">Odrzucone (błędy)</span>
                        <span class="text-2xl font-bold text-rose-700 mt-1 block">{{ importItem.failed_records }}</span>
                    </div>
                </div>
            </div>

            <!-- Error Logs Section -->
            <div class="bg-white rounded-xl shadow-xs border border-neutral-200 overflow-hidden">
                <div class="p-4 sm:p-6 border-b border-neutral-100 flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-base sm:text-lg font-bold text-neutral-900">Dziennik błędów importu</h2>
                        <p class="text-xs text-neutral-500 mt-0.5">Wykaz rekordów, które nie spełniły reguł walidacji biznesowej.</p>
                    </div>
                    <span
                        v-if="logs.length"
                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 ring-1 ring-rose-600/20 whitespace-nowrap shrink-0"
                    >
                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                        {{ formatErrorCount(logs.length) }}
                    </span>
                </div>

                <!-- Case 1: No errors (Clean Import) -->
                <div v-if="!logs.length" class="p-12 text-center">
                    <div class="w-12 h-12 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                    </div>
                    <h3 class="text-base font-semibold text-neutral-800">Brak błędów w imporcie</h3>
                    <p class="text-sm text-neutral-500 mt-1 max-w-md mx-auto">
                        Wszystkie transakcje z tego pliku przeszły walidację pomyślnie i zostały zapisane w bazie danych.
                    </p>
                </div>

                <!-- Case 2: Error logs table -->
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-neutral-600 min-w-[500px]">
                        <thead class="bg-neutral-50 border-b border-neutral-200 text-xs font-semibold text-neutral-500 uppercase tracking-wider">
                            <tr>
                                <th class="px-6 py-3.5 whitespace-nowrap">ID transakcji</th>
                                <th class="px-6 py-3.5">Powód błędu</th>
                                <th class="px-6 py-3.5 whitespace-nowrap text-right">Data rejestracji</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-100">
                            <tr
                                v-for="log in logs"
                                :key="log.id"
                                class="hover:bg-neutral-50/75 transition"
                            >
                                <td class="px-6 py-4 font-mono text-xs font-medium text-neutral-900 whitespace-nowrap">
                                    {{ log.transaction_id || '(brak ID)' }}
                                </td>
                                <td class="px-6 py-4 text-rose-700 font-medium">
                                    <span class="inline-block px-2 py-0.5 rounded bg-rose-50 border border-rose-200/60 text-xs">
                                        {{ log.error_message }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-neutral-500 text-xs text-right whitespace-nowrap">
                                    {{ formatDate(log.created_at) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useImports } from '../composables/useImports';
import { formatDate } from '../utils/formatDate';
import StatusBadge from '../components/StatusBadge.vue';

const props = defineProps({
    id: {
        type: [String, Number],
        required: true,
    },
});

const importItem = ref(null);
const { loading, error, fetchImport } = useImports();

const logs = computed(() => importItem.value?.logs || []);

const formatErrorCount = (count) => {
    if (count === 1) return '1 błąd';
    const lastDigit = count % 10;
    const lastTwoDigits = count % 100;
    if (lastDigit >= 2 && lastDigit <= 4 && (lastTwoDigits < 12 || lastTwoDigits > 14)) {
        return `${count} błędy`;
    }
    return `${count} błędów`;
};

const loadImportDetails = async () => {
    try {
        importItem.value = await fetchImport(props.id);
    } catch {
        // handled in composable
    }
};

onMounted(() => {
    loadImportDetails();
});
</script>
