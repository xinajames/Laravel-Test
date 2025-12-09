<script setup>
import { CheckCircleIcon } from '@heroicons/vue/20/solid';
import { QUESTIONNAIRE_ANSWER } from '@/Composables/Enums.js';
import RemarkItem from '@/Components/StoreRatings/RemarkItem.vue';

const props = defineProps({
    questionnaires: Object,
});

function computeTotal(questionnaire) {
    let total = 0;
    let length = 0;
    if (Array.isArray(questionnaire)) {
        questionnaire.forEach((question) => {
            if (question.answer === QUESTIONNAIRE_ANSWER.Yes) {
                total += 100;
            }
        });
        length += questionnaire.length;
    } else {
        for (const index in questionnaire) {
            questionnaire[index].forEach((question) => {
                if (question.answer === QUESTIONNAIRE_ANSWER.Yes) {
                    total += 100;
                }
            });
            length += questionnaire[index].length;
        }
    }
    return total > 0 ? Math.round(total / length) : 0;
}

function handlePercentage(answer) {
    if (answer !== QUESTIONNAIRE_ANSWER.NotApplicable) {
        return answer === QUESTIONNAIRE_ANSWER.Yes ? '100%' : '0%';
    }
    return null;
}
</script>

<template>
    <div class="space-y-6">
        <div v-for="(questionnaire, index) in questionnaires ?? []" :key="index">
            <div class="bg-white rounded-2xl border border-gray-200 p-4">
                <h5 class="text-lg font-bold text-gray-900">
                    {{ index }}
                </h5>

                <div class="bg-gray-50 rounded-2xl p-4 mt-4">
                    <!-- Header Row -->
                    <div class="grid grid-cols-12 gap-6 border-b pb-2">
                        <p class="col-span-8 text-sm font-semibold text-gray-500">
                            Assessment Areas
                        </p>
                        <p class="col-span-1 text-sm font-semibold text-gray-500">Yes</p>
                        <p class="col-span-1 text-sm font-semibold text-gray-500">No</p>
                        <p class="col-span-1 text-sm font-semibold text-gray-500">N/A</p>
                        <p class="col-span-1 text-sm font-semibold text-gray-500">
                            {{ computeTotal(questionnaire) }}%
                        </p>
                    </div>

                    <!-- Content -->
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
                            >
                                <p
                                    v-if="categoryIndex === 'Store Conditions'"
                                    class="font-bold text-gray-900 py-3 border-y"
                                >
                                    {{ categoryIndex }}
                                </p>
                                <p v-else class="font-semibold text-gray-900 py-3 border-y">
                                    {{
                                        Object.keys(questionnaire).indexOf(categoryIndex) +
                                        (questionnaire['Assessment Areas']?.length ?? 1)
                                    }}. {{ categoryIndex }}
                                </p>
                            </div>

                            <!-- Question Rows -->
                            <div
                                v-for="(question, qIndex) in questionnaire[categoryIndex]"
                                :key="qIndex"
                            >
                                <div
                                    :class="
                                        qIndex !== questionnaire[categoryIndex].length - 1
                                            ? 'border-b'
                                            : ''
                                    "
                                    class="grid grid-cols-12 gap-6 py-3"
                                >
                                    <!-- Question Text -->
                                    <div class="col-span-8 text-sm font-medium text-gray-900">
                                        <span
                                            :class="[
                                                categoryIndex === 'Assessment Areas' ||
                                                categoryIndex === 'Store Conditions'
                                                    ? 'visible font-semibold text-gray-600'
                                                    : 'invisible',
                                            ]"
                                        >
                                            {{
                                                categoryIndex === 'Store Conditions'
                                                    ? '19.' + question.order
                                                    : question.order
                                            }}.
                                        </span>
                                        <span class="text-gray-900">{{ question.question }}</span>
                                        <div v-if="question.remarks?.length">
                                            <remark-item
                                                v-for="remark in question.remarks"
                                                :key="remark.id"
                                                :remark="remark"
                                            />
                                        </div>
                                    </div>

                                    <!-- Answer Icons -->
                                    <div class="col-span-1">
                                        <check-circle-icon
                                            v-if="question.answer === QUESTIONNAIRE_ANSWER.Yes"
                                            class="text-primary size-5"
                                        />
                                    </div>
                                    <div class="col-span-1">
                                        <check-circle-icon
                                            v-if="question.answer === QUESTIONNAIRE_ANSWER.No"
                                            class="text-primary size-5"
                                        />
                                    </div>
                                    <div class="col-span-1">
                                        <check-circle-icon
                                            v-if="
                                                question.answer ===
                                                QUESTIONNAIRE_ANSWER.NotApplicable
                                            "
                                            class="text-primary size-5"
                                        />
                                    </div>

                                    <!-- Percentage -->
                                    <div class="col-span-1 text-sm text-gray-500">
                                        {{ handlePercentage(question.answer) }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Flat Question Row -->
                        <div
                            v-else
                            :class="index === questionnaire.length - 1 ? '' : 'border-b'"
                            class="grid grid-cols-12 gap-6 py-3"
                        >
                            <p class="col-span-8 text-sm font-medium text-gray-900">
                                {{ category.order }}. {{ category.question }}
                            </p>
                            <div class="col-span-1">
                                <check-circle-icon
                                    v-if="category.answer === QUESTIONNAIRE_ANSWER.Yes"
                                    class="text-primary size-5"
                                />
                            </div>
                            <div class="col-span-1">
                                <check-circle-icon
                                    v-if="category.answer === QUESTIONNAIRE_ANSWER.No"
                                    class="text-primary size-5"
                                />
                            </div>
                            <div class="col-span-1">
                                <check-circle-icon
                                    v-if="category.answer === QUESTIONNAIRE_ANSWER.NotApplicable"
                                    class="text-primary size-5"
                                />
                            </div>
                            <div class="col-span-1 text-sm text-gray-500">
                                {{ handlePercentage(category.answer) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
