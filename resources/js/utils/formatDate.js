export function formatDate(isoString) {
    if (!isoString) return '-';

    return new Date(isoString).toLocaleString('pl-PL', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    });
}
