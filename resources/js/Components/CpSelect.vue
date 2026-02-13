<script setup>
defineProps({
    modelValue: String,
    label: String,
    error: String,
    id: String,
    options: {
        type: Array,
        default: () => [],
        // Each option: { value: '', label: '' }
    },
    required: {
        type: Boolean,
        default: false,
    },
    placeholder: {
        type: String,
        default: '',
    },
});

defineEmits(['update:modelValue']);
</script>

<template>
    <div>
        <label v-if="label" :for="id" class="cp-label">
            {{ label }}
            <span v-if="required" class="text-state-danger ml-0.5">*</span>
        </label>
        <select
            :id="id"
            :value="modelValue"
            :required="required"
            class="cp-input"
            @change="$emit('update:modelValue', $event.target.value)"
        >
            <option v-if="placeholder" value="" disabled>{{ placeholder }}</option>
            <option
                v-for="option in options"
                :key="option.value"
                :value="option.value"
            >
                {{ option.label }}
            </option>
        </select>
        <p v-if="error" class="cp-error">{{ error }}</p>
    </div>
</template>
