<script setup>
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Moment from 'moment';
import TextInput from '@/Components/Common/Input/TextInput.vue';
import PrimaryButton from '@/Components/Common/Button/PrimaryButton.vue';
import SecondaryButton from '@/Components/Common/Button/SecondaryButton.vue';
import MailIcon from '@/Components/Icon/MailIcon.vue';

defineProps({
    status: {
        type: String,
    },
});

const page = usePage();

const app = computed(() => {
    return page.props.app;
});

const form = useForm({
    email: '',
});

const success = ref(false);

const year = Moment(new Date()).format('YYYY');

const submit = () => {
    success.value = false;
    form.post(route('password.email'), {
        onSuccess: () => {
            success.value = true;
        },
    });
};

const goToLogin = () => {
    router.visit('login');
};
</script>

<template>
    <Head title="Forgot Password" />

    <div class="flex min-h-screen flex-1">
        <div
            class="relative flex flex-1 flex-col justify-center px-4 py-12 sm:px-6 lg:flex-none lg:px-20 xl:px-24"
        >
            <div v-if="!success" class="mx-auto w-full max-w-sm lg:w-96">
                <div>
                    <img alt="Julie's Logo" class="h-9" src="/img/julies_logo.png" />
                    <h2 class="mt-8 text-3xl leading-10 font-bold">Forgot your password?</h2>
                    <p class="mt-2 text-sm">
                        Enter your email address and we will send a password reset link to you.
                    </p>
                </div>

                <div class="mt-10">
                    <div>
                        <form class="space-y-6" @submit.prevent="submit">
                            <TextInput
                                v-model="form.email"
                                id="email"
                                input-class="!border-gray-300"
                                label="Email"
                                type="email"
                                :error="form.errors ? form.errors.email : []"
                                :required="true"
                            />

                            <div class="space-y-2">
                                <PrimaryButton
                                    class="w-full flex !font-medium justify-center !bg-primary"
                                    type="submit"
                                >
                                    Get Password Reset Link
                                </PrimaryButton>
                                <SecondaryButton
                                    class="w-full !font-medium flex justify-center"
                                    type="button"
                                    @click="goToLogin()"
                                >
                                    Back to Login
                                </SecondaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div v-else class="mx-auto w-full max-w-sm lg:w-96 text-center">
                <img alt="Julie's Logo" class="h-9" src="/img/julies_logo.png" />
                <div class="mt-20">
                    <div
                        class="flex justify-center mx-auto items-center w-20 h-20 rounded-full border border-primary"
                    >
                        <MailIcon class="text-primary size-12" />
                    </div>

                    <h2 class="mt-6 text-3xl leading-10 font-bold">
                        Password reset link has been sent
                    </h2>
                    <p class="mt-2 text-sm"></p>
                </div>

                <SecondaryButton
                    class="mt-6 w-full !font-medium flex justify-center"
                    type="button"
                    @click="goToLogin()"
                >
                    Back to Login
                </SecondaryButton>
            </div>
            <p class="text-gray-800 text-sm absolute bottom-10 px-10 sm:px-0">
                © {{ year }} Julie’s JFORM All rights reserved.
            </p>
        </div>
        <div class="relative hidden w-0 flex-1 lg:block">
            <img
                class="absolute inset-0 size-full object-cover"
                src="/img/julies_banner.png"
                alt=""
            />
            <div class="absolute right-20 bottom-10 px-10 sm:px-0">
                <div class="flex gap-6">
                    <Link href="#" class="text-sm text-white">Privacy Policy</Link>
                    <Link href="#" class="text-sm text-white">Terms and Conditions</Link>
                </div>
            </div>
        </div>
    </div>
</template>
