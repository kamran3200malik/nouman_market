import { storageUrl } from '@/Utils/storage';

export function renderMarkdown(content) {
    if (!content || typeof content !== 'string') return '';

    // 0. Normalize newlines (handle Windows CRLF \r\n)
    let text = content.replace(/\r\n/g, '\n').replace(/\r/g, '\n');

    // 1. Markdown Images: ![alt](url) - with flexible spacing
    text = text.replace(/!\[(.*?)\]\(\s*([^)]+?)\s*\)/g, (match, alt, url) => {
        const cleanUrl = url.trim().replace(/^["']|["']$/g, '');
        const resolvedUrl = storageUrl(cleanUrl);
        const altText = (alt || '').trim();
        const hasCaption = altText && altText.toLowerCase() !== 'beauty image' && altText.length > 0;
        const captionHtml = hasCaption
            ? `<figcaption class="py-2 px-3 text-center text-xs text-slate-500 italic bg-rose-50/60">${escapeHtml(altText)}</figcaption>`
            : '';

        return `<figure class="my-6 rounded-2xl overflow-hidden shadow-sm border border-rose-100 bg-white">
            <img src="${resolvedUrl}" alt="${escapeHtml(altText || 'Beauty Article Photo')}" class="w-full max-h-[550px] object-cover rounded-t-2xl" loading="lazy" />
            ${captionHtml}
        </figure>`;
    });

    // 2. Standard HTML <img> tags if pasted
    text = text.replace(/<img\s+([^>]*?)src=["'](.*?)["']([^>]*?)>/gi, (match, before, src, after) => {
        const resolvedSrc = storageUrl(src.trim());
        return `<img ${before}src="${resolvedSrc}"${after} class="w-full max-h-[550px] object-cover rounded-2xl my-5 border border-rose-100 shadow-sm" loading="lazy">`;
    });

    // 3. Markdown Links: [text](url)
    text = text.replace(/\[(.*?)\]\(\s*([^)]+?)\s*\)/g, (match, label, url) => {
        return `<a href="${url.trim()}" target="_blank" rel="noopener noreferrer" class="text-rose-600 hover:text-rose-700 underline font-semibold">${escapeHtml(label)}</a>`;
    });

    // 4. Headings
    text = text.replace(/^### (.*$)/gim, '<h3 class="text-xl font-serif font-bold text-slate-900 mt-6 mb-3">$1</h3>');
    text = text.replace(/^## (.*$)/gim, '<h2 class="text-2xl font-serif font-bold text-slate-900 mt-8 mb-4 border-b border-rose-100 pb-2 text-slate-900">$1</h2>');
    text = text.replace(/^# (.*$)/gim, '<h1 class="text-3xl font-serif font-bold text-slate-900 mt-8 mb-4">$1</h1>');

    // 5. Blockquotes: > quote
    text = text.replace(/^\> (.*$)/gim, '<blockquote class="p-4 my-4 rounded-2xl bg-gradient-to-r from-rose-50 to-pink-50 border-l-4 border-rose-500 text-slate-700 italic font-serif text-sm leading-relaxed">$1</blockquote>');

    // 6. Bold & Italic
    text = text.replace(/\*\*(.*?)\*\*/g, '<strong class="font-bold text-slate-900">$1</strong>');
    text = text.replace(/\*(.*?)\*/g, '<em class="italic text-slate-800">$1</em>');

    // 7. Unordered lists: - item
    text = text.replace(/^\- (.*$)/gim, '<li class="ml-4 list-disc text-slate-700 leading-relaxed">$1</li>');

    // 8. Numbered lists: 1. item
    text = text.replace(/^\d+\. (.*$)/gim, '<li class="ml-4 list-decimal text-slate-700 leading-relaxed">$1</li>');

    // 9. Horizontal rules: ---
    text = text.replace(/^---$/gim, '<hr class="my-6 border-rose-100" />');

    // 10. Split by double newlines into clean paragraph blocks
    const blocks = text.split(/\n\n+/);
    const htmlBlocks = blocks.map((block) => {
        const trimmed = block.trim();
        if (!trimmed) return '';
        if (
            trimmed.startsWith('<h1') ||
            trimmed.startsWith('<h2') ||
            trimmed.startsWith('<h3') ||
            trimmed.startsWith('<figure') ||
            trimmed.startsWith('<img') ||
            trimmed.startsWith('<blockquote') ||
            trimmed.startsWith('<li') ||
            trimmed.startsWith('<hr')
        ) {
            return trimmed;
        }
        return `<p class="text-slate-700 leading-relaxed my-3 text-sm sm:text-base">${trimmed.replace(/\n/g, '<br>')}</p>`;
    });

    return htmlBlocks.join('\n');
}

function escapeHtml(str) {
    if (!str) return '';
    return str
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
