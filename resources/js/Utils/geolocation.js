// Helper to resolve application URLs with subfolder support (e.g. /beauty/public/...)
function resolveAppEndpoint(name, params = {}) {
    try {
        if (typeof route === 'function') {
            return route(name, params);
        }
        if (typeof window !== 'undefined' && typeof window.route === 'function') {
            return window.route(name, params);
        }
    } catch (_) {}

    let basePrefix = '';
    if (typeof window !== 'undefined') {
        const path = window.location.pathname || '';
        const publicMatch = path.match(/^(\/?[^/]+\/public)/);
        if (publicMatch) {
            basePrefix = publicMatch[1].startsWith('/') ? publicMatch[1] : `/${publicMatch[1]}`;
        } else if (path.startsWith('/beauty')) {
            basePrefix = path.includes('/public') ? '/beauty/public' : '/beauty';
        }
    }

    const pathSuffix = name.startsWith('/') ? name : `/${name.replace(/\./g, '/')}`;
    let url = `${basePrefix}${pathSuffix}`;

    const paramKeys = Object.keys(params);
    if (paramKeys.length > 0) {
        const queryString = paramKeys.map(k => `${encodeURIComponent(k)}=${encodeURIComponent(params[k])}`).join('&');
        url += (url.includes('?') ? '&' : '?') + queryString;
    }

    return url;
}

// Helper to fetch with timeout across all browser versions
function fetchWithTimeout(url, options = {}, timeoutMs = 4000) {
    const controller = typeof AbortController !== 'undefined' ? new AbortController() : null;
    const signal = controller ? controller.signal : undefined;
    const timer = controller ? setTimeout(() => controller.abort(), timeoutMs) : null;

    return fetch(url, { ...options, signal })
        .then((res) => {
            if (timer) clearTimeout(timer);
            return res;
        })
        .catch((err) => {
            if (timer) clearTimeout(timer);
            throw err;
        });
}

// Helper to get GPS coordinates from browser with configurable options
function getGpsCoordinates(options = {}) {
    return new Promise((resolve, reject) => {
        if (typeof window === 'undefined') {
            return reject(new Error('Window not available.'));
        }

        // Browsers block geolocation on non-secure origins (plain HTTP without localhost)
        const isSecure = window.isSecureContext || 
                         window.location.hostname === 'localhost' || 
                         window.location.hostname === '127.0.0.1';

        if (!isSecure || !navigator?.geolocation) {
            return reject(new Error('Insecure context or Geolocation unsupported.'));
        }

        let hasResolved = false;
        const fallbackTimer = setTimeout(() => {
            if (!hasResolved) {
                hasResolved = true;
                reject(new Error('GPS location request timed out.'));
            }
        }, (options.timeout || 5000) + 1000);

        navigator.geolocation.getCurrentPosition(
            (pos) => {
                if (hasResolved) return;
                hasResolved = true;
                clearTimeout(fallbackTimer);
                resolve({
                    latitude: pos.coords.latitude,
                    longitude: pos.coords.longitude,
                    accuracy: pos.coords.accuracy,
                    source: 'gps',
                });
            },
            (err) => {
                if (hasResolved) return;
                hasResolved = true;
                clearTimeout(fallbackTimer);
                reject(err);
            },
            options
        );
    });
}

