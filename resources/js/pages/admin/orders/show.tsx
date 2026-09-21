import { Head, Link } from '@inertiajs/react';
import { PageHeader } from '@/components/admin/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import adminRoutes from '@/routes/admin';
import type { BreadcrumbItem } from '@/types';
import type { AdminOrder } from '@/types/admin';

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

    return <Badge className={map[status] ?? ''}>{status}</Badge>;
}

export default function OrderShow({ order }: { order: AdminOrder }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Admin', href: adminRoutes.dashboard().url },
        { title: 'Orders', href: adminRoutes.orders.index().url },
        { title: order.invoice_no, href: adminRoutes.orders.show(order.id).url },
    ];

    const subtotal = parseFloat(order.line_total);
    const discount = parseFloat(order.discount_total);
    const shipping = parseFloat(order.shipping_total);
    const tax = parseFloat(order.total_tax);
    const total = parseFloat(order.total);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Order ${order.invoice_no} — Admin`} />
            <div className="flex flex-col gap-6 p-6">
                <PageHeader
                    title={`Order ${order.invoice_no}`}
                    action={
                        <div className="flex items-center gap-2">
                            {statusBadge(order.status)}
                            {paymentBadge(order.payment_status)}
                            {syncBadge(order.sync_status)}
                        </div>
                    }
                />

                <div className="grid gap-6 lg:grid-cols-3">
                    <div className="lg:col-span-2 space-y-6">
                        {/* Customer */}
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between">
                                <CardTitle>Customer</CardTitle>
                                {order.user && (
                                    <Button asChild variant="outline" size="sm">
                                        <Link href={adminRoutes.users.show(order.user.id).url}>View Profile</Link>
                                    </Button>
                                )}
                            </CardHeader>
                            <CardContent className="space-y-2">
                                <Row label="Name" value={`${order.billing_first_name} ${order.billing_last_name}`} />
                                {order.billing_email && <Row label="Email" value={order.billing_email} />}
                                {order.billing_phone && <Row label="Phone" value={order.billing_phone} />}
                                {order.user?.email && order.user.email !== order.billing_email && (
                                    <Row label="Account Email" value={order.user.email} />
                                )}
                                {order.customer_note && (
                                    <div>
                                        <p className="text-sm text-muted-foreground">Note</p>
                                        <p className="text-sm mt-0.5 p-2 bg-muted rounded">{order.customer_note}</p>
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {/* Addresses */}
                        <div className="grid gap-6 sm:grid-cols-2">
                            <Card>
                                <CardHeader><CardTitle>Billing Address</CardTitle></CardHeader>
                                <CardContent className="text-sm space-y-0.5">
                                    <p className="font-medium">{order.billing_first_name} {order.billing_last_name}</p>
                                    {order.billing_company && <p>{order.billing_company}</p>}
                                    <p>{order.billing_address_1}</p>
                                    {order.billing_address_2 && <p>{order.billing_address_2}</p>}
                                    <p>{order.billing_city}{order.billing_state ? `, ${order.billing_state}` : ''} {order.billing_postcode}</p>
                                    <p>{order.billing_country}</p>
                                    {order.billing_phone && <p className="text-muted-foreground">{order.billing_phone}</p>}
                                    {order.billing_email && <p className="text-muted-foreground">{order.billing_email}</p>}
                                </CardContent>
                            </Card>
                            <Card>
                                <CardHeader><CardTitle>Shipping Address</CardTitle></CardHeader>
                                <CardContent className="text-sm space-y-0.5">
                                    <p className="font-medium">{order.shipping_first_name} {order.shipping_last_name}</p>
                                    {order.shipping_company && <p>{order.shipping_company}</p>}
                                    <p>{order.shipping_address_1}</p>
                                    {order.shipping_address_2 && <p>{order.shipping_address_2}</p>}
                                    <p>{order.shipping_city}{order.shipping_state ? `, ${order.shipping_state}` : ''} {order.shipping_postcode}</p>
                                    <p>{order.shipping_country}</p>
                                    {order.shipping_phone && <p className="text-muted-foreground">{order.shipping_phone}</p>}
                                </CardContent>
                            </Card>
                        </div>

                        {/* Order Items */}
                        <Card>
                            <CardHeader><CardTitle>Order Items</CardTitle></CardHeader>
                            <CardContent className="p-0">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Product</TableHead>
                                            <TableHead>SKU</TableHead>
                                            <TableHead className="text-right">Unit Price</TableHead>
                                            <TableHead className="text-right">Tax</TableHead>
                                            <TableHead className="text-right">Qty</TableHead>
                                            <TableHead className="text-right">Line Total</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {order.items?.map((item) => (
                                            <TableRow key={item.id}>
                                                <TableCell>
                                                    {item.product ? (
                                                        <Link href={adminRoutes.products.show(item.product.id).url} className="text-sm hover:underline font-medium">
                                                            {item.name}
                                                        </Link>
                                                    ) : (
                                                        <span className="text-sm font-medium">{item.name}</span>
                                                    )}
                                                </TableCell>
                                                <TableCell className="text-sm font-mono text-muted-foreground">{item.sku}</TableCell>
                                                <TableCell className="text-right text-sm">{order.currency} {parseFloat(item.unit_price).toFixed(2)}</TableCell>
                                                <TableCell className="text-right text-sm text-muted-foreground">{parseFloat(item.unit_tax).toFixed(2)}</TableCell>
                                                <TableCell className="text-right text-sm">{item.quantity}</TableCell>
                                                <TableCell className="text-right text-sm font-medium">
                                                    {order.currency} {parseFloat(item.total).toFixed(2)}
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>

                                {/* Totals */}
                                <div className="p-4 space-y-2 border-t">
                                    <TotalRow label="Subtotal" value={`${order.currency} ${subtotal.toFixed(2)}`} />
                                    {discount > 0 && <TotalRow label="Discount" value={`-${order.currency} ${discount.toFixed(2)}`} className="text-green-600" />}
                                    <TotalRow label="Shipping" value={`${order.currency} ${shipping.toFixed(2)}`} />
                                    <TotalRow label="Tax" value={`${order.currency} ${tax.toFixed(2)}`} />
                                    <Separator />
                                    <TotalRow label="Total" value={`${order.currency} ${total.toFixed(2)}`} bold />
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <div className="space-y-6">
                        {/* Order Info */}
                        <Card>
                            <CardHeader><CardTitle>Order Info</CardTitle></CardHeader>
                            <CardContent className="space-y-2">
                                <Row label="Invoice No" value={order.invoice_no} />
                                <Row label="Order Key" value={order.order_key} />
                                <Row label="Currency" value={order.currency} />
                                <Row label="Created Via" value={order.created_via ?? '—'} />
                                <Row label="Transaction Date" value={order.transaction_date ? new Date(order.transaction_date).toLocaleDateString() : '—'} />
                                <Row label="ERP Order ID" value={order.erp_order_id ?? '—'} />
                                <Row label="Created" value={new Date(order.created_at).toLocaleString()} />
                            </CardContent>
                        </Card>

                        {/* ERP Sync */}
                        <Card>
                            <CardHeader><CardTitle>ERP Sync</CardTitle></CardHeader>
                            <CardContent className="space-y-2">
                                <div className="flex items-center gap-2">
                                    <span className="text-sm text-muted-foreground w-28">Status</span>
                                    {syncBadge(order.sync_status)}
                                </div>
                                <Row label="Attempts" value={String(order.sync_attempts)} />
                                <Row label="Last Sync" value={order.synced_at ? new Date(order.synced_at).toLocaleString() : '—'} />
                                {order.sync_error && (
                                    <div>
                                        <p className="text-sm text-muted-foreground mb-1">Error</p>
                                        <p className="text-xs font-mono p-2 bg-red-50 dark:bg-red-950/20 text-red-700 dark:text-red-400 rounded border border-red-200 dark:border-red-900 break-all">
                                            {order.sync_error}
                                        </p>
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {/* Meta */}
                        {(order.customer_ip_address) && (
                            <Card>
                                <CardHeader><CardTitle>Technical</CardTitle></CardHeader>
                                <CardContent className="space-y-2">
                                    {order.customer_ip_address && <Row label="IP Address" value={order.customer_ip_address} />}
                                </CardContent>
                            </Card>
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}

function Row({ label, value }: { label: string; value: string }) {
    return (
        <div className="flex items-start gap-2">
            <span className="text-sm text-muted-foreground w-28 shrink-0">{label}</span>
            <span className="text-sm break-all">{value}</span>
        </div>
    );
}

function TotalRow({ label, value, bold, className }: { label: string; value: string; bold?: boolean; className?: string }) {
    return (
        <div className={`flex justify-between text-sm ${bold ? 'font-semibold text-base' : ''} ${className ?? ''}`}>
            <span className={bold ? '' : 'text-muted-foreground'}>{label}</span>
            <span>{value}</span>
        </div>
    );
}
