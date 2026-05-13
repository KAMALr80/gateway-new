import { Head, Link, router } from '@inertiajs/react';
import { Plus, Pencil, Trash2 } from 'lucide-react';
import { PageHeader } from '@/components/admin/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import adminRoutes from '@/routes/admin';
import type { BreadcrumbItem } from '@/types';
import type { HomepageConfig } from '@/types/admin';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: adminRoutes.dashboard().url },
    { title: 'Homepage Configs', href: adminRoutes.homepage.index().url },
];

export default function HomepageIndex({ configs }: { configs: HomepageConfig[] }) {
    function deleteConfig(config: HomepageConfig) {
        if (!confirm(`Delete config "${config.label}"? This will also remove all its sections.`)) return;
        router.delete(adminRoutes.homepage.destroy(config.id).url);
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Homepage Configs — Admin" />
            <div className="flex flex-col gap-6 p-6">
                <PageHeader
                    title="Homepage Configs"
                    description={`${configs.length} client${configs.length !== 1 ? 's' : ''} configured`}
                    action={
                        <Button asChild size="sm">
                            <Link href={adminRoutes.homepage.create().url}>
                                <Plus className="mr-1 h-4 w-4" /> Add Client
                            </Link>
                        </Button>
                    }
                />

                <div className="rounded-lg border bg-card">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Label</TableHead>
                                <TableHead>Client Key</TableHead>
                                <TableHead className="text-right">Sections</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Created</TableHead>
                                <TableHead />
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {configs.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={6} className="py-8 text-center text-muted-foreground">
                                        No homepage configs yet. Add your first client.
                                    </TableCell>
                                </TableRow>
                            )}
                            {configs.map((config) => (
                                <TableRow key={config.id}>
                                    <TableCell className="font-medium">{config.label}</TableCell>
                                    <TableCell>
                                        <code className="rounded bg-muted px-1.5 py-0.5 text-xs">{config.client_key}</code>
                                    </TableCell>
                                    <TableCell className="text-right">{config.sections_count ?? 0}</TableCell>
                                    <TableCell>
                                        {config.is_active ? (
                                            <Badge className="bg-green-100 text-green-800 border-green-200 dark:bg-green-900/20 dark:text-green-400">Active</Badge>
                                        ) : (
                                            <Badge variant="secondary">Inactive</Badge>
                                        )}
                                    </TableCell>
                                    <TableCell className="text-sm text-muted-foreground">
                                        {new Date(config.created_at).toLocaleDateString()}
                                    </TableCell>
                                    <TableCell>
                                        <div className="flex items-center justify-end gap-2">
                                            <Button asChild variant="ghost" size="sm">
                                                <Link href={adminRoutes.homepage.edit(config.id).url}>
                                                    <Pencil className="h-4 w-4" />
                                                    <span className="ml-1">Edit</span>
                                                </Link>
                                            </Button>
                                            <Button variant="ghost" size="sm" onClick={() => deleteConfig(config)}>
                                                <Trash2 className="h-4 w-4 text-destructive" />
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>
            </div>
        </AppLayout>
    );
}