// Detect location via Backend or IP Services
async function getIpCoordinates() {
    // 1. Try Laravel backend geolocation endpoint
    try {
        const detectUrl = resolveAppEndpoint('geolocation.detect');
        const res = await fetchWithTimeout(detectUrl, {
            headers: { 'Accept': 'application/json' },
        }, 3500);
        if (res.ok) {
            const data = await res.json();
            if (data && data.latitude && data.longitude) {
                return {
                    latitude: Number(data.latitude),
                    longitude: Number(data.longitude),
                    city: data.city || '',
                    region: data.state || data.area || '',
                    country: data.country || 'Pakistan',
                    postal: data.postcode || '',
                    fullAddress: data.fullAddress || '',
                    source: data.source || 'backend_ip',
                };
            }
        }
    } catch (_) {}

    // 2. Try Client-side ipwho.is
    try {
        const res = await fetchWithTimeout('https://ipwho.is/', {}, 3500);
        if (res.ok) {
            const data = await res.json();
            if (data.success && data.latitude && data.longitude) {
                return {
                    latitude: Number(data.latitude),
                    longitude: Number(data.longitude),
                    city: data.city || '',
                    region: data.region || '',
                    country: data.country || '',
                    postal: data.postal || '',
                    source: 'ip',
                };
            }
        }
    } catch (_) {}

    // 3. Try Client-side freeipapi.com
    try {
        const res = await fetchWithTimeout('https://freeipapi.com/api/json', {}, 3500);
        if (res.ok) {
            const data = await res.json();
            if (data.latitude && data.longitude) {
                return {
                    latitude: Number(data.latitude),
                    longitude: Number(data.longitude),
                    city: data.cityName || '',
                    region: data.regionName || '',
                    country: data.countryName || '',
                    postal: data.zipCode || '',
                    source: 'ip',
                };
            }
        }
    } catch (_) {}

    // 4. Try Client-side ipapi.co
    try {
        const res = await fetchWithTimeout('https://ipapi.co/json/', {}, 3500);
        if (res.ok) {
            const data = await res.json();
            if (data.latitude && data.longitude) {
                return {
                    latitude: Number(data.latitude),
                    longitude: Number(data.longitude),
                    city: data.city || '',
                    region: data.region || '',
                    country: data.country_name || '',
                    postal: data.postal || '',
                    source: 'ip',
                };
            }
        }
    } catch (_) {}

    // Default Fallback
    return {
        latitude: 33.6844,
        longitude: 73.0479,
        city: 'Islamabad',
        region: 'Islamabad Capital Territory',
        country: 'Pakistan',
        postal: '44000',
        fullAddress: 'Islamabad, Pakistan',
        source: 'default',
    };
}

// Reverse Geocode using Backend or External Services
async function reverseGeocode(latitude, longitude) {
    // 1. Try backend reverse geocoding
    try {
        const reverseUrl = resolveAppEndpoint('geolocation.reverse', { lat: latitude, lon: longitude });
        const res = await fetchWithTimeout(reverseUrl, {
            headers: { 'Accept': 'application/json' },
        }, 4000);
        if (res.ok) {
            const data = await res.json();
            if (data.success && (data.city || data.fullAddress)) {
                return data;
            }
        }
    } catch (_) {}

    // 2. Try BigDataCloud
    try {
        const res = await fetchWithTimeout(
            `https://api.bigdatacloud.net/data/reverse-geocode-client?latitude=${latitude}&longitude=${longitude}&localityLanguage=en`,
            {},
            4000
        );
        if (res.ok) {
            const data = await res.json();
            const city = data.city || data.locality || data.principalSubdivision || '';
            const locality = data.locality || '';
            const state = data.principalSubdivision || '';
            const country = data.countryName || '';
            const postcode = data.postcode || '';

            const parts = [];
            if (locality && locality !== city) parts.push(locality);
            if (city) parts.push(city);
            if (state && state !== city && !parts.includes(state)) parts.push(state);
            if (country && !parts.includes(country)) parts.push(country);

            return {
                latitude,
                longitude,
                fullAddress: parts.join(', ') || `${city}, ${country}`.trim(),
                road: '',
                neighbourhood: locality,
                area: locality || state,
                city: city || state,
                state,
                country,
                postcode,
                raw: data,
            };
        }
    } catch (_) {}

    // 3. Try OpenStreetMap Nominatim
    try {
        const res = await fetchWithTimeout(
            `https://nominatim.openstreetmap.org/reverse?format=json&lat=${latitude}&lon=${longitude}&zoom=18&addressdetails=1`,
            { headers: { 'Accept-Language': 'en' } },
            4000
        );
        if (res.ok) {
            const data = await res.json();
            const addr = data.address || {};
            const road = addr.road || addr.street || addr.footway || addr.path || '';
            const houseNumber = addr.house_number || addr.building || '';
            const neighbourhood = addr.neighbourhood || addr.suburb || addr.residential || addr.quarter || addr.subdivision || '';
            const area = neighbourhood || addr.city_district || addr.county || '';
            const city = addr.city || addr.town || addr.municipality || addr.state_district || addr.state || '';
            const state = addr.state || '';
            const country = addr.country || '';
            const postcode = addr.postcode || '';

            const parts = [];
            if (houseNumber) parts.push(houseNumber);
            if (road) parts.push(road);
            if (neighbourhood && neighbourhood !== road) parts.push(neighbourhood);
            if (city && !parts.includes(city)) parts.push(city);
            if (country && !parts.includes(country)) parts.push(country);

            return {
                latitude,
                longitude,
                fullAddress: parts.length > 0 ? parts.join(', ') : (data.display_name ? data.display_name.split(',').slice(0, 4).join(', ') : `${latitude.toFixed(5)}, ${longitude.toFixed(5)}`),
                road,
                neighbourhood,
                area,
                city,
                state,
                country,
                postcode,
                raw: data,
            };
        }
    } catch (_) {}

    return {
        latitude,
        longitude,
        fullAddress: `Lat: ${latitude.toFixed(5)}, Lon: ${longitude.toFixed(5)}`,
        road: '',
        neighbourhood: '',
        area: '',
        city: '',
        state: '',
        country: '',
        postcode: '',
        raw: null,
    };
}

