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
import type { AdminCategory, Paginated } from '@/types/admin';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: adminRoutes.dashboard().url },
    { title: 'Categories', href: adminRoutes.categories.index().url },
];

interface Filters extends Record<string, string | number | undefined> { search?: string; parent_id?: string; is_active?: string; per_page?: string }

export default function CategoriesIndex({
    categories,
    parentCategories,
    filters,
}: {
    categories: Paginated<AdminCategory>;
    parentCategories: { id: number; name: string }[];
    filters: Filters;
}) {
    function applyFilter(key: string, value: string) {
        router.get(adminRoutes.categories.index().url, { ...filters, [key]: value, page: 1 }, { preserveState: true, preserveScroll: true });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Categories — Admin" />
            <div className="flex flex-col gap-6 p-6">
                <PageHeader title="Categories" description={`${categories.meta.total} total`} />

                <div className="flex flex-wrap gap-3">
                    <Input
                        placeholder="Search name or slug…"
                        className="h-9 w-64"
                        defaultValue={filters.search ?? ''}
                        onChange={(e) => applyFilter('search', e.target.value)}
                    />
                    <Select value={filters.parent_id ?? ''} onValueChange={(v) => applyFilter('parent_id', v)}>
                        <SelectTrigger className="h-9 w-44">
                            <SelectValue placeholder="Parent category" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="">All categories</SelectItem>
                            <SelectItem value="root">Top-level only</SelectItem>
                            {parentCategories.map((p) => (
                                <SelectItem key={p.id} value={String(p.id)}>{p.name}</SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
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
                                <TableHead>Name</TableHead>
                                <TableHead>Slug</TableHead>
                                <TableHead>Parent</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Sort</TableHead>
                                <TableHead className="text-right">Products</TableHead>
                                <TableHead />
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {categories.data.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={7} className="text-center text-muted-foreground py-8">No categories found</TableCell>
                                </TableRow>
                            )}
                            {categories.data.map((cat) => (
                                <TableRow key={cat.id}>
                                    <TableCell className="font-medium">{cat.name}</TableCell>
                                    <TableCell className="text-muted-foreground text-sm font-mono">{cat.slug}</TableCell>
                                    <TableCell className="text-sm text-muted-foreground">
                                        {cat.parent ? (
                                            <Link href={adminRoutes.categories.show(cat.parent.id).url} className="hover:underline">
                                                {cat.parent.name}
                                            </Link>
                                        ) : '—'}
                                    </TableCell>
                                    <TableCell>
                                        {cat.is_active
                                            ? <Badge className="bg-green-100 text-green-800 border-green-200 dark:bg-green-900/20 dark:text-green-400">Active</Badge>
                                            : <Badge variant="secondary">Inactive</Badge>
                                        }
                                    </TableCell>
                                    <TableCell className="text-muted-foreground">{cat.sort_order}</TableCell>
                                    <TableCell className="text-right">{cat.products_count ?? 0}</TableCell>
                                    <TableCell>
                                        <Button asChild variant="ghost" size="sm">
                                            <Link href={adminRoutes.categories.show(cat.id).url}>View</Link>
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>
                <DataTablePagination meta={categories.meta} filters={filters} routeUrl={adminRoutes.categories.index().url} />
            </div>
        </AppLayout>
    );
}
