import { Head, Link } from '@inertiajs/react';
import { PageHeader } from '@/components/admin/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import adminRoutes from '@/routes/admin';
import type { BreadcrumbItem } from '@/types';
import type { AdminCategory } from '@/types/admin';

export default function CategoryShow({ category }: { category: AdminCategory }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Admin', href: adminRoutes.dashboard().url },
        { title: 'Categories', href: adminRoutes.categories.index().url },
        { title: category.name, href: adminRoutes.categories.show(category.id).url },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${category.name} — Admin`} />
            <div className="flex flex-col gap-6 p-6">
                <PageHeader title={category.name} />

                <div className="grid gap-6 lg:grid-cols-3">
                    <div className="lg:col-span-2 space-y-6">
                        <Card>
                            <CardHeader><CardTitle>Category Details</CardTitle></CardHeader>
                            <CardContent className="space-y-3">
                                <Row label="Name" value={category.name} />
                                <Row label="Slug" value={category.slug} />
                                <Row label="ERP Category ID" value={category.erp_category_id ?? '—'} />
                                <Row label="Description" value={category.description ?? '—'} />
                                <Row label="Sort Order" value={String(category.sort_order)} />
                                <div className="flex items-center gap-2">
                                    <span className="text-sm text-muted-foreground w-28">Status</span>
                                    {category.is_active
                                        ? <Badge className="bg-green-100 text-green-800 border-green-200 dark:bg-green-900/20 dark:text-green-400">Active</Badge>
                                        : <Badge variant="secondary">Inactive</Badge>
                                    }
                                </div>
                                {category.parent && (
                                    <div className="flex items-center gap-2">
                                        <span className="text-sm text-muted-foreground w-28">Parent</span>
                                        <Link href={adminRoutes.categories.show(category.parent.id).url} className="text-sm hover:underline">
                                            {category.parent.name}
                                        </Link>
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between">
                                <CardTitle>Stats</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                <div className="flex items-center justify-between">
                                    <span className="text-sm text-muted-foreground">Products</span>
                                    <div className="flex items-center gap-3">
                                        <span className="text-2xl font-bold">{category.products_count ?? 0}</span>
                                        <Button asChild variant="outline" size="sm">
                                            <Link href={`${adminRoutes.products.index().url}?category_id=${category.id}`}>View Products</Link>
                                        </Button>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Sub-categories */}
                        {(category.children?.length ?? 0) > 0 && (
                            <Card>
                                <CardHeader><CardTitle>Sub-categories ({category.children!.length})</CardTitle></CardHeader>
                                <CardContent>
                                    <div className="space-y-2">
                                        {category.children!.map((child) => (
                                            <div key={child.id} className="flex items-center justify-between py-2 border-b last:border-0">
                                                <div>
                                                    <p className="text-sm font-medium">{child.name}</p>
                                                    <p className="text-xs text-muted-foreground">{child.slug}</p>
                                                </div>
                                                <div className="flex items-center gap-2">
                                                    {child.is_active
                                                        ? <Badge className="bg-green-100 text-green-800 border-green-200 text-xs dark:bg-green-900/20 dark:text-green-400">Active</Badge>
                                                        : <Badge variant="secondary" className="text-xs">Inactive</Badge>
                                                    }
                                                    <Button asChild variant="ghost" size="sm">
                                                        <Link href={adminRoutes.categories.show(child.id).url}>View</Link>
                                                    </Button>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </CardContent>
                            </Card>
                        )}
                    </div>

                    {/* Image */}
                    <Card>
                        <CardHeader><CardTitle>Image</CardTitle></CardHeader>
                        <CardContent>
                            {category.primary_image?.public_url ? (
                                <img
                                    src={category.primary_image.public_url}
                                    alt={category.primary_image.alt ?? category.name}
                                    className="w-full rounded-lg object-contain bg-muted max-h-64"
                                />
                            ) : (
                                <div className="h-32 rounded-lg bg-muted flex items-center justify-center text-sm text-muted-foreground">No image</div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}

function Row({ label, value }: { label: string; value: string }) {
    return (
        <div className="flex items-start gap-2">
            <span className="text-sm text-muted-foreground w-28 shrink-0">{label}</span>
            <span className="text-sm">{value}</span>
        </div>
    );
}
