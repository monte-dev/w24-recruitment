<template>
    <div class="min-h-screen flex flex-col bg-neutral-50 text-neutral-900 antialiased font-sans w-full max-w-full overflow-x-hidden">
        <AppNavbar />

        <main class="flex-1 w-full max-w-full">
            <router-view />
        </main>

        <footer class="bg-white border-t border-neutral-200 py-6 mt-12 text-center text-xs text-neutral-500">
            <div class="max-w-6xl mx-auto px-4">
                <p>System importu transakcji bankowych &bull; Zadanie rekrutacyjne</p>
            </div>
        </footer>
    </div>
</template>

<script setup>
import AppNavbar from './components/AppNavbar.vue';
</script>
