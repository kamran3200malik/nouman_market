<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import AppAlert from '@/Components/AppAlert.vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import Swal from 'sweetalert2';
import { detectCurrentAddress } from '@/Utils/geolocation';

const step = ref(1);


const loading = ref(false);
const errors = ref({});

// Map state
let map = null;
let marker = null;
const mapInitialized = ref(false);

// Step 1: Basic Information
const basicInfo = ref({
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: '',
});

// Step 2: Professional Information
const professionalInfo = ref({
    business_name: '',
    professional_type: '',
    years_of_experience: '',
    bio: '',
    full_description: '',
    specializations: [],
    languages: ['Urdu', 'English'],
    home_service_available: false,
    home_service_fee: 0,
    max_service_distance: 25,
    service_areas: '',
});

const languageOptions = [
    'Urdu', 'English', 'Punjabi', 'Pashto', 'Sindhi', 'Balochi', 'Saraiki', 'Hindko', 'Arabic'
];

const professionalTypes = [
    { value: 'bridal_makeup_artist', label: 'Bridal Specialist', icon: '👰', desc: 'Barat, Walima & Haute Bridal' },
    { value: 'makeup_artist', label: 'Makeup Artist', icon: '💄', desc: 'Party, Glam & HD Makeup' },
    { value: 'hair_stylist', label: 'Hair Stylist', icon: '💇‍♀️', desc: 'Couture, Balayage & Cuts' },
    { value: 'nail_artist', label: 'Nail Artist', icon: '💅', desc: 'Gel-X, Acrylics & Nail Art' },
    { value: 'facial_specialist', label: 'Facial Clinic', icon: '✨', desc: 'HydraFacial & Skin Glow' },
    { value: 'mehndi_artist', label: 'Mehndi Artist', icon: '🎨', desc: 'Organic Henna & Bridal Mehndi' },
    { value: 'lash_artist', label: 'Lash Specialist', icon: '👁️', desc: 'Extensions & Lash Lifts' },
    { value: 'brow_artist', label: 'Brow Specialist', icon: '🪄', desc: 'Microblading & Shaping' },
    { value: 'beauty_salon', label: 'Beauty Salon', icon: '🏢', desc: 'Full-Service Salon Studio' },
    { value: 'spa', label: 'Luxury Spa', icon: '💆‍♀️', desc: 'Massages & Body Rituals' },
    { value: 'other', label: 'Other Specialist', icon: '🌸', desc: 'Custom Aesthetic Services' },
];

const specializationOptions = [
    'Bridal Makeup', 'Party Makeup', 'Engagement Makeup', 'HD Glass Skin', 'Airbrush Makeup',
    'Hair Styling', 'Hair Cutting', 'Balayage Color', 'Keratin & Botox', 'Bridal Updo',
    'Gel-X Nails', 'Acrylic Extensions', 'Korean Nail Art', 'Russian Manicure', 'Pedicure Spa',
    'HydraFacial', 'Chemical Peels', 'Microdermabrasion', 'Dermal Infusion', 'Anti-Aging Glow',
    'Organic Bridal Mehndi', 'Arabic Henna', 'Indian Mehndi', 'Tattoo Henna',
    'Volume Lashes', 'Lash Lift & Tint', 'Brow Lamination', 'Brow Threading',
    'Aromatherapy Massage', 'Deep Tissue Massage', 'Moroccan Bath', 'Full Body Polish',
];

// Step 3: Location
const locationInfo = ref({
    country: 'Pakistan',
    city: '',
    area: '',
    address: '',
    latitude: null,
    longitude: null,
});

const cities = [
    'Karachi', 'Lahore', 'Islamabad', 'Rawalpindi', 'Attock', 'Faisalabad',
    'Multan', 'Peshawar', 'Quetta', 'Sialkot', 'Gujranwala', 'Hyderabad',
];

// Step 4: Documents (CNIC Front & Back required)
const documents = ref({
    cnic_front: null,
    cnic_back: null,
    professional_certificate: null,
    business_registration: null,
    other_document: null,
});

// Step 5: Portfolio
const portfolio = ref({
    profile_image: null,
    cover_image: null,
    portfolio_images: [],
});

const profilePreview = ref(null);
const coverPreview = ref(null);
const portfolioPreviews = ref([]);

function handlePhoneInput(e) {
    const raw = e.target.value.replace(/[^0-9]/g, '');
    basicInfo.value.phone = raw.slice(0, 11);
}

// Validation
const canProceedStep1 = computed(() => {
    return basicInfo.value.first_name.trim() && 
           basicInfo.value.last_name.trim() && 
           basicInfo.value.email.trim() && 
           basicInfo.value.phone.trim().length === 11 && 
           basicInfo.value.password && 
           basicInfo.value.password.length >= 8 &&
           basicInfo.value.password === basicInfo.value.password_confirmation;
});

const canProceedStep2 = computed(() => {
    return professionalInfo.value.business_name.trim() && 
           professionalInfo.value.professional_type && 
           professionalInfo.value.years_of_experience !== '' && 
           professionalInfo.value.bio.trim();
});

const canProceedStep3 = computed(() => {
    return locationInfo.value.city && 
           locationInfo.value.area.trim() && 
           locationInfo.value.address.trim();
});

const canProceedStep4 = computed(() => {
    return documents.value.cnic_front !== null && documents.value.cnic_back !== null;
});

const agreedToTerms = ref(false);
const showTermsModal = ref(false);
const termsLanguage = ref('ur'); // Default to Urdu

const canSubmit = computed(() => {
    return canProceedStep1.value &&
           canProceedStep2.value &&
           canProceedStep3.value &&
           canProceedStep4.value &&
           agreedToTerms.value;
});

const canProceedToNextStep = computed(() => {
    switch(step.value) {
        case 1: return canProceedStep1.value;
        case 2: return canProceedStep2.value;
        case 3: return canProceedStep3.value;
        case 4: return canProceedStep4.value;
        case 5: return agreedToTerms.value;
        default: return true;
    }
});

function acceptTermsFromModal() {
    agreedToTerms.value = true;
    showTermsModal.value = false;
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'success',
        title: 'Partner Terms & Conditions Accepted',
        showConfirmButton: false,
        timer: 2500
    });
}

const stepsList = [
    { num: 1, title: 'Account', subtitle: 'Personal Details', icon: '👤' },
    { num: 2, title: 'Profile', subtitle: 'Salon & Expertise', icon: '💼' },
    { num: 3, title: 'Location', subtitle: 'City & Map Pin', icon: '📍' },
    { num: 4, title: 'Documents', subtitle: 'CNIC & Verification', icon: '📄' },
    { num: 5, title: 'Review', subtitle: 'Verify & Launch', icon: '✨' },
];

function goToStep(stepNumber) {
    step.value = stepNumber;
    window.scrollTo({ top: 0, behavior: 'smooth' });
    if (stepNumber === 3) {
        initMap();
    }
}

function handleFileUpload(field, event) {
    const file = event.target.files[0];
    if (file) {
        if (field === 'portfolio_images') {
            const files = Array.from(event.target.files);
            files.forEach(f => {
                portfolio.value.portfolio_images.push(f);
                const reader = new FileReader();
                reader.onload = (e) => {
                    portfolioPreviews.value.push({ name: f.name, url: e.target.result });
                };
                reader.readAsDataURL(f);
            });
        } else if (['cnic_front', 'cnic_back', 'professional_certificate', 'business_registration', 'other_document'].includes(field)) {
            documents.value[field] = file;
        } else if (field === 'profile_image') {
            portfolio.value.profile_image = file;
            const reader = new FileReader();
            reader.onload = (e) => { profilePreview.value = e.target.result; };
            reader.readAsDataURL(file);
        } else if (field === 'cover_image') {
            portfolio.value.cover_image = file;
            const reader = new FileReader();
            reader.onload = (e) => { coverPreview.value = e.target.result; };
            reader.readAsDataURL(file);
        }
    }
}

function removePortfolioImage(index) {
    portfolio.value.portfolio_images.splice(index, 1);
    portfolioPreviews.value.splice(index, 1);
}

function toggleSpecialization(spec) {
    const index = professionalInfo.value.specializations.indexOf(spec);
    if (index > -1) {
        professionalInfo.value.specializations.splice(index, 1);
    } else {
        professionalInfo.value.specializations.push(spec);
    }
}

const isAllSpecializationsSelected = computed(() => {
    return professionalInfo.value.specializations.length === specializationOptions.length;
});

function toggleAllSpecializations() {
    if (isAllSpecializationsSelected.value) {
        professionalInfo.value.specializations = [];
    } else {
        professionalInfo.value.specializations = [...specializationOptions];
    }
}

function selectProfessionalType(val) {
    professionalInfo.value.professional_type = val;
}

function selectCityChip(c) {
    locationInfo.value.city = c;
    if (map) {
        const coords = {
            'Karachi': [24.8607, 67.0011],
            'Lahore': [31.5204, 74.3587],
            'Islamabad': [33.6844, 73.0479],
            'Rawalpindi': [33.5651, 73.0169],
            'Attock': [33.7680, 72.3660],
            'Faisalabad': [31.4504, 73.1350],
            'Multan': [30.1575, 71.5249],
            'Peshawar': [34.0151, 71.5249],
            'Quetta': [30.1798, 66.9750],
        };
        if (coords[c]) {
            map.setView(coords[c], 12);
        }
    }
}

const isDetectingLocation = ref(false);

