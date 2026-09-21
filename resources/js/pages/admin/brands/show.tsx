import { Head, Link } from '@inertiajs/react';
import { PageHeader } from '@/components/admin/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import adminRoutes from '@/routes/admin';
import type { BreadcrumbItem } from '@/types';
import type { AdminBrand } from '@/types/admin';

export default function BrandShow({ brand }: { brand: AdminBrand }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Admin', href: adminRoutes.dashboard().url },
        { title: 'Brands', href: adminRoutes.brands.index().url },
        { title: brand.name, href: adminRoutes.brands.show(brand.id).url },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${brand.name} — Admin`} />
            <div className="flex flex-col gap-6 p-6">
                <PageHeader title={brand.name} />

                <div className="grid gap-6 lg:grid-cols-3">
                    <div className="lg:col-span-2 space-y-6">
                        <Card>
                            <CardHeader><CardTitle>Brand Details</CardTitle></CardHeader>
                            <CardContent className="space-y-3">
                                <Row label="Name" value={brand.name} />
                                <Row label="Slug" value={brand.slug} />
                                <Row label="ERP Brand ID" value={brand.erp_brand_id ?? '—'} />
                                <Row label="Description" value={brand.description ?? '—'} />
                                <div className="flex items-center gap-2">
                                    <span className="text-sm text-muted-foreground w-28">Status</span>
                                    {brand.is_active
                                        ? <Badge className="bg-green-100 text-green-800 border-green-200 dark:bg-green-900/20 dark:text-green-400">Active</Badge>
                                        : <Badge variant="secondary">Inactive</Badge>
                                    }
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader><CardTitle>Stats</CardTitle></CardHeader>
                            <CardContent>
                                <div className="flex items-center justify-between">
                                    <span className="text-sm text-muted-foreground">Products</span>
                                    <div className="flex items-center gap-3">
                                        <span className="text-2xl font-bold">{brand.products_count ?? 0}</span>
                                        <Button asChild variant="outline" size="sm">
                                            <Link href={`${adminRoutes.products.index().url}?brand_id=${brand.id}`}>View Products</Link>
                                        </Button>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Media */}
                    <Card>
                        <CardHeader><CardTitle>Images ({brand.media?.length ?? 0})</CardTitle></CardHeader>
                        <CardContent className="space-y-3">
                            {!brand.media?.length && <p className="text-sm text-muted-foreground">No images</p>}
                            {brand.media?.map((m) => (
                                <div key={m.id} className="relative">
                                    <img src={m.public_url ?? m.url ?? ''} alt={m.alt ?? brand.name} className="w-full rounded-lg object-contain max-h-48 bg-muted" />
                                    {m.is_primary && <Badge className="absolute top-2 left-2 text-xs">Primary</Badge>}
                                </div>
                            ))}
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
