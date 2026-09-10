<template>
    <header class="bg-white border-b border-neutral-200 sticky top-0 z-30 shadow-xs w-full">
        <div class="max-w-6xl mx-auto px-3 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-14 sm:h-16 gap-2 sm:gap-4">
                <!-- Brand / Logo -->
                <router-link to="/" class="flex items-center gap-2 sm:gap-3 group shrink-0">
                    <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-lg bg-orange-700 flex items-center justify-center text-white shadow-sm group-hover:bg-orange-800 transition shrink-0">
                        <svg class="w-4 h-4 sm:w-6 sm:h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.5m-15 10.5V10.5M3 21h18M3 10.5h18" />
                        </svg>
                    </div>
                    <div class="flex flex-col justify-center">
                        <span class="font-bold text-neutral-900 text-sm sm:text-lg tracking-tight leading-tight whitespace-nowrap">Importer Transakcji</span>
                    </div>
                </router-link>

                <!-- Navigation Links -->
                <nav class="flex items-center gap-1 sm:gap-2 shrink-0">
                    <router-link
                        to="/"
                        class="px-2 sm:px-3.5 py-1.5 sm:py-2 rounded-lg text-xs sm:text-sm font-medium whitespace-nowrap transition cursor-pointer"
                        :class="route.name === 'import.upload' ? 'bg-orange-50 text-orange-700 font-semibold' : 'text-neutral-600 hover:text-neutral-900 hover:bg-neutral-100'"
                    >
                        <span>Nowy<span class="hidden sm:inline"> import</span></span>
                    </router-link>
                    <router-link
                        to="/imports"
                        class="px-2 sm:px-3.5 py-1.5 sm:py-2 rounded-lg text-xs sm:text-sm font-medium whitespace-nowrap transition cursor-pointer"
                        :class="route.name === 'import.list' || route.name === 'import.detail' ? 'bg-orange-50 text-orange-700 font-semibold' : 'text-neutral-600 hover:text-neutral-900 hover:bg-neutral-100'"
                    >
                        <span>Historia<span class="hidden sm:inline"> importów</span></span>
                    </router-link>
                </nav>
            </div>
        </div>
    </header>
</template>

<script setup>
import { useRoute } from 'vue-router';

const route = useRoute();
</script>
