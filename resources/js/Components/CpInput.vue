<script setup>
import { computed, ref } from 'vue';
import CpIcon from '@/Components/CpIcon.vue';

const props = defineProps({
    modelValue: String,
    type: {
        type: String,
        default: 'text',
    },
    label: String,
    error: String,
    id: String,
    placeholder: {
        type: String,
        default: '',
    },
    required: {
        type: Boolean,
        default: false,
    },
    autofocus: {
        type: Boolean,
        default: false,
    },
    autocomplete: {
        type: String,
        default: '',
    },
});

defineEmits(['update:modelValue']);

const showPassword = ref(false);
const isPasswordType = computed(() => props.type === 'password');
const resolvedType = computed(() => {
    if (isPasswordType.value && showPassword.value) return 'text';
    return props.type;
});
</script>

<template>
    <div>
        <label v-if="label" :for="id" class="cp-label">
            {{ label }}
            <span v-if="required" class="text-state-danger ml-0.5">*</span>
        </label>
        <div class="relative">
            <input
                :id="id"
                :type="resolvedType"
                :value="modelValue"
                :placeholder="placeholder"
                :required="required"
                :autofocus="autofocus"
                :autocomplete="autocomplete"
                class="cp-input"
                :class="{ 'pr-10': isPasswordType }"
                @input="$emit('update:modelValue', $event.target.value)"
            />
            <button
                v-if="isPasswordType"
                type="button"
                tabindex="-1"
                @click="showPassword = !showPassword"
                class="absolute inset-y-0 right-0 flex items-center pr-3 text-cafe-400 hover:text-accent-500 dark:text-cafe-500 dark:hover:text-accent-400 transition-colors duration-150"
            >
                <CpIcon :name="showPassword ? 'eye-off' : 'eye'" :size="18" />
            </button>
        </div>
        <p v-if="error" class="cp-error">{{ error }}</p>
    </div>
</template>
