<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import ConsentController from '@/actions/App/Http/Controllers/OAuth/ConsentController';

type ConsentScope = {
    name: string;
    description: string;
};

const props = defineProps<{
    clientName: string;
    requestedScopes: ConsentScope[];
    hasUnsupportedScopes: boolean;
}>();

const form = useForm({});

const accept = () => {
    form.post(ConsentController.accept.url());
};

const reject = () => {
    form.post(ConsentController.reject.url());
};
</script>

<template>
    <Head title="Berechtigungen bestätigen" />

    <main class="mx-auto max-w-lg px-6 py-12">
        <section class="rounded-xl border p-6 shadow-sm">
            <h1 class="text-2xl font-semibold">Zugriff bestätigen</h1>

            <p class="mt-3 text-sm text-muted-foreground">
                <strong>{{ props.clientName }}</strong>
                möchte auf folgende Informationen deines Kontos zugreifen:
            </p>

            <div
                v-if="props.hasUnsupportedScopes"
                class="mt-4 rounded-md border p-3 text-sm"
                role="status"
            >
                Diese Anwendung fordert weitere Berechtigungen an, die derzeit
                nicht unterstützt werden. Diese werden nicht freigegeben.
            </div>

            <section
                class="mt-6 border-y py-4"
                aria-label="Angeforderte Berechtigungen"
            >
                <h2 class="font-medium">Angeforderte Berechtigungen</h2>

                <p
                    v-if="props.requestedScopes.length === 0"
                    class="mt-3 text-sm text-muted-foreground"
                >
                    Es gibt keine unterstützten Berechtigungen.
                </p>

                <ul v-else class="mt-2 divide-y">
                    <li
                        v-for="scope in props.requestedScopes"
                        :key="scope.name"
                        class="py-3"
                    >
                        <span class="block font-medium">{{ scope.name }}</span>
                        <span class="block text-sm text-muted-foreground">
                            {{ scope.description }}
                        </span>
                    </li>
                </ul>
            </section>

            <p class="mt-4 text-sm text-muted-foreground">
                Bei Zustimmung merkt sich Identity diese Freigabe für diese
                Anwendung. Hydra kann sie bei passenden zukünftigen Anfragen
                überspringen.
            </p>

            <form class="mt-6 space-y-5" @submit.prevent="accept">
                <div class="flex flex-col gap-3 sm:flex-row">
                    <button
                        type="submit"
                        class="rounded-md bg-primary px-4 py-2 text-primary-foreground disabled:opacity-50"
                        :disabled="form.processing"
                    >
                        Zugriff erlauben
                    </button>

                    <button
                        type="button"
                        class="rounded-md border px-4 py-2 disabled:opacity-50"
                        :disabled="form.processing"
                        @click="reject"
                    >
                        Ablehnen
                    </button>
                </div>
            </form>
        </section>
    </main>
</template>
