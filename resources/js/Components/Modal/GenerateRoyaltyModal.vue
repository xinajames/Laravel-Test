<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import Modal from '@/Components/Shared/Modal.vue';
import SecondaryButton from '@/Components/Common/Button/SecondaryButton.vue';
import PrimaryButton from '@/Components/Common/Button/PrimaryButton.vue';
import TextArea from '@/Components/Common/Input/TextArea.vue';
import TextInput from '@/Components/Common/Input/TextInput.vue';
import DragAndDropFileUpload from '@/Components/Common/File/DragAndDropFileUpload.vue';
import FileItem from '@/Components/Shared/FileItem.vue';
import Checkbox from '@/Components/Common/Checkbox/Checkbox.vue';
import InputError from '@/Components/Default/InputError.vue';
import DropdownSelect from '@/Components/Common/Select/DropdownSelect.vue';

const emits = defineEmits(['close', 'save']);

const props = defineProps({
    open: Boolean,
});

const page = usePage();

const activeTab = ref('royalty');
const isGenerating = ref(false);

const form = useForm({
    title: '',
    remarks: '',
    generate_files: [2], // Array to store selected file types [1, 2] for MNSR and RWB - RWB (2) is always selected
    mnsr: [{ id: 'mnsr_upload_0', files: [] }],
    jbms: [{ id: 'jbms_upload_0', files: [] }],
    pos: [{ id: 'pos_upload_0', files: [] }],
    // zen: [{ id: 'zen_upload_0', files: [] }],
    file_size: null,
    // Sales History form fields
    sales_month: '',
    sales_year: '',
});

const months = [
    { value: '01', label: 'January' },
    { value: '02', label: 'February' },
    { value: '03', label: 'March' },
    { value: '04', label: 'April' },
    { value: '05', label: 'May' },
    { value: '06', label: 'June' },
    { value: '07', label: 'July' },
    { value: '08', label: 'August' },
    { value: '09', label: 'September' },
    { value: '10', label: 'October' },
    { value: '11', label: 'November' },
    { value: '12', label: 'December' },
];

const currentYear = new Date().getFullYear();
const years = Array.from({ length: 10 }, (_, i) => ({
    value: (currentYear - i).toString(),
    label: (currentYear - i).toString(),
}));

function handleMnsrUpload(files, index) {
    if (!form.mnsr[index]) {
        // Initialize mnsr[index] if it doesn't exist
        form.mnsr[index] = { id: `mnsr_upload_${index}`, files: [] };
    }
    form.mnsr[index].files = files;
}

function handlejbmsUpload(files, index) {
    if (!form.jbms[index]) {
        // Initialize jbms[index] if it doesn't exist
        form.jbms[index] = { id: `jbms_upload_${index}`, files: [] };
    }
    form.jbms[index].files = files;
}

function handleposUpload(files, index) {
    if (!form.pos[index]) {
        // Initialize pos[index] if it doesn't exist
        form.pos[index] = { id: `pos_upload_${index}`, files: [] };
    }
    form.pos[index].files = files;
}

// function handlezenUpload(files, index) {
//     if (!form.zen[index]) {
//         // Initialize zen[index] if it doesn't exist
//         form.zen[index] = { id: `zen_upload_${index}`, files: [] };
//     }
//     form.zen[index].files = files;
// }

function handleRemoveFile(parentIndex, fileIndex, type = 'jbms') {
    const targetArray = form[type];
    if (targetArray && targetArray[parentIndex]) {
        targetArray[parentIndex].files.splice(fileIndex, 1);
    }
}

function handleCheckboxChange(checked, value) {
    if (checked) {
        // Add value if not already in the array
        if (!form.generate_files.includes(value)) {
            form.generate_files.push(value);
        }
    } else {
        // Remove value if present in the array
        form.generate_files = form.generate_files.filter((item) => item !== value);
    }
}

