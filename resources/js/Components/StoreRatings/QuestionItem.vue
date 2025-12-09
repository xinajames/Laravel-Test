<script setup>
import { HandThumbUpIcon, HandThumbDownIcon } from '@heroicons/vue/20/solid';
import { QUESTIONNAIRE_ANSWER } from '@/Composables/Enums.js';
import { reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import AddCommentModal from '@/Components/Modal/AddCommentModal.vue';
import AnnotationIcon from '@/Components/Icon/AnnotationIcon.vue';
import RemarkItem from '@/Components/StoreRatings/RemarkItem.vue';
import SecondaryButton from '@/Components/Default/SecondaryButton.vue';

const emits = defineEmits(['comment']);

const props = defineProps({
    category: { type: String, default: null },
    question: Object,
    customClass: String,
    customOrder: { type: String, default: null },
});

const addComment = reactive({
    open: false,
    model: 'store-rating-questionnaire',
    data: null,
});

function openCommentModal() {
    addComment.open = true;
    addComment.data = props.question;
}

function selectOption(value) {
    let data = {
        answer: value,
    };
    router.post(route('storeRatingQuestionnaire.update', props.question.id), data, {
        preserveScroll: true,
    });
}

function saveComment(remarks) {
    props.question.remarks = remarks;
}
</script>
<template>
    <div class="px-4 py-3 rounded-b-2xl" :class="customClass">
        <div class="flex gap-8 items-center justify-between">
            <div class="flex gap-8 items-center">
                <div
                    :class="
                        category === 'Assessment Areas' || customOrder ? 'visible' : 'invisible'
                    "
                    class="rounded-full bg-gray-200 h-8 w-8 flex items-center justify-center font-semibold text-sm flex-shrink-0"
                >
                    {{ customOrder ?? question.order }}
                </div>
                <p class="text-sm font-medium text-gray-900 break-words">
                    {{ question.question }}
                </p>
            </div>
            <div v-if="question.selectable !== false" class="flex gap-4 items-center">
                <div class="flex gap-2 items-center text-sm">
                    <div class="isolate inline-flex rounded-md shadow-sm">
                        <button
                            type="button"
                            class="relative inline-flex gap-2 items-center rounded-l-md py-1.5 pl-3 pr-4 border border-gray-200 hover:bg-gray-50 focus:z-10"
                            :class="
                                question.answer === QUESTIONNAIRE_ANSWER.Yes
                                    ? 'bg-green-600 text-white hover:bg-green-700'
                                    : 'bg-white text-gray-400'
                            "
                            @click="selectOption(QUESTIONNAIRE_ANSWER.Yes)"
                        >
                            <HandThumbUpIcon class="size-5" aria-hidden="true" />
                            <span
                                :class="
                                    question.answer === QUESTIONNAIRE_ANSWER.Yes
                                        ? 'text-white'
                                        : 'text-gray-700'
                                "
                            >
                                Yes
                            </span>
                        </button>

                        <button
                            type="button"
                            class="relative -ml-px inline-flex gap-2 items-center rounded-r-md py-1.5 pl-3 pr-4 border border-gray-200 focus:z-10"
                            :class="
                                question.answer === QUESTIONNAIRE_ANSWER.No
                                    ? 'bg-red-500 text-white hover:bg-red-700'
                                    : 'bg-white text-gray-400 hover:bg-gray-50'
                            "
                            @click="selectOption(QUESTIONNAIRE_ANSWER.No)"
                        >
                            <HandThumbDownIcon class="size-5" aria-hidden="true" />
                            <span
                                :class="
                                    question.answer === QUESTIONNAIRE_ANSWER.No
                                        ? 'text-white'
                                        : 'text-gray-700'
                                "
                            >
                                No
                            </span>
                        </button>
                    </div>

                    <button
                        type="button"
                        class="relative inline-flex gap-2 items-center rounded-md py-1.5 pl-3 pr-4 border border-gray-200 focus:z-10"
                        :class="
                            question.answer === QUESTIONNAIRE_ANSWER.NotApplicable
                                ? 'bg-gray-700 text-white hover:bg-gray-900'
                                : 'bg-white text-gray-400 hover:bg-gray-50'
                        "
                        @click="selectOption(QUESTIONNAIRE_ANSWER.NotApplicable)"
                    >
                        <span
                            :class="
                                question.answer === QUESTIONNAIRE_ANSWER.NotApplicable
                                    ? 'text-white'
                                    : 'text-gray-700'
                            "
                        >
                            N/A
                        </span>
                    </button>
                </div>

                <SecondaryButton @click="openCommentModal">
                    <AnnotationIcon />
                </SecondaryButton>
            </div>
        </div>
        <div v-if="question.remarks && question.remarks.length > 0">
            <remark-item v-for="remark in question.remarks" :remark="remark" page="create" />
        </div>

        <AddCommentModal
            :data="addComment.data"
            :model="addComment.model"
            :open="addComment.open"
            @close="addComment.open = false"
            @save="saveComment"
        />
    </div>
</template>
