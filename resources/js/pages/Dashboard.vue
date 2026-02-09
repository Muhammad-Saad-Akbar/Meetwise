<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="bg-yellow-500 pt-10 pb-5 mt-14">
            <h4 class="text-xl text-center font-bold">Welcome Saad</h4>
        </div>
    </AppLayout>
<!--    <h1 class="text-red-500 text-2xl font-bold text-center">Saaad</h1>-->
</template>
