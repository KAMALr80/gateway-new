import { Head, Link, router } from '@inertiajs/react';
import { DataTablePagination } from '@/components/admin/data-table';
import { PageHeader } from '@/components/admin/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import adminRoutes from '@/routes/admin';
import type { BreadcrumbItem } from '@/types';
import type { AdminBrand, Paginated } from '@/types/admin';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: adminRoutes.dashboard().url },
    { title: 'Brands', href: adminRoutes.brands.index().url },
];

interface Filters extends Record<string, string | number | undefined> { search?: string; is_active?: string; per_page?: string }

export default function BrandsIndex({ brands, filters }: { brands: Paginated<AdminBrand>; filters: Filters }) {
    function applyFilter(key: string, value: string) {
        router.get(adminRoutes.brands.index().url, { ...filters, [key]: value, page: 1 }, { preserveState: true, preserveScroll: true });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Brands — Admin" />
            <div className="flex flex-col gap-6 p-6">
                <PageHeader title="Brands" description={`${brands.meta.total} total`} />

                <div className="flex flex-wrap gap-3">
                    <Input
                        placeholder="Search name or slug…"
                        className="h-9 w-64"
                        defaultValue={filters.search ?? ''}
                        onChange={(e) => applyFilter('search', e.target.value)}
                    />
                    <Select value={filters.is_active ?? ''} onValueChange={(v) => applyFilter('is_active', v)}>
                        <SelectTrigger className="h-9 w-36">
                            <SelectValue placeholder="Active status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="">All</SelectItem>
                            <SelectItem value="true">Active</SelectItem>
                            <SelectItem value="false">Inactive</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div className="rounded-lg border bg-card">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Logo</TableHead>
                                <TableHead>Name</TableHead>
                                <TableHead>Slug</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="text-right">Products</TableHead>
                                <TableHead />
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {brands.data.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={6} className="text-center text-muted-foreground py-8">No brands found</TableCell>
                                </TableRow>
                            )}
                            {brands.data.map((brand) => (
                                <TableRow key={brand.id}>
                                    <TableCell>
                                        {brand.primary_image?.public_url ? (
                                            <img src={brand.primary_image.public_url} alt={brand.name} className="h-8 w-8 rounded object-contain" />
                                        ) : (
                                            <div className="h-8 w-8 rounded bg-muted flex items-center justify-center text-xs text-muted-foreground">—</div>
                                        )}
                                    </TableCell>
                                    <TableCell className="font-medium">{brand.name}</TableCell>
                                    <TableCell className="text-muted-foreground text-sm font-mono">{brand.slug}</TableCell>
                                    <TableCell>
                                        {brand.is_active
                                            ? <Badge className="bg-green-100 text-green-800 border-green-200 dark:bg-green-900/20 dark:text-green-400">Active</Badge>
                                            : <Badge variant="secondary">Inactive</Badge>
                                        }
                                    </TableCell>
                                    <TableCell className="text-right">{brand.products_count ?? 0}</TableCell>
                                    <TableCell>
                                        <Button asChild variant="ghost" size="sm">
                                            <Link href={adminRoutes.brands.show(brand.id).url}>View</Link>
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>
                <DataTablePagination meta={brands.meta} filters={filters} routeUrl={adminRoutes.brands.index().url} />
            </div>
        </AppLayout>
    );
}
