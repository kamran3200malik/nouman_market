<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    modelValue: {
        type: Object,
        required: true
    },
    holidays: {
        type: Array,
        default: () => []
    }
});

const emit = defineEmits(['update:modelValue', 'addHoliday', 'removeHoliday']);

const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

const newHoliday = ref({
    date: '',
    reason: ''
});

function updateDaySchedule(day, field, value) {
    const updatedSchedule = { ...props.modelValue };
    if (!updatedSchedule[day]) {
        updatedSchedule[day] = { enabled: false, slots: [] };
    }
    updatedSchedule[day][field] = value;
    emit('update:modelValue', updatedSchedule);
}

function addSlot(day) {
    const updatedSchedule = { ...props.modelValue };
    if (!updatedSchedule[day]) {
        updatedSchedule[day] = { enabled: true, slots: [] };
    }
    updatedSchedule[day].slots.push({ start: '09:00', end: '17:00' });
    emit('update:modelValue', updatedSchedule);
}

function removeSlot(day, index) {
    const updatedSchedule = { ...props.modelValue };
    updatedSchedule[day].slots.splice(index, 1);
    emit('update:modelValue', updatedSchedule);
}

function updateSlot(day, index, field, value) {
    const updatedSchedule = { ...props.modelValue };
    updatedSchedule[day].slots[index][field] = value;
    emit('update:modelValue', updatedSchedule);
}

function addHoliday() {
    if (newHoliday.value.date) {
        emit('addHoliday', { ...newHoliday.value });
        newHoliday.value = { date: '', reason: '' };
    }
}

function removeHoliday(index) {
    emit('removeHoliday', index);
}
</script>

<template>
    <div class="space-y-6">
        <!-- Weekly Schedule -->
        <div class="bg-white rounded-xl p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Weekly Schedule</h3>
            <p class="text-sm text-gray-600 mb-6">Set your regular working hours for each day of the week.</p>
            
            <div class="space-y-4">
                <div
                    v-for="day in days"
                    :key="day"
                    class="border border-gray-200 rounded-lg p-4"
                >
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-3">
                            <input
                                type="checkbox"
                                :checked="modelValue[day]?.enabled || false"
                                @change="updateDaySchedule(day, 'enabled', $event.target.checked)"
                                class="rounded border-gray-300 text-pink-600 focus:ring-pink-500"
                            />
                            <span class="font-medium text-gray-900">{{ day }}</span>
                        </div>
                    </div>
                    
                    <div v-if="modelValue[day]?.enabled" class="space-y-3 ml-7">
                        <div
                            v-for="(slot, index) in (modelValue[day]?.slots || [])"
                            :key="index"
                            class="flex items-center gap-2"
                        >
                            <input
                                type="time"
                                :value="slot.start"
                                @input="updateSlot(day, index, 'start', $event.target.value)"
                                class="px-3 py-2 rounded-lg border border-gray-300 focus:border-pink-500 focus:ring-pink-500"
                            />
                            <span class="text-gray-500">to</span>
                            <input
                                type="time"
                                :value="slot.end"
                                @input="updateSlot(day, index, 'end', $event.target.value)"
                                class="px-3 py-2 rounded-lg border border-gray-300 focus:border-pink-500 focus:ring-pink-500"
                            />
                            <button
                                @click="removeSlot(day, index)"
                                class="text-red-600 hover:text-red-700"
                            >
                                ✕
                            </button>
                        </div>
                        
                        <button
                            @click="addSlot(day)"
                            class="text-sm text-pink-600 hover:text-pink-700"
                        >
                            + Add Time Slot
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Holidays -->
        <div class="bg-white rounded-xl p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Holidays & Unavailable Dates</h3>
            <p class="text-sm text-gray-600 mb-6">Mark dates when you will not be available for bookings.</p>
            
            <div class="flex gap-4 mb-6">
                <input
                    v-model="newHoliday.date"
                    type="date"
                    class="flex-1 px-4 py-2 rounded-lg border border-gray-300 focus:border-pink-500 focus:ring-pink-500"
                />
                <input
                    v-model="newHoliday.reason"
                    type="text"
                    placeholder="Reason (optional)"
                    class="flex-1 px-4 py-2 rounded-lg border border-gray-300 focus:border-pink-500 focus:ring-pink-500"
                />
                <button
                    @click="addHoliday"
                    class="px-6 py-2 bg-pink-600 text-white rounded-lg hover:bg-pink-700"
                >
                    Add Holiday
                </button>
            </div>
            
            <div v-if="holidays.length > 0" class="space-y-2">
                <div
                    v-for="(holiday, index) in holidays"
                    :key="index"
                    class="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
                >
                    <div>
                        <p class="font-medium text-gray-900">{{ new Date(holiday.date).toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) }}</p>
                        <p v-if="holiday.reason" class="text-sm text-gray-600">{{ holiday.reason }}</p>
                    </div>
                    <button
                        @click="removeHoliday(index)"
                        class="text-red-600 hover:text-red-700"
                    >
                        ✕
                    </button>
                </div>
            </div>
            
            <div v-else class="text-center py-8 text-gray-500">
                <p>No holidays added</p>
            </div>
        </div>
    </div>
</template>
