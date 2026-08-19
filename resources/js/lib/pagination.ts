/**
 * Laravel's paginator labels arrive as HTML entities ("&laquo; Previous").
 * Rendering them with v-html would be needless, so they are decoded to text.
 */
export function paginationLabel(label: string): string {
    return label
        .replace(/&laquo;/g, '«')
        .replace(/&raquo;/g, '»')
        .replace(/&nbsp;/g, ' ')
        .replace(/&amp;/g, '&')
        .trim();
}
