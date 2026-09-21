import { Head } from '@inertiajs/react';
import { Users, ShoppingCart, Package, Tag, Layers } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import adminRoutes from '@/routes/admin';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: adminRoutes.dashboard().url },
];

interface Stats {
    users: number;
    orders: number;
    products: number;
    brands: number;
    categories: number;
}

export default function AdminDashboard({ stats }: { stats: Stats }) {
    const cards = [
        { title: 'Users', value: stats.users, icon: Users, href: adminRoutes.users.index().url },
        { title: 'Orders', value: stats.orders, icon: ShoppingCart, href: adminRoutes.orders.index().url },
        { title: 'Products', value: stats.products, icon: Package, href: adminRoutes.products.index().url },
        { title: 'Categories', value: stats.categories, icon: Layers, href: adminRoutes.categories.index().url },
        { title: 'Brands', value: stats.brands, icon: Tag, href: adminRoutes.brands.index().url },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Admin Dashboard" />
            <div className="flex flex-col gap-6 p-6">
                <h1 className="text-2xl font-semibold tracking-tight">Admin Dashboard</h1>
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
                    {cards.map(({ title, value, icon: Icon, href }) => (
                        <a key={title} href={href}>
                            <Card className="hover:bg-muted/50 transition-colors cursor-pointer">
                                <CardHeader className="flex flex-row items-center justify-between pb-2">
                                    <CardTitle className="text-sm font-medium text-muted-foreground">{title}</CardTitle>
                                    <Icon className="h-4 w-4 text-muted-foreground" />
                                </CardHeader>
                                <CardContent>
                                    <p className="text-2xl font-bold">{value.toLocaleString()}</p>
                                </CardContent>
                            </Card>
                        </a>
                    ))}
                </div>
            </div>
        </AppLayout>
    );
}
