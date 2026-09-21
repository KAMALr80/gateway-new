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
import type { AdminOrder, Paginated } from '@/types/admin';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: adminRoutes.dashboard().url },
    { title: 'Orders', href: adminRoutes.orders.index().url },
];

interface Filters extends Record<string, string | number | undefined> {
    search?: string;
    status?: string;
    payment_status?: string;
    sync_status?: string;
    date_from?: string;
    date_to?: string;
    per_page?: string;
}

function statusBadge(status: string) {
    const map: Record<string, string> = {
        pending: 'bg-yellow-100 text-yellow-800 border-yellow-200',
        processing: 'bg-blue-100 text-blue-800 border-blue-200',
        shipped: 'bg-indigo-100 text-indigo-800 border-indigo-200',
        delivered: 'bg-green-100 text-green-800 border-green-200',
        cancelled: 'bg-red-100 text-red-800 border-red-200',
        completed: 'bg-green-100 text-green-800 border-green-200',
    };

    return <Badge className={map[status] ?? ''}>{status}</Badge>;
}

function paymentBadge(status: string) {
    if (status === 'paid') {
return <Badge className="bg-green-100 text-green-800 border-green-200 dark:bg-green-900/20 dark:text-green-400">Paid</Badge>;
}

    return <Badge variant="secondary">{status}</Badge>;
}

function syncBadge(status: string) {
    const map: Record<string, string> = {
        synced: 'bg-green-100 text-green-800 border-green-200',
        pending: 'bg-yellow-100 text-yellow-800 border-yellow-200',
        syncing: 'bg-blue-100 text-blue-800 border-blue-200',
        failed: 'bg-red-100 text-red-800 border-red-200',
    };

    return <Badge className={`text-xs ${map[status] ?? ''}`}>{status}</Badge>;
}

export default function OrdersIndex({ orders, filters }: { orders: Paginated<AdminOrder>; filters: Filters }) {
    function applyFilter(key: string, value: string) {
        router.get(adminRoutes.orders.index().url, { ...filters, [key]: value, page: 1 }, { preserveState: true, preserveScroll: true });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Orders — Admin" />
            <div className="flex flex-col gap-6 p-6">
                <PageHeader title="Orders" description={`${orders.meta.total} total`} />

                <div className="flex flex-wrap gap-3">
                    <Input
                        placeholder="Invoice, name, email…"
                        className="h-9 w-64"
                        defaultValue={filters.search ?? ''}
                        onChange={(e) => applyFilter('search', e.target.value)}
                    />
                    <Select value={filters.status ?? ''} onValueChange={(v) => applyFilter('status', v)}>
                        <SelectTrigger className="h-9 w-36">
                            <SelectValue placeholder="Order status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="">All statuses</SelectItem>
                            <SelectItem value="pending">Pending</SelectItem>
                            <SelectItem value="processing">Processing</SelectItem>
                            <SelectItem value="shipped">Shipped</SelectItem>
                            <SelectItem value="delivered">Delivered</SelectItem>
                            <SelectItem value="completed">Completed</SelectItem>
                            <SelectItem value="cancelled">Cancelled</SelectItem>
                        </SelectContent>
                    </Select>
                    <Select value={filters.payment_status ?? ''} onValueChange={(v) => applyFilter('payment_status', v)}>
                        <SelectTrigger className="h-9 w-40">
                            <SelectValue placeholder="Payment" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="">All payments</SelectItem>
                            <SelectItem value="due">Due</SelectItem>
                            <SelectItem value="paid">Paid</SelectItem>
                        </SelectContent>
                    </Select>
                    <Select value={filters.sync_status ?? ''} onValueChange={(v) => applyFilter('sync_status', v)}>
                        <SelectTrigger className="h-9 w-36">
                            <SelectValue placeholder="Sync status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="">All sync</SelectItem>
                            <SelectItem value="pending">Pending</SelectItem>
                            <SelectItem value="syncing">Syncing</SelectItem>
                            <SelectItem value="synced">Synced</SelectItem>
                            <SelectItem value="failed">Failed</SelectItem>
                        </SelectContent>
                    </Select>
                    <Input
                        type="date"
                        className="h-9 w-40"
                        value={filters.date_from ?? ''}
                        onChange={(e) => applyFilter('date_from', e.target.value)}
                        title="From date"
                    />
                    <Input
                        type="date"
                        className="h-9 w-40"
                        value={filters.date_to ?? ''}
                        onChange={(e) => applyFilter('date_to', e.target.value)}
                        title="To date"
                    />
                </div>

                <div className="rounded-lg border bg-card">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Invoice</TableHead>
                                <TableHead>Customer</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Payment</TableHead>
                                <TableHead>Sync</TableHead>
                                <TableHead className="text-right">Total</TableHead>
                                <TableHead className="text-right">Items</TableHead>
                                <TableHead>Date</TableHead>
                                <TableHead />
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {orders.data.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={9} className="text-center text-muted-foreground py-8">No orders found</TableCell>
                                </TableRow>
                            )}
                            {orders.data.map((order) => (
                                <TableRow key={order.id}>
                                    <TableCell className="font-mono text-sm">{order.invoice_no}</TableCell>
                                    <TableCell>
                                        {order.user ? (
                                            <Link href={adminRoutes.users.show(order.user.id).url} className="text-sm hover:underline">
                                                {order.billing_first_name} {order.billing_last_name}
                                            </Link>
                                        ) : (
                                            <span className="text-sm">{order.billing_first_name} {order.billing_last_name}</span>
                                        )}
                                    </TableCell>
                                    <TableCell>{statusBadge(order.status)}</TableCell>
                                    <TableCell>{paymentBadge(order.payment_status)}</TableCell>
                                    <TableCell>{syncBadge(order.sync_status)}</TableCell>
                                    <TableCell className="text-right text-sm font-medium">
                                        {order.currency} {parseFloat(order.total).toFixed(2)}
                                    </TableCell>
                                    <TableCell className="text-right text-muted-foreground">{order.items_count ?? 0}</TableCell>
                                    <TableCell className="text-sm text-muted-foreground">{new Date(order.created_at).toLocaleDateString()}</TableCell>
                                    <TableCell>
                                        <Button asChild variant="ghost" size="sm">
                                            <Link href={adminRoutes.orders.show(order.id).url}>View</Link>
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>
                <DataTablePagination meta={orders.meta} filters={filters} routeUrl={adminRoutes.orders.index().url} />
            </div>
        </AppLayout>
    );
}
