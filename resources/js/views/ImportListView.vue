<template>
    <div class="max-w-6xl mx-auto py-6 sm:py-8 px-3 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold text-neutral-900 tracking-tight">Historia importów</h1>
                <p class="mt-1 text-sm text-neutral-600">Zestawienie wszystkich zrealizowanych importów plików transakcyjnych.</p>
            </div>
            <router-link
                to="/"
                class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-orange-700 text-white font-medium text-sm hover:bg-orange-800 shadow-xs transition shrink-0"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                <span>Nowy import</span>
            </router-link>
        </div>

        <!-- Error Alert -->
        <div v-if="error" class="mb-6 p-4 rounded-lg bg-rose-50 border border-rose-200 text-rose-800 text-sm flex items-center justify-between">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-rose-500 shrink-0" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                </svg>
                <span>{{ error }}</span>
            </div>
            <button
                @click="loadImports"
                class="text-xs font-semibold text-rose-800 underline hover:text-rose-900 cursor-pointer"
            >
                Spróbuj ponownie
            </button>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="bg-white rounded-xl shadow-xs border border-neutral-200 p-12 text-center">
            <svg class="animate-spin h-8 w-8 text-orange-600 mx-auto" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p class="mt-3 text-sm text-neutral-500">Pobieranie historii importów...</p>
        </div>

        <!-- Empty State -->
        <div v-else-if="!imports.length" class="bg-white rounded-xl shadow-xs border border-neutral-200 p-12 text-center">
            <div class="w-12 h-12 rounded-full bg-neutral-100 text-neutral-400 flex items-center justify-center mx-auto mb-3">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m8.25 3v6.75m0 0l-3-3m3 3l3-3M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                </svg>
            </div>
            <h3 class="text-base font-semibold text-neutral-800">Brak historii importów</h3>
            <p class="mt-1 text-sm text-neutral-500 max-w-sm mx-auto">Nie zaimportowano jeszcze żadnych plików. Rozpocznij od wgrania pierwszego pliku CSV, JSON lub XML.</p>
            <router-link
                to="/"
                class="mt-5 inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-orange-700 text-white text-sm font-medium hover:bg-orange-800 transition"
            >
                Wgraj pierwszy plik
            </router-link>
        </div>

        <!-- Table View -->
        <div v-else class="bg-white rounded-xl shadow-xs border border-neutral-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-neutral-600 min-w-[580px]">
                    <thead class="bg-neutral-50 border-b border-neutral-200 text-xs font-semibold text-neutral-500 uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-3.5">Plik</th>
                            <th class="px-6 py-3.5">Data importu</th>
                            <th class="px-6 py-3.5">Status</th>
                            <th class="px-6 py-3.5 text-right">Wszystkie</th>
                            <th class="px-6 py-3.5 text-right text-emerald-700">Poprawne</th>
                            <th class="px-6 py-3.5 text-right text-rose-700">Błędne</th>
                            <th class="px-6 py-3.5 text-right">Akcja</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        <tr
                            v-for="item in imports"
                            :key="item.id"
                            class="hover:bg-neutral-50/75 transition"
                        >
                            <td class="px-6 py-4 font-medium text-neutral-900">
                                <span class="block truncate max-w-xs" :title="item.file_name">{{ item.file_name }}</span>
                            </td>
                            <td class="px-6 py-4 text-neutral-500 whitespace-nowrap">
                                {{ formatDate(item.created_at) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <StatusBadge :status="item.status" />
                            </td>
                            <td class="px-6 py-4 text-right font-medium text-neutral-800">
                                {{ item.total_records }}
                            </td>
                            <td class="px-6 py-4 text-right font-semibold text-emerald-600">
                                {{ item.successful_records }}
                            </td>
                            <td class="px-6 py-4 text-right font-semibold text-rose-600">
                                {{ item.failed_records }}
                            </td>
                            <td class="px-6 py-4 text-right whitespace-nowrap">
                                <router-link
                                    :to="`/imports/${item.id}`"
                                    class="inline-flex items-center gap-1 text-xs font-semibold text-orange-700 hover:text-orange-900 transition px-2.5 py-1.5 rounded-md hover:bg-orange-50"
                                >
                                    <span>Szczegóły</span>
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                                    </svg>
                                </router-link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useImports } from '../composables/useImports';
import { formatDate } from '../utils/formatDate';
import StatusBadge from '../components/StatusBadge.vue';

const imports = ref([]);
const { loading, error, fetchImports } = useImports();

const loadImports = async () => {
    try {
        imports.value = await fetchImports();
    } catch {
        // handled in composable
    }
};

onMounted(() => {
    loadImports();
});
</script>
