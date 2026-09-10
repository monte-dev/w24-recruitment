<template>
    <div class="max-w-3xl mx-auto py-6 sm:py-8 px-3 sm:px-6">
        <!-- Page Header -->
        <div class="mb-8 text-center sm:text-left">
            <h1 class="text-2xl sm:text-3xl font-bold text-neutral-900 tracking-tight">Import transakcji bankowych</h1>
            <p class="mt-2 text-sm text-neutral-600">
                Wybierz lub przeciągnij plik z transakcjami w formacie <span class="font-semibold text-neutral-800">CSV, JSON</span> lub <span class="font-semibold text-neutral-800">XML</span> (maks. 10 MB).
            </p>
        </div>

        <!-- Result Card (shown after successful or partial import) -->
        <div v-if="importResult" class="bg-white rounded-xl shadow-xs border border-neutral-200 p-6 mb-8">
            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4 pb-4 border-b border-neutral-100">
                <div>
                    <div class="flex items-center gap-3 flex-wrap">
                        <h2 class="text-lg font-semibold text-neutral-900">Wynik importu</h2>
                        <StatusBadge :status="importResult.status" class="shrink-0" />
                    </div>
                    <p class="text-sm text-neutral-500 mt-1">Plik: <span class="font-medium text-neutral-700 break-all">{{ importResult.file_name }}</span></p>
                </div>
                <button
                    @click="resetForm"
                    type="button"
                    class="self-start sm:self-auto text-xs font-medium text-neutral-500 hover:text-neutral-800 px-2.5 py-1.5 rounded-md hover:bg-neutral-100 transition cursor-pointer"
                >
                    Wgraj kolejny plik
                </button>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-3 gap-4 my-6">
                <div class="bg-neutral-50 rounded-lg p-4 text-center border border-neutral-100">
                    <span class="text-xs font-medium text-neutral-500 uppercase tracking-wider block">Wszystkie</span>
                    <span class="text-2xl font-bold text-neutral-900 mt-1 block">{{ importResult.total_records }}</span>
                </div>
                <div class="bg-emerald-50 rounded-lg p-4 text-center border border-emerald-100">
                    <span class="text-xs font-medium text-emerald-700 uppercase tracking-wider block">Poprawne</span>
                    <span class="text-2xl font-bold text-emerald-700 mt-1 block">{{ importResult.successful_records }}</span>
                </div>
                <div class="bg-rose-50 rounded-lg p-4 text-center border border-rose-100">
                    <span class="text-xs font-medium text-rose-700 uppercase tracking-wider block">Błędne</span>
                    <span class="text-2xl font-bold text-rose-700 mt-1 block">{{ importResult.failed_records }}</span>
                </div>
            </div>

            <!-- Action links -->
            <div class="flex flex-col sm:flex-row items-center justify-end gap-3 pt-2">
                <router-link
                    to="/imports"
                    class="w-full sm:w-auto text-center px-4 py-2.5 rounded-lg border border-neutral-300 text-sm font-medium text-neutral-700 hover:bg-neutral-50 transition"
                >
                    Przejdź do historii importów
                </router-link>
                <router-link
                    :to="`/imports/${importResult.id}`"
                    class="w-full sm:w-auto text-center px-4 py-2.5 rounded-lg bg-orange-700 text-sm font-medium text-white hover:bg-orange-800 shadow-xs transition"
                >
                    <span v-if="importResult.failed_records > 0">Zobacz szczegóły i logi błędów ({{ importResult.failed_records }})</span>
                    <span v-else>Zobacz szczegóły importu</span>
                </router-link>
            </div>
        </div>

        <!-- Upload Form -->
        <div v-else class="bg-white rounded-xl shadow-xs border border-neutral-200 p-6 sm:p-8">
            <!-- Error Alert -->
            <div v-if="error" class="mb-6 p-4 rounded-lg bg-rose-50 border border-rose-200 text-rose-800 text-sm flex items-start gap-3">
                <svg class="w-5 h-5 text-rose-500 shrink-0 mt-0.5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                </svg>
                <div class="flex-1">
                    <p class="font-medium">{{ error }}</p>
                    <ul v-if="validationErrors.file" class="mt-1 list-disc list-inside text-rose-700 text-xs">
                        <li v-for="(err, idx) in validationErrors.file" :key="idx">{{ err }}</li>
                    </ul>
                </div>
            </div>

            <!-- Drag & Drop Zone -->
            <div
                @dragover.prevent="isDragging = true"
                @dragleave.prevent="isDragging = false"
                @drop.prevent="handleDrop"
                :class="[
                    'relative border-2 border-dashed rounded-xl p-8 sm:p-12 text-center transition cursor-pointer',
                    isDragging ? 'border-orange-500 bg-orange-50/50' : 'border-neutral-300 hover:border-neutral-400 bg-neutral-50/50 hover:bg-neutral-50',
                    selectedFile ? 'border-orange-400 bg-orange-50/20' : ''
                ]"
                @click="triggerFileInput"
            >
                <input
                    ref="fileInput"
                    type="file"
                    accept=".csv,.json,.xml,text/csv,application/json,application/xml,text/xml"
                    class="hidden"
                    @change="handleFileSelect"
                />

                <!-- State: File Selected -->
                <div v-if="selectedFile" class="flex flex-col items-center">
                    <div class="w-14 h-14 rounded-full bg-orange-100 text-orange-600 flex items-center justify-center mb-3">
                        <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                        </svg>
                    </div>
                    <span class="text-base font-semibold text-neutral-900 block truncate max-w-md">{{ selectedFile.name }}</span>
                    <span class="text-xs text-neutral-500 mt-1 block">{{ formatFileSize(selectedFile.size) }}</span>
                    <button
                        @click.stop="clearFile"
                        type="button"
                        class="mt-3 text-xs font-medium text-neutral-500 hover:text-neutral-800 px-2.5 py-1.5 rounded-md hover:bg-neutral-100 transition cursor-pointer"
                    >
                        Usuń i wybierz inny plik
                    </button>
                </div>

                <!-- State: Empty Dropzone -->
                <div v-else class="flex flex-col items-center">
                    <div class="w-14 h-14 rounded-full bg-neutral-100 text-neutral-400 flex items-center justify-center mb-3">
                        <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                        </svg>
                    </div>
                    <p class="text-sm font-medium text-neutral-700">
                        <span class="text-orange-600 font-semibold hover:underline">Kliknij, aby wybrać</span> lub przeciągnij plik tutaj
                    </p>
                    <p class="text-xs text-neutral-400 mt-1">Obsługiwane formaty: .csv, .json, .xml (do 10 MB)</p>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="mt-6 flex justify-end">
                <button
                    @click="handleSubmit"
                    :disabled="!selectedFile || loading"
                    type="button"
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3 rounded-lg bg-orange-700 text-white font-medium text-sm hover:bg-orange-800 focus:ring-4 focus:ring-orange-200 disabled:opacity-50 disabled:cursor-not-allowed transition shadow-xs cursor-pointer"
                >
                    <svg v-if="loading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>{{ loading ? 'Przetwarzanie pliku...' : 'Rozpocznij import' }}</span>
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { useImports } from '../composables/useImports';
import StatusBadge from '../components/StatusBadge.vue';

const fileInput = ref(null);
const selectedFile = ref(null);
const isDragging = ref(false);
const importResult = ref(null);

const { loading, error, validationErrors, uploadImport, clearErrors } = useImports();

const triggerFileInput = () => {
    fileInput.value?.click();
};

const handleFileSelect = (event) => {
    const file = event.target.files?.[0];
    if (file) {
        setFile(file);
    }
};

const handleDrop = (event) => {
    isDragging.value = false;
    const file = event.dataTransfer?.files?.[0];
    if (file) {
        setFile(file);
    }
};

const setFile = (file) => {
    clearErrors();
    importResult.value = null;
    selectedFile.value = file;
};

const clearFile = () => {
    selectedFile.value = null;
    clearErrors();
    if (fileInput.value) {
        fileInput.value.value = '';
    }
};

const resetForm = () => {
    clearFile();
    importResult.value = null;
};

const formatFileSize = (bytes) => {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

const handleSubmit = async () => {
    if (!selectedFile.value) return;

    try {
        const result = await uploadImport(selectedFile.value);
        importResult.value = result;
    } catch {
        // error handling managed inside useImports composable
    }
};
</script>
