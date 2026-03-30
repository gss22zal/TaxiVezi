<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="min-h-screen flex flex-col items-center justify-center bg-[#0F172A] px-4">
        <!-- Logo -->
        <div class="mb-8 text-center">
            <Link href="/" class="flex items-center gap-3">
                <div class="flex h-14 w-14 items-center justify-center rounded-full bg-yellow-500">
                    <svg class="h-8 w-8 text-gray-900" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z"/>
                    </svg>
                </div>
            </Link>
            <div class="mt-4">
                <h1 class="text-2xl font-bold text-white">TaxiVezi</h1>
                <p class="text-sm text-red-500 font-medium">Система управления такси</p>
            </div>
        </div>

        <!-- Form Container -->
        <div class="w-full max-w-md rounded-xl border border-gray-700 bg-[#1F2937] p-8 shadow-xl">
            <slot />
        </div>

        <!-- Footer Links -->
        <div class="mt-6 text-center text-sm text-gray-500">
            <Link href="/" class="hover:text-white">Вернуться на главную</Link>
        </div>
    </div>
</template>