function save() {
    isGenerating.value = true;

    if (activeTab.value === 'royalty') {
        // Clear any previous validation errors
        form.clearErrors('generate_files');
        form.clearErrors('mnsr');
        form.clearErrors('jbms');
        form.clearErrors('pos');

        // Validate that at least one of MNSR or RWB is selected
        if (!form.generate_files.includes(1) && !form.generate_files.includes(2)) {
            form.setError('generate_files', 'Please select at least one file type to generate.');
            isGenerating.value = false;
            return;
        }

        // Always require JBMIS and POS files
        if (!form.jbms[0] || !form.jbms[0].files || form.jbms[0].files.length === 0) {
            form.setError('jbms', 'JBMIS files are required.');
            isGenerating.value = false;
            return;
        }

        if (!form.pos[0] || !form.pos[0].files || form.pos[0].files.length === 0) {
            form.setError('pos', 'POS files are required.');
            isGenerating.value = false;
            return;
        }


        form.post(route('royalty.generate'), {
            preserveScroll: true,
            onSuccess: (pageData) => {
                if (
                    Object.keys(form.errors).length === 0 &&
                    !form.errors.upload_validation &&
                    page.props.flash.success
                ) {
                    emits('save', form.remarks);
                    form.reset();
                    isGenerating.value = false;
                }
            },
            onError: () => {
                isGenerating.value = false;
            },
        });
    } else if (activeTab.value === 'sales_history') {
        // Clear any previous validation errors
        form.clearErrors('sales_month');
        form.clearErrors('sales_year');

        // Validate month and year selection
        if (!form.sales_month) {
            form.setError('sales_month', 'Please select a month.');
            isGenerating.value = false;
            return;
        }

        if (!form.sales_year) {
            form.setError('sales_year', 'Please select a year.');
            isGenerating.value = false;
            return;
        }

        // Handle sales history generation
        form.transform((data) => ({
            ...data,
            sales_month: parseInt(data.sales_month),
            sales_year: parseInt(data.sales_year),
        })).post(route('royalty.generate.sales_history'), {
            preserveScroll: true,
            onSuccess: (pageData) => {
                if (
                    Object.keys(form.errors).length === 0 &&
                    !form.errors.upload_validation &&
                    page.props.flash.success
                ) {
                    emits('save', form.remarks);
                    form.reset();
                    isGenerating.value = false;
                }
            },
            onError: () => {
                isGenerating.value = false;
            },
        });
    }
}
</script>

