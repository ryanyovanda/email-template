<script setup lang="ts">
import { Monitor, Paperclip, Reply, Smartphone, Star } from '@lucide/vue';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

const props = withDefaults(
    defineProps<{
        html: string;
        subject?: string;
        senderName?: string;
        senderEmail?: string;
        attachment?: string | null;
        loading?: boolean;
    }>(),
    {
        subject: '',
        senderName: 'You',
        senderEmail: '',
        attachment: null,
        loading: false,
    },
);

type Device = 'desktop' | 'mobile';

const device = ref<Device>('desktop');
const frame = ref<HTMLIFrameElement | null>(null);
const frameHeight = ref(560);

let observer: ResizeObserver | null = null;

const initial = computed(
    () => props.senderName.trim().charAt(0).toUpperCase() || '?',
);

const sentAt = computed(() =>
    new Date().toLocaleString(undefined, {
        month: 'short',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    }),
);

/**
 * Gmail renders the message body on white with its own base font, and strips
 * anything outside the body. Mirroring that here means the preview matches what
 * a recruiter sees rather than what the app's own theme would show.
 */
const document_ = computed(
    () => `<!doctype html>
<html><head><meta charset="utf-8"><base target="_blank">
<style>
  html,body{margin:0;padding:0;background:#ffffff;}
  body{font-family:Roboto,RobotoDraft,Helvetica,Arial,sans-serif;font-size:14px;color:#202124;-webkit-font-smoothing:antialiased;}
  img{max-width:100%;}
</style></head>
<body>${props.html || '<div style="padding:40px;text-align:center;color:#9aa0a6;font-size:13px">Fill in the form to see your email here.</div>'}</body></html>`,
);

function measure(): void {
    const doc = frame.value?.contentDocument;

    if (!doc?.body) {
        return;
    }

    frameHeight.value = Math.max(240, doc.body.scrollHeight + 8);
}

function onLoad(): void {
    measure();

    observer?.disconnect();

    const body = frame.value?.contentDocument?.body;

    if (body && 'ResizeObserver' in window) {
        observer = new ResizeObserver(() => measure());
        observer.observe(body);
    }
}

watch(
    () => props.html,
    () => window.setTimeout(measure, 50),
);
watch(device, () => window.setTimeout(measure, 50));

onBeforeUnmount(() => observer?.disconnect());
</script>

<template>
    <div class="flex h-full flex-col">
        <div class="mb-3 flex items-center justify-between gap-2">
            <div class="flex items-center gap-2">
                <span class="text-sm font-medium">Gmail preview</span>
                <span
                    v-if="loading"
                    class="animate-pulse text-xs text-muted-foreground"
                    >updating…</span
                >
            </div>

            <div class="flex items-center gap-1 rounded-md bg-muted p-1">
                <Button
                    v-for="option in ['desktop', 'mobile'] as Device[]"
                    :key="option"
                    type="button"
                    variant="ghost"
                    size="sm"
                    :class="
                        cn(
                            'h-7 gap-1.5 px-2 text-xs',
                            device === option && 'bg-background shadow-sm',
                        )
                    "
                    @click="device = option"
                >
                    <Monitor v-if="option === 'desktop'" class="size-3.5" />
                    <Smartphone v-else class="size-3.5" />
                    {{ option === 'desktop' ? 'Desktop' : 'Mobile' }}
                </Button>
            </div>
        </div>

        <div class="flex-1 overflow-auto rounded-xl border bg-[#f6f8fc] p-4">
            <div
                :class="
                    cn(
                        'mx-auto overflow-hidden rounded-lg bg-white shadow-sm transition-[max-width]',
                        device === 'mobile' ? 'max-w-[380px]' : 'max-w-full',
                    )
                "
            >
                <!-- Gmail message header -->
                <div class="border-b border-[#f1f3f4] px-5 pt-4 pb-3">
                    <div class="flex items-start justify-between gap-3">
                        <h3
                            class="text-[1.375rem] leading-7 font-normal text-[#202124]"
                        >
                            {{ subject || 'No subject yet' }}
                        </h3>
                        <div class="flex shrink-0 items-center gap-3 pt-1">
                            <Star class="size-4 text-[#5f6368]" />
                            <Reply class="size-4 text-[#5f6368]" />
                        </div>
                    </div>

                    <div class="mt-3 flex items-center gap-3">
                        <div
                            class="flex size-10 shrink-0 items-center justify-center rounded-full bg-[#1a73e8] text-sm font-medium text-white"
                        >
                            {{ initial }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-baseline gap-1.5">
                                <span
                                    class="truncate text-sm font-bold text-[#202124]"
                                    >{{ senderName }}</span
                                >
                                <span
                                    v-if="senderEmail"
                                    class="truncate text-xs text-[#5f6368]"
                                    >&lt;{{ senderEmail }}&gt;</span
                                >
                            </div>
                            <div class="text-xs text-[#5f6368]">to me</div>
                        </div>
                        <div class="shrink-0 text-xs text-[#5f6368]">
                            {{ sentAt }}
                        </div>
                    </div>
                </div>

                <!-- Message body, isolated so the app's theme cannot leak in -->
                <iframe
                    ref="frame"
                    :srcdoc="document_"
                    title="Email preview"
                    sandbox="allow-same-origin"
                    class="w-full border-0"
                    :style="{ height: `${frameHeight}px` }"
                    @load="onLoad"
                />

                <!-- Attachment chip -->
                <div
                    v-if="attachment"
                    class="border-t border-[#f1f3f4] px-5 py-4"
                >
                    <div class="text-xs text-[#5f6368]">One attachment</div>
                    <div
                        class="mt-2 inline-flex items-center gap-2 rounded-lg border border-[#dadce0] px-3 py-2"
                    >
                        <Paperclip class="size-4 text-[#5f6368]" />
                        <span class="text-xs text-[#202124]">{{
                            attachment
                        }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
