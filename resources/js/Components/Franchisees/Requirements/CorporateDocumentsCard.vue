<script setup>
import DragAndDropFileUpload from '@/Components/Common/File/DragAndDropFileUpload.vue';
import FileItem from '@/Components/Shared/FileItem.vue';

const props = defineProps({
    form: Object,
});

function handleUpload(files, index) {
    props.form.documents_corporate[index].files = files;
}

function handleRemoveFile(parentIndex, fileIndex) {
    props.form.documents_corporate[parentIndex].files.splice(fileIndex, 1);
}
</script>

<template>
    <div class="bg-white shadow sm:rounded-2xl p-6">
        <p class="text-xl font-semibold">Corporate Documents</p>
        <p class="mt-1 text-xs text-gray-500 font-medium">If applicable</p>
        <div class="border-t mt-5">
            <div v-for="(document, index) in form.documents_corporate" class="mt-5">
                <div>
                    <p class="font-medium text-sm text-gray-700 mb-1">
                        {{ document.label }}
                    </p>

                    <FileItem
                        v-for="(file, fileIndex) in document.files"
                        :file="file"
                        class="mb-2"
                        @remove="handleRemoveFile(index, fileIndex)"
                    />

                    <DragAndDropFileUpload
                        :id="document.value"
                        :file-types="'.jpg,.jpeg,.png,.pdf,.doc,.docx'"
                        custom-class="p-2 !rounded-md"
                        label="Upload a file"
                        label-class="!text-indigo-600"
                        type="single_line"
                        @uploaded="handleUpload($event, index)"
                    />
                </div>
            </div>
        </div>
    </div>
</template>
