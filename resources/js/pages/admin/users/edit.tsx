import { Head, useForm } from '@inertiajs/react';
import { PageHeader } from '@/components/admin/page-header';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import adminRoutes from '@/routes/admin';
import type { BreadcrumbItem } from '@/types';
import type { AdminUser } from '@/types/admin';

export default function UserEdit({ user }: { user: AdminUser }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Admin', href: adminRoutes.dashboard().url },
        { title: 'Users', href: adminRoutes.users.index().url },
        { title: user.name, href: adminRoutes.users.show(user.id).url },
        { title: 'Edit', href: adminRoutes.users.edit(user.id).url },
    ];

    const { data, setData, patch, processing, errors } = useForm({
        name: user.name,
        email: user.email,
        phone: user.phone ?? '',
        approval_status: user.approval_status,
        is_admin: user.is_admin,
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        patch(adminRoutes.users.update(user.id).url);
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit ${user.name} — Admin`} />
            <div className="flex flex-col gap-6 p-6 max-w-xl">
                <PageHeader title={`Edit: ${user.name}`} />
                <form onSubmit={submit} className="space-y-5">
                    <div className="grid gap-2">
                        <Label htmlFor="name">Full Name</Label>
                        <Input id="name" value={data.name} onChange={(e) => setData('name', e.target.value)} required />
                        <InputError message={errors.name} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="email">Email</Label>
                        <Input id="email" type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} required />
                        <InputError message={errors.email} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="phone">Phone</Label>
                        <Input id="phone" value={data.phone} onChange={(e) => setData('phone', e.target.value)} />
                        <InputError message={errors.phone} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="approval_status">Approval Status</Label>
                        <Select value={data.approval_status} onValueChange={(v) => setData('approval_status', v as 'pending' | 'approved' | 'rejected')}>
                            <SelectTrigger id="approval_status">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="pending">Pending</SelectItem>
                                <SelectItem value="approved">Approved</SelectItem>
                                <SelectItem value="rejected">Rejected</SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError message={errors.approval_status} />
                    </div>
                    <div className="flex items-center gap-2">
                        <Checkbox
                            id="is_admin"
                            checked={data.is_admin}
                            onCheckedChange={(checked) => setData('is_admin', !!checked)}
                        />
                        <Label htmlFor="is_admin">Admin privileges</Label>
                    </div>
                    <div className="flex gap-3">
                        <Button type="submit" disabled={processing}>Save Changes</Button>
                        <Button type="button" variant="outline" onClick={() => history.back()}>Cancel</Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
