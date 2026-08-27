<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Code2, Sparkles } from '@lucide/vue';
import { cn } from '@/lib/utils';
import {
    build as buildTemplate,
    generate as generateTemplate,
} from '@/routes/templates';

defineProps<{
    mode: 'ai' | 'html';
    price: number;
}>();
</script>

<template>
    <div class="grid gap-3 sm:grid-cols-2">
        <Link
            :href="generateTemplate()"
            :aria-current="mode === 'ai' ? 'page' : undefined"
            :class="
                cn(
                    'flex items-start gap-3 rounded-xl border p-4 transition-colors',
                    mode === 'ai'
                        ? 'border-primary bg-primary/5 ring-1 ring-primary/30'
                        : 'hover:bg-accent/40',
                )
            "
        >
            <Sparkles
                class="mt-0.5 size-5 shrink-0"
                :class="
                    mode === 'ai' ? 'text-primary' : 'text-muted-foreground'
                "
            />
            <span class="min-w-0">
                <span class="block text-sm font-semibold"
                    >Generate with AI</span
                >
                <span
                    class="mt-1 block text-xs leading-relaxed text-muted-foreground"
                >
                    Describe the layout you want in plain words and the AI
                    builds it for you.
                </span>
                <span class="mt-2 block text-xs font-medium"
                    >{{ price }} credits</span
                >
            </span>
        </Link>

        <Link
            :href="buildTemplate()"
            :aria-current="mode === 'html' ? 'page' : undefined"
            :class="
                cn(
                    'flex items-start gap-3 rounded-xl border p-4 transition-colors',
                    mode === 'html'
                        ? 'border-primary bg-primary/5 ring-1 ring-primary/30'
                        : 'hover:bg-accent/40',
                )
            "
        >
            <Code2
                class="mt-0.5 size-5 shrink-0"
                :class="
                    mode === 'html' ? 'text-primary' : 'text-muted-foreground'
                "
            />
            <span class="min-w-0">
                <span class="block text-sm font-semibold">Make your own</span>
                <span
                    class="mt-1 block text-xs leading-relaxed text-muted-foreground"
                >
                    Write or paste your own HTML, with a live preview and a full
                    guide beside it.
                </span>
                <span
                    class="mt-2 block text-xs font-medium text-emerald-600 dark:text-emerald-400"
                >
                    Free
                </span>
            </span>
        </Link>
    </div>
</template>
