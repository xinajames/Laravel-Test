<script setup>
import { reactive, ref, computed, onMounted } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { vMaska } from 'maska/vue';
import Modal from '@/Components/Shared/Modal.vue';
import TextInput from '@/Components/Common/Input/TextInput.vue';
import SecondaryButton from '@/Components/Common/Button/SecondaryButton.vue';
import PrimaryButton from '@/Components/Common/Button/PrimaryButton.vue';
import SearchInputDropdown from '@/Components/Common/Select/SearchInputDropdown.vue';

const emits = defineEmits(['close']);
const props = defineProps({
    open: Boolean,
    franchisee: Object,
    store: Object,
});

const isStore = computed(() => !!props.store);

const form = useForm({
    province: isStore.value
        ? props.store?.store_province
        : props.franchisee?.residential_address_province,
    city: isStore.value ? props.store?.store_city : props.franchisee?.residential_address_city,
    barangay: isStore.value
        ? props.store?.store_barangay
        : props.franchisee?.residential_address_barangay,
    street: isStore.value
        ? props.store?.store_street
        : props.franchisee?.residential_address_street,
    postal: isStore.value
        ? props.store?.store_postal_code
        : props.franchisee?.residential_address_postal,
});

const locationsData = ref({});

function getLocationsData() {
    let url = route('enums.getDataList', { key: 'locations-dropdown' });
    axios.get(url).then((response) => {
        locationsData.value = response.data[0].value;
    });
}

onMounted(() => {
    getLocationsData();
});

const provinceOptions = computed(() => {
    return Object.keys(locationsData.value).map((province) => ({
        label: province,
        value: province,
    }));
});

const cityOptions = computed(() => {
    const selectedProvince = form.province;
    if (selectedProvince && locationsData.value[selectedProvince]) {
        return Object.keys(locationsData.value[selectedProvince].cities).map((city) => ({
            label: city,
            value: city,
        }));
    }
    return [];
});

const barangayOptions = computed(() => {
    const selectedProvince = form.province;
    const selectedCity = form.city;
    if (
        selectedProvince &&
        selectedCity &&
        locationsData.value[selectedProvince] &&
        locationsData.value[selectedProvince].cities[selectedCity]
    ) {
        return locationsData.value[selectedProvince].cities[selectedCity].barangays.map(
            (barangay) => ({
                label: barangay,
                value: barangay,
            })
        );
    }
    return [];
});

const postal_code = reactive({
    mask: '####',
    eager: true,
});

function handleUpdateData(field, selectedOption) {
    const valueChanged = form[field] !== selectedOption.value;

    form[field] = selectedOption.value;

    // Only clear dependent fields if the value actually changed
    if (valueChanged) {
        if (field === 'province') {
            form.city = '';
            form.barangay = '';
        }
        if (field === 'city') {
            form.barangay = '';
        }
    }
}

function save() {
    let data = {};
    if (isStore.value) {
        data = {
            store_province: form.province,
            store_city: form.city,
            store_barangay: form.barangay,
            store_street: form.street,
            store_postal_code: form.postal,
        };
        router.post(route('stores.update', props.store.id), data, {
            onSuccess: () => {
                emits('close');
            },
            preserveScroll: true,
        });
    } else {
        data = {
            residential_address_province: form.province,
            residential_address_city: form.city,
            residential_address_barangay: form.barangay,
            residential_address_street: form.street,
            residential_address_postal: form.postal,
        };
        router.post(route('franchisees.update', props.franchisee.id), data, {
            onSuccess: () => {
                emits('close');
            },
            preserveScroll: true,
        });
    }
}
</script>

<template>
    <Modal max-width="lg" :open="open" @close="emits('close')">
        <template #content>
            <form @submit.prevent="save">
                <div class="p-4 border-b">
                    <h5 class="text-lg font-medium text-gray-900">
                        {{ isStore ? 'Store Address' : 'Residence Address' }}
                    </h5>
                </div>
                <div class="p-4 space-y-4">
                    <!-- Province Dropdown -->
                    <SearchInputDropdown
                        :dataList="provinceOptions"
                        v-model="form.province"
                        :with-image="false"
                        :required="true"
                        label="Province"
                        @update-data="
                            (selectedOption) => handleUpdateData('province', selectedOption)
                        "
                    />
                    <!-- City Dropdown -->
                    <SearchInputDropdown
                        :dataList="cityOptions"
                        v-model="form.city"
                        :with-image="false"
                        :required="true"
                        label="City or Municipality"
                        @update-data="(selectedOption) => handleUpdateData('city', selectedOption)"
                    />
                    <!-- Barangay Dropdown -->
                    <SearchInputDropdown
                        :dataList="barangayOptions"
                        v-model="form.barangay"
                        :with-image="false"
                        :required="true"
                        label="Barangay"
                        @update-data="
                            (selectedOption) => handleUpdateData('barangay', selectedOption)
                        "
                    />
                    <!-- Street Input -->
                    <TextInput
                        v-model="form.street"
                        input-class="border-gray-300"
                        label="Street"
                        :required="true"
                    />
                    <!-- Postal Code Input -->
                    <TextInput
                        v-model="form.postal"
                        v-maska="postal_code"
                        input-class="border-gray-300 !w-40"
                        label="Postal Code"
                        :required="true"
                    />
                </div>
                <div class="border-t p-4 flex justify-end gap-2">
                    <SecondaryButton type="button" @click="emits('close')">Cancel</SecondaryButton>
                    <PrimaryButton :disabled="form.processing" type="submit">
                        Save Changes
                    </PrimaryButton>
                </div>
            </form>
        </template>
    </Modal>
</template>
