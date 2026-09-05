<script setup>
import { ref } from 'vue';

const props = defineProps({
    modelValue: {
        type: [File, Array],
        default: null
    },
    label: {
        type: String,
        required: true
    },
    accept: {
        type: String,
        default: 'image/*'
    },
    multiple: {
        type: Boolean,
        default: false
    },
    maxSize: {
        type: Number,
        default: 5 // MB
    },
    preview: {
        type: Boolean,
        default: true
    }
});

const emit = defineEmits(['update:modelValue', 'error']);

const error = ref('');
const dragOver = ref(false);

function handleFileSelect(event) {
    const files = event.target.files;
    processFiles(files);
}

function handleDrop(event) {
    event.preventDefault();
    dragOver.value = false;
    const files = event.dataTransfer.files;
    processFiles(files);
}

function processFiles(files) {
    error.value = '';
    
    if (files.length === 0) return;
    
    if (props.multiple) {
        const fileArray = Array.from(files);
        const validFiles = [];
        
        for (const file of fileArray) {
            if (validateFile(file)) {
                validFiles.push(file);
            }
        }
        
        emit('update:modelValue', [...(props.modelValue || []), ...validFiles]);
    } else {
        const file = files[0];
        if (validateFile(file)) {
            emit('update:modelValue', file);
        }
    }
}

function validateFile(file) {
    // Check file size
    const fileSizeMB = file.size / (1024 * 1024);
    if (fileSizeMB > props.maxSize) {
        error.value = `File size must be less than ${props.maxSize}MB`;
        emit('error', error.value);
        return false;
    }
    
    // Check file type
    const acceptedTypes = props.accept.split(',').map(t => t.trim());
    const fileExtension = '.' + file.name.split('.').pop().toLowerCase();
    const isAccepted = acceptedTypes.some(type => {
        if (type.startsWith('.')) {
            return fileExtension === type;
        }
        return file.type.startsWith(type.replace('*', ''));
    });
    
    if (!isAccepted) {
        error.value = `File type not accepted. Accepted types: ${props.accept}`;
        emit('error', error.value);
        return false;
    }
    
    return true;
}

function removeFile(index) {
    if (props.multiple && Array.isArray(props.modelValue)) {
        const newFiles = [...props.modelValue];
        newFiles.splice(index, 1);
        emit('update:modelValue', newFiles);
    } else {
        emit('update:modelValue', null);
    }
}

function getPreviewUrl(file) {
    if (file instanceof File) {
        return URL.createObjectURL(file);
    }
    return file;
}
</script>

<template>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">{{ label }}</label>
        
        <div
            @dragover.prevent="dragOver = true"
            @dragleave.prevent="dragOver = false"
            @drop="handleDrop"
            :class="[
                'border-2 border-dashed rounded-lg p-6 text-center transition-colors',
                dragOver ? 'border-pink-500 bg-pink-50' : 'border-gray-300 hover:border-gray-400'
            ]"
        >
            <input
                type="file"
                :accept="accept"
                :multiple="multiple"
                @change="handleFileSelect"
                class="hidden"
                :id="`file-upload-${label.replace(/\s/g, '-')}`"
            />
            <label :for="`file-upload-${label.replace(/\s/g, '-')}`" class="cursor-pointer">
                <div class="text-4xl mb-2">📁</div>
                <p class="text-gray-600 mb-1">
                    <span class="text-pink-600 font-medium">Click to upload</span> or drag and drop
                </p>
                <p class="text-xs text-gray-500">
                    {{ accept }} (Max {{ maxSize }}MB)
                </p>
            </label>
        </div>
        
        <p v-if="error" class="mt-2 text-sm text-red-600">{{ error }}</p>
        
        <!-- Previews -->
        <div v-if="preview && modelValue" class="mt-4">
            <div v-if="multiple && Array.isArray(modelValue)" class="grid grid-cols-4 gap-4">
                <div
                    v-for="(file, index) in modelValue"
                    :key="index"
                    class="relative group"
                >
                    <img
                        :src="getPreviewUrl(file)"
                        :alt="file.name"
                        class="w-full h-24 object-cover rounded-lg"
                    />
                    <button
                        @click="removeFile(index)"
                        class="absolute top-1 right-1 w-6 h-6 bg-red-500 text-white rounded-full opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center"
                    >
                        ✕
                    </button>
                    <p class="text-xs text-gray-600 mt-1 truncate">{{ file.name }}</p>
                </div>
            </div>
            <div v-else class="relative inline-block">
                <img
                    :src="getPreviewUrl(modelValue)"
                    :alt="modelValue.name"
                    class="w-32 h-32 object-cover rounded-lg"
                />
                <button
                    @click="removeFile(0)"
                    class="absolute top-1 right-1 w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center"
                >
                    ✕
                </button>
                <p class="text-xs text-gray-600 mt-1 truncate">{{ modelValue.name }}</p>
            </div>
        </div>
    </div>
</template>
