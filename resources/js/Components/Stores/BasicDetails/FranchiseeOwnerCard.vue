<script setup>
import { computed, onMounted, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import SearchInputDropdown from '@/Components/Common/Select/SearchInputDropdown.vue';

const props = defineProps({
    id: Number,
    form: Object,
    withHeader: { type: Boolean, default: true },
});

const fullFranchiseeLabel = computed(() => {
    const name = props.form.franchisee_name || '';
    const corp = props.form.corporation_name || '';
    return corp ? `${name} - ${corp}` : name;
});

const options = ref([]);

const handleUpdateData = (value) => {
    props.form.franchisee_id = value.id;
    props.form.franchisee_name = value.franchisee_name;
    props.form.corporation_name = value.corporation_name;

    router.post(
        route('stores.update', props.id),
        { franchisee_id: value.id, application_step: 'basic-details' },
        {
            onSuccess: () => {
                fetchFranchiseeDetails(value.id);
            },
        }
    );
};

function fetchFranchiseeDetails(franchiseeId) {
    let url = route('franchisees.getQuickDetails', { id: franchiseeId });
    axios
        .get(url)
        .then((response) => {
            props.form.franchisee_code = response.data.franchisee_code;
            props.form.franchisee_name = response.data.franchisee_name;
            props.form.corporation_name = response.data.corporation_name;
        })
        .catch((error) => {
            console.error('Error fetching franchisee details:', error);
        });
}

function getFranchisees() {
    let url = route('franchisees.getDataList');
    axios.get(url).then((response) => {
        options.value = response.data;
    });
}

onMounted(() => {
    getFranchisees();
});
</script>

<template>
    <div class="p-6 bg-white rounded-2xl">
        <h2 class="text-xl font-sans font-bold pb-4 border-b">Franchisee Owner</h2>

        <div class="mt-5">
            <SearchInputDropdown
                :dataList="options"
                :modelValue="fullFranchiseeLabel"
                :required="true"
                :with-image="true"
                label="Store owned by"
                @update-data="handleUpdateData"
            />
        </div>
    </div>
</template>
