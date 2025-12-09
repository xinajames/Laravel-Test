<script setup>
import { onMounted, ref } from 'vue';
import { Head } from '@inertiajs/vue3';

import axios from 'axios';
import Layout from '@/Layouts/Admin/Layout.vue';
import FranchiseeDetailsCard from '@/Components/Dashboard/FranchiseeDetailsCard.vue';
import TotalStoresCard from '@/Components/Dashboard/TotalStoresCard.vue';
import FranchiseStoreCard from '@/Components/Dashboard/FranchiseStoreCard.vue';
import CompanyOwnedStoreCard from '@/Components/Dashboard/CompanyOwnedStoreCard.vue';
import OpeningClosureCard from '@/Components/Dashboard/OpeningClosureCard.vue';
import TemporaryClosureCard from '@/Components/Dashboard/TemporaryClosureCard.vue';

const segments = ref([
    { label: 'Luzon', count: 800, class: 'bg-rose-300', iconClass: 'text-rose-300' },
    { label: 'Visayas', count: 800, class: 'bg-orange-200', iconClass: 'text-orange-200' },
    { label: 'Mindanao', count: 800, class: 'bg-red-400', iconClass: 'text-red-400' },
]);

const storeTypes = ref([
    { label: 'Branch', iconClass: 'text-[#FFD29F]' },
    { label: 'Express', iconClass: 'text-[#F395A1]' },
    { label: 'Junior', iconClass: 'text-[#FFBCE0]' },
    { label: 'Outlet', iconClass: 'text-[#AB9CE0]' },
]);

const allStores = ref([]);
const franchiseStores = ref([]);
const companyOwnedStores = ref([]);

function getData() {
    axios.get(route('dashboard.getFranchiseeCountDetails')).then(({ data }) => {
        const map = { LUZ: 'Luzon', VIS: 'Visayas', MIN: 'Mindanao' };

        allStores.value = Object.entries(data.store)
            .filter(([k]) => k !== 'NULL')
            .map(([k, v]) => ({
                store_group: 'All',
                group: map[k] || 'N/A',
                count: v.total,
                storeType: {
                    branch: v.Branch,
                    express: v.Express,
                    junior: v.Junior,
                    outlet: v.Outlet,
                },
            }));

        franchiseStores.value = Object.entries(data.franchisee_stores)
            .filter(([k]) => k !== 'NULL')
            .map(([k, v]) => ({
                store_group: 'FullFranchise',
                group: map[k] || 'N/A',
                count: v.total,
                storeType: {
                    branch: v.Branch,
                    express: v.Express,
                    junior: v.Junior,
                    outlet: v.Outlet,
                },
            }));

        companyOwnedStores.value = Object.entries(data.company_owned_stores)
            .filter(([k]) => k !== 'NULL')
            .map(([k, v]) => ({
                store_group: 'CompanyOwned',
                group: map[k] || 'N/A',
                count: v.total,
                storeType: {
                    branch: v.Branch,
                    express: v.Express,
                    junior: v.Junior,
                    outlet: v.Outlet,
                },
            }));
    });
}

onMounted(getData);
</script>

<template>
    <Head title="Dashboard" />
    <Layout :contentNoPadding="true" :showTopBar="false">
        <div class="relative">
            <div class="absolute inset-0 h-[240px]">
                <div class="absolute inset-0 bg-[#EFE280]"></div>

                <!-- linear-gradient overlay #F9FAFB → transparent (higher z-index) -->
                <div class="absolute inset-0 bg-gradient-to-b from-transparent to-[#F9FAFB]"></div>

                <div
                    class="absolute inset-0 h-full"
                    style="
                        background-image: url('/img/dashboard_background.png');
                        background-repeat: repeat-x;
                        background-size: auto 100%;
                    "
                />
            </div>
            <div class="relative z-10 pt-10 pb-6">
                <h1 class="text-3xl font-bold text-gray-900 px-10">My Dashboard</h1>
                <div class="mt-6">
                    <FranchiseeDetailsCard />
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 px-10 mt-6">
                        <TotalStoresCard :storeTypes="storeTypes" :stores="allStores" />
                        <FranchiseStoreCard :storeTypes="storeTypes" :stores="franchiseStores" />
                        <CompanyOwnedStoreCard
                            :storeTypes="storeTypes"
                            :stores="companyOwnedStores"
                        />
                    </div>
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 px-10 mt-6">
                        <OpeningClosureCard :storeTypes="storeTypes" />
                        <TemporaryClosureCard :storeTypes="storeTypes" />
                    </div>
                </div>
            </div>
        </div>
    </Layout>
</template>
