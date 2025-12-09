<script setup>
import { ref, watch } from 'vue';

const emits = defineEmits(['toggle-sort']);

const props = defineProps({
    header: String,
    show: { type: Boolean, default: true },
    sortable: { type: Boolean, default: false },
    sortData: { type: Object, default: () => [] },
    data: String,
});

const sortFlag = ref(props.sortData ? props.sortData.column === props.data : false);
const order = ref(props.sortData ? props.sortData.order : 'desc');

watch(
    props.sortData,
    (newValue) => {
        if (props.sortable && props.data !== newValue.column) {
            sortFlag.value = false;
        }
    },
    { deep: true, immediate: true }
);

function toggleSort() {
    sortFlag.value = !sortFlag.value;
    order.value = sortFlag.value ? 'asc' : 'desc';
    emits('toggle-sort', props.data, order.value);
}
</script>

<template>
    <th
        class="py-3 px-4 text-left text-xs text-[#6B7280] bg-gray-100 font-medium uppercase whitespace-nowrap"
        scope="col"
    >
        <div class="flex items-center">
            <div>
                <span>{{ header }}</span>
            </div>
            <div>
                <svg
                    v-if="sortable"
                    :class="sortFlag ? 'rotate-180' : ''"
                    class="cursor-pointer transform transition-colors ease-in-out duration-150"
                    fill="none"
                    height="24"
                    viewBox="0 0 24 24"
                    width="24"
                    xmlns="http://www.w3.org/2000/svg"
                    @click="toggleSort"
                >
                    <path d="M8 10.5742L12.1642 14.7384L16.3284 10.5742" fill="#A0A4A8" />
                </svg>
            </div>
        </div>
    </th>
</template>
