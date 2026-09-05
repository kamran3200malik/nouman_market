/**
 * Resolves a storage file path to its absolute or correctly base-prefixed URL.
 * Works seamlessly whether running on root domain, virtual host (e.g. beauty.test),
 * or subfolder (e.g. localhost/beauty/public or 192.168.x.x/beauty/public).
 *
 * @param {string|null|undefined} path - The relative storage path or URL
 * @param {string} fallback - Fallback URL if path is empty
 * @returns {string}
 */
export function storageUrl(path, fallback = '') {
    if (!path || typeof path !== 'string') return fallback;

    // Handle data/blob URLs
    if (path.startsWith('data:') || path.startsWith('blob:')) {
        return path;
    }

    // Determine base storage URL
    let baseStorage = '';
    if (typeof window !== 'undefined') {
        if (window.__STORAGE_URL__) {
            baseStorage = window.__STORAGE_URL__.replace(/\/+$/, '');
        } else if (window.Ziggy && window.Ziggy.url) {
            baseStorage = `${window.Ziggy.url.replace(/\/+$/, '')}/storage`;
        } else if (window.location) {
            const subfolderMatch = window.location.pathname.match(/^(\/[^\/]+\/public)/);
            if (subfolderMatch) {
                baseStorage = `${window.location.origin}${subfolderMatch[1]}/storage`;
            } else {
                baseStorage = `${window.location.origin}/storage`;
            }
        }
    }
    if (!baseStorage) {
        baseStorage = '/storage';
    }

    // Check for double-encoded or local absolute storage URLs
    // e.g. "http://192.168.100.2/storage/services/..." or "http://localhost/storage/..."
    const storageMatch = path.match(/(?:https?:\/\/[^\/]+)?(?:\/[^\/]+\/public)?\/storage\/(.+)$/i);
    if (storageMatch) {
        const cleanSubPath = storageMatch[1].replace(/^\/+/, '');
        // If the inner part itself is an external URL (e.g. storage/http://...), return that external URL
        if (cleanSubPath.startsWith('http://') || cleanSubPath.startsWith('https://')) {
            return cleanSubPath;
        }
        return `${baseStorage}/${cleanSubPath}`;
    }

    // If it's a genuine external URL (Unsplash, UI-avatars, Placehold, etc.), return as is
    if (path.startsWith('http://') || path.startsWith('https://')) {
        return path;
    }

    // Strip leading slashes and optional leading 'storage/'
    const cleanPath = path.replace(/^\/+/, '').replace(/^storage\//, '');
    return `${baseStorage}/${cleanPath}`;
}
