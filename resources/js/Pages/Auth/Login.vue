<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import Button from 'primevue/button';
import Message from 'primevue/message';

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
};

const usarDemo = (email) => {
    form.email = email;
    form.password = 'password';
    submit();
};
</script>

<template>
    <Head title="Ingresar" />
    <div class="min-h-screen flex items-center justify-center p-4"
         style="background: radial-gradient(circle at 30% 20%, #1e293b, #0f172a);">
        <div class="w-full max-w-md">
            <div class="text-center mb-6">
                <div class="inline-flex w-14 h-14 rounded-2xl bg-blue-600 items-center justify-center text-white text-2xl font-bold mb-3">K</div>
                <h1 class="text-2xl font-semibold text-white">KUDOS Demo</h1>
                <p class="text-slate-400 text-sm">Sistema ERP · Multi-empresa</p>
            </div>

            <div class="bg-white rounded-2xl shadow-2xl p-7">
                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Correo</label>
                        <InputText v-model="form.email" type="email" class="w-full"
                                   placeholder="admin@empresa-a.com" autofocus />
                        <Message v-if="form.errors.email" severity="error" variant="simple" size="small" class="mt-1">
                            {{ form.errors.email }}
                        </Message>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Contraseña</label>
                        <Password v-model="form.password" :feedback="false" toggleMask
                                  class="w-full" inputClass="w-full" placeholder="••••••••" />
                    </div>

                    <Button type="submit" label="Ingresar" class="w-full"
                            :loading="form.processing" />
                </form>

                <div class="mt-6 pt-5 border-t border-slate-100">
                    <p class="text-xs text-slate-400 mb-2 text-center">Cuentas demo (clic para entrar)</p>
                    <div class="grid grid-cols-2 gap-2">
                        <Button label="Empresa A" severity="secondary" outlined size="small"
                                @click="usarDemo('admin@empresa-a.com')" />
                        <Button label="Empresa B" severity="secondary" outlined size="small"
                                @click="usarDemo('admin@empresa-b.com')" />
                    </div>
                </div>
            </div>
            <p class="text-center text-slate-500 text-xs mt-5">
                Cada empresa ve únicamente sus propios datos (multi-empresa).
            </p>
        </div>
    </div>
</template>