const detectCurrentLocation = async () => {
    isDetectingLocation.value = true;
    try {
        const loc = await detectCurrentAddress();
        if (loc.latitude && loc.longitude) {
            locationInfo.value.latitude = parseFloat(Number(loc.latitude).toFixed(6));
            locationInfo.value.longitude = parseFloat(Number(loc.longitude).toFixed(6));

            if (map) {
                map.setView([loc.latitude, loc.longitude], 15);
                if (marker) {
                    marker.setLatLng([loc.latitude, loc.longitude]);
                } else if (typeof L !== 'undefined') {
                    marker = L.marker([loc.latitude, loc.longitude], { draggable: true }).addTo(map);
                    marker.on('dragend', (e) => {
                        const { lat, lng } = e.target.getLatLng();
                        locationInfo.value.latitude = parseFloat(lat.toFixed(6));
                        locationInfo.value.longitude = parseFloat(lng.toFixed(6));
                    });
                }
            }
        }
        if (loc.fullAddress) {
            locationInfo.value.address = loc.fullAddress;
        }
        if (loc.area) {
            locationInfo.value.area = loc.area;
        }
        if (loc.city) {
            const matchedCity = cities.find(c => c.toLowerCase() === loc.city.toLowerCase() || c.toLowerCase().includes(loc.city.toLowerCase()) || loc.city.toLowerCase().includes(c.toLowerCase()));
            if (matchedCity) {
                locationInfo.value.city = matchedCity;
            } else {
                locationInfo.value.city = loc.city;
            }
        }
        Swal.fire({
            icon: 'success',
            title: 'Studio Location Detected! 📍',
            text: loc.fullAddress,
            timer: 2500,
            showConfirmButton: false,
        });
    } catch (err) {
        Swal.fire({
            icon: 'info',
            title: 'Location Notice',
            text: err.message || 'Could not auto-detect location. Please enter manually.',
            confirmButtonColor: '#be185d',
        });
    } finally {
        isDetectingLocation.value = false;
    }
};

function trySubmit() {

    if (!canProceedStep1.value) {
        Swal.fire({
            icon: 'warning',
            title: 'Incomplete Account Details',
            text: 'Please fill in your Name, Email, 11-digit Mobile Number, and matching Password (min 8 chars) in Step 1.',
            confirmButtonColor: '#be185d',
        });
        goToStep(1);
        return;
    }
    if (!canProceedStep2.value) {
        Swal.fire({
            icon: 'warning',
            title: 'Incomplete Salon Profile',
            text: 'Please select your Category, Salon Name, Years of Experience, and Bio in Step 2.',
            confirmButtonColor: '#be185d',
        });
        goToStep(2);
        return;
    }
    if (!canProceedStep3.value) {
        Swal.fire({
            icon: 'warning',
            title: 'Incomplete Location',
            text: 'Please provide your City, Area, and Full Address in Step 3.',
            confirmButtonColor: '#be185d',
        });
        goToStep(3);
        return;
    }
    if (!canProceedStep4.value) {
        Swal.fire({
            icon: 'warning',
            title: 'Missing CNIC Documents',
            text: 'Please upload both CNIC Front and CNIC Back images in Step 4.',
            confirmButtonColor: '#be185d',
        });
        goToStep(4);
        return;
    }
    if (!agreedToTerms.value) {
        Swal.fire({
            icon: 'warning',
            title: 'Terms & Conditions Required',
            text: 'Please review and check the BeautyBook Partner Terms & Conditions Agreement before submitting your application.',
            confirmButtonColor: '#be185d',
        });
        goToStep(5);
        return;
    }
    submitRegistration();
}

function toggleLanguage(lang) {
    const idx = professionalInfo.value.languages.indexOf(lang);
    if (idx > -1) {
        professionalInfo.value.languages.splice(idx, 1);
    } else {
        professionalInfo.value.languages.push(lang);
    }
}



function updateMarkerFromInputs() {
    if (map && locationInfo.value.latitude && locationInfo.value.longitude) {
        const lat = Number(locationInfo.value.latitude);
        const lng = Number(locationInfo.value.longitude);
        if (!isNaN(lat) && !isNaN(lng)) {
            map.setView([lat, lng], map.getZoom() || 14);
            if (marker) {
                marker.setLatLng([lat, lng]);
            } else {
                marker = L.marker([lat, lng]).addTo(map);
            }
        }
    }
}

function submitRegistration() {
    loading.value = true;
    errors.value = {};

    const formData = new FormData();
    
    // Basic Info
    Object.keys(basicInfo.value).forEach(key => {
        formData.append(key, basicInfo.value[key]);
    });
    
    // Professional Info
    Object.keys(professionalInfo.value).forEach(key => {
        if (key === 'specializations' || key === 'languages') {
            formData.append(key, JSON.stringify(professionalInfo.value[key]));
        } else if (key === 'home_service_available') {
            formData.append(key, professionalInfo.value[key] ? '1' : '0');
        } else {
            formData.append(key, professionalInfo.value[key] ?? '');
        }
    });
    
    // Location Info
    Object.keys(locationInfo.value).forEach(key => {
        if (locationInfo.value[key] !== null && locationInfo.value[key] !== undefined) {
            formData.append(key, locationInfo.value[key]);
        }
    });
    
    // Documents (CNIC Front & Back)
    if (documents.value.cnic_front) formData.append('cnic_front', documents.value.cnic_front);
    if (documents.value.cnic_back) formData.append('cnic_back', documents.value.cnic_back);
    if (documents.value.professional_certificate) formData.append('professional_certificate', documents.value.professional_certificate);
    if (documents.value.business_registration) formData.append('business_registration', documents.value.business_registration);
    if (documents.value.other_document) formData.append('other_document', documents.value.other_document);
    
    // Portfolio
    if (portfolio.value.profile_image) formData.append('profile_image', portfolio.value.profile_image);
    if (portfolio.value.cover_image) formData.append('cover_image', portfolio.value.cover_image);
    portfolio.value.portfolio_images.forEach((file, index) => {
        formData.append(`portfolio_images[${index}]`, file);
    });

    formData.append('agreed_to_terms', agreedToTerms.value ? '1' : '0');

    router.post(route('artist.register.store'), formData, {
        forceFormData: true,
        onError: (err) => {
            errors.value = err;
            loading.value = false;
            console.error('Validation errors:', err);
            Swal.fire({
                icon: 'error',
                title: 'Please check your form',
                text: Object.values(err).join(', '),
                confirmButtonColor: '#be185d',
            });
            if (err.first_name || err.last_name || err.email || err.phone || err.password) {
                goToStep(1);
            } else if (err.business_name || err.professional_type || err.years_of_experience || err.bio) {
                goToStep(2);
            } else if (err.city || err.area || err.address) {
                goToStep(3);
            } else if (err.cnic_front || err.cnic_back) {
                goToStep(4);
            }
        },
        onSuccess: () => {
            loading.value = false;
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Application submitted successfully',
                showConfirmButton: false,
                timer: 3000
            });
        }
    });
}

// Initialize map when step 3 is reached
const initMap = () => {
    nextTick(() => {
        const mapContainer = document.getElementById('location-map');
        if (!mapContainer) return;

        if (map) {
            map.remove();
            map = null;
        }

        const defaultLat = locationInfo.value.latitude || 24.8607;
        const defaultLng = locationInfo.value.longitude || 67.0011;
        const zoom = locationInfo.value.latitude ? 14 : 6;

        map = L.map('location-map').setView([defaultLat, defaultLng], zoom);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        if (locationInfo.value.latitude && locationInfo.value.longitude) {
            marker = L.marker([locationInfo.value.latitude, locationInfo.value.longitude]).addTo(map);
        }

        map.on('click', (e) => {
            const { lat, lng } = e.latlng;
            locationInfo.value.latitude = lat;
            locationInfo.value.longitude = lng;

            if (marker) {
                marker.setLatLng([lat, lng]);
            } else {
                marker = L.marker([lat, lng]).addTo(map);
            }
        });

        mapInitialized.value = true;
    });
};

const cleanupMap = () => {
    if (map) {
        map.remove();
        map = null;
        marker = null;
        mapInitialized.value = false;
    }
};

onMounted(() => {
    if (step.value === 3) {
        initMap();
    }
});

onUnmounted(() => {
    cleanupMap();
});
</script>

