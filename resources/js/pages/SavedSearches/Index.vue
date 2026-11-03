<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '../../components/AppLayout.vue';
import { formatPrice } from '../../format.js';

defineProps({
    savedSearches: { type: Array, required: true },
});

function summary(search) {
    return [
        search.property_type_label ?? 'Any type',
        search.max_price !== null ? `up to ${formatPrice(search.max_price)}` : null,
        search.min_bedrooms !== null ? `${search.min_bedrooms}+ beds` : null,
        search.region,
    ]
        .filter(Boolean)
        .join(' · ');
}

function resultsUrl(search) {
    return `/?${new URLSearchParams(search.criteria)}`;
}

function destroy(search) {
    router.delete(`/saved-searches/${search.id}`, { preserveScroll: true });
}
</script>

<template>
    <Head title="Saved searches" />

    <AppLayout heading="Saved searches" subheading="Searches you've saved from the listings page.">
        <ul v-if="savedSearches.length" class="divide-y divide-slate-200 rounded-xl border border-slate-200">
            <li
                v-for="search in savedSearches"
                :key="search.id"
                class="flex flex-wrap items-center justify-between gap-3 p-4"
            >
                <span class="font-medium">{{ summary(search) }}</span>

                <span class="flex items-center gap-4 text-sm">
                    <Link :href="resultsUrl(search)" class="text-slate-700 underline-offset-2 hover:underline">
                        View results
                    </Link>
                    <button
                        type="button"
                        class="text-red-600 underline-offset-2 hover:underline"
                        @click="destroy(search)"
                    >
                        Delete
                    </button>
                </span>
            </li>
        </ul>

        <p
            v-else
            class="rounded-xl border border-dashed border-slate-300 p-10 text-center text-slate-500"
        >
            No saved searches yet. Filter the
            <Link href="/" class="underline underline-offset-2">listings</Link>
            and choose “Save this search”.
        </p>
    </AppLayout>
</template>
