<script setup lang="ts">
export interface SelectOption {
    value: string | number;
    label: string;
}

interface Props {
    modelValue?: string | number | null;
    label?: string;
    error?: string;
    id?: string;
    options?: SelectOption[];
    required?: boolean;
    placeholder?: string;
}

withDefaults(defineProps<Props>(), {
    options: () => [],
    required: false,
    placeholder: '',
});

defineEmits<(e: 'update:modelValue', value: string) => void>();
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
            @change="$emit('update:modelValue', ($event.target as HTMLSelectElement).value)"
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