<template>
    <Modal max-width="lg" :open="open" @close="emits('close')">
        <template v-slot:content>
            <form @submit.prevent="save">
                <div class="py-4 px-6 border-b">
                    <h5 class="text-lg font-medium text-gray-900">Generate Royalty</h5>
                </div>

                <!-- Tab Navigation -->
                <div class="border-b border-gray-200">
                    <nav class="-mb-px flex space-x-8 px-6" aria-label="Tabs">
                        <button
                            type="button"
                            :class="[
                                activeTab === 'royalty'
                                    ? 'border-primary text-primary'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                'whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm',
                                { 'pointer-events-none': isGenerating },
                            ]"
                            :disabled="isGenerating"
                            @click="activeTab = 'royalty'"
                        >
                            Royalty
                        </button>
                        <button
                            type="button"
                            :class="[
                                activeTab === 'sales_history'
                                    ? 'border-primary text-primary'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                'whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm',
                                { 'pointer-events-none': isGenerating },
                            ]"
                            :disabled="isGenerating"
                            @click="activeTab = 'sales_history'"
                        >
                            Sales History
                        </button>
                    </nav>
                </div>

                <!-- Tab Content -->
                <div class="p-6 space-y-4">
                    <!-- Royalty Tab -->
                    <div v-if="activeTab === 'royalty'" class="space-y-4">
                        <TextInput v-model="form.title" label="Title" />
                        <TextArea
                            v-model="form.remarks"
                            input-class="!border-gray-300"
                            label="Remarks"
                        />

                        <div>
                            <p class="font-heading font-medium text-sm text-gray-700 mb-3">
                                Generate File/s
                            </p>
                            <div class="space-y-3">
                                <div class="flex items-center gap-3">
                                    <Checkbox
                                        id="mnsr-checkbox"
                                        name="generate_files"
                                        :checked="form.generate_files.includes(1)"
                                        @update:checked="
                                            (checked) => handleCheckboxChange(checked, 1)
                                        "
                                    />
                                    <label
                                        for="mnsr-checkbox"
                                        class="text-sm text-gray-700 cursor-pointer"
                                    >
                                        MNSR
                                    </label>
                                </div>
                                <div class="flex items-center gap-3">
                                    <Checkbox
                                        id="rwb-checkbox"
                                        name="generate_files"
                                        :checked="form.generate_files.includes(2)"
                                        @update:checked="
                                            (checked) => handleCheckboxChange(checked, 2)
                                        "
                                    />
                                    <label for="rwb-checkbox" class="text-sm text-gray-700 cursor-pointer">
                                        RWB
                                    </label>
                                </div>
                            </div>
                            <InputError class="mt-2" :message="form.errors.generate_files" />
                        </div>

                        <div>
                            <p class="font-heading font-medium text-sm text-gray-700 mb-2">
                                Upload MNSR File
                            </p>

                            <div v-for="(mnsrItem, index) in form.mnsr" :key="mnsrItem.id">
                                <FileItem
                                    v-for="(file, fileIndex) in mnsrItem.files"
                                    :key="fileIndex"
                                    class="mb-2"
                                    :file="file"
                                    @remove="handleRemoveFile(index, fileIndex, 'mnsr')"
                                />
                            </div>

                            <DragAndDropFileUpload
                                :id="form.mnsr[0]?.id || 'mnsr_upload_0'"
                                custom-class="p-2 !rounded-md"
                                label="Upload a file "
                                label-class="!text-primary"
                                type="single_line"
                                @uploaded="handleMnsrUpload($event, 0)"
                            />
                            <InputError class="mt-2" :message="form.errors.mnsr" />
                        </div>

                        <div>
                            <p class="font-heading font-medium text-sm text-gray-700 mb-2">
                                Upload JBMIS Files *
                            </p>

                            <div v-for="(jbmsItem, index) in form.jbms" :key="jbmsItem.id">
                                <FileItem
                                    v-for="(file, fileIndex) in jbmsItem.files"
                                    :key="fileIndex"
                                    class="mb-2"
                                    :file="file"
                                    @remove="handleRemoveFile(index, fileIndex, 'jbms')"
                                />
                            </div>

                            <DragAndDropFileUpload
                                :id="form.jbms[0]?.id || 'jbms_upload_0'"
                                custom-class="p-2 !rounded-md"
                                label="Upload a file "
                                label-class="!text-primary"
                                type="single_line"
                                @uploaded="handlejbmsUpload($event, 0)"
                            />
                            <InputError class="mt-2" :message="form.errors.jbms" />
                        </div>
                        <div>
                            <p class="font-heading font-medium text-sm text-gray-700">Upload POS Files *</p>
                            <p class="text-gray-700 mb-2">Last synced on Month DD, YYYY</p>

                            <div v-for="(posItem, index) in form.pos" :key="posItem.id">
                                <FileItem
                                    v-for="(file, fileIndex) in posItem.files"
                                    :key="fileIndex"
                                    class="mb-2"
                                    :file="file"
                                    @remove="handleRemoveFile(index, fileIndex, 'pos')"
                                />
                            </div>

                            <DragAndDropFileUpload
                                :id="form.pos[0]?.id || 'pos_upload_0'"
                                custom-class="p-2 !rounded-md"
                                label="Upload a file "
                                label-class="!text-primary"
                                type="single_line"
                                @uploaded="handleposUpload($event, 0)"
                            />
                            <InputError class="mt-2" :message="form.errors.pos" />
                        </div>
                    </div>

                    <!-- Sales History Tab -->
                    <div v-if="activeTab === 'sales_history'" class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <DropdownSelect
                                    v-model="form.sales_month"
                                    label="Month"
                                    :error="form.errors.sales_month"
                                    required
                                >
                                    <option value="">Select Month</option>
                                    <option
                                        v-for="month in months"
                                        :key="month.value"
                                        :value="month.value"
                                    >
                                        {{ month.label }}
                                    </option>
                                </DropdownSelect>
                            </div>
                            <div>
                                <DropdownSelect
                                    v-model="form.sales_year"
                                    label="Year"
                                    :error="form.errors.sales_year"
                                    required
                                >
                                    <option value="">Select Year</option>
                                    <option
                                        v-for="year in years"
                                        :key="year.value"
                                        :value="year.value"
                                    >
                                        {{ year.label }}
                                    </option>
                                </DropdownSelect>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="border-t p-4 flex justify-end gap-2">
                    <SecondaryButton type="button" :disabled="isGenerating" @click="emits('close')">
                        Cancel
                    </SecondaryButton>
                    <PrimaryButton
                        :disabled="form.processing || isGenerating"
                        type="submit"
                        @click="save()"
                    >
                        Generate
                    </PrimaryButton>
                </div>
            </form>
        </template>
    </Modal>
</template>
