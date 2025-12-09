<script setup>
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/Common/Button/PrimaryButton.vue';
import PasswordInput from '@/Components/Common/Input/PasswordInput.vue';
import CheckIcon from '@/Components/Icon/CheckIcon.vue';
import { computed, ref } from 'vue';
import Moment from 'moment';

const props = defineProps({
    email: {
        type: String,
        required: true,
    },
    token: {
        type: String,
        required: true,
    },
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const year = Moment(new Date()).format('YYYY');

const updateSuccess = ref(false);

const validationRules = computed(() => [
    {
        label: 'Use 8-32 characters',
        isValid: form.password.length >= 8 && form.password.length <= 32,
    },
    {
        label: 'Use at least one number',
        isValid: /[0-9]/.test(form.password),
    },
    {
        label: 'Use at least one symbol',
        isValid: /[!@#$%^&*(),.?":{}|<>]/.test(form.password),
    },
    {
        label: 'Use at least one lower case letter',
        isValid: /[a-z]/.test(form.password),
    },
    {
        label: 'Use at least one upper case letter',
        isValid: /[A-Z]/.test(form.password),
    },
    {
        label: 'New & confirm password match',
        isValid: form.password !== '' && form.password === form.password_confirmation,
    },
]);

const allValid = computed(() => validationRules.value.every((rule) => rule.isValid));

const submit = () => {
    form.post(route('password.store'), {
        onSuccess: () => {
            updateSuccess.value = true;
        },
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Reset Password" />

    <div class="flex min-h-screen flex-1">
        <div
            class="relative flex flex-1 flex-col justify-center px-4 py-12 sm:px-6 lg:flex-none lg:px-20 xl:px-24"
        >
            <div class="mx-auto w-full max-w-sm lg:w-96">
                <div>
                    <img alt="Julie's Logo" class="h-9" src="/img/julies_logo.png" />

                    <div v-if="updateSuccess" class="mt-10 flex flex-col items-center text-center">
                        <div
                            class="bg-[#D72136] p-3.5 shadow-[0px_4px_34px_0px_#FFDB3B] rounded-[24px] w-[126px] h-[127px]"
                        >
                            <img alt="" src="/img/julies_bakeshop_logo2.png" />
                        </div>
                        <h2 class="mt-8 text-3xl font-bold">Welcome to Julie's JFORM</h2>
                        <p class="mt-2 text-sm"></p>
                        <PrimaryButton
                            class="mt-8 w-full !font-medium flex justify-center !bg-primary"
                            type="button"
                            @click="goToDashboard"
                        >
                            Go to Dashboard
                        </PrimaryButton>
                    </div>

                    <div v-else>
                        <div>
                            <h2 class="mt-8 text-3xl font-bold">Reset your Password</h2>
                            <p class="mt-2 text-sm">Create a new password for your account</p>
                        </div>
                    </div>
                </div>

                <div v-if="!updateSuccess" class="mt-10">
                    <form class="space-y-4" @submit.prevent="submit">
                        <PasswordInput
                            id="password"
                            v-model="form.password"
                            :required="true"
                            input-class="!border-gray-300"
                            label="New Password"
                        />

                        <PasswordInput
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            :required="true"
                            input-class="!border-gray-300"
                            label="Confirm Password"
                        />

                        <div class="mt-6 mb-2">
                            <div
                                v-for="(rule, index) in validationRules"
                                :key="index"
                                class="flex items-center space-x-1 mb-2"
                            >
                                <CheckIcon
                                    :class="rule.isValid ? 'text-green-600' : 'text-gray-400'"
                                    class="mr-2"
                                />
                                <span
                                    :class="rule.isValid ? 'text-gray-700' : 'text-gray-400'"
                                    class="text-sm font-medium"
                                >
                                    {{ rule.label }}
                                </span>
                            </div>
                        </div>

                        <PrimaryButton
                            class="w-full !font-medium flex justify-center !bg-primary"
                            type="submit"
                        >
                            Update Password
                        </PrimaryButton>
                    </form>
                </div>
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
