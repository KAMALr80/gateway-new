import { Link, usePage } from '@inertiajs/react';
import {
    Layers,
    LayoutGrid,
    LayoutTemplate,
    Package,
    ShoppingCart,
    Tag,
    Users,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/hooks/use-current-url';
import adminRoutes from '@/routes/admin';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: adminRoutes.dashboard(),
        icon: LayoutGrid,
    },
];

const adminNavItems: NavItem[] = [
    { title: 'Users', href: adminRoutes.users.index(), icon: Users },
    { title: 'Orders', href: adminRoutes.orders.index(), icon: ShoppingCart },
    { title: 'Products', href: adminRoutes.products.index(), icon: Package },
    { title: 'Categories', href: adminRoutes.categories.index(), icon: Layers },
    { title: 'Brands', href: adminRoutes.brands.index(), icon: Tag },
    { title: 'Homepage', href: adminRoutes.homepage.index(), icon: LayoutTemplate },
];

function AdminNav({ items }: { items: NavItem[] }) {
    const { isCurrentUrl } = useCurrentUrl();

    return (
        <SidebarGroup className="px-2 py-0">
            <SidebarGroupLabel>Admin</SidebarGroupLabel>
            <SidebarMenu>
                {items.map((item) => (
                    <SidebarMenuItem key={item.title}>
                        <SidebarMenuButton asChild isActive={isCurrentUrl(item.href)} tooltip={{ children: item.title }}>
                            <Link href={item.href} prefetch>
                                {item.icon && <item.icon />}
                                <span>{item.title}</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                ))}
            </SidebarMenu>
        </SidebarGroup>
    );
}

export function AppSidebar() {
    const { auth } = usePage().props as { auth: { user: { is_admin?: boolean } | null } };

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={adminRoutes.dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
                {auth.user?.is_admin && <AdminNav items={adminNavItems} />}
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
