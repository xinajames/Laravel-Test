<script setup>
import { CheckIcon, FlagIcon } from '@heroicons/vue/24/solid';
import { reactive, ref, watch } from 'vue';
import { CREATE_STORE_STEP } from '@/Composables/Enums.js';

const props = defineProps({
    currentStep: { type: String, default: 'basic-details' },
});

const currentStepIndex = ref(0); // Basic details
const steps = reactive([
    {
        id: '01',
        name: 'BASIC DETAILS',
        subText: 'Enter the store name, type, and general details.',
        status: 1,
        value: CREATE_STORE_STEP.BasicDetails,
    },
    {
        id: '02',
        name: 'CONTACT INFO',
        subText: 'Provide the store’s primary contact details.',
        status: 0,
        value: CREATE_STORE_STEP.ContactInfo,
    },
    {
        id: '03',
        name: 'SPECIFICATIONS',
        subText: 'Add location size, equipment, or other store specifications.',
        status: 0,
        value: CREATE_STORE_STEP.Specifications,
    },
    {
        id: '04',
        name: 'STORE REQUIREMENTS',
        subText: 'List any special requirements or provisions for the store.',
        status: 0,
        value: CREATE_STORE_STEP.StoreRequirements,
    },
    {
        id: '05',
        name: 'FINISH',
        subText: 'Review the information and complete store creation.',
        status: 0,
        value: CREATE_STORE_STEP.Finished,
        icon: FlagIcon,
    },
]);

watch(
    () => props.currentStep,
    (currentStep) => {
        if (currentStep) {
            currentStepIndex.value = steps.findIndex((step) => step.value === currentStep);
        }
    },
    {
        immediate: true,
    }
);
</script>

<template>
    <div class="border-gray-200">
        <nav aria-label="Progress" class="mx-auto max-w-md">
            <ol class="rounded-md flex flex-col" role="list">
                <li
                    v-for="(step, stepIdx) in steps"
                    :key="step.value"
                    class="relative flex-1 overflow-hidden border-t first:border-t-0 border-gray-200s"
                >
                    <div
                        :class="[
                            stepIdx === currentStepIndex
                                ? 'border-l-4 border-primary'
                                : 'border-l-4 border-transparent',
                            'overflow-hidden',
                        ]"
                    >
                        <!-- Completed Step -->
                        <div class="group flex items-start px-6 py-5 text-sm font-medium">
                            <div>
                                <div class="flex-shrink-0">
                                    <div
                                        v-if="stepIdx < currentStepIndex"
                                        class="flex size-10 items-center justify-center rounded-full bg-primary"
                                    >
                                        <CheckIcon aria-hidden="true" class="size-6 text-white" />
                                    </div>
                                    <div
                                        v-else-if="stepIdx === currentStepIndex"
                                        class="flex size-10 items-center justify-center rounded-full border-2 border-primary"
                                    >
                                        <!-- If the step has an icon, show it instead of the step ID -->
                                        <template v-if="step.icon">
                                            <component
                                                :is="step.icon"
                                                class="size-6 text-primary"
                                            />
                                        </template>
                                        <template v-else>
                                            <span class="text-primary">{{ step.id }}</span>
                                        </template>
                                    </div>
                                    <div
                                        v-else-if="stepIdx === steps.length - 1 && step.icon"
                                        class="flex size-10 items-center justify-center rounded-full border-2 border-gray-300"
                                    >
                                        <!-- Resolve the icon explicitly -->
                                        <component :is="step.icon" class="size-6 text-gray-500" />
                                    </div>
                                    <div
                                        v-else
                                        class="flex size-10 items-center justify-center rounded-full border-2 border-gray-300"
                                    >
                                        <span class="text-gray-500">{{ step.id }}</span>
                                    </div>
                                </div>
                            </div>
                            <span class="ml-4 flex min-w-0 flex-col">
                                <span
                                    :class="[
                                        stepIdx === currentStepIndex ? 'text-primary' : '',
                                        stepIdx > currentStepIndex
                                            ? 'text-gray-500 font-medium'
                                            : 'font-semibold',
                                    ]"
                                    class="text-xs font-medium cursor-pointer select-none"
                                >
                                    {{ step.name }}
                                </span>
                                <span class="text-sm font-medium text-gray-500">
                                    {{ step.subText }}
                                </span>
                            </span>
                        </div>
                    </div>
                </li>
            </ol>
        </nav>
    </div>
</template>
