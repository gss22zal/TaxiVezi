<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const page = usePage()
const error = computed(() => page.props.flash?.error || null)

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Вход в систему" />

        <div v-if="status" class="mb-4 rounded-lg bg-green-500/20 px-4 py-3 text-sm font-medium text-green-500">
            {{ status }}
        </div>

        <div v-if="error" class="mb-4 rounded-lg bg-red-500/20 px-4 py-3 text-sm font-medium text-red-500">
            {{ error }}
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <!-- Email -->
            <div>
                <InputLabel for="email" value="Email" class="text-gray-300" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-2 block w-full rounded-lg border border-gray-600 bg-gray-800 px-4 py-3 text-white placeholder-gray-500 focus:border-red-500 focus:ring-red-500"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="example@mail.ru"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <!-- Password -->
            <div>
                <InputLabel for="password" value="Пароль" class="text-gray-300" />

                <TextInput
                    id="password"
                    type="password"
                    class="mt-2 block w-full rounded-lg border border-gray-600 bg-gray-800 px-4 py-3 text-white placeholder-gray-500 focus:border-red-500 focus:ring-red-500"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                    placeholder="••••••••"
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <!-- Remember & Forgot -->
            <div class="flex items-center justify-between">
                <label class="flex items-center">
                    <Checkbox name="remember" v-model:checked="form.remember" class="rounded border-gray-600 bg-gray-700 text-red-500 focus:ring-red-500" />
                    <span class="ml-2 text-sm text-gray-400">Запомнить меня</span>
                </label>

                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="text-sm text-gray-400 hover:text-white hover:underline"
                >
                    Забыли пароль?
                </Link>
            </div>

            <!-- Submit Button -->
            <div>
                <PrimaryButton
                    class="w-full justify-center rounded-lg bg-red-500 py-3 text-center font-medium text-white hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 focus:ring-offset-gray-900 disabled:opacity-50"
                    :class="{ 'opacity-50': form.processing }"
                    :disabled="form.processing"
                >
                    Войти
                </PrimaryButton>
            </div>

            <!-- Register Link -->
            <div class="text-center text-sm text-gray-400">
                Нет аккаунта?
                <Link :href="route('register')" class="text-red-500 hover:text-red-400 hover:underline">
                    Зарегистрироваться
                </Link>
            </div>
        </form>
    </GuestLayout>
</template>