<template>
    <PublicLayout>
        <div class="py-8 sm:py-12">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 space-y-8">
                <!-- 1. EDITORIAL LUXURY HEADER -->
                <div class="text-center space-y-3 relative">
                    <div class="inline-flex items-center gap-2 rounded-full bg-pink-100 px-4 py-1 text-xs font-bold uppercase tracking-wider text-pink-800 border border-pink-200 shadow-xs">
                        <span>🌸</span>
                        <span>Premier Female Beauty Guild</span>
                    </div>

                    <h1 class="font-serif text-3xl sm:text-4xl lg:text-5xl font-normal text-slate-900 leading-tight">
                        Grow Your Beauty Business & <br class="hidden sm:inline" />
                        <span class="italic text-glam-700 font-serif">Welcome High-Value Clients</span>
                    </h1>

                    <p class="text-xs sm:text-sm text-slate-600 max-w-2xl mx-auto leading-relaxed">
                        Join verified beauty parlours, master hair stylists, and bridal artists. Accept instant bookings with transparent earnings.
                    </p>

                    <!-- Trust Strip -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 sm:gap-3 max-w-3xl mx-auto pt-2">
                        <div class="rounded-xl bg-white/80 backdrop-blur-md p-2.5 border border-pink-100 text-center shadow-2xs">
                            <span class="text-sm">⚡</span>
                            <p class="text-[11px] font-bold text-slate-900 mt-0.5">Instant Bookings</p>
                            <p class="text-[10px] text-slate-500">24/7 calendar system</p>
                        </div>
                        <div class="rounded-xl bg-white/80 backdrop-blur-md p-2.5 border border-pink-100 text-center shadow-2xs">
                            <span class="text-sm">💎</span>
                            <p class="text-[11px] font-bold text-slate-900 mt-0.5">Verified Badge</p>
                            <p class="text-[10px] text-slate-500">Build instant trust</p>
                        </div>
                        <div class="rounded-xl bg-white/80 backdrop-blur-md p-2.5 border border-pink-100 text-center shadow-2xs">
                            <span class="text-sm">👛</span>
                            <p class="text-[11px] font-bold text-slate-900 mt-0.5">Prompt Payouts</p>
                            <p class="text-[10px] text-slate-500">Direct to bank/wallet</p>
                        </div>
                        <div class="rounded-xl bg-white/80 backdrop-blur-md p-2.5 border border-pink-100 text-center shadow-2xs">
                            <span class="text-sm">🌸</span>
                            <p class="text-[11px] font-bold text-slate-900 mt-0.5">0% First Month</p>
                            <p class="text-[10px] text-slate-500">Keep 100% earnings</p>
                        </div>
                    </div>
                </div>

                <!-- 2. LUXURY MULTI-STEP PROGRESS TRACKER -->
                <div class="rounded-2xl bg-white/90 backdrop-blur-md p-4 sm:p-5 border border-pink-200/80 shadow-md">
                    <div class="grid grid-cols-3 sm:grid-cols-5 gap-2">
                        <button
                            v-for="s in stepsList"
                            :key="s.num"
                            type="button"
                            @click="goToStep(s.num)"
                            :disabled="step < s.num"
                            class="flex flex-col items-center text-center p-2 rounded-xl transition-all cursor-pointer group"
                            :class="[
                                step === s.num ? 'bg-pink-100/70 border border-pink-300 shadow-xs' : '',
                                step > s.num ? 'hover:bg-pink-50/50' : '',
                                step < s.num ? 'opacity-60 cursor-not-allowed' : ''
                            ]"
                        >
                            <div
                                class="flex h-9 w-9 items-center justify-center rounded-full text-xs font-bold transition-all shadow-xs"
                                :class="[
                                    step === s.num ? 'bg-gradient-to-r from-glam-600 to-rose-900 text-white ring-2 ring-pink-300' : '',
                                    step > s.num ? 'bg-emerald-600 text-white' : '',
                                    step < s.num ? 'bg-slate-100 text-slate-500' : ''
                                ]"
                            >
                                <span v-if="step > s.num">✓</span>
                                <span v-else>{{ s.num }}</span>
                            </div>
                            <span class="mt-1.5 text-xs font-bold text-slate-900">{{ s.title }}</span>
                            <span class="text-[10px] text-slate-500 hidden sm:block">{{ s.subtitle }}</span>
                        </button>
                    </div>
                </div>

                <!-- 3. MAIN REGISTRATION FORM CARD -->
                <div class="rounded-3xl bg-white/95 backdrop-blur-xl p-6 sm:p-10 shadow-2xl border border-pink-200/90 relative">
                    <!-- STEP 1: BASIC INFORMATION & ACCOUNT -->
                    <div v-if="step === 1" class="space-y-6">
                        <div class="border-b border-pink-100 pb-4">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-pink-700 bg-pink-100 px-2.5 py-0.5 rounded-full">Step 1 of 6</span>
                            <h2 class="font-serif text-2xl font-bold text-slate-900 mt-2">Account & Contact Details</h2>
                            <p class="text-xs text-slate-500">Provide your personal credentials to manage your salon appointments.</p>
                        </div>

                        <div class="grid gap-5 sm:grid-cols-2">
                            <div>
                                <label class="block text-xs font-bold text-slate-800 mb-1.5">First Name *</label>
                                <input
                                    v-model="basicInfo.first_name"
                                    type="text"
                                    placeholder="e.g. Ayesha"
                                    class="w-full rounded-xl bg-pink-50/30 px-3.5 py-2.5 text-xs sm:text-sm text-slate-800 border border-pink-200/80 focus:border-glam-600 focus:bg-white focus:ring-1 focus:ring-pink-300 transition"
                                    :class="{ 'border-rose-400 bg-rose-50/40': errors.first_name }"
                                />
                                <p v-if="errors.first_name" class="mt-1 text-[11px] text-rose-600">{{ errors.first_name }}</p>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-800 mb-1.5">Last Name *</label>
                                <input
                                    v-model="basicInfo.last_name"
                                    type="text"
                                    placeholder="e.g. Khan"
                                    class="w-full rounded-xl bg-pink-50/30 px-3.5 py-2.5 text-xs sm:text-sm text-slate-800 border border-pink-200/80 focus:border-glam-600 focus:bg-white focus:ring-1 focus:ring-pink-300 transition"
                                    :class="{ 'border-rose-400 bg-rose-50/40': errors.last_name }"
                                />
                                <p v-if="errors.last_name" class="mt-1 text-[11px] text-rose-600">{{ errors.last_name }}</p>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-800 mb-1.5">Email Address *</label>
                                <input
                                    v-model="basicInfo.email"
                                    type="email"
                                    placeholder="ayesha@salon.com"
                                    class="w-full rounded-xl bg-pink-50/30 px-3.5 py-2.5 text-xs sm:text-sm text-slate-800 border border-pink-200/80 focus:border-glam-600 focus:bg-white focus:ring-1 focus:ring-pink-300 transition"
                                    :class="{ 'border-rose-400 bg-rose-50/40': errors.email }"
                                />
                                <p v-if="errors.email" class="mt-1 text-[11px] text-rose-600">{{ errors.email }}</p>
                            </div>

                            <div>
                                <div class="flex items-center justify-between mb-1.5">
                                    <label class="block text-xs font-bold text-slate-800">Mobile Number * (Exact 11 Digits)</label>
                                    <span class="text-[10px] text-slate-500">{{ basicInfo.phone.length }}/11 digits</span>
                                </div>
                                <input
                                    :value="basicInfo.phone"
                                    @input="handlePhoneInput"
                                    type="tel"
                                    maxlength="11"
                                    placeholder="03001234567"
                                    class="w-full rounded-xl bg-pink-50/30 px-3.5 py-2.5 text-xs sm:text-sm text-slate-800 border border-pink-200/80 focus:border-glam-600 focus:bg-white focus:ring-1 focus:ring-pink-300 transition tracking-wider"
                                    :class="{ 'border-rose-400 bg-rose-50/40': errors.phone || (basicInfo.phone && basicInfo.phone.length !== 11) }"
                                />
                                <p v-if="basicInfo.phone && basicInfo.phone.length !== 11" class="mt-1 text-[11px] text-rose-600">Mobile number must be exactly 11 digits (e.g. 03001234567).</p>
                                <p v-if="errors.phone" class="mt-1 text-[11px] text-rose-600">{{ errors.phone }}</p>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-800 mb-1.5">Password * (Min. 8 characters)</label>
                                <input
                                    v-model="basicInfo.password"
                                    type="password"
                                    placeholder="••••••••"
                                    class="w-full rounded-xl bg-pink-50/30 px-3.5 py-2.5 text-xs sm:text-sm text-slate-800 border border-pink-200/80 focus:border-glam-600 focus:bg-white focus:ring-1 focus:ring-pink-300 transition"
                                    :class="{ 'border-rose-400 bg-rose-50/40': errors.password }"
                                />
                                <p v-if="errors.password" class="mt-1 text-[11px] text-rose-600">{{ errors.password }}</p>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-800 mb-1.5">Confirm Password *</label>
                                <input
                                    v-model="basicInfo.password_confirmation"
                                    type="password"
                                    placeholder="••••••••"
                                    class="w-full rounded-xl bg-pink-50/30 px-3.5 py-2.5 text-xs sm:text-sm text-slate-800 border border-pink-200/80 focus:border-glam-600 focus:bg-white focus:ring-1 focus:ring-pink-300 transition"
                                    :class="{ 'border-rose-400 bg-rose-50/40': errors.password_confirmation || (basicInfo.password_confirmation && basicInfo.password !== basicInfo.password_confirmation) }"
                                />
                                <p v-if="basicInfo.password_confirmation && basicInfo.password !== basicInfo.password_confirmation" class="mt-1 text-[11px] text-rose-600">Passwords do not match.</p>
                            </div>
                        </div>
                    </div>

                    <!-- STEP 2: PROFESSIONAL INFORMATION -->
                    <div v-if="step === 2" class="space-y-6">
                        <div class="border-b border-pink-100 pb-4">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-pink-700 bg-pink-100 px-2.5 py-0.5 rounded-full">Step 2 of 6</span>
                            <h2 class="font-serif text-2xl font-bold text-slate-900 mt-2">Salon & Professional Profile</h2>
                            <p class="text-xs text-slate-500">Highlight your expertise, services, and signature beauty craft.</p>
                        </div>

                        <div class="space-y-5">
                            <div>
                                <label class="block text-xs font-bold text-slate-800 mb-1.5">Business / Salon Name *</label>
                                <input
                                    v-model="professionalInfo.business_name"
                                    type="text"
                                    placeholder="e.g. Maison Luxe Studio & Bridal Lounge"
                                    class="w-full rounded-xl bg-pink-50/30 px-3.5 py-2.5 text-xs sm:text-sm text-slate-800 border border-pink-200/80 focus:border-glam-600 focus:bg-white transition"
                                    :class="{ 'border-rose-400': errors.business_name }"
                                />
                                <p v-if="errors.business_name" class="mt-1 text-[11px] text-rose-600">{{ errors.business_name }}</p>
                            </div>

                            <!-- Professional Type Grid Selector -->
                            <div>
                                <label class="block text-xs font-bold text-slate-800 mb-2">Select Your Primary Category *</label>
                                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2.5">
                                    <div
                                        v-for="type in professionalTypes"
                                        :key="type.value"
                                        @click="selectProfessionalType(type.value)"
                                        class="p-3 rounded-2xl border transition-all cursor-pointer flex flex-col justify-between space-y-1"
                                        :class="professionalInfo.professional_type === type.value 
                                            ? 'bg-glam-50 border-glam-600 ring-2 ring-glam-600/30 shadow-sm' 
                                            : 'bg-white border-pink-100 hover:border-pink-300 hover:bg-pink-50/30'"
                                    >
                                        <div class="flex items-center justify-between">
                                            <span class="text-xl">{{ type.icon }}</span>
                                            <span v-if="professionalInfo.professional_type === type.value" class="h-4 w-4 rounded-full bg-glam-700 text-white text-[9px] flex items-center justify-center font-bold">✓</span>
                                        </div>
                                        <div>
                                            <h4 class="font-serif text-xs font-bold text-slate-900">{{ type.label }}</h4>
                                            <p class="text-[10px] text-slate-500 line-clamp-1">{{ type.desc }}</p>
                                        </div>
                                    </div>
                                </div>
                                <p v-if="errors.professional_type" class="mt-1 text-[11px] text-rose-600">{{ errors.professional_type }}</p>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-800 mb-1.5">Years of Professional Experience *</label>
                                <input
                                    v-model="professionalInfo.years_of_experience"
                                    type="number"
                                    min="0"
                                    max="50"
                                    placeholder="e.g. 5"
                                    class="w-full sm:w-64 rounded-xl bg-pink-50/30 px-3.5 py-2.5 text-xs sm:text-sm text-slate-800 border border-pink-200/80 focus:border-glam-600 focus:bg-white transition"
                                    :class="{ 'border-rose-400': errors.years_of_experience }"
                                />
                                <p v-if="errors.years_of_experience" class="mt-1 text-[11px] text-rose-600">{{ errors.years_of_experience }}</p>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-800 mb-1.5">Short Bio * (Shown on search cards)</label>
                                <textarea
                                    v-model="professionalInfo.bio"
                                    rows="2"
                                    placeholder="Specialized in royal bridal glam, HD party makeup, and red-carpet hairstyles..."
                                    class="w-full rounded-xl bg-pink-50/30 px-3.5 py-2.5 text-xs sm:text-sm text-slate-800 border border-pink-200/80 focus:border-glam-600 focus:bg-white transition"
                                    :class="{ 'border-rose-400': errors.bio }"
                                />
                                <p v-if="errors.bio" class="mt-1 text-[11px] text-rose-600">{{ errors.bio }}</p>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-800 mb-1.5">Full Description & Training (Optional)</label>
                                <textarea
                                    v-model="professionalInfo.full_description"
                                    rows="4"
                                    placeholder="Share your certifications, studio setup, brand products used (MAC, Huda Beauty, NARS), and customer satisfaction guarantees..."
                                    class="w-full rounded-xl bg-pink-50/30 px-3.5 py-2.5 text-xs sm:text-sm text-slate-800 border border-pink-200/80 focus:border-glam-600 focus:bg-white transition"
                                />
                            </div>

                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <label class="block text-xs font-bold text-slate-800">Specializations & Signature Treatments</label>
                                    <button
                                        type="button"
                                        @click="toggleAllSpecializations"
                                        class="text-[11px] font-bold text-glam-700 hover:text-glam-900 transition flex items-center gap-1 cursor-pointer bg-pink-100/60 hover:bg-pink-100 px-2.5 py-0.5 rounded-lg border border-pink-200"
                                    >
                                        <span>{{ isAllSpecializationsSelected ? '✕ Deselect All' : '✓ Select All (' + specializationOptions.length + ')' }}</span>
                                    </button>
                                </div>
                                <div class="flex flex-wrap gap-1.5">
                                    <button
                                        v-for="spec in specializationOptions"
                                        :key="spec"
                                        type="button"
                                        @click="toggleSpecialization(spec)"
                                        class="rounded-full px-3 py-1 text-xs font-semibold transition-all cursor-pointer border"
                                        :class="professionalInfo.specializations.includes(spec)
                                            ? 'bg-glam-700 text-white border-glam-700 shadow-xs scale-102'
                                            : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-pink-50 hover:border-pink-300'"
                                    >
                                        {{ spec }} {{ professionalInfo.specializations.includes(spec) ? '✓' : '+' }}
                                    </button>
                                </div>
                            </div>

                            <!-- Spoken Languages -->
                            <div>
                                <label class="block text-xs font-bold text-slate-800 mb-2">Spoken Languages</label>
                                <div class="flex flex-wrap gap-1.5">
                                    <button
                                        v-for="lang in languageOptions"
                                        :key="lang"
                                        type="button"
                                        @click="toggleLanguage(lang)"
                                        class="rounded-full px-3 py-1 text-xs font-semibold transition-all cursor-pointer border"
                                        :class="professionalInfo.languages.includes(lang)
                                            ? 'bg-glam-700 text-white border-glam-700 shadow-xs'
                                            : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-pink-50'"
                                    >
                                        {{ lang }} {{ professionalInfo.languages.includes(lang) ? '✓' : '+' }}
                                    </button>
                                </div>
                            </div>

                            <!-- Home / On-Location Service Mode -->
                            <div class="rounded-2xl border border-pink-200/80 bg-pink-50/40 p-4 space-y-3">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h4 class="font-serif text-sm font-bold text-slate-900 flex items-center gap-1.5">
                                            <span>🏡</span> Provide Home / On-Location Visits
                                        </h4>
                                        <p class="text-[11px] text-slate-500">Offer bridal and makeover appointments at the client's home or venue.</p>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input
                                            type="checkbox"
                                            v-model="professionalInfo.home_service_available"
                                            class="sr-only peer"
                                        />
                                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-glam-700"></div>
                                    </label>
                                </div>

                                <div v-if="professionalInfo.home_service_available" class="pt-3 border-t border-pink-200/60 grid gap-3 sm:grid-cols-2">
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-700 mb-1">Extra Travel Fee (PKR)</label>
                                        <input
                                            v-model.number="professionalInfo.home_service_fee"
                                            type="number"
                                            min="0"
                                            placeholder="e.g. 1500"
                                            class="w-full rounded-xl bg-white px-3.5 py-2 text-xs text-slate-800 border border-pink-200 focus:border-glam-600 focus:outline-none transition"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-700 mb-1">Max Travel Radius (KM)</label>
                                        <input
                                            v-model.number="professionalInfo.max_service_distance"
                                            type="number"
                                            min="1"
                                            max="200"
                                            placeholder="e.g. 25"
                                            class="w-full rounded-xl bg-white px-3.5 py-2 text-xs text-slate-800 border border-pink-200 focus:border-glam-600 focus:outline-none transition"
                                        />
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="block text-[11px] font-bold text-slate-700 mb-1">Preferred Service Neighborhoods / Towns (Optional)</label>
                                        <input
                                            v-model="professionalInfo.service_areas"
                                            type="text"
                                            placeholder="e.g. DHA, Clifton, Gulshan, Bahria Town, Saddar"
                                            class="w-full rounded-xl bg-white px-3.5 py-2 text-xs text-slate-800 border border-pink-200 focus:border-glam-600 focus:outline-none transition"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- STEP 3: LOCATION & MAP PIN -->
                    <div v-if="step === 3" class="space-y-6">
                        <div class="border-b border-pink-100 pb-4">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-pink-700 bg-pink-100 px-2.5 py-0.5 rounded-full">Step 3 of 6</span>
                            <h2 class="font-serif text-2xl font-bold text-slate-900 mt-2">Location & Studio Address</h2>
                            <p class="text-xs text-slate-500">Help clients in your city discover and navigate to your studio or home visit zone.</p>
                        </div>

                        <div class="space-y-5">
                            <div>
                                <label class="block text-xs font-bold text-slate-800 mb-1.5">City *</label>
                                <div class="flex flex-wrap gap-1.5 mb-2.5">
                                    <button
                                        v-for="c in cities"
                                        :key="c"
                                        type="button"
                                        @click="selectCityChip(c)"
                                        class="rounded-full px-3 py-1 text-xs font-semibold transition cursor-pointer border"
                                        :class="locationInfo.city === c 
                                            ? 'bg-glam-700 text-white border-glam-700 shadow-xs' 
                                            : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-pink-50'"
                                    >
                                        {{ c }}
                                    </button>
                                </div>
                                <select
                                    v-model="locationInfo.city"
                                    class="w-full sm:w-72 rounded-xl bg-pink-50/30 px-3.5 py-2.5 text-xs sm:text-sm text-slate-800 border border-pink-200/80 focus:border-glam-600 focus:bg-white transition"
                                    :class="{ 'border-rose-400': errors.city }"
                                >
                                    <option value="">Select or choose city</option>
                                    <option v-for="c in cities" :key="c" :value="c">{{ c }}</option>
                                </select>
                                <p v-if="errors.city" class="mt-1 text-[11px] text-rose-600">{{ errors.city }}</p>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-800 mb-1.5">Area / Neighborhood *</label>
                                <input
                                    v-model="locationInfo.area"
                                    type="text"
                                    placeholder="e.g. Clifton Block 4, DHA Phase 5, Gulberg III"
                                    class="w-full rounded-xl bg-pink-50/30 px-3.5 py-2.5 text-xs sm:text-sm text-slate-800 border border-pink-200/80 focus:border-glam-600 focus:bg-white transition"
                                    :class="{ 'border-rose-400': errors.area }"
                                />
                                <p v-if="errors.area" class="mt-1 text-[11px] text-rose-600">{{ errors.area }}</p>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-800 mb-1.5">Full Studio Address *</label>
                                <textarea
                                    v-model="locationInfo.address"
                                    rows="2"
                                    placeholder="Street address, building number, floor or landmark..."
                                    class="w-full rounded-xl bg-pink-50/30 px-3.5 py-2.5 text-xs sm:text-sm text-slate-800 border border-pink-200/80 focus:border-glam-600 focus:bg-white transition"
                                    :class="{ 'border-rose-400': errors.address }"
                                />
                                <p v-if="errors.address" class="mt-1 text-[11px] text-rose-600">{{ errors.address }}</p>
                            </div>

                            <!-- Map Pin Box with GPS Detect and Direct Inputs -->
                            <div>
                                <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-800">Pinpoint Location on Map</label>
                                        <p class="text-[11px] text-slate-500">Click on map or auto-detect with your GPS.</p>
                                    </div>
                                    <button
                                        type="button"
                                        @click="detectCurrentLocation"
                                        class="text-xs font-bold text-glam-700 hover:text-glam-900 bg-pink-100 hover:bg-pink-200 px-3 py-1.5 rounded-xl border border-pink-300 transition flex items-center gap-1.5 cursor-pointer shadow-xs"
                                    >
                                        <span>📍</span>
                                        <span>Detect My GPS Location</span>
                                    </button>
                                </div>
                                <div id="location-map" class="h-64 w-full rounded-2xl border border-pink-200 shadow-inner z-0"></div>

                                <!-- Latitude & Longitude Direct Fields -->
                                <div class="grid grid-cols-2 gap-3 mt-3">
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-700 mb-1">Latitude (GPS)</label>
                                        <input
                                            v-model.number="locationInfo.latitude"
                                            @input="updateMarkerFromInputs"
                                            type="number"
                                            step="0.000001"
                                            placeholder="e.g. 24.8607"
                                            class="w-full rounded-xl bg-pink-50/30 px-3 py-2 text-xs text-slate-800 border border-pink-200/80 focus:border-glam-600 focus:bg-white transition font-mono"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-700 mb-1">Longitude (GPS)</label>
                                        <input
                                            v-model.number="locationInfo.longitude"
                                            @input="updateMarkerFromInputs"
                                            type="number"
                                            step="0.000001"
                                            placeholder="e.g. 67.0011"
                                            class="w-full rounded-xl bg-pink-50/30 px-3 py-2 text-xs text-slate-800 border border-pink-200/80 focus:border-glam-600 focus:bg-white transition font-mono"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- STEP 4: VERIFICATION DOCUMENTS (CNIC Front & Back) -->
                    <div v-if="step === 4" class="space-y-6">
                        <div class="border-b border-pink-100 pb-4">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-pink-700 bg-pink-100 px-2.5 py-0.5 rounded-full">Step 4 of 6</span>
                            <h2 class="font-serif text-2xl font-bold text-slate-900 mt-2">Verification & Trust Documents</h2>
                            <p class="text-xs text-slate-500">Please provide both Front and Back photos of your original CNIC.</p>
                        </div>

                        <div class="rounded-2xl bg-pink-50/50 p-4 border border-pink-200/80 flex items-center gap-3">
                            <span class="text-2xl">🔒</span>
                            <div class="text-xs text-slate-700">
                                <p class="font-bold text-slate-900">256-Bit Encrypted & Privacy Protected</p>
                                <p class="text-slate-500">Your CNIC and documents are strictly confidential and only reviewed by authorized verification admins.</p>
                            </div>
                        </div>

                        <!-- CNIC 2-Card Grid (Front & Back) -->
                        <div class="grid gap-5 sm:grid-cols-2">
                            <!-- CNIC Front Side -->
                            <div class="rounded-2xl border-2 border-dashed p-5 text-center transition" :class="documents.cnic_front ? 'border-emerald-400 bg-emerald-50/20' : 'border-pink-300 bg-pink-50/20 hover:border-pink-400'">
                                <span class="text-3xl">🪪</span>
                                <h4 class="font-serif text-sm font-bold text-slate-900 mt-2">CNIC Front Side *</h4>
                                <p class="text-[11px] text-slate-500 mb-3">Clear photo of CNIC front with name and photo (JPG, PNG, PDF)</p>
                                <label class="inline-block rounded-xl bg-white px-4 py-2 text-xs font-bold text-glam-700 border border-pink-200 shadow-xs hover:bg-pink-50 cursor-pointer">
                                    <span>{{ documents.cnic_front ? 'Change Front Photo' : 'Upload CNIC Front' }}</span>
                                    <input type="file" @change="handleFileUpload('cnic_front', $event)" accept="image/*,.pdf" class="hidden" />
                                </label>
                                <p v-if="documents.cnic_front" class="mt-2 text-xs font-semibold text-emerald-600">✓ {{ documents.cnic_front.name }}</p>
                                <p v-if="errors.cnic_front" class="mt-1 text-[11px] text-rose-600">{{ errors.cnic_front }}</p>
                            </div>

                            <!-- CNIC Back Side -->
                            <div class="rounded-2xl border-2 border-dashed p-5 text-center transition" :class="documents.cnic_back ? 'border-emerald-400 bg-emerald-50/20' : 'border-pink-300 bg-pink-50/20 hover:border-pink-400'">
                                <span class="text-3xl">🪪</span>
                                <h4 class="font-serif text-sm font-bold text-slate-900 mt-2">CNIC Back Side *</h4>
                                <p class="text-[11px] text-slate-500 mb-3">Clear photo of CNIC back with address and chip (JPG, PNG, PDF)</p>
                                <label class="inline-block rounded-xl bg-white px-4 py-2 text-xs font-bold text-glam-700 border border-pink-200 shadow-xs hover:bg-pink-50 cursor-pointer">
                                    <span>{{ documents.cnic_back ? 'Change Back Photo' : 'Upload CNIC Back' }}</span>
                                    <input type="file" @change="handleFileUpload('cnic_back', $event)" accept="image/*,.pdf" class="hidden" />
                                </label>
                                <p v-if="documents.cnic_back" class="mt-2 text-xs font-semibold text-emerald-600">✓ {{ documents.cnic_back.name }}</p>
                                <p v-if="errors.cnic_back" class="mt-1 text-[11px] text-rose-600">{{ errors.cnic_back }}</p>
                            </div>

                            <!-- Professional Certificate -->
                            <div class="rounded-2xl border-2 border-dashed p-5 text-center transition" :class="documents.professional_certificate ? 'border-emerald-400 bg-emerald-50/20' : 'border-slate-200 bg-slate-50/30 hover:border-pink-300'">
                                <span class="text-3xl">📜</span>
                                <h4 class="font-serif text-sm font-bold text-slate-900 mt-2">Beauty Diploma / Certificate</h4>
                                <p class="text-[11px] text-slate-500 mb-3">Optional: Makeup academy, cosmetology or salon course</p>
                                <label class="inline-block rounded-xl bg-white px-4 py-2 text-xs font-bold text-slate-700 border border-slate-200 shadow-xs hover:bg-slate-50 cursor-pointer">
                                    <span>{{ documents.professional_certificate ? 'Change File' : 'Upload Certificate' }}</span>
                                    <input type="file" @change="handleFileUpload('professional_certificate', $event)" accept="image/*,.pdf" class="hidden" />
                                </label>
                                <p v-if="documents.professional_certificate" class="mt-2 text-xs font-semibold text-emerald-600">✓ {{ documents.professional_certificate.name }}</p>
                            </div>

                            <!-- Business Registration -->
                            <div class="rounded-2xl border-2 border-dashed p-5 text-center transition" :class="documents.business_registration ? 'border-emerald-400 bg-emerald-50/20' : 'border-slate-200 bg-slate-50/30 hover:border-pink-300'">
                                <span class="text-3xl">🏢</span>
                                <h4 class="font-serif text-sm font-bold text-slate-900 mt-2">Salon NTN / Registration</h4>
                                <p class="text-[11px] text-slate-500 mb-3">Optional: Business license or tax certificate</p>
                                <label class="inline-block rounded-xl bg-white px-4 py-2 text-xs font-bold text-slate-700 border border-slate-200 shadow-xs hover:bg-slate-50 cursor-pointer">
                                    <span>{{ documents.business_registration ? 'Change File' : 'Upload Registration' }}</span>
                                    <input type="file" @change="handleFileUpload('business_registration', $event)" accept="image/*,.pdf" class="hidden" />
                                </label>
                                <p v-if="documents.business_registration" class="mt-2 text-xs font-semibold text-emerald-600">✓ {{ documents.business_registration.name }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- STEP 5: REVIEW & SUBMIT APPLICATION -->
                    <div v-if="step === 5" class="space-y-6">
                        <div class="border-b border-pink-100 pb-4">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-pink-700 bg-pink-100 px-2.5 py-0.5 rounded-full">Step 5 of 5</span>
                            <h2 class="font-serif text-2xl font-bold text-slate-900 mt-2">Review & Submit Application</h2>
                            <p class="text-xs text-slate-500">Verify your information before submitting to our concierge onboarding team.</p>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <!-- Basic Info Summary -->
                            <div class="rounded-2xl bg-pink-50/40 p-4 border border-pink-100 space-y-2">
                                <div class="flex items-center justify-between">
                                    <h4 class="font-serif text-xs font-bold text-slate-900">👤 Personal Contact</h4>
                                    <button type="button" @click="goToStep(1)" class="text-[10px] font-bold text-glam-700 hover:underline">Edit</button>
                                </div>
                                <p class="text-xs text-slate-800"><strong>Name:</strong> {{ basicInfo.first_name }} {{ basicInfo.last_name }}</p>
                                <p class="text-xs text-slate-800"><strong>Email:</strong> {{ basicInfo.email }}</p>
                                <p class="text-xs text-slate-800"><strong>Phone:</strong> {{ basicInfo.phone }} (11 Digits)</p>
                            </div>

                            <!-- Business Profile Summary -->
                            <div class="rounded-2xl bg-pink-50/40 p-4 border border-pink-100 space-y-2">
                                <div class="flex items-center justify-between">
                                    <h4 class="font-serif text-xs font-bold text-slate-900">💼 Salon Details</h4>
                                    <button type="button" @click="goToStep(2)" class="text-[10px] font-bold text-glam-700 hover:underline">Edit</button>
                                </div>
                                <p class="text-xs text-slate-800"><strong>Salon:</strong> {{ professionalInfo.business_name }}</p>
                                <p class="text-xs text-slate-800"><strong>Type:</strong> {{ professionalTypes.find(t => t.value === professionalInfo.professional_type)?.label }}</p>
                                <p class="text-xs text-slate-800"><strong>Experience:</strong> {{ professionalInfo.years_of_experience }} Years</p>
                                <p class="text-xs text-slate-800"><strong>Languages:</strong> {{ professionalInfo.languages.join(', ') || 'None selected' }}</p>
                                <p class="text-xs text-slate-800">
                                    <strong>Home Visits:</strong> 
                                    <span v-if="professionalInfo.home_service_available" class="text-emerald-700 font-semibold">
                                        Yes (PKR {{ professionalInfo.home_service_fee }} fee, {{ professionalInfo.max_service_distance }}km radius)
                                    </span>
                                    <span v-else class="text-slate-500">Studio Only</span>
                                </p>
                                <p class="text-xs text-slate-800 line-clamp-1"><strong>Bio:</strong> {{ professionalInfo.bio }}</p>
                            </div>

                            <!-- Location Summary -->
                            <div class="rounded-2xl bg-pink-50/40 p-4 border border-pink-100 space-y-2">
                                <div class="flex items-center justify-between">
                                    <h4 class="font-serif text-xs font-bold text-slate-900">📍 Studio Location</h4>
                                    <button type="button" @click="goToStep(3)" class="text-[10px] font-bold text-glam-700 hover:underline">Edit</button>
                                </div>
                                <p class="text-xs text-slate-800"><strong>City:</strong> {{ locationInfo.city }}</p>
                                <p class="text-xs text-slate-800"><strong>Area:</strong> {{ locationInfo.area }}</p>
                                <p class="text-xs text-slate-800 line-clamp-1"><strong>Address:</strong> {{ locationInfo.address }}</p>
                                <p v-if="locationInfo.latitude && locationInfo.longitude" class="text-xs text-emerald-700 font-mono">
                                    <strong>GPS Pin:</strong> {{ Number(locationInfo.latitude).toFixed(4) }}, {{ Number(locationInfo.longitude).toFixed(4) }}
                                </p>
                            </div>

                            <!-- Verification Summary -->
                            <div class="rounded-2xl bg-pink-50/40 p-4 border border-pink-100 space-y-2">
                                <div class="flex items-center justify-between">
                                    <h4 class="font-serif text-xs font-bold text-slate-900">📄 CNIC Verification</h4>
                                    <button type="button" @click="goToStep(4)" class="text-[10px] font-bold text-glam-700 hover:underline">Edit</button>
                                </div>
                                <p class="text-xs text-emerald-700">✓ CNIC Front: {{ documents.cnic_front?.name }}</p>
                                <p class="text-xs text-emerald-700">✓ CNIC Back: {{ documents.cnic_back?.name }}</p>
                                <p class="text-[11px] text-slate-500 italic pt-1">✨ Profile photo, cover banner & portfolio gallery can be customized directly from your studio profile once approved.</p>
                            </div>
                        </div>

                        <!-- TERMS & CONDITIONS AGREEMENT BLOCK -->
                        <div class="rounded-3xl bg-linear-to-br from-pink-50/90 via-white to-rose-50/80 p-5 sm:p-6 border border-pink-200 shadow-xs space-y-4">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-pink-100 pb-3">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-[10px] font-bold uppercase tracking-wider text-rose-700 bg-rose-100 px-2.5 py-0.5 rounded-full">Legal & Partner Agreement</span>
                                        <span class="text-[10px] font-bold text-slate-500 bg-slate-100 px-2 py-0.5 rounded-full">قوانین و ضوابط</span>
                                    </div>
                                    <h3 class="font-serif text-base font-bold text-slate-900 mt-1">
                                        {{ termsLanguage === 'ur' ? 'بیوٹی بک پارٹنر شرائط و ضوابط اور قانونی معاہدہ' : 'Marketplace Partner Terms, Code of Conduct & Policies' }}
                                    </h3>
                                </div>

                                <div class="flex items-center gap-2 self-start sm:self-auto">
                                    <!-- Language Toggle -->
                                    <div class="inline-flex rounded-xl bg-slate-100 p-1 border border-slate-200 text-xs font-bold">
                                        <button
                                            type="button"
                                            @click="termsLanguage = 'en'"
                                            :class="termsLanguage === 'en' ? 'bg-white text-rose-700 shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                                            class="px-2.5 py-1 rounded-lg transition cursor-pointer"
                                        >
                                            English
                                        </button>
                                        <button
                                            type="button"
                                            @click="termsLanguage = 'ur'"
                                            :class="termsLanguage === 'ur' ? 'bg-white text-rose-700 shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                                            class="px-2.5 py-1 rounded-lg transition cursor-pointer font-serif"
                                        >
                                            اردو
                                        </button>
                                    </div>

                                    <button
                                        type="button"
                                        @click="showTermsModal = true"
                                        class="inline-flex items-center gap-1.5 text-xs font-bold text-glam-700 hover:text-glam-900 bg-pink-100 hover:bg-pink-200 px-3 py-1.5 rounded-xl border border-pink-300 transition cursor-pointer shadow-xs"
                                    >
                                        <span>📄</span>
                                        <span>{{ termsLanguage === 'ur' ? 'مکمل معاہدہ پڑھیں' : 'Read Full Policies' }}</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Highlights Grid (English or Urdu) -->
                            <div v-if="termsLanguage === 'en'" class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs text-slate-700">
                                <div class="flex items-start gap-2.5 rounded-2xl bg-rose-50/70 p-3.5 border border-rose-200">
                                    <span class="text-rose-600 text-lg">🚫</span>
                                    <div>
                                        <strong class="text-rose-950 block font-bold">Zero Tolerance: Illegal & Sexual Services</strong>
                                        <span class="text-[11px] text-rose-800 leading-tight block mt-0.5">Strict prohibition of illegal, sexual, or adult activities. Violations result in instant lifetime ban and legal prosecution (Police & FIA).</span>
                                    </div>
                                </div>

                                <div class="flex items-start gap-2.5 rounded-2xl bg-amber-50/70 p-3.5 border border-amber-200">
                                    <span class="text-amber-600 text-lg">🛡️</span>
                                    <div>
                                        <strong class="text-amber-950 block font-bold">No Fake Products & Stolen Images</strong>
                                        <span class="text-[11px] text-amber-800 leading-tight block mt-0.5">Counterfeit/expired cosmetics or uploading third-party/fake portfolio photos is strictly prohibited. All content must be 100% genuine.</span>
                                    </div>
                                </div>

                                <div class="flex items-start gap-2.5 rounded-2xl bg-slate-50 p-3.5 border border-slate-200">
                                    <span class="text-indigo-600 text-lg">⚖️</span>
                                    <div>
                                        <strong class="text-slate-900 block font-bold">Platform Limitation of Liability</strong>
                                        <span class="text-[11px] text-slate-600 leading-tight block mt-0.5">BeautyBook is a technology marketplace and holds NO liability for salon disputes, allergic reactions, service quality, or independent artist actions.</span>
                                    </div>
                                </div>

                                <div class="flex items-start gap-2.5 rounded-2xl bg-emerald-50/70 p-3.5 border border-emerald-200">
                                    <span class="text-emerald-600 text-lg">💰</span>
                                    <div>
                                        <strong class="text-emerald-950 block font-bold">10% Platform Commission & Payouts</strong>
                                        <span class="text-[11px] text-emerald-800 leading-tight block mt-0.5">Standard 10% platform fee on fulfilled client appointments and salon product sales. Direct 90% bank payouts.</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Urdu Highlights Grid -->
                            <div v-else dir="rtl" class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs text-slate-700 font-sans text-right">
                                <div class="flex items-start gap-2.5 rounded-2xl bg-rose-50/70 p-3.5 border border-rose-200">
                                    <span class="text-rose-600 text-lg">🚫</span>
                                    <div>
                                        <strong class="text-rose-950 block font-bold text-[13px]">غیر قانونی و جنسی سرگرمیوں پر مکمل پابندی</strong>
                                        <span class="text-[11px] text-rose-800 leading-normal block mt-0.5">مساج یا بیوٹی سروسز کی آڑ میں کسی بھی قسم کی فحاشی یا جنسی مطالبات کی قطعی ممانعت ہے۔ خلاف ورزی پر فوری مستقل پابندی اور قانونی کارروائی (پولیس اور ایف آئی اے) ہو گی۔</span>
                                    </div>
                                </div>

                                <div class="flex items-start gap-2.5 rounded-2xl bg-amber-50/70 p-3.5 border border-amber-200">
                                    <span class="text-amber-600 text-lg">🛡️</span>
                                    <div>
                                        <strong class="text-amber-950 block font-bold text-[13px]">جعلی مصنوعات اور چوری شدہ تصاویر پر پابندی</strong>
                                        <span class="text-[11px] text-amber-800 leading-normal block mt-0.5">دو نمبر، غیر معیاری یا ایکسپائرڈ پروڈکٹس فروخت کرنا اور انٹرنیٹ سے کسی دوسرے کی جعلی تصاویر لگانا سخت منع ہے۔ تمام ڈیٹا اصلی ہونا لازمی ہے۔</span>
                                    </div>
                                </div>

                                <div class="flex items-start gap-2.5 rounded-2xl bg-slate-50 p-3.5 border border-slate-200">
                                    <span class="text-indigo-600 text-lg">⚖️</span>
                                    <div>
                                        <strong class="text-slate-900 block font-bold text-[13px]">پلیٹ فارم کی ذمہ داری سے استثنیٰ (Disclaimer)</strong>
                                        <span class="text-[11px] text-slate-600 leading-normal block mt-0.5">بیوٹی بک محض ٹیکنالوجی پلیٹ فارم ہے۔ کسی بھی ذاتی تنازعے، جلد کی الرجی، سروس کے نقصانات یا آرٹسٹ کے عمل کا پلیٹ فارم ہرگز ذمہ دار نہیں ہوگا۔ آرٹسٹ خود 100٪ ذمہ دار ہے۔</span>
                                    </div>
                                </div>

                                <div class="flex items-start gap-2.5 rounded-2xl bg-emerald-50/70 p-3.5 border border-emerald-200">
                                    <span class="text-emerald-600 text-lg">💰</span>
                                    <div>
                                        <strong class="text-emerald-950 block font-bold text-[13px]">10 فیصد پلیٹ فارم کمیشن اور بینک ٹرانسفر</strong>
                                        <span class="text-[11px] text-emerald-800 leading-normal block mt-0.5">مکمل ہونے والی بکنگز اور پروڈکٹ آرڈرز پر 10٪ پلیٹ فارم فیس لاگو ہو گی۔ 90٪ خالص آمدن براہِ راست بینک اکاؤنٹ میں منتقل کی جائے گی۔</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Interactive Agreement Checkbox -->
                            <div class="rounded-2xl border-2 border-pink-200 bg-white p-4">
                                <label class="flex items-start gap-3 cursor-pointer text-xs text-slate-800">
                                    <input
                                        v-model="agreedToTerms"
                                        id="artist-terms-check"
                                        type="checkbox"
                                        class="h-4 w-4 mt-0.5 rounded text-glam-600 focus:ring-glam-500 cursor-pointer shrink-0"
                                    />
                                    <span v-if="termsLanguage === 'en'" class="font-medium leading-relaxed">
                                        I have read, understood, and agree to the 
                                        <button type="button" @click.stop="showTermsModal = true" class="font-bold text-glam-700 hover:underline mx-1 cursor-pointer">
                                            BeautyBook Luxe Artist Partner Terms of Service
                                        </button>, 
                                        <strong>Illegal Activity & Product Authenticity Policy</strong>, and 
                                        <strong>Platform Limitation of Liability Agreement</strong>. I certify that all submitted credentials and CNIC documents are authentic.
                                    </span>
                                    <span v-else dir="rtl" class="font-medium leading-relaxed font-sans text-right">
                                        میں نے 
                                        <button type="button" @click.stop="showTermsModal = true" class="font-bold text-glam-700 hover:underline mx-1 cursor-pointer">
                                            بیوٹی بک لکس آرٹسٹ پارٹنر شرائط و ضوابط
                                        </button>، 
                                        <strong>غیر قانونی کام اور جعلی مصنوعات کی ممانعت کی پالیسی</strong> اور 
                                        <strong>پلیٹ فارم کے عدم ذمہ داری معاہدے</strong> کو پڑھ کر تسلیم کر لیا ہے۔ میں تصدیق کرتا/کرتی ہوں کہ تمام شناختی کوائف اور تصاویر اصلی ہیں۔
                                    </span>
                                </label>
                            </div>
                        </div>

                        <div class="rounded-2xl bg-amber-50 p-4 border border-amber-200 text-xs text-amber-900 space-y-1">
                            <p class="font-bold">✨ What happens next?</p>
                            <p class="text-amber-800">Our concierge onboarding team typically reviews and verifies salon applications within 24 business hours. You will receive an SMS and email notification upon approval.</p>
                        </div>
                    </div>

                    <!-- 4. NAVIGATION ACTION CONTROLS -->
                    <div class="flex items-center justify-between pt-8 mt-8 border-t border-pink-100">
                        <button
                            v-if="step > 1"
                            type="button"
                            @click="goToStep(step - 1)"
                            class="rounded-xl border border-pink-200 bg-white px-5 py-2.5 text-xs font-bold uppercase tracking-wider text-slate-700 hover:bg-pink-50 transition cursor-pointer"
                        >
                            &larr; Back
                        </button>
                        <div v-else></div>

                        <div class="flex items-center gap-3">
                            <button
                                v-if="step < 5"
                                type="button"
                                @click="goToStep(step + 1)"
                                :disabled="!canProceedToNextStep"
                                class="rounded-xl px-6 py-2.5 text-xs font-bold uppercase tracking-wider text-white shadow-md transition cursor-pointer"
                                :class="canProceedToNextStep 
                                    ? 'bg-gradient-to-r from-glam-600 via-glam-700 to-rose-900 hover:scale-102 shadow-pink-900/20' 
                                    : 'bg-slate-300 text-slate-500 cursor-not-allowed'"
                            >
                                Continue &rarr;
                            </button>

                            <button
                                v-if="step === 5"
                                type="button"
                                @click="trySubmit"
                                :disabled="loading || !agreedToTerms"
                                class="rounded-xl bg-gradient-to-r from-glam-600 via-glam-700 to-rose-900 px-8 py-3 text-xs font-bold uppercase tracking-wider text-white shadow-lg shadow-pink-900/30 transition hover:scale-102 cursor-pointer disabled:bg-slate-300 disabled:cursor-not-allowed flex items-center gap-2"
                            >
                                <span v-if="loading" class="animate-spin text-sm">⏳</span>
                                <span>{{ loading ? 'Submitting Application...' : 'Submit Application &rarr;' }}</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Already Registered Link -->
                <div class="text-center pt-2">
                    <p class="text-xs text-slate-600">
                        Already have an approved artist account?
                        <Link :href="route('login')" class="font-bold text-glam-700 hover:text-glam-900 underline ml-1">
                            Sign In to Portal
                        </Link>
                    </p>
                </div>
            </div>
        </div>

        <!-- FULL TERMS & CONDITIONS MODAL (BILINGUAL ENGLISH & URDU) -->
        <transition
            enter-active-class="ease-out duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="ease-in duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="showTermsModal"
                class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/75 backdrop-blur-sm flex items-center justify-center p-3 sm:p-6"
            >
                <div
                    @click.stop
                    class="relative w-full max-w-4xl rounded-3xl bg-white shadow-2xl overflow-hidden border border-pink-100 flex flex-col max-h-[94vh]"
                >
                    <!-- Modal Header with Language Switch -->
                    <div class="px-6 py-4 border-b border-pink-100 flex items-center justify-between bg-pink-50/60 shrink-0">
                        <div class="flex items-center gap-3">
                            <div>
                                <span class="text-[10px] font-bold uppercase tracking-wider text-rose-700 bg-rose-100 px-2 py-0.5 rounded-full">Legal & Partner Agreement</span>
                                <h3 class="font-serif text-lg sm:text-xl font-bold text-slate-900 mt-1">
                                    {{ termsLanguage === 'ur' ? 'بیوٹی بک لکس پارٹنر شرائط و ضوابط اور قانونی پالیسی' : 'BeautyBook Luxe Artist & Salon Partner Terms of Service' }}
                                </h3>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <!-- Toggle Button in Modal -->
                            <div class="inline-flex rounded-xl bg-white p-1 border border-pink-200 text-xs font-bold shadow-2xs">
                                <button
                                    type="button"
                                    @click="termsLanguage = 'en'"
                                    :class="termsLanguage === 'en' ? 'bg-rose-600 text-white' : 'text-slate-600 hover:text-slate-900'"
                                    class="px-3 py-1 rounded-lg transition cursor-pointer"
                                >
                                    English
                                </button>
                                <button
                                    type="button"
                                    @click="termsLanguage = 'ur'"
                                    :class="termsLanguage === 'ur' ? 'bg-rose-600 text-white font-serif' : 'text-slate-600 hover:text-slate-900'"
                                    class="px-3 py-1 rounded-lg transition cursor-pointer font-serif"
                                >
                                    اردو
                                </button>
                            </div>

                            <button
                                @click="showTermsModal = false"
                                type="button"
                                class="flex h-8 w-8 items-center justify-center rounded-full bg-white hover:bg-slate-200 text-slate-700 text-xs font-bold border border-slate-200 transition cursor-pointer"
                            >
                                ✕
                            </button>
                        </div>
                    </div>

                    <!-- Modal Body (ENGLISH VIEW) -->
                    <div v-if="termsLanguage === 'en'" class="p-6 overflow-y-auto space-y-5 text-xs text-slate-700 leading-relaxed text-left">
                        <div class="rounded-2xl bg-pink-50/60 p-4 border border-pink-100 text-slate-800 text-xs">
                            <p><strong>Effective Date:</strong> August 2026</p>
                            <p class="mt-1 text-slate-600">Please carefully review the partner policies governing artist accounts, service conduct, marketplace sales, and legal responsibilities on the BeautyBook Luxe platform.</p>
                        </div>

                        <!-- Clause 1: Illegal & Sexual Activities Ban -->
                        <div class="space-y-1.5 rounded-2xl bg-rose-50/50 p-3.5 border border-rose-200">
                            <h4 class="font-serif font-bold text-sm text-rose-950 flex items-center gap-2">
                                <span class="text-rose-600 font-black">1.</span> STRICT BAN ON ILLEGAL WORK & SEXUAL/ADULT SERVICES
                            </h4>
                            <p class="text-rose-900">
                                BeautyBook Luxe enforces a <strong>STRICT ZERO-TOLERANCE POLICY</strong> against prostitution, solicitation of sexual acts, pornography, harassment, or adult-related conduct. Massage and salon services must NEVER be used as a front for illicit or inappropriate services. Any partner involved in such activities will face immediate permanent account termination, confiscation of pending payouts, and direct criminal reporting to law enforcement agencies (Pakistan Police & FIA Cybercrime / Anti-Human Trafficking Wings).
                            </p>
                        </div>

                        <!-- Clause 2: Counterfeit Products & Stolen Images -->
                        <div class="space-y-1.5 rounded-2xl bg-amber-50/50 p-3.5 border border-amber-200">
                            <h4 class="font-serif font-bold text-sm text-amber-950 flex items-center gap-2">
                                <span class="text-amber-600 font-black">2.</span> PROHIBITION ON BOGUS/FAKE PRODUCTS & STOLEN IMAGES
                            </h4>
                            <p class="text-amber-900">
                                Artists and salon vendors are strictly prohibited from selling fake, unbranded, expired, toxic, or counterfeit cosmetic/skincare items. Furthermore, uploading stolen stock photos, another stylist's portfolio work, or false/manipulated before-and-after imagery is strictly forbidden. All portfolio and retail product images must be 100% authentic and produced by the registered salon.
                            </p>
                        </div>

                        <!-- Clause 3: Platform Limitation of Liability -->
                        <div class="space-y-1.5 rounded-2xl bg-slate-50 p-3.5 border border-slate-200">
                            <h4 class="font-serif font-bold text-sm text-slate-900 flex items-center gap-2">
                                <span class="text-indigo-600 font-black">3.</span> PLATFORM DISCLAIMER & LIMITATION OF LIABILITY
                            </h4>
                            <p class="text-slate-800">
                                BeautyBook Luxe operates exclusively as an independent technological intermediary connecting beauty service providers and clients. <strong>THE PLATFORM ASSUMES NO LEGAL RESPONSIBILITY OR LIABILITY</strong> for:
                            </p>
                            <ul class="list-disc pl-5 space-y-1 text-slate-700 mt-1">
                                <li>Allergic reactions, chemical burns, hair damage, or physical injuries arising from artist treatments or sold retail cosmetics.</li>
                                <li>Property damage, theft, personal disputes, or behavioral misconduct occurring during home visits or studio appointments.</li>
                                <li>Any unauthorized, fraudulent, or illegal acts committed by individual artists, salon employees, or clients.</li>
                            </ul>
                            <p class="text-slate-800 mt-1">Each beauty partner operates as an independent contractor and retains 100% sole civil, criminal, and financial liability for their actions and services.</p>
                        </div>

                        <!-- Clause 4: Platform Commission (10%) -->
                        <div class="space-y-1.5">
                            <h4 class="font-serif font-bold text-sm text-slate-900 flex items-center gap-2">
                                <span class="text-rose-600">4.</span> Marketplace Commission & Financial Payouts
                            </h4>
                            <p>BeautyBook Luxe charges a standard <strong>10% platform commission fee</strong> on completed service bookings and multi-vendor salon product sales. Net sales proceeds (90%) are transferred directly to the artist's verified bank account on a bi-weekly cycle.</p>
                        </div>

                        <!-- Clause 5: Booking Attendance & Rescheduling -->
                        <div class="space-y-1.5">
                            <h4 class="font-serif font-bold text-sm text-slate-900 flex items-center gap-2">
                                <span class="text-rose-600">5.</span> Booking Attendance, Rescheduling & No-Show SLA
                            </h4>
                            <p>Artists agree to maintain an accurate calendar and honor all confirmed appointments on time. If an artist must reschedule due to emergency, a minimum of <strong>12 hours advance notice</strong> must be provided to the client. Repeated unexcused cancellations will lead to marketplace suspension.</p>
                        </div>

                        <!-- Clause 6: Health & Hygiene Standards -->
                        <div class="space-y-1.5">
                            <h4 class="font-serif font-bold text-sm text-slate-900 flex items-center gap-2">
                                <span class="text-rose-600">6.</span> Health, Sanitation & Client Safety
                            </h4>
                            <p>All makeup brushes, nail implements, hair cutting shears, and facial machinery must be thoroughly sterilized and disinfected before every client session. Only skin-safe, certified cosmetic products are permissible.</p>
                        </div>

                        <!-- Clause 7: Privacy & Data Protection -->
                        <div class="space-y-1.5">
                            <h4 class="font-serif font-bold text-sm text-slate-900 flex items-center gap-2">
                                <span class="text-rose-600">7.</span> Privacy, Conduct & Account Termination
                            </h4>
                            <p>Client contact details and residential addresses must never be disclosed or used outside of appointment fulfillment. BeautyBook Luxe reserves the right to terminate accounts that breach platform trust.</p>
                        </div>
                    </div>

                    <!-- Modal Body (URDU VIEW) -->
                    <div v-else dir="rtl" class="p-6 overflow-y-auto space-y-5 text-xs text-slate-700 leading-relaxed font-sans text-right">
                        <div class="rounded-2xl bg-pink-50/60 p-4 border border-pink-100 text-slate-800 text-xs">
                            <p class="font-bold text-slate-900">تاریخِ نفاذ: اگست 2026</p>
                            <p class="mt-1 text-slate-600">براہِ کرم بیوٹی بک لکس پر بطور آرٹسٹ یا سیلون رجسٹریشن کروانے سے پہلے درج ذیل تمام قانونی شرائط اور پالیسیوں کو غور سے پڑھ کر تسلیم کریں۔</p>
                        </div>

                        <!-- اردو کلاز 1: غیر قانونی اور جنسی کام کی مکمل ممانعت -->
                        <div class="space-y-1.5 rounded-2xl bg-rose-50/60 p-3.5 border border-rose-200">
                            <h4 class="font-serif font-bold text-sm text-rose-950 flex items-center gap-2">
                                <span class="text-rose-600 font-black">1.</span> غیر قانونی کام اور جنسی / فحش سرگرمیوں پر سخت ترین پابندی (Zero Tolerance)
                            </h4>
                            <p class="text-rose-900 leading-relaxed">
                                بیوٹی بک لکس پر کسی بھی قسم کے غیر قانونی کام، جسم فروشی، جنسی خدمات کی ترغیب، فحاشی، نامناسب رویے یا ہراسانی کی <strong>سخت ترین ممانعت</strong> ہے۔ مساج یا بیوٹی سروسز کی آڑ میں کسی بھی غیر اخلاقی یا فحش سرگرمی کی قطعی اجازت نہیں ہے۔ خلاف ورزی کرنے والے کا اکاؤنٹ فوری طور پر ہمیشہ کے لیے بند کر دیا جائے گا، تمام بقایا رقوم ضبط کر لی جائیں گی اور ملزم کے خلاف فوری طور پر متعلقہ قانونی اداروں (پولیس اور ایف آئی اے سائبر کرائم) کو رپورٹ کر کے سخت قانونی کارروائی عمل میں لائی جائے گی۔
                            </p>
                        </div>

                        <!-- اردو کلاز 2: جعلی مصنوعات اور چوری شدہ تصاویر پر پابندی -->
                        <div class="space-y-1.5 rounded-2xl bg-amber-50/60 p-3.5 border border-amber-200">
                            <h4 class="font-serif font-bold text-sm text-amber-950 flex items-center gap-2">
                                <span class="text-amber-600 font-black">2.</span> جعلی / بوگس مصنوعات اور چوری شدہ تصاویر پر مکمل پابندی
                            </h4>
                            <p class="text-amber-900 leading-relaxed">
                                دو نمبر، جعلی، ایکسپائرڈ، غیر معیاری، یا نقصان دہ بیوٹی اور اسکن کیئر مصنوعات بیچنا سخت جرم ہے۔ اس کے علاوہ انٹرنیٹ سے کسی دوسرے آرٹسٹ کے کام کی تصاویر چوری کر کے اپنے پورٹ فولیو میں لگانا یا جعلی دعوے کرنا سخت منع ہے۔ آرٹسٹ کا تمام پورٹ فولیو اور فروخت کی جانے والی مصنوعات 100٪ اصلی اور تصدیق شدہ ہونی چاہئیں۔
                            </p>
                        </div>

                        <!-- اردو کلاز 3: پلیٹ فارم کی عدم ذمہ داری اور قانونی استثنیٰ -->
                        <div class="space-y-1.5 rounded-2xl bg-slate-50 p-3.5 border border-slate-200">
                            <h4 class="font-serif font-bold text-sm text-slate-900 flex items-center gap-2">
                                <span class="text-indigo-600 font-black">3.</span> پلیٹ فارم کی ذمہ داری سے استثنیٰ (Legal Disclaimer)
                            </h4>
                            <p class="text-slate-800 leading-relaxed">
                                بیوٹی بک لکس محض ایک آزاد ٹیکنالوجی پلیٹ فارم ہے جو کلائنٹس اور بیوٹی پروفیشنلز کو آپس میں جوڑتا ہے۔ <strong>پلیٹ فارم درج ذیل کسی بھی معاملے کا قطعی ذمہ دار یا جوابدہ نہیں ہوگا:</strong>
                            </p>
                            <ul class="list-disc pr-5 space-y-1 text-slate-700 mt-1">
                                <li>کسی بھی سروس کے دوران جلد کے جلنے، بالوں کے خراب ہونے، الرجی یا کسی جسمانی نقصان کی صورت میں۔</li>
                                <li>ہوم سروس یا اسٹوڈیو میں چوری، مالی نقصان، ذاتی جھگڑے یا کسی غیر اخلاقی رویے کی صورت میں۔</li>
                                <li>آرٹسٹ یا اس کے عملے کے کسی بھی غیر مجاز، دھوکہ دہی یا غیر قانونی عمل کی صورت میں۔</li>
                            </ul>
                            <p class="text-slate-800 mt-1 font-semibold">ہر آرٹسٹ ایک آزاد بزنس کے طور پر کام کرتا ہے اور اپنے تمام کاموں، کلائنٹس کے ساتھ لین دین اور حفاظتی اقدامات کا بذاتِ خود 100 فیصد قانونی اور مالی طور پر ذمہ دار ہے۔</p>
                        </div>

                        <!-- اردو کلاز 4: 10 فیصد کمیشن -->
                        <div class="space-y-1.5">
                            <h4 class="font-serif font-bold text-sm text-slate-900 flex items-center gap-2">
                                <span class="text-rose-600 font-black">4.</span> پلیٹ فارم کمیشن (10٪) اور بینک ادائیگیاں
                            </h4>
                            <p class="leading-relaxed">مکمل ہونے والی تمام بکنگز اور مصنوعات کی فروخت پر 10 فیصد مارکیٹ پلیس فیس لاگو ہو گی۔ 90 فیصد خالص رقم شیڈول کے مطابق آرٹسٹ کے تصدیق شدہ بینک اکاؤنٹ میں منتقل کی جائے گی۔</p>
                        </div>

                        <!-- اردو کلاز 5: بکنگز اور وقت کی پابندی -->
                        <div class="space-y-1.5">
                            <h4 class="font-serif font-bold text-sm text-slate-900 flex items-center gap-2">
                                <span class="text-rose-600 font-black">5.</span> اپائنٹمنٹس کی پابندی اور منسوخی کی پالیسی
                            </h4>
                            <p class="leading-relaxed">آرٹسٹ تصدیق شدہ بکنگ کے وقت پر پہنچنے کا پابند ہے۔ کسی ناگزیر ایمرجنسی کی صورت میں کم از کم 12 گھنٹے پہلے کلائنٹ کو مطلع کرنا لازمی ہے۔ بار بار بکنگ منسوخ کرنے پر اکاؤنٹ پر پابندی لگائی جا سکتی ہے۔</p>
                        </div>

                        <!-- اردو کلاز 6: صفائی اور حفظانِ صحت کے اصول -->
                        <div class="space-y-1.5">
                            <h4 class="font-serif font-bold text-sm text-slate-900 flex items-center gap-2">
                                <span class="text-rose-600 font-black">6.</span> صفائی اور حفظانِ صحت کے اصول (Hygiene Standards)
                            </h4>
                            <p class="leading-relaxed">تمام بیوٹی ٹولز، برش، قینچی اور مشینوں کو ہر کلائنٹ کے بعد سینیٹائز کرنا لازمی ہے۔ صرف اسکن فرینڈلی اور محفوظ کاسمیٹکس کے استعمال کی اجازت ہے۔</p>
                        </div>

                        <!-- اردو کلاز 7: کلائنٹ ڈیٹا کی رازداری -->
                        <div class="space-y-1.5">
                            <h4 class="font-serif font-bold text-sm text-slate-900 flex items-center gap-2">
                                <span class="text-rose-600 font-black">7.</span> کلائنٹ کی رازداری اور ضابطہ اخلاق
                            </h4>
                            <p class="leading-relaxed">کلائنٹ کے فون نمبر اور پتے کی مکمل رازداری برقرار رکھی جائے گی۔ کسی بھی کلائنٹ کا ڈیٹا ذاتی مقاصد یا کسی تیسرے فریق کو فراہم کرنا سخت ممنوع ہے۔</p>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="px-6 py-4 border-t border-pink-100 flex items-center justify-between bg-slate-50 shrink-0">
                        <span class="text-[11px] text-slate-500">
                            {{ termsLanguage === 'ur' ? 'تسلیم کرنے پر آپ کا سیلون ان تمام قواعد کا پابند ہو گا۔' : 'By accepting, you bind your salon profile to these operational guidelines.' }}
                        </span>
                        <div class="flex items-center gap-2.5">
                            <button
                                @click="showTermsModal = false"
                                type="button"
                                class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 transition cursor-pointer"
                            >
                                {{ termsLanguage === 'ur' ? 'بند کریں' : 'Close' }}
                            </button>
                            <button
                                @click="acceptTermsFromModal"
                                type="button"
                                class="rounded-xl bg-gradient-to-r from-glam-600 via-glam-700 to-rose-900 px-6 py-2 text-xs font-bold uppercase tracking-wider text-white shadow-md transition hover:scale-102 cursor-pointer font-sans"
                            >
                                {{ termsLanguage === 'ur' ? '✓ میں نے شرائط پڑھ کر تسلیم کر لیں' : '✓ I Understand & Accept Terms' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </transition>
    </PublicLayout>
</template>
