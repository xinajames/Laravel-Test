<script setup>
import { v4 as uuid } from 'uuid';
import { ref, watch, onMounted, onUnmounted, nextTick } from 'vue';

// emits
const emits = defineEmits([
    'input',
    'filter-data',
    'blur',
    'update:modelValue',
    'update-data',
    'clear-input',
]);

// props
const props = defineProps({
    dataList: Array,
    disabled: { type: Boolean, default: false },
    freeText: { type: Boolean, default: false, required: false },
    id: {
        type: String,
        default() {
            return `search-input-dropdown-${uuid()}`;
        },
    },
    inputClass: { type: String, default: '' },
    label: { type: String, default: '' },
    modelValue: [String, Number],
    menuClass: { type: String, default: '' },
    placeholder: String,
    required: Boolean,
    type: { type: String, default: '' },
    withImage: { type: Boolean, default: false },
});

// data
const dropdownOpen = ref(false);
const filteredData = ref([]);
const showClearButton = ref(false);
const searching = ref(false);
const selectedValue = ref(null);
const dropdownStyle = ref({});
const inputContainerRef = ref(null);

// Watch for changes in modelValue (input field)
watch(
    () => props.modelValue,
    (newVal) => {
        if (!newVal) {
            selectedValue.value = null; // Clear selected value when input is empty
        }
    }
);

// methods
function clearInput() {
    emits('clear-input');
    showClearButton.value = false;
    selectedValue.value = null;
}

function filterData(value) {
    emits('update:modelValue', value);
    let searchInput = value.toLowerCase();
    searching.value = true;
    filteredData.value = props.dataList.filter((data) => {
        if (data.label) {
            return data.label.toLowerCase().includes(searchInput);
        }
    });
    emits('filter-data', filteredData.value);
}

function keyup() {
    if (props.modelValue) {
        if (props.modelValue.length > 0) {
            searching.value = true;
        }
    }
}

function selectedOption(index) {
    dropdownOpen.value = false;
    showClearButton.value = true;
    if (searching.value && props.modelValue !== null) {
        selectedValue.value = filteredData.value[index];
    } else {
        selectedValue.value = props.dataList[index];
    }
    emits('update-data', selectedValue.value);
    searching.value = false;
}

function toggleDropdown() {
    if (!dropdownOpen.value) {
        window.dispatchEvent(new CustomEvent('close-dropdowns'));
    }
    dropdownOpen.value = !dropdownOpen.value;

    if (dropdownOpen.value) {
        nextTick(() => {
            const rect = inputContainerRef.value.getBoundingClientRect();
            dropdownStyle.value = {
                top: `${rect.bottom + window.scrollY}px`,
                left: `${rect.left + window.scrollX}px`,
                width: `${rect.width}px`,
            };
        });
    }
}

onMounted(() => {
    window.addEventListener('close-dropdowns', closeDropdown);
});

onUnmounted(() => {
    window.removeEventListener('close-dropdowns', closeDropdown);
});

function closeDropdown() {
    dropdownOpen.value = false;
}
</script>

<template>
    <div ref="inputContainerRef">
        <label v-if="label" :for="id" class="block text-sm/6 font-medium text-gray-900">
            {{ label }}
            <span v-if="required" class="text-red-500">*</span>
        </label>
        <div class="mt-1 relative rounded-md shadow-sm">
            <!-- Selected value with image inside the input -->
            <div
                v-if="withImage && selectedValue && selectedValue.image"
                class="absolute left-3 top-1/2 transform -translate-y-1/2 flex items-center"
            >
                <img
                    :src="selectedValue.image"
                    alt="selected icon"
                    class="mr-2 w-5 h-5 rounded-full"
                />
            </div>

            <!-- Input Field -->
            <input
                :id="id"
                ref="input"
                :class="[
                    { 'bg-gray-100 text-gray-500': disabled },
                    inputClass,
                    {
                        'px-10': withImage && selectedValue && selectedValue.image,
                        'px-3': !(selectedValue && selectedValue.image),
                    },
                ]"
                :placeholder="placeholder"
                :required="required"
                :value="modelValue"
                class="block w-full rounded-md border border-gray-300 bg-white py-1.5 text-base outline-none focus:ring-0 focus:ring-primary focus:border-primary sm:text-sm disabled:cursor-not-allowed disabled:bg-gray-50 disabled:text-gray-500"
                v-bind="$attrs"
                @blur="$emit('blur', $event.target.value)"
                @focus="toggleDropdown"
                @input="filterData($event.target.value)"
                @keyup="keyup($event)"
            />

            <!-- Icons (Search or Close) -->
            <div class="pointer-events-none absolute inset-y-0 right-2 flex items-center">
                <svg
                    v-if="!searching"
                    class="size-5"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.5"
                    viewBox="0 0 24 24"
                    xmlns="http://www.w3.org/2000/svg"
                >
                    <path
                        d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />
                </svg>
                <svg
                    v-else
                    class="size-5"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.5"
                    viewBox="0 0 24 24"
                    xmlns="http://www.w3.org/2000/svg"
                >
                    <path
                        d="m19.5 8.25-7.5 7.5-7.5-7.5"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />
                </svg>
            </div>
        </div>

        <!-- Dropdown rendered outside modal -->
        <teleport to="body">
            <div
                v-if="dropdownOpen"
                :style="dropdownStyle"
                class="absolute z-[9999] bg-white shadow-lg rounded-sm max-h-44 overflow-y-auto"
            >
                <Transition
                    enter-active-class="transition ease-out duration-200"
                    enter-from-class="opacity-0 scale-95"
                    enter-to-class="opacity-100 scale-100"
                    leave-active-class="transition ease-in duration-75"
                    leave-from-class="opacity-100 scale-100"
                    leave-to-class="opacity-0 scale-95"
                >
                    <div>
                        <div class="px-2 py-1">
                            <div v-if="searching && modelValue !== null">
                                <div
                                    v-if="
                                        filteredData.length === 0 && searching && freeText === false
                                    "
                                >
                                    <a
                                        class="group flex items-center px-2 py-2 text-sm leading-5 text-black-darkest cursor-pointer"
                                        role="menuitem"
                                    >
                                        Sorry, we couldn't find any results
                                    </a>
                                </div>
                                <div v-for="(result, index) in filteredData" :key="index">
                                    <div class="py-1" @click="selectedOption(index)">
                                        <a
                                            class="group flex items-center px-2 py-2 text-sm leading-5 text-black-darkest cursor-pointer"
                                        >
                                            <img
                                                v-if="result.image && withImage"
                                                :src="result.image"
                                                alt="icon"
                                                class="mr-2 w-5 h-5 rounded-full"
                                            />
                                            {{ result.label }}
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div v-else>
                                <div v-for="(data, index) in dataList" :key="index">
                                    <div class="py-1">
                                        <div
                                            class="group flex items-center px-2 py-2 text-sm leading-5 text-black-darkest cursor-pointer"
                                            @click="selectedOption(index)"
                                        >
                                            <img
                                                v-if="data.image && withImage"
                                                :src="data.image"
                                                alt="icon"
                                                class="mr-2 w-5 h-5 rounded-full"
                                            />
                                            {{ data.label }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </Transition>
            </div>
        </teleport>
    </div>
</template>
