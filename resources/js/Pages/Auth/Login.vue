<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Moment from 'moment';
import PasswordInput from '@/Components/Common/Input/PasswordInput.vue';
import PrimaryButton from '@/Components/Common/Button/PrimaryButton.vue';
import TextInput from '@/Components/Common/Input/TextInput.vue';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
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
    password: '',
    remember: false,
});

const year = Moment(new Date()).format('YYYY');

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Log in" />
    <div class="flex min-h-screen flex-1">
        <div
            class="relative flex flex-1 flex-col justify-center px-4 py-12 sm:px-6 lg:flex-none lg:px-20 xl:px-24"
        >
            <div class="mx-auto w-full max-w-sm lg:w-96">
                <div>
                    <img alt="Julie's Logo" class="h-9" src="/img/julies_logo.png" />
                    <h2 class="mt-8 text-3xl font-bold">Welcome</h2>
                    <p class="mt-2 text-sm">Log in to your account</p>
                </div>

                <div class="mt-5">
                    <div v-if="form.errors?.email" class="mt-1 text-sm text-red-600 pb-4">
                        {{ form.errors.email }}
                    </div>
                    <div>
                        <form autocomplete="off" class="space-y-4" @submit.prevent="submit">
                            <TextInput
                                id="email"
                                v-model="form.email"
                                autocomplete="off"
                                input-class="!border-gray-300"
                                label="Email"
                                type="email"
                            />

                            <PasswordInput
                                id="password"
                                v-model="form.password"
                                :error="form.errors ? form.errors.password : []"
                                autocomplete="off"
                                input-class="!border-gray-300"
                                label="Password"
                            />

                            <div class="my-6 text-left">
                                <Link
                                    v-if="canResetPassword"
                                    :href="route('password.request')"
                                    class="font-medium text-sm text-primary hover:underline focus:outline-none focus:ring-2 focus:ring-offset-2"
                                >
                                    Forgot your password?
                                </Link>
                            </div>

                            <PrimaryButton
                                :disabled="form.processing"
                                class="w-full !font-medium flex justify-center !bg-primary"
                                type="submit"
                            >
                                <span v-if="form.processing">Logging in...</span>
                                <span v-else>Login</span>
                            </PrimaryButton>
                        </form>
                    </div>
                </div>
            </div>
            <p class="text-gray-800 text-sm absolute bottom-10 px-10 sm:px-0">
                © {{ year }} Julie’s JFORM All rights reserved.
            </p>
        </div>
        <div class="relative hidden w-0 flex-1 lg:block">
            <img
                alt=""
                class="absolute inset-0 size-full object-cover"
                src="/img/julies_banner.png"
            />
            <div class="absolute right-20 bottom-10 px-10 sm:px-0">
                <div class="flex items-center gap-6 text-white text-sm">
                    <Link class="hover:underline" href="#">Privacy Policy</Link>
                    <Link class="hover:underline" href="#">Terms and Conditions</Link>
                    <span class="opacity-80">v{{ app.version }}.{{ app.environment }}</span>
                </div>
            </div>
        </div>
    </div>
</template>
