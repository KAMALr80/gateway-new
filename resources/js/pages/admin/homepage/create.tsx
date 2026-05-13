import { Head, useForm } from '@inertiajs/react';
import { PageHeader } from '@/components/admin/page-header';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import AppLayout from '@/layouts/app-layout';
import adminRoutes from '@/routes/admin';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: adminRoutes.dashboard().url },
    { title: 'Homepage Configs', href: adminRoutes.homepage.index().url },
    { title: 'New Client', href: adminRoutes.homepage.create().url },
];

export default function HomepageCreate() {
    const { data, setData, post, processing, errors } = useForm({
        label: '',
        client_key: '',
        is_active: true,
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        post(adminRoutes.homepage.store().url);
    }

    function deriveKey(label: string) {
        return label.toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9\-_]/g, '');
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="New Homepage Config — Admin" />
            <div className="flex flex-col gap-6 p-6 max-w-xl">
                <PageHeader title="New Homepage Config" />
                <form onSubmit={submit} className="space-y-5">
                    <div className="grid gap-2">
                        <Label htmlFor="label">Label</Label>
                        <Input
                            id="label"
                            placeholder="e.g. Website"
                            value={data.label}
                            onChange={(e) => {
                                setData('label', e.target.value);
                                if (!data.client_key || data.client_key === deriveKey(data.label)) {
                                    setData('client_key', deriveKey(e.target.value));
                                }
                            }}
                            required
                        />
                        <InputError message={errors.label} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="client_key">Client Key</Label>
                        <Input
                            id="client_key"
                            placeholder="e.g. website or mobile-app"
                            value={data.client_key}
                            onChange={(e) => setData('client_key', e.target.value)}
                            required
                        />
                        <p className="text-xs text-muted-foreground">Lowercase letters, numbers, hyphens and underscores only. Used in API calls: <code className="rounded bg-muted px-1">GET /api/homepage?client=website</code></p>
                        <InputError message={errors.client_key} />
                    </div>

                    <div className="flex items-center gap-3">
                        <Switch
                            id="is_active"
                            checked={data.is_active}
                            onCheckedChange={(v) => setData('is_active', v)}
                        />
                        <Label htmlFor="is_active">Active</Label>
                    </div>

                    <div className="flex gap-3">
                        <Button type="submit" disabled={processing}>Create Config</Button>
                        <Button type="button" variant="outline" onClick={() => history.back()}>Cancel</Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
