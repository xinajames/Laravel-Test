<script setup>
import DragAndDropFileUpload from '@/Components/Common/File/DragAndDropFileUpload.vue';
import FileItem from '@/Components/Shared/FileItem.vue';

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    withHeader: {
        type: Boolean,
        default: true,
    },
});

function handleUpload(files, index) {
    const section = props.form.documents_others?.[index];
    if (!section) return;

    if (!Array.isArray(section.files)) {
        section.files = [];
    }

    section.files.push(...files);
}

function handleRemoveFile(parentIndex, fileIndex) {
    const section = props.form.documents_others?.[parentIndex];
    if (!section || !Array.isArray(section.files)) return;

    section.files.splice(fileIndex, 1);
}
</script>

<template>
    <div>
        <div
            v-for="(document, index) in form.documents_others"
            :key="document.value"
            class="bg-white shadow sm:rounded-2xl p-6"
        >
            <p class="text-xl font-semibold">Other Documents</p>
            <p class="font-medium text-sm text-gray-700 mb-1">
                Attach other supporting documents for your store creation here.
            </p>

            <div class="border-t my-5 pt-5">
                <div v-if="document.files && document.files.length > 0" class="mb-4">
                    <FileItem
                        v-for="(file, fileIndex) in document.files"
                        :key="fileIndex"
                        :file="file"
                        class="mb-2"
                        @remove="handleRemoveFile(index, fileIndex)"
                    />
                </div>

                <DragAndDropFileUpload
                    :id="document.value"
                    :file-types="'.jpg,.jpeg,.png,.pdf,.doc,.docx'"
                    :multiple="true"
                    custom-class="p-2 !rounded-md"
                    label="Upload a file"
                    label-class="!text-indigo-600"
                    @uploaded="(files) => handleUpload(files, index)"
                />
            </div>
        </div>
    </div>
</template>
