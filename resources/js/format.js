const priceFormatter = new Intl.NumberFormat('en-GB', {
    style: 'currency',
    currency: 'GBP',
    maximumFractionDigits: 0,
});

export function formatPrice(price) {
    return priceFormatter.format(price);
}

export function formatDate(iso) {
    if (!iso) {
        return null;
    }

    return new Intl.DateTimeFormat('en-GB', { dateStyle: 'long' }).format(new Date(iso));
}

export function searchSummary(search) {
    return [
        search.property_type_label ?? 'Any type',
        search.max_price !== null ? `up to ${formatPrice(search.max_price)}` : null,
        search.min_bedrooms !== null ? `${search.min_bedrooms}+ beds` : null,
        search.region,
    ]
        .filter(Boolean)
        .join(' · ');
}
