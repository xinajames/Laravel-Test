<script setup>
import { debounce } from 'lodash';
import { computed, onMounted, reactive, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import DropdownSelect from '@/Components/Common/Select/DropdownSelect.vue';
import PrimaryButton from '@/Components/Common/Button/PrimaryButton.vue';
import SecondaryButton from '@/Components/Common/Button/SecondaryButton.vue';
import TextInput from '@/Components/Common/Input/TextInput.vue';

const props = defineProps({
    form: Object,
    franchisee: Object,
    withHeader: { type: Boolean, default: true },
    page: { type: String, default: 'create' },
});

const editField = ref(null);

let backgroundForm = reactive({
    current_step: 'franchisee-info',
    errors: null,
});

const isOtherBackgroundSelected = computed(() => {
    return props.form.background === 'Others';
});

const isOtherSourceSelected = computed(() => {
    return props.form.source_of_information === 'Others';
});

const isOtherGenerationSelected = computed(() => {
    return props.form.generation === 'Others';
});

function handleEdit(field, clear) {
    editField.value = clear ? null : field;
    if (clear) {
        delete backgroundForm[field];
    } else {
        backgroundForm[field] = props.franchisee[field];

        if (field === 'background' && props.franchisee.background === 'Others') {
            backgroundForm.custom_background = props.franchisee.custom_background || '';
        }
        if (
            field === 'source_of_information' &&
            props.franchisee.source_of_information === 'Others'
        ) {
            backgroundForm.custom_source_of_information =
                props.franchisee.custom_source_of_information || '';
        }
        if (field === 'generation' && props.franchisee.generation === 'Others') {
            backgroundForm.custom_generation = props.franchisee.custom_generation || '';
        }
    }
    backgroundForm.errors = null;
}

const backgrounds = ref(null);

const generations = ref(null);

const sources = ref(null);

const handleUpdate = debounce(function (field) {
    if (props.page !== 'edit') {
        // apply the field update
        if (field) {
            backgroundForm[field] = props.form[field];
            backgroundForm.application_step = 'franchisee-info';
        }

        const payload = { ...backgroundForm };

        if (props.page === 'show' && backgroundForm.background !== 'Others') {
            delete payload.custom_background;
        }

        if (props.page === 'show' && backgroundForm.source_of_information !== 'Others') {
            delete payload.custom_source_of_information;
        }

        if (props.page === 'show' && backgroundForm.generation !== 'Others') {
            delete payload.custom_generation;
        }

        props.page === 'show'
            ? router.post(route('franchisees.update', props.franchisee.id), payload, {
                  preserveScroll: true,
                  onSuccess: clearForm,
                  onError: (errors) => {
                      backgroundForm.errors = errors;
                  },
              })
            : axios
                  .post(route('franchisees.update', props.franchisee.id), payload)
                  .then(clearForm)
                  .catch((err) => {
                      backgroundForm.errors = err.response?.data?.errors || null;
                  });

        function clearForm() {
            editField.value = null;

            switch (field) {
                case 'background':
                    backgroundForm.background = null;
                    if (props.page === 'create' && payload.background !== 'Others') {
                        props.form.custom_background = '';
                    }
                    break;

                case 'source_of_information':
                    backgroundForm.custom_source_of_information = null;
                    if (props.page === 'create' && payload.source_of_information !== 'Others') {
                        props.form.custom_source_of_information = '';
                    }
                    break;

                case 'generation':
                    backgroundForm.custom_generation = null;
                    if (props.page === 'create' && payload.generation !== 'Others') {
                        props.form.custom_generation = '';
                    }
                    break;
            }

            backgroundForm.errors = null;
            backgroundForm.current_step = 'franchisee-info';
        }
    }
}, 500);

function getBackgrounds() {
    let url = route('enums.getDataList', { key: 'background-dropdown' });
    axios.get(url).then((response) => {
        backgrounds.value = [...response.data, { value: 'Others', label: 'Others' }];
    });
}

function getGenerations() {
    let url = route('enums.getDataList', { key: 'generation-dropdown' });
    axios.get(url).then((response) => {
        generations.value = [...response.data, { value: 'Others', label: 'Others' }];
    });
}

function getSources() {
    let url = route('enums.getDataList', { key: 'source-of-information-dropdown' });
    axios.get(url).then((response) => {
        sources.value = [...response.data, { value: 'Others', label: 'Others' }];
    });
}

onMounted(() => {
    getBackgrounds();
    getGenerations();
    getSources();
});

const canUpdateFranchisees = computed(() => {
    return usePage().props.auth.permissions.includes('update-franchisees');
});
</script>
<template>
    <div class="bg-white shadow sm:rounded-2xl p-6">
        <h4 v-if="withHeader" class="font-sans font-semibold">Background Information</h4>
        <div
            v-if="page === 'create' || page === 'edit'"
            class="space-y-5"
            :class="withHeader ? 'border-t mt-5' : ''"
        >
            <div class="grid grid-cols-2 gap-4" :class="withHeader ? 'mt-5' : ''">
                <div class="flex flex-col space-y-5">
                    <DropdownSelect
                        v-model="form.background"
                        custom-class="border-gray-300"
                        :value="form.background"
                        label="Background"
                        @update:modelValue="handleUpdate('background')"
                    >
                        <option
                            v-for="(background, index) in backgrounds"
                            :key="index"
                            :value="background.value"
                        >
                            {{ background.label }}
                        </option>
                    </DropdownSelect>

                    <TextInput
                        v-model="form.education"
                        input-class="border-gray-300"
                        label="Education"
                        @blur="handleUpdate('education')"
                    />
                </div>

                <TextInput
                    v-if="isOtherBackgroundSelected"
                    v-model="form.custom_background"
                    input-class="border-gray-300"
                    label="Background (Others)"
                    @blur="handleUpdate('custom_background')"
                />
            </div>

            <TextInput
                v-model="form.course"
                input-class="border-gray-300"
                label="Course (in college)"
                @blur="handleUpdate('course')"
            />

            <TextInput
                v-model="form.occupation"
                input-class="border-gray-300"
                label="Occupation"
                @blur="handleUpdate('occupation')"
            />

            <div class="grid grid-cols-2 gap-4">
                <DropdownSelect
                    v-model="form.source_of_information"
                    custom-class="border-gray-300"
                    :value="form.source_of_information"
                    label="Source of Information"
                    @update:modelValue="handleUpdate('source_of_information')"
                >
                    <option v-for="(source, index) in sources" :key="index" :value="source.value">
                        {{ source.label }}
                    </option>
                </DropdownSelect>

                <TextInput
                    v-if="isOtherSourceSelected"
                    v-model="form.custom_source_of_information"
                    input-class="border-gray-300"
                    label="Source of Information (Others)"
                    @blur="handleUpdate('custom_source_of_information')"
                />
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="flex flex-col space-y-5">
                    <DropdownSelect
                        v-model="form.generation"
                        custom-class="border-gray-300"
                        :value="form.generation"
                        label="Generation"
                        @update:modelValue="handleUpdate('generation')"
                    >
                        <option
                            v-for="(generation, index) in generations"
                            :key="index"
                            :value="generation.value"
                        >
                            {{ generation.value }}
                        </option>
                    </DropdownSelect>

                    <TextInput
                        v-model="form.legacy"
                        input-class="border-gray-300"
                        label="Legacy"
                        @blur="handleUpdate('legacy')"
                    />
                </div>

                <TextInput
                    v-if="isOtherGenerationSelected"
                    v-model="form.custom_generation"
                    input-class="border-gray-300"
                    label="Generation (Others)"
                    @blur="handleUpdate('custom_generation')"
                />
            </div>
        </div>
        <div v-else class="mt-5 space-y-3 divide divide-y divide-gray-200">
            <div class="grid grid-cols-3 gap-4 items-center">
                <h5 class="font-medium text-gray-500">Background</h5>
                <div class="col-span-2">
                    <div v-if="editField === 'background'" class="flex gap-4 items-center">
                        <div
                            :class="[
                                'grid w-full gap-2',
                                backgroundForm.background === 'Others'
                                    ? 'grid-cols-5'
                                    : 'grid-cols-1',
                            ]"
                        >
                            <div class="col-span-2">
                                <DropdownSelect
                                    v-model="backgroundForm.background"
                                    custom-class="border-gray-300"
                                    :value="backgroundForm.background"
                                >
                                    <option
                                        v-for="(background, index) in backgrounds"
                                        :key="index"
                                        :value="background.value"
                                    >
                                        {{ background.label }}
                                    </option>
                                </DropdownSelect>
                            </div>

                            <TextInput
                                v-if="backgroundForm.background === 'Others'"
                                v-model="backgroundForm.custom_background"
                                input-class="border-gray-300"
                                placeholder="Background (Others)"
                                class="col-span-3"
                            />
                        </div>
                        <div class="flex items-center gap-2 mt-1">
                            <SecondaryButton
                                class="!font-medium"
                                @click="handleEdit('background', true)"
                            >
                                Cancel
                            </SecondaryButton>
                            <PrimaryButton class="!font-medium" @click="handleUpdate(null)">
                                Save
                            </PrimaryButton>
                        </div>
                    </div>
                    <div v-else class="flex justify-between items-center">
                        <h5 class="text-gray-900">
                            {{
                                franchisee.background === 'Others'
                                    ? franchisee.custom_background || 'N/A'
                                    : franchisee.background || 'N/A'
                            }}
                        </h5>
                        <SecondaryButton
                            v-if="canUpdateFranchisees"
                            class="!ring-transparent !px-0 !py-0 !font-medium !text-[#A32130]"
                            @click="handleEdit('background', false)"
                        >
                            Edit
                        </SecondaryButton>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-3 gap-4 items-center pt-3">
                <h5 class="font-medium text-gray-500">Education</h5>
                <div class="col-span-2">
                    <div v-if="editField === 'education'" class="flex gap-4 items-center">
                        <div class="flex-1">
                            <TextInput
                                v-model="backgroundForm.education"
                                input-class="border-gray-300"
                            />
                        </div>
                        <div class="flex items-center gap-2 mt-1">
                            <SecondaryButton
                                class="!font-medium"
                                @click="handleEdit('education', true)"
                            >
                                Cancel
                            </SecondaryButton>
                            <PrimaryButton class="!font-medium" @click="handleUpdate(null)">
                                Save
                            </PrimaryButton>
                        </div>
                    </div>
                    <div v-else class="flex justify-between items-center">
                        <h5 class="text-gray-900">
                            {{ franchisee.education || 'N/A' }}
                        </h5>
                        <SecondaryButton
                            v-if="canUpdateFranchisees"
                            class="!ring-transparent !px-0 !py-0 !font-medium !text-[#A32130]"
                            @click="handleEdit('education', false)"
                        >
                            Edit
                        </SecondaryButton>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-3 gap-4 items-center pt-3">
                <h5 class="font-medium text-gray-500">Course</h5>
                <div class="col-span-2">
                    <div v-if="editField === 'course'" class="flex gap-4 items-center">
                        <div class="flex-1">
                            <TextInput
                                v-model="backgroundForm.course"
                                input-class="border-gray-300"
                            />
                        </div>
                        <div class="flex items-center gap-2 mt-1">
                            <SecondaryButton
                                class="!font-medium"
                                @click="handleEdit('course', true)"
                            >
                                Cancel
                            </SecondaryButton>
                            <PrimaryButton class="!font-medium" @click="handleUpdate(null)">
                                Save
                            </PrimaryButton>
                        </div>
                    </div>
                    <div v-else class="flex justify-between items-center">
                        <h5 class="text-gray-900">
                            {{ franchisee.course || 'N/A' }}
                        </h5>
                        <SecondaryButton
                            v-if="canUpdateFranchisees"
                            class="!ring-transparent !px-0 !py-0 !font-medium !text-[#A32130]"
                            @click="handleEdit('course', false)"
                        >
                            Edit
                        </SecondaryButton>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-3 gap-4 items-center pt-3">
                <h5 class="font-medium text-gray-500">Occupation</h5>
                <div class="col-span-2">
                    <div v-if="editField === 'occupation'" class="flex gap-4 items-center">
                        <div class="flex-1">
                            <TextInput
                                v-model="backgroundForm.occupation"
                                input-class="border-gray-300"
                            />
                        </div>
                        <div class="flex items-center gap-2 mt-1">
                            <SecondaryButton
                                class="!font-medium"
                                @click="handleEdit('occupation', true)"
                            >
                                Cancel
                            </SecondaryButton>
                            <PrimaryButton class="!font-medium" @click="handleUpdate(null)">
                                Save
                            </PrimaryButton>
                        </div>
                    </div>
                    <div v-else class="flex justify-between items-center">
                        <h5 class="text-gray-900">
                            {{ franchisee.occupation || 'N/A' }}
                        </h5>
                        <SecondaryButton
                            v-if="canUpdateFranchisees"
                            class="!ring-transparent !px-0 !py-0 !font-medium !text-[#A32130]"
                            @click="handleEdit('occupation', false)"
                        >
                            Edit
                        </SecondaryButton>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-3 gap-4 items-center pt-3">
                <h5 class="font-medium text-gray-500">Source of Information</h5>
                <div class="col-span-2">
                    <div
                        v-if="editField === 'source_of_information'"
                        class="flex gap-4 items-center"
                    >
                        <div
                            :class="[
                                'grid w-full gap-2',
                                backgroundForm.source_of_information === 'Others'
                                    ? 'grid-cols-5'
                                    : 'grid-cols-1',
                            ]"
                        >
                            <div class="col-span-2">
                                <DropdownSelect
                                    v-model="backgroundForm.source_of_information"
                                    custom-class="border-gray-300"
                                    :value="backgroundForm.source_of_information"
                                >
                                    <option
                                        v-for="(source, index) in sources"
                                        :key="index"
                                        :value="source.value"
                                    >
                                        {{ source.label }}
                                    </option>
                                </DropdownSelect>
                            </div>
                            <TextInput
                                v-if="backgroundForm.source_of_information === 'Others'"
                                v-model="backgroundForm.custom_source_of_information"
                                input-class="border-gray-300"
                                placeholder="Source of Information (Others)"
                                class="col-span-3"
                            />
                        </div>
                        <div class="flex items-center gap-2 mt-1">
                            <SecondaryButton
                                class="!font-medium"
                                @click="handleEdit('source_of_information', true)"
                            >
                                Cancel
                            </SecondaryButton>
                            <PrimaryButton class="!font-medium" @click="handleUpdate(null)">
                                Save
                            </PrimaryButton>
                        </div>
                    </div>
                    <div v-else class="flex justify-between items-center">
                        <h5 class="text-gray-900">
                            {{
                                franchisee.source_of_information === 'Others'
                                    ? franchisee.custom_source_of_information || 'N/A'
                                    : franchisee.source_of_information || 'N/A'
                            }}
                        </h5>
                        <SecondaryButton
                            v-if="canUpdateFranchisees"
                            class="!ring-transparent !px-0 !py-0 !font-medium !text-[#A32130]"
                            @click="handleEdit('source_of_information', false)"
                        >
                            Edit
                        </SecondaryButton>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-3 gap-4 items-center pt-3">
                <h5 class="font-medium text-gray-500">Legacy</h5>
                <div class="col-span-2">
                    <div v-if="editField === 'legacy'" class="flex gap-4 items-center">
                        <div class="flex-1">
                            <TextInput
                                v-model="backgroundForm.legacy"
                                input-class="border-gray-300"
                            />
                        </div>
                        <div class="flex items-center gap-2 mt-1">
                            <SecondaryButton
                                class="!font-medium"
                                @click="handleEdit('legacy', true)"
                            >
                                Cancel
                            </SecondaryButton>
                            <PrimaryButton class="!font-medium" @click="handleUpdate(null)">
                                Save
                            </PrimaryButton>
                        </div>
                    </div>
                    <div v-else class="flex justify-between items-center">
                        <h5 class="text-gray-900">
                            {{ franchisee.legacy || 'N/A' }}
                        </h5>
                        <SecondaryButton
                            v-if="canUpdateFranchisees"
                            class="!ring-transparent !px-0 !py-0 !font-medium !text-[#A32130]"
                            @click="handleEdit('legacy', false)"
                        >
                            Edit
                        </SecondaryButton>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-3 gap-4 items-center pt-3">
                <h5 class="font-medium text-gray-500">Generation</h5>
                <div class="col-span-2">
                    <div v-if="editField === 'generation'" class="flex gap-4 items-center">
                        <div
                            :class="[
                                'grid w-full gap-2',
                                backgroundForm.generation === 'Others'
                                    ? 'grid-cols-5'
                                    : 'grid-cols-1',
                            ]"
                        >
                            <div class="col-span-2">
                                <DropdownSelect
                                    v-model="backgroundForm.generation"
                                    custom-class="border-gray-300"
                                    :value="backgroundForm.generation"
                                >
                                    <option
                                        v-for="(generation, index) in generations"
                                        :key="index"
                                        :value="generation.value"
                                    >
                                        {{ generation.label }}
                                    </option>
                                </DropdownSelect>
                            </div>
                            <TextInput
                                v-if="backgroundForm.generation === 'Others'"
                                v-model="backgroundForm.custom_generation"
                                input-class="border-gray-300"
                                placeholder="Generation (Others)"
                                class="col-span-3"
                            />
                        </div>
                        <div class="flex items-center gap-2 mt-1">
                            <SecondaryButton
                                class="!font-medium"
                                @click="handleEdit('generation', true)"
                            >
                                Cancel
                            </SecondaryButton>
                            <PrimaryButton class="!font-medium" @click="handleUpdate(null)">
                                Save
                            </PrimaryButton>
                        </div>
                    </div>
                    <div v-else class="flex justify-between items-center">
                        <h5 class="text-gray-900">
                            {{
                                franchisee.generation === 'Others'
                                    ? franchisee.custom_generation || 'N/A'
                                    : franchisee.generation || 'N/A'
                            }}
                        </h5>
                        <SecondaryButton
                            v-if="canUpdateFranchisees"
                            class="!ring-transparent !px-0 !py-0 !font-medium !text-[#A32130]"
                            @click="handleEdit('generation', false)"
                        >
                            Edit
                        </SecondaryButton>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
