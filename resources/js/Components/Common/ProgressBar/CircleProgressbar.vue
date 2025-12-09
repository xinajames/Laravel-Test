<script setup>
import { CheckIcon } from '@heroicons/vue/20/solid';

defineProps({
    currentIndex: Number,
    steps: {
        type: Array,
        required: true,
    },
});
</script>

<template>
    <nav aria-label="Progress">
        <ol role="list" class="flex items-center w-full">
            <li
                v-for="(step, stepIdx) in steps"
                :key="step.name"
                class="relative flex-1 flex flex-col items-center"
            >
                <!-- Progress Line (before the step, except first step) -->
                <div
                    v-if="stepIdx !== 0"
                    class="absolute left-0 right-1/2 top-4 h-[2px]"
                    :class="
                        stepIdx === currentIndex || currentIndex > stepIdx
                            ? 'bg-primary'
                            : 'bg-gray-200'
                    "
                ></div>

                <!-- Progress Line (after the step, except last step) -->
                <div
                    v-if="stepIdx !== steps.length - 1"
                    class="absolute left-1/2 right-0 top-4 h-[2px]"
                    :class="currentIndex > stepIdx ? 'bg-primary' : 'bg-gray-200'"
                ></div>

                <!-- Step Indicator -->
                <div class="relative z-10 flex items-center justify-center">
                    <template v-if="currentIndex > stepIdx">
                        <a
                            href="#"
                            class="relative flex size-8 items-center justify-center rounded-full bg-primary hover:bg-indigo-900"
                        >
                            <CheckIcon class="size-5 text-white" aria-hidden="true" />
                        </a>
                    </template>
                    <template v-else-if="currentIndex === stepIdx">
                        <a
                            href="#"
                            class="relative flex size-8 items-center justify-center rounded-full border-2 border-primary bg-white"
                            aria-current="step"
                        >
                            <span class="size-2.5 rounded-full bg-primary" aria-hidden="true" />
                        </a>
                    </template>
                    <template v-else>
                        <a
                            href="#"
                            class="group relative flex size-8 items-center justify-center rounded-full border-2 border-gray-300 bg-white hover:border-gray-400"
                        >
                            <span
                                class="size-2.5 rounded-full bg-transparent group-hover:bg-gray-300"
                                aria-hidden="true"
                            />
                        </a>
                    </template>
                </div>

                <!-- Step Name Below -->
                <p class="mt-2 text-xs text-gray-700 text-center truncate">{{ step.name }}</p>
            </li>
        </ol>
    </nav>
</template>
