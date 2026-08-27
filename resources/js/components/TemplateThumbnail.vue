<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue';

/**
 * A real, scaled-down render of the email rather than a mock-up, so the card
 * shows the actual design. The frame is inert: it neither runs scripts nor takes
 * pointer events, so the whole card stays clickable.
 */
const props = withDefaults(
    defineProps<{
        html: string;
        /** Width the email is authored for; the scale is derived from it. */
        designWidth?: number;
        /** Visible height of the cropped preview. */
        height?: number;
    }>(),
    { designWidth: 640, height: 250 },
);

const container = ref<HTMLElement | null>(null);
const scale = ref(0.4);
const visible = ref(false);

let resizeObserver: ResizeObserver | null = null;
let intersectionObserver: IntersectionObserver | null = null;

function measure(): void {
    if (container.value) {
        scale.value = container.value.clientWidth / props.designWidth;
    }
}

onMounted(() => {
    measure();

    if (container.value && 'ResizeObserver' in window) {
        resizeObserver = new ResizeObserver(measure);
        resizeObserver.observe(container.value);
    }

    // Cards below the fold do not need their frame built until they are reached.
    if (container.value && 'IntersectionObserver' in window) {
        intersectionObserver = new IntersectionObserver(
            (entries) => {
                if (entries.some((entry) => entry.isIntersecting)) {
                    visible.value = true;
                    intersectionObserver?.disconnect();
                }
            },
            { rootMargin: '300px' },
        );
        intersectionObserver.observe(container.value);
    } else {
        visible.value = true;
    }
});

onBeforeUnmount(() => {
    resizeObserver?.disconnect();
    intersectionObserver?.disconnect();
});
</script>

<template>
    <div
        ref="container"
        class="relative overflow-hidden bg-white"
        :style="{ height: `${height}px` }"
    >
        <iframe
            v-if="visible"
            :srcdoc="html"
            title=""
            aria-hidden="true"
            tabindex="-1"
            sandbox="allow-same-origin"
            scrolling="no"
            class="pointer-events-none border-0"
            :style="{
                width: `${designWidth}px`,
                height: `${Math.round(height / scale) + 40}px`,
                transform: `scale(${scale})`,
                transformOrigin: 'top left',
            }"
        />
        <!-- Fades the crop so it reads as a preview rather than a cut-off email. -->
        <div
            class="pointer-events-none absolute inset-x-0 bottom-0 h-10 bg-gradient-to-t from-white to-transparent"
        />
    </div>
</template>
