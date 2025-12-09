<script setup>
import { computed, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { EyeIcon, EyeSlashIcon } from '@heroicons/vue/24/outline/index.js';

import AvatarFileUpload from '@/Components/Common/Avatar/AvatarFileUpload.vue';
import TextInput from '@/Components/Common/Input/TextInput.vue';
import PrimaryButton from '@/Components/Common/Button/PrimaryButton.vue';
import PasswordChecker from '@/Components/Shared/PasswordChecker.vue';

const props = defineProps({
    user: Object,
});

const form = useForm({
    avatar: null,
    name: props.user.name,
    email: props.user.email,
    current_password: '',
    password: '',
    password_confirmation: '',
    photo: null,
});

const userAvatar = computed(() => {
    return props.user.avatar
        ? `${props.user.avatar}?${new Date().getTime()}`
        : props.user.profile_photo_url;
});

const user = computed(() => {
    return props.user;
});

const avatar = ref(null);

const contains_eight_characters = ref(false);
const contains_lowercase = ref(false);
const contains_number = ref(false);
const contains_uppercase = ref(false);
const contains_symbol = ref(false);
const valid_password = ref(false);
const passwords_match = ref(false);

const input_current_password_type = ref('password');
const input_password_type = ref('password');
const input_confirm_password_type = ref('password');

function checkPassword() {
    contains_eight_characters.value = /^(?=.{8,})/.test(form.password);
    contains_number.value = /^(?=.*[0-9])/.test(form.password);
    contains_lowercase.value = /^(?=.*[a-z])/.test(form.password);
    contains_uppercase.value = /^(?=.*[A-Z])/.test(form.password);
    contains_symbol.value = /^(?=.*[!@#$%^&*])/.test(form.password);
    passwords_match.value = form.password === form.password_confirmation;

    valid_password.value =
        contains_eight_characters.value === true &&
        contains_number.value === true &&
        contains_lowercase.value === true &&
        contains_uppercase.value === true &&
        contains_symbol.value === true &&
        passwords_match.value === true;
}

function showPassword(type, field) {
    if (field === 'current') input_current_password_type.value = type;
    else if (field === 'password') input_password_type.value = type;
    else if (field === 'confirm') input_confirm_password_type.value = type;
}

function handlePhoto(file) {
    let validFileTypes = ['image/jpg', 'image/jpeg', 'image/png'];
    if (!validFileTypes.includes(file.type)) {
        uploadError.value = 'Invalid file type. Please upload a .png, .jpeg, or .jpg file.';
    } else {
        form.photo = file;
        const reader = new FileReader();
        reader.onload = (e) => {
            userAvatar.value = e.target.result;
        };
        reader.readAsDataURL(file);
    }
}

const submitted = ref(false);

function handleUpdate() {
    const formData = new FormData();
    formData.append('name', form.name);
    formData.append('email', form.email);
    if (form.photo) {
        formData.append('photo', form.photo);
    }

    form.post(route('settings.updateProfile'), {
        preserveScroll: true,
        onSuccess: () => {
            userAvatar.value = `${form.photo}?${new Date().getTime()}`;
        },
        onError: (e) => {
            form.errors = e.response.data.errors;
        },
    });
}

function updatePassword() {
    submitted.value = true; // Track form submission

    // Prevent form submission if password validation fails
    if (!valid_password.value) {
        form.errors.password = 'Password does not meet the requirements.';
        return;
    }

    form.post(route('settings.updatePassword'), {
        preserveScroll: true,
        onSuccess: () => {
            form.current_password = '';
            form.password = '';
            form.password_confirmation = '';
            submitted.value = false; // Reset after successful update
        },
    });
}
</script>

<template>
    <div class="p-4 sm:p-8">
        <div class="bg-white border border-gray-200 rounded-2xl p-4 md:p-6">
            <div class="flex justify-between items-center mb-8 gap-8">
                <div>
                    <AvatarFileUpload
                        :imageUrl="userAvatar"
                        :showBadge="true"
                        alt="User Avatar"
                        badgeColor="bg-green-500"
                        customClass="w-[100px] h-[100px]"
                        imageClass="w-[100px] h-[100px] rounded-full"
                        @uploaded="handlePhoto($event)"
                    />
                </div>
                <div class="flex-1 w-full">
                    <TextInput
                        v-model="form.name"
                        :error="form.errors.name"
                        class="w-full"
                        label="Name"
                        placeholder="Enter your name"
                    />
                    <TextInput
                        v-model="form.email"
                        :disabled="true"
                        :error="form.errors.email"
                        class="w-full mt-4"
                        label="Email"
                        placeholder="Enter your email"
                    />
                </div>
            </div>
            <PrimaryButton :disabled="form.processing" @click="handleUpdate">
                Save Changes
            </PrimaryButton>
        </div>

        <div class="bg-white border border-gray-200 rounded-2xl p-4 md:p-6 mt-4">
            <p class="text-lg font-semibold mb-4">Update Password</p>
            <form class="space-y-4 p-4" @submit.prevent="updatePassword">
                <div class="relative">
                    <TextInput
                        v-model="form.current_password"
                        :error="form.errors.current_password"
                        :required="true"
                        :type="input_current_password_type"
                        label="Current Password"
                        @update:modelValue="checkPassword"
                    />
                    <div
                        :class="form.errors.current_password ? 'mt-0 mr-6' : 'mt-6'"
                        class="absolute cursor-pointer inset-y-0 right-0 pr-3 flex items-center text-sm leading-5"
                    >
                        <eye-icon
                            v-if="input_current_password_type === 'password'"
                            class="w-5 h-5"
                            @click="showPassword('text', 'current')"
                        />
                        <eye-slash-icon
                            v-else
                            class="w-5 h-5"
                            @click="showPassword('password', 'current')"
                        />
                    </div>
                </div>
                <div class="relative">
                    <TextInput
                        v-model="form.password"
                        :error="form.errors.password"
                        :required="true"
                        :type="input_password_type"
                        label="New Password"
                        @update:modelValue="checkPassword"
                    />
                    <div
                        :class="form.errors.password ? 'mt-0 mr-6' : 'mt-6'"
                        class="absolute cursor-pointer inset-y-0 right-0 pr-3 flex items-center text-sm leading-5"
                    >
                        <eye-icon
                            v-if="input_password_type === 'password'"
                            class="w-5 h-5"
                            @click="showPassword('text', 'password')"
                        />
                        <eye-slash-icon
                            v-else
                            class="w-5 h-5"
                            @click="showPassword('password', 'password')"
                        />
                    </div>
                </div>

                <div class="relative">
                    <TextInput
                        v-model="form.password_confirmation"
                        :error="submitted && !passwords_match ? 'Passwords do not match' : ''"
                        :required="true"
                        :type="input_confirm_password_type"
                        label="Confirm Password"
                        @update:modelValue="checkPassword"
                    />
                    <div
                        :class="submitted && !passwords_match ? 'mt-0 mr-6' : 'mt-6'"
                        class="absolute cursor-pointer inset-y-0 right-0 pr-3 flex items-center text-sm leading-5"
                    >
                        <eye-icon
                            v-if="input_confirm_password_type === 'password'"
                            class="w-5 h-5"
                            @click="showPassword('text', 'confirm')"
                        />
                        <eye-slash-icon
                            v-else
                            class="w-5 h-5"
                            @click="showPassword('password', 'confirm')"
                        />
                    </div>
                </div>

                <password-checker
                    :contains_eight_characters="contains_eight_characters"
                    :contains_lowercase="contains_lowercase"
                    :contains_number="contains_number"
                    :contains_symbol="contains_symbol"
                    :contains_uppercase="contains_uppercase"
                    :passwords_match="passwords_match"
                />

                <primary-button
                    :disabled="form.processing"
                    class="text-sm font-semibold"
                    @click="updatePassword"
                >
                    Update Password
                </primary-button>
            </form>
        </div>
    </div>
</template>
