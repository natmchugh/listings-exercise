<script setup>
import { Link, usePage } from '@inertiajs/vue3';

defineProps({
    heading: { type: String, required: true },
    subheading: { type: String, default: null },
});

const page = usePage();
</script>

<template>
    <div class="mx-auto max-w-5xl px-4 py-10">
        <header class="mb-8">
            <nav class="flex items-center gap-6 text-sm font-medium text-slate-500">
                <Link href="/" class="transition hover:text-slate-900">Street Listings</Link>
                <Link href="/saved-searches" class="transition hover:text-slate-900">
                    Saved searches
                </Link>
                <Link href="/alerts" class="flex items-center gap-1.5 transition hover:text-slate-900">
                    Alerts
                    <span
                        v-if="page.props.unreadAlertsCount > 0"
                        class="rounded-full bg-sky-600 px-2 py-0.5 text-xs font-semibold text-white"
                    >
                        {{ page.props.unreadAlertsCount }}
                    </span>
                </Link>
            </nav>
            <h1 class="mt-2 text-3xl font-bold tracking-tight">{{ heading }}</h1>
            <p v-if="subheading" class="mt-1 text-slate-500">{{ subheading }}</p>
        </header>

        <main>
            <slot />
        </main>
    </div>
</template>
