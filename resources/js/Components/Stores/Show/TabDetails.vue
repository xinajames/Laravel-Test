<script setup>
import { onMounted, onUnmounted, ref } from 'vue';
import GeneralInformationCard from '@/Components/Stores/BasicDetails/GeneralInformationCard.vue';
import ManagementOperationCard from '@/Components/Stores/BasicDetails/ManagementOperationCard.vue';
import OpeningFranchiseTimeline from '@/Components/Stores/BasicDetails/OpeningFranchiseTimeline.vue';
import FinancialLegalDocuments from '@/Components/Stores/Specifications/FinancialLegalDocuments.vue';
import LocationSpecification from '@/Components/Stores/Specifications/LocationSpecification.vue';
import LeaseLegalInformation from '@/Components/Stores/Specifications/LeaseLegalInformation.vue';
import StoreMaintenanceUpdate from '@/Components/Stores/Specifications/StoreMaintenanceUpdate.vue';
import StoreAddressCard from '@/Components/Stores/ContactInfo/StoreAddressCard.vue';
import EquipmentTechnology from '@/Components/Stores/ContactInfo/EquipmentTechnology.vue';
import OthersDetailsCard from '@/Components/Stores/ContactInfo/OthersDetailsCard.vue';

const props = defineProps({
    store: Object,
});

const navigations = [
    {
        label: 'General Information',
        url: '#general-information',
    },
    {
        label: 'Management & Operations',
        url: '#management-operations',
    },
    {
        label: 'Opening & Franchise Timeline ',
        url: '#opening-franchise-timeline',
    },
    {
        label: 'Financial & Legal Documents',
        url: '#financial-legal-documents',
    },
    {
        label: 'Location & Specifications',
        url: '#location-specifications',
    },
    {
        label: 'Lease & Legal Information',
        url: '#lease-legal-information',
    },
    {
        label: 'Store Maintenance & Updates',
        url: '#store-maintenance-updates',
    },
    {
        label: 'Store Address & Communication Details',
        url: '#store-address-communication-details',
    },
    {
        label: 'Equipment & Technology',
        url: '#equipment-technology',
    },
    {
        label: 'Other Details',
        url: '#other-details',
    },
];

const activeNav = ref('#general-information');

function handleNav(nav) {
    activeNav.value = nav.url;

    const element = document.querySelector(nav.url);
    if (element) {
        const headerOffset = 120;
        const elementPosition = element.getBoundingClientRect().top + window.scrollY;
        const offsetPosition = elementPosition - headerOffset;

        window.scrollTo({
            top: offsetPosition,
            behavior: 'smooth',
        });
    }
}

function updateActiveNav() {
    let closestSection = null;
    let minDistance = Infinity;

    navigations.forEach((nav) => {
        const section = document.querySelector(nav.url);
        if (section) {
            const rect = section.getBoundingClientRect();
            const distance = Math.abs(rect.top - 250); // Adjusted header offset

            if (distance < minDistance && rect.top >= -50) {
                minDistance = distance;
                closestSection = nav.url;
            }
        }
    });

    if (closestSection) {
        activeNav.value = closestSection;
    }
}

let scrollTimeout;

function handleScroll() {
    if (scrollTimeout) {
        cancelAnimationFrame(scrollTimeout);
    }
    scrollTimeout = requestAnimationFrame(updateActiveNav);
}

onMounted(() => {
    window.addEventListener('scroll', handleScroll);
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
});
</script>

<template>
    <div class="p-8">
        <div class="flex gap-6">
            <div class="space-y-1 sticky top-36 h-[calc(100vh-6rem)] overflow-y-auto">
                <div
                    v-for="(nav, index) in navigations"
                    class="cursor-pointer"
                    @click="handleNav(nav)"
                >
                    <div
                        :class="
                            nav.url === activeNav
                                ? 'rounded-md bg-gray-200 text-gray-900'
                                : 'text-gray-600'
                        "
                        class="p-2 hover:bg-gray-200 hover:rounded-md hover:text-gray-900"
                    >
                        <p class="font-medium">
                            {{ nav.label }}
                        </p>
                    </div>
                </div>
            </div>
            <div class="space-y-4 flex-1">
                <div id="general-information">
                    <GeneralInformationCard :form="store" :store="store" page="show" />
                </div>

                <div id="management-operations">
                    <ManagementOperationCard :form="store" :store="store" page="show" />
                </div>

                <div id="opening-franchise-timeline">
                    <OpeningFranchiseTimeline :form="store" :store="store" page="show" />
                </div>

                <div id="financial-legal-documents">
                    <FinancialLegalDocuments :form="store" :store="store" page="show" />
                </div>

                <div id="location-specifications">
                    <LocationSpecification :form="store" :store="store" page="show" />
                </div>

                <div id="lease-legal-information">
                    <LeaseLegalInformation :form="store" :store="store" page="show" />
                </div>

                <div id="store-maintenance-updates">
                    <StoreMaintenanceUpdate :form="store" :store="store" page="show" />
                </div>

                <div id="store-address-communication-details">
                    <StoreAddressCard :form="store" :store="store" page="show" />
                </div>

                <div id="equipment-technology">
                    <EquipmentTechnology :form="store" :store="store" page="show" />
                </div>

                <div id="other-details">
                    <OthersDetailsCard :form="store" :store="store" page="show" />
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped></style>
