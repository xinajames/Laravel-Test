<script setup>
import { debounce } from 'lodash';
import { onMounted, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import DragAndDropFileUpload from '@/Components/Common/File/DragAndDropFileUpload.vue';
import DropdownSelect from '@/Components/Common/Select/DropdownSelect.vue';
import TextInput from '@/Components/Common/Input/TextInput.vue';
import XCloseIcon from '@/Components/Icon/XCloseIcon.vue';

const props = defineProps({
    form: Object,
    store: Object,
    withHeader: { type: Boolean, default: true },
});

const photoUpload = ref(null);

const storeOverviewData = ref({
    current_step: 'basic-details',
    application_step: 'basic-details',
});

const storeTypes = ref(null);

const storeGroup = ref(null);

const statuses = ref(null);

const regionsData = ref(null);

function getRegionsData() {
    let url = route('enums.getDataList', { key: 'region-dropdown' });
    axios.get(url).then((response) => {
        regionsData.value = response.data;
    });
}

function handleUpload(files, index) {
    props.form.photos = files;
    handleUpdate('photos');
}

function handleRemoveFile(parentIndex, fileIndex) {
    if (!props.form.store_photo[parentIndex]?.files) return;
    props.form.store_photo[parentIndex].files.splice(fileIndex, 1);
}

function getStoreGroups() {
    let url = route('enums.getDataList', { key: 'store-group-enum' });
    axios.get(url).then((response) => {
        storeGroup.value = response.data;
    });
}

function getStoreStatuses() {
    let url = route('enums.getDataList', { key: 'store-status-enum' });
    axios.get(url).then((response) => {
        statuses.value = response.data;
    });
}

function getStoreTypes() {
    let url = route('enums.getDataList', { key: 'store-type-enum' });
    axios.get(url).then((response) => {
        storeTypes.value = response.data;
    });
}

function handleRemovePhoto(id) {
    props.form.delete_photo_id = id;
    handleUpdate('delete_photo_id');
}

const handleUpdate = debounce(function (field) {
    storeOverviewData.value[field] = props.form[field];
    router.post(route('stores.update', props.store.id), storeOverviewData.value, {
        onSuccess: () => {
            props.form.photos = [];
            props.form.delete_photo_id = null;
            if (photoUpload.value) {
                photoUpload.value.fileList.value = [];
            }
            storeOverviewData.value = {
                current_step: 'basic-details',
                application_step: 'basic-details',
            };
        },
        preserveScroll: true,
    });
}, 500);

onMounted(() => {
    getStoreGroups();
    getStoreStatuses();
    getStoreTypes();
    getRegionsData();
});
</script>

<template>
    <div class="p-6 bg-white rounded-2xl">
        <h2 class="text-xl font-sans font-bold pb-4 border-b">Store Overview</h2>

        <div class="space-y-5 mt-5">
            <div>
                <DragAndDropFileUpload
                    ref="photoUpload"
                    :required="form.photos && form.photos.length < 1 && !store.store_photos"
                    custom-class="!rounded-md py-8"
                    help-text="PNG, JPG up to 10MB"
                    icon-class="text-gray-400"
                    label="Upload a file"
                    label-class="!text-indigo-600"
                    textLabel="Store Photo"
                    type="multi_line"
                    @uploaded="handleUpload($event, index)"
                />
                <div class="flex gap-6 mt-4">
                    <div v-for="(photo, index) in store.store_photos" :key="index" class="relative">
                        <div
                            class="absolute top-2 right-1 rounded-full bg-white p-1 cursor-pointer"
                            @click="handleRemovePhoto(photo.id)"
                        >
                            <XCloseIcon class="size-5 text-gray-800" />
                        </div>
                        <img
                            :key="index"
                            :src="photo.preview"
                            alt="store-photos"
                            class="size-24 object-cover object-center rounded-lg"
                        />
                    </div>
                </div>
            </div>

            <TextInput
                v-model="form.jbs_name"
                :required="true"
                input-class="!border-gray-300"
                label="JBS Name"
                @blur="handleUpdate('jbs_name')"
            />

            <div class="grid lg:grid-cols-2 gap-4 pb-5 border-b border-gray-200">
                <DropdownSelect
                    v-model="form.store_type"
                    :required="true"
                    :value="form.store_type"
                    custom-class="!border-gray-300"
                    label="Store Type"
                    @update:modelValue="handleUpdate('store_type')"
                >
                    <option v-for="(type, index) in storeTypes" :key="index" :value="type.value">
                        {{ type.label }}
                    </option>
                </DropdownSelect>

                <DropdownSelect
                    v-model="form.store_group"
                    :required="true"
                    :value="form.store_group"
                    custom-class="!border-gray-300"
                    label="Store Group"
                    @update:modelValue="handleUpdate('store_group')"
                >
                    <option v-for="(group, index) in storeGroup" :key="index" :value="group.value">
                        {{ group.label }}
                    </option>
                </DropdownSelect>
            </div>

            <div class="grid lg:grid-cols-2 gap-4 pb-5 border-b border-gray-200">
                <div class="w-full">
                    <TextInput
                        v-model="form.cluster_code"
                        input-class="!border-gray-300"
                        label="Cluster Code"
                    />
                </div>
                <div class="w-full">
                    <TextInput
                        v-model="form.franchisee_code"
                        :disabled="true"
                        input-class="!border-gray-300"
                        label="Franchisee Code"
                    />
                </div>
                <div class="w-full">
                    <div class="grid">
                        <TextInput
                            v-model="form.sales_point_code"
                            input-class="!border-gray-300"
                            label="Sales Point Code"
                            @blur="handleUpdate('sales_point_code')"
                        />
                    </div>
                </div>
                <div class="w-full">
                    <div class="grid">
                        <TextInput
                            v-model="form.jbmis_code"
                            input-class="!border-gray-300"
                            label="JBMIS Code"
                            @blur="handleUpdate('jbmis_code')"
                        />
                    </div>
                </div>
            </div>

            <div class="grid lg:grid-cols-3">
                <DropdownSelect
                    v-model="form.store_status"
                    :value="form.store_status"
                    custom-class="!border-gray-300"
                    label="Status"
                    :required="true"
                    @update:modelValue="handleUpdate('store_status')"
                >
                    <option v-for="(status, index) in statuses" :key="index" :value="status.value">
                        {{ status.label }}
                    </option>
                </DropdownSelect>
            </div>

            <DropdownSelect
                v-model="form.region"
                :value="form.region"
                custom-class="!border-gray-300"
                label="Region"
                @update:modelValue="handleUpdate('region')"
            >
                <option v-for="(region, index) in regionsData" :key="index" :value="region.value">
                    {{ region.label }}
                </option>
            </DropdownSelect>

            <TextInput
                v-model="form.area"
                input-class="!border-gray-300"
                label="Area"
                @blur="handleUpdate('area')"
            />

            <TextInput
                v-model="form.district"
                input-class="!border-gray-300"
                label="District"
                @blur="handleUpdate('district')"
            />

            <div class="border-t border-gray-200 pt-5">
                <TextInput
                    v-model="form.google_maps_link"
                    input-class="!border-gray-300"
                    label="Google Maps Link"
                    @blur="handleUpdate('google_maps_link')"
                />
            </div>
        </div>
    </div>
</template>
