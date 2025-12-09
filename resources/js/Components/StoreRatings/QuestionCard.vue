<script setup>
import CameraIcon from '@/Components/Icon/CameraIcon.vue';
import QuestionItem from '@/Components/StoreRatings/QuestionItem.vue';
import PhotographIcon from '@/Components/Icon/PhotographIcon.vue';
import PrimaryButton from '@/Components/Common/Button/PrimaryButton.vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    questionnaires: Object,
    storeRating: Object,
});
</script>

<template>
    <div>
        <div class="flex justify-between items-center">
            <h5 class="text-xl font-semibold">{{ storeRating.step_label || 'N/A' }}</h5>
            <div class="flex gap-4">
                <PrimaryButton
                    @click="
                        () => {
                            router.visit(route('storeRatings.upload', storeRating.id));
                        }
                    "
                >
                    <CameraIcon />
                    Add
                </PrimaryButton>
                <button
                    v-if="storeRating && storeRating.photos.length > 0"
                    class="!bg-rose-50 flex py-2 px-4 rounded-md items-center gap-2 text-sm font-medium text-[#A32130]"
                    @click="$emit('openLightBox')"
                >
                    <PhotographIcon />
                    All Photos ({{ storeRating.photos.length }})
                </button>
            </div>
        </div>

        <div
            v-for="(questionnaire, questionnaireIndex) in questionnaires ?? []"
            :key="questionnaireIndex"
            class="mt-6"
        >
            <div class="border border-gray-200 rounded-2xl">
                <div class="bg-gray-700 p-4 rounded-t-2xl">
                    <p class="text-white font-bold text-sm">
                        {{ questionnaireIndex }}
                    </p>
                </div>
                <div>
                    <div
                        v-for="(category, categoryIndex) in questionnaire ?? []"
                        :key="categoryIndex"
                    >
                        <div v-if="Array.isArray(category)">
                            <div
                                v-if="
                                    Object.keys(questionnaire).length > 0 &&
                                    categoryIndex !== 'Assessment Areas'
                                "
                                class="flex gap-8 items-center border-y bg-white px-4 py-3"
                            >
                                <div
                                    v-if="categoryIndex !== 'Store Conditions'"
                                    class="rounded-full bg-gray-200 h-8 w-8 flex items-center justify-center font-semibold text-sm flex-shrink-0"
                                >
                                    {{
                                        Object.keys(questionnaire).indexOf(categoryIndex) +
                                        (questionnaire['Assessment Areas']?.length ?? 1)
                                    }}
                                </div>
                                <p class="text-gray-900 font-semibold">
                                    {{ categoryIndex }}
                                </p>
                            </div>
                            <div class="divide-y">
                                <question-item
                                    v-for="(question, qIndex) in questionnaire[categoryIndex]"
                                    :category="categoryIndex"
                                    :custom-class="
                                        question.order % 2 === 0 ? 'bg-white' : 'bg-gray-50'
                                    "
                                    :question="question"
                                    :custom-order="
                                        categoryIndex === 'Store Conditions'
                                            ? `19.${question.order}`
                                            : null
                                    "
                                />
                            </div>
                        </div>
                        <div v-else>
                            <question-item
                                category="Assessment Areas"
                                :custom-class="category.order % 2 !== 0 ? 'bg-white' : 'bg-gray-50'"
                                :question="category"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
