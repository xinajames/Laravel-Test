<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import ProfileOverviewCard from '@/Components/Franchisees/BasicDetails/ProfileOverviewCard.vue';
import PersonalInformationCard from '@/Components/Franchisees/BasicDetails/PersonalInformationCard.vue';
import ContactInformation from '@/Components/Franchisees/BasicDetails/ContactInformation.vue';
import FranchiseManagementContactsCard from '@/Components/Franchisees/FranchiseInfo/FranchiseManagementContactsCard.vue';
import TrainingManualsCard from '@/Components/Franchisees/FranchiseInfo/TrainingManualsCard.vue';
import ApplicationDetailsCard from '@/Components/Franchisees/FranchiseInfo/ApplicationDetailsCard.vue';
import BackgroundInformationCard from '@/Components/Franchisees/FranchiseInfo/BackgroundInformationCard.vue';
import AdditionalNotesCard from '@/Components/Franchisees/FranchiseInfo/AdditionalNotesCard.vue';

const props = defineProps({
    franchisee: Object,
});

const navigations = [
    {
        label: 'Profile',
        url: '#profile',
    },
    {
        label: 'Personal Information',
        url: '#personal-information',
    },
    {
        label: 'Contact Information',
        url: '#contact-information',
    },
    {
        label: 'Franchise Management Contacts',
        url: '#franchise-management-contacts',
    },
    {
        label: 'Training and Manuals',
        url: '#training-and-manuals',
    },
    {
        label: 'Application Details',
        url: '#application-details',
    },
    {
        label: 'Background Information',
        url: '#background-information',
    },
    {
        label: 'Additional Notes',
        url: '#additional-notes',
    },
];

const activeNav = ref('#profile');

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
                    :key="index"
                    class="cursor-pointer"
                    @click="handleNav(nav)"
                >
                    <div
                        class="p-2 transition"
                        :class="{
                            'rounded-md bg-gray-200 text-gray-900 font-bold': nav.url === activeNav,
                            'text-gray-600 hover:bg-gray-200 hover:text-gray-900 rounded-md':
                                nav.url !== activeNav,
                        }"
                    >
                        <p class="font-medium">
                            {{ nav.label }}
                        </p>
                    </div>
                </div>
            </div>
            <div class="space-y-4 flex-1">
                <ProfileOverviewCard
                    id="profile"
                    :franchisee="franchisee"
                    :profile_photo="franchisee.franchisee_profile_photo"
                    page="show"
                />
                <PersonalInformationCard
                    id="personal-information"
                    :franchisee="franchisee"
                    page="show"
                />
                <ContactInformation id="contact-information" :franchisee="franchisee" page="show" />
                <FranchiseManagementContactsCard
                    id="franchise-management-contacts"
                    :franchisee="franchisee"
                    page="show"
                />
                <TrainingManualsCard
                    id="training-and-manuals"
                    :franchisee="franchisee"
                    page="show"
                />
                <ApplicationDetailsCard
                    id="application-details"
                    :franchisee="franchisee"
                    page="show"
                />
                <BackgroundInformationCard
                    id="background-information"
                    :franchisee="franchisee"
                    page="show"
                />
                <AdditionalNotesCard id="additional-notes" :franchisee="franchisee" page="show" />
            </div>
        </div>
    </div>
</template>

<style scoped></style>
