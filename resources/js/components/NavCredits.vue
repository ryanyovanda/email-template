<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Coins } from '@lucide/vue';
import { computed } from 'vue';
import {
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';

const page = usePage();
const { state } = useSidebar();

const credits = computed(() => (page.props.credits as number | null) ?? null);

const collapsed = computed(() => state.value === 'collapsed');

const formatted = computed(() =>
    credits.value === null ? '' : credits.value.toLocaleString(),
);
</script>

<template>
    <SidebarMenu v-if="credits !== null">
        <SidebarMenuItem>
            <SidebarMenuButton
                as-child
                :tooltip="`${formatted} credits`"
                class="h-auto"
            >
                <Link :href="dashboard()">
                    <Coins />
                    <span
                        v-if="!collapsed"
                        class="flex flex-1 items-baseline justify-between gap-2"
                    >
                        <span class="text-xs text-muted-foreground"
                            >Credits</span
                        >
                        <span class="text-sm font-semibold tabular-nums">{{
                            formatted
                        }}</span>
                    </span>
                </Link>
            </SidebarMenuButton>
        </SidebarMenuItem>
    </SidebarMenu>
</template>