/**
 * Main entrypoint to auto-detect current address & coordinates.
 */
export async function detectCurrentAddress() {
    let coords = null;
    let detectionSource = 'gps';

    // Check if secure context for GPS
    const isSecure = typeof window !== 'undefined' && 
                     (window.isSecureContext || window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1');

    if (isSecure && navigator?.geolocation) {
        // 1. Try High Accuracy GPS
        try {
            coords = await getGpsCoordinates({ enableHighAccuracy: true, timeout: 4000, maximumAge: 30000 });
        } catch (_) {
            // 2. Try Standard GPS
            try {
                coords = await getGpsCoordinates({ enableHighAccuracy: false, timeout: 3500, maximumAge: 60000 });
            } catch (_) {
                // GPS failed, proceed to IP
            }
        }
    }

    // 3. If GPS was unavailable, insecure context, or failed -> Use IP Geolocation
    if (!coords) {
        coords = await getIpCoordinates();
        detectionSource = coords.source || 'ip';
    }

    const { latitude, longitude } = coords;

    // If we already have full address and city from backend IP, and coords source was IP, we can use it directly or enhance with reverse geocoding
    if (coords.fullAddress && coords.city && (detectionSource === 'backend_ip' || detectionSource === 'default')) {
        return {
            latitude,
            longitude,
            fullAddress: coords.fullAddress,
            road: '',
            neighbourhood: coords.region || '',
            area: coords.region || coords.city || '',
            city: coords.city,
            state: coords.region || '',
            country: coords.country || 'Pakistan',
            postcode: coords.postal || '',
            source: detectionSource,
            raw: coords,
        };
    }

    // Reverse geocode the coordinates
    const geocoded = await reverseGeocode(latitude, longitude);
    geocoded.source = detectionSource;

    // Merge any missing city/state from initial coordinates if geocoding was sparse
    if (!geocoded.city && coords.city) geocoded.city = coords.city;
    if (!geocoded.area && (coords.region || coords.city)) geocoded.area = coords.region || coords.city;
    if (!geocoded.fullAddress || geocoded.fullAddress.startsWith('Lat:')) {
        if (coords.fullAddress) {
            geocoded.fullAddress = coords.fullAddress;
        } else if (geocoded.city) {
            geocoded.fullAddress = [geocoded.city, geocoded.state || coords.region, geocoded.country || coords.country].filter(Boolean).join(', ');
        }
    }

    return geocoded;
}


