import { Link, usePage } from '@inertiajs/react';
import {
    Boxes,
    ClipboardList,
    LayoutGrid,
    Package,
    Settings,
    SlidersHorizontal,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
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
import { index as ordersIndex } from '@/routes/orders';
import { index as productManagementIndex } from '@/routes/product-management';
import { index as productsIndex } from '@/routes/products';
import { edit as profileEdit } from '@/routes/profile';
import { index as reportsIndex } from '@/routes/reports';
import type { NavItem } from '@/types';

export function AppSidebar() {
    const { auth } = usePage().props;
    const isAdmin = Boolean(auth.user?.is_admin);
    const mainNavItems: NavItem[] = [
        {
            title: 'Dashboard',
            href: dashboard(),
            icon: LayoutGrid,
        },
        {
            title: isAdmin ? 'Orders' : 'My Orders',
            href: ordersIndex(),
            icon: ClipboardList,
        },
        ...(isAdmin
            ? [
                  {
                      title: 'Inventory',
                      href: productsIndex(),
                      icon: Boxes,
                  },
                  {
                      title: 'Reports',
                      href: reportsIndex(),
                      icon: Package,
                  },
                  {
                      title: 'Product Management',
                      href: productManagementIndex(),
                      icon: SlidersHorizontal,
                  },
              ]
            : []),
        {
            title: 'Settings',
            href: profileEdit(),
            icon: Settings,
        },
    ];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
