<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    Activity,
    CreditCard,
    Puzzle,
    FileText,
    LayoutGrid,
    LayoutTemplate,
    Settings,
    Shield,
    UserCircle,
    Users,
} from '@lucide/vue';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavCredits from '@/components/NavCredits.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { dashboard as adminDashboard } from '@/routes/admin';
import { index as aiUsageIndex } from '@/routes/admin/ai-usage';
import { index as adminSettingsIndex } from '@/routes/admin/settings';
import { index as adminTemplatesIndex } from '@/routes/admin/templates';
import { index as adminUsersIndex } from '@/routes/admin/users';
import { edit as applicantProfileEdit } from '@/routes/applicant-profile';
import { index as applicationsIndex } from '@/routes/applications';
import { index as creditsIndex } from '@/routes/credits';
import { index as templatesIndex } from '@/routes/templates';
import type { NavItem } from '@/types';

const page = usePage();

const isAdmin = computed(() => page.props.isAdmin === true);

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Templates',
        href: templatesIndex(),
        icon: LayoutTemplate,
    },
    {
        title: 'My applications',
        href: applicationsIndex(),
        icon: FileText,
    },
    {
        title: 'Buy credits',
        href: creditsIndex(),
        icon: CreditCard,
    },
    {
        title: 'My profile & CV',
        href: applicantProfileEdit(),
        icon: UserCircle,
    },
];

const adminNavItems: NavItem[] = [
    {
        title: 'Overview',
        href: adminDashboard(),
        icon: Shield,
    },
    {
        title: 'Users',
        href: adminUsersIndex(),
        icon: Users,
    },
    {
        title: 'Templates',
        href: adminTemplatesIndex(),
        icon: LayoutTemplate,
    },
    {
        title: 'AI usage',
        href: aiUsageIndex(),
        icon: Activity,
    },
    {
        title: 'Settings',
        href: adminSettingsIndex(),
        icon: Settings,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Gmail extension',
        href: 'https://chromewebstore.google.com/detail/insert-and-send-html-with/bcflbfdlpegakpncdgmejelcolhmfkjh',
        icon: Puzzle,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
            <NavMain v-if="isAdmin" :items="adminNavItems" label="Admin" />
        </SidebarContent>

        <SidebarFooter>
            <NavCredits />
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
