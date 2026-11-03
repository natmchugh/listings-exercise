<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '../../components/AppLayout.vue';
import Pagination from '../../components/Pagination.vue';
import { formatDate, formatPrice, searchSummary } from '../../format.js';

defineProps({
    alerts: { type: Object, required: true },
});
</script>

<template>
    <Head title="Alerts" />

    <AppLayout heading="Alerts" subheading="New listings that matched one of your saved searches.">
        <ul v-if="alerts.data.length" class="divide-y divide-slate-200 rounded-xl border border-slate-200">
            <li
                v-for="alert in alerts.data"
                :key="alert.id"
                class="flex flex-wrap items-start justify-between gap-3 p-4"
                :class="{ 'bg-sky-50': alert.is_unread, 'text-slate-400': !alert.listing_available }"
            >
                <div>
                    <p class="font-medium">
                        <span
                            v-if="alert.is_unread"
                            class="mr-2 rounded-full bg-sky-600 px-2 py-0.5 text-xs font-semibold text-white"
                        >
                            New
                        </span>
                        <Link
                            v-if="alert.listing_available"
                            :href="`/listings/${alert.listing.id}`"
                            class="underline-offset-2 hover:underline"
                        >
                            {{ alert.listing.address_line_1 }}, {{ alert.listing.city }}
                        </Link>
                        <span v-else>{{ alert.listing.address_line_1 }}, {{ alert.listing.city }}</span>
                    </p>
                    <p class="mt-1 text-sm">
                        {{ formatPrice(alert.listing.price) }} · {{ alert.listing.bedrooms }} beds ·
                        {{ alert.listing.property_type_label }}
                        <span v-if="!alert.listing_available"> · No longer available</span>
                    </p>
                    <p class="mt-1 text-sm text-slate-500">
                        <template v-if="alert.saved_search">
                            Matched your search: {{ searchSummary(alert.saved_search) }}
                        </template>
                        <template v-else>Matched a search you've since deleted</template>
                    </p>
                </div>

                <span class="text-sm text-slate-500">{{ formatDate(alert.created_at) }}</span>
            </li>
        </ul>

        <p
            v-else
            class="rounded-xl border border-dashed border-slate-300 p-10 text-center text-slate-500"
        >
            No alerts yet.
            <Link href="/saved-searches" class="underline underline-offset-2">Save a search</Link>
            and we'll tell you when something new matches.
        </p>

        <Pagination :meta="alerts.meta" :links="alerts.links" />
    </AppLayout>
</template>
