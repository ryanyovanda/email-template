<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Loader2, Sparkles, TriangleAlert } from '@lucide/vue';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import TemplateModeTabs from '@/components/TemplateModeTabs.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
    build as buildTemplate,
    index as templatesIndex,
} from '@/routes/templates';
import { store as generateTemplate } from '@/routes/templates/generate';

const props = defineProps<{
    balance: number;
    price: number;
    reward: number;
    terms: string[];
    examples: string[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Templates', href: templatesIndex() },
            { title: 'Generate with AI', href: templatesIndex() },
        ],
    },
});

const form = useForm({
    brief: '',
    accept_terms: false as boolean,
});

const exhausted = computed(() => props.balance < props.price);

const canSubmit = computed(
    () =>
        !exhausted.value &&
        form.brief.trim().length >= 20 &&
        form.accept_terms &&
        !form.processing,
);

function useExample(example: string): void {
    form.brief = example;
}

function submit(): void {
    form.post(generateTemplate().url, { preserveScroll: true });
}
</script>

<template>
    <Head title="Design a template" />

    <div class="mx-auto w-full max-w-2xl space-y-7 p-4">
        <Button variant="ghost" size="sm" class="-ml-2" as-child>
            <Link :href="templatesIndex()">
                <ArrowLeft class="size-4" /> Back to templates
            </Link>
        </Button>

        <Heading
            title="Design your own template"
            description="Choose how you want to build it. Both end up in the same place: a private template you can use for every application."
        />

        <TemplateModeTabs mode="ai" :price="price" />

        <p class="text-sm leading-relaxed text-muted-foreground">
            Describe the layout you want and the AI builds it, in the same
            format as every other template here. You can fill it in by hand or
            let the AI write the copy, exactly as usual.
        </p>

        <div class="grid gap-3 sm:grid-cols-2">
            <div class="rounded-lg border px-4 py-3">
                <div class="text-xs text-muted-foreground">
                    This design costs
                </div>
                <div class="mt-0.5 text-lg font-semibold">
                    {{ price }} credits
                </div>
            </div>
            <div class="rounded-lg border px-4 py-3">
                <div class="text-xs text-muted-foreground">Your balance</div>
                <div
                    class="mt-0.5 text-lg font-semibold"
                    :class="exhausted ? 'text-destructive' : ''"
                >
                    {{ balance.toLocaleString() }}
                </div>
            </div>
        </div>

        <form class="space-y-6" @submit.prevent="submit">
            <div class="grid gap-2">
                <Label for="brief">What should it look like?</Label>
                <Textarea
                    id="brief"
                    v-model="form.brief"
                    rows="6"
                    :disabled="exhausted || form.processing"
                    placeholder="A bold, colourful layout for a graphic designer applying to a creative studio. Big name at the top, room for a short pitch and a few standout projects."
                />
                <div class="flex justify-between text-xs text-muted-foreground">
                    <span
                        >Say what the role is, how it should feel, and what
                        sections you need.</span
                    >
                    <span>{{ form.brief.trim().length }} / 1200</span>
                </div>
                <InputError :message="form.errors.brief" />
            </div>

            <div class="space-y-2">
                <p class="text-xs text-muted-foreground">
                    Or start from one of these
                </p>
                <div class="flex flex-col gap-2">
                    <button
                        v-for="example in examples"
                        :key="example"
                        type="button"
                        class="rounded-lg border px-3 py-2 text-left text-xs leading-relaxed transition-colors hover:bg-accent/50"
                        :disabled="exhausted || form.processing"
                        @click="useExample(example)"
                    >
                        {{ example }}
                    </button>
                </div>
            </div>

            <!-- Terms -->
            <section class="rounded-xl border p-5">
                <h2 class="text-sm font-semibold">Before you generate</h2>
                <ul class="mt-3 space-y-2">
                    <li
                        v-for="(term, index) in terms"
                        :key="index"
                        class="flex gap-2.5 text-sm leading-relaxed text-muted-foreground"
                    >
                        <span
                            class="mt-1.5 size-1 shrink-0 rounded-full bg-current text-muted-foreground/50"
                        />
                        <span>{{ term }}</span>
                    </li>
                </ul>

                <label
                    class="mt-4 flex cursor-pointer items-start gap-3 text-sm"
                >
                    <input
                        v-model="form.accept_terms"
                        type="checkbox"
                        class="mt-0.5 size-4 shrink-0 rounded border"
                        :disabled="exhausted || form.processing"
                    />
                    <span>
                        I have read the points above, and I agree that an
                        administrator may publish this design to the shared
                        template library.
                    </span>
                </label>
                <InputError class="mt-2" :message="form.errors.accept_terms" />
            </section>

            <div
                v-if="exhausted"
                class="flex gap-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/40 dark:text-amber-200"
            >
                <TriangleAlert class="mt-0.5 size-4 shrink-0" />
                <p>
                    This costs {{ price }} credits and you have {{ balance }}.
                    Your allowance tops up at the start of next month. Meanwhile
                    the shared template library is open to you, and
                    <Link :href="buildTemplate()" class="font-medium underline"
                        >writing your own HTML</Link
                    >
                    is free.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <Button type="submit" :disabled="!canSubmit">
                    <Loader2
                        v-if="form.processing"
                        class="size-4 animate-spin"
                    />
                    <Sparkles v-else class="size-4" />
                    {{
                        form.processing
                            ? 'Designing…'
                            : `Design my template · ${price} credits`
                    }}
                </Button>
                <span
                    v-if="form.processing"
                    class="text-xs text-muted-foreground"
                    >This takes up to a minute. Leave the page open.</span
                >
            </div>
        </form>
    </div>
</template>
