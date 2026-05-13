import { Head, router, useForm } from '@inertiajs/react';
import { ChevronDown, ChevronUp, Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { PageHeader } from '@/components/admin/page-header';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import adminRoutes from '@/routes/admin';
import type { BreadcrumbItem } from '@/types';
import type { AdminBrand, AdminCategory, HomepageConfig, HomepageSection, HomepageSectionSettings, HomepageSectionType } from '@/types/admin';

interface Props {
    config: HomepageConfig & { sections: HomepageSection[] };
    categories: Pick<AdminCategory, 'id' | 'name'>[];
    brands: Pick<AdminBrand, 'id' | 'name'>[];
}

const SECTION_LABELS: Record<HomepageSectionType, string> = {
    featured_category: 'Featured Category',
    banner: 'Banner',
    hero: 'Hero',
    product_carousel: 'Product Carousel',
    brand_showcase: 'Brand Showcase',
};

function sectionBadgeColor(type: HomepageSectionType) {
    const map: Record<HomepageSectionType, string> = {
        featured_category: 'bg-blue-100 text-blue-800 border-blue-200 dark:bg-blue-900/20 dark:text-blue-400',
        banner: 'bg-orange-100 text-orange-800 border-orange-200 dark:bg-orange-900/20 dark:text-orange-400',
        hero: 'bg-purple-100 text-purple-800 border-purple-200 dark:bg-purple-900/20 dark:text-purple-400',
        product_carousel: 'bg-teal-100 text-teal-800 border-teal-200 dark:bg-teal-900/20 dark:text-teal-400',
        brand_showcase: 'bg-yellow-100 text-yellow-800 border-yellow-200 dark:bg-yellow-900/20 dark:text-yellow-400',
    };
    return map[type] ?? '';
}

function settingsSummary(section: HomepageSection, categories: Props['categories'], brands: Props['brands']): string {
    const s = section.settings ?? {};
    switch (section.type) {
        case 'featured_category': {
            const cat = categories.find((c) => c.id === s.category_id);
            return cat ? `${cat.name} · limit ${s.product_limit ?? 8}` : 'No category set';
        }
        case 'banner':
            return s.image_url ? `Image: ${s.image_url.slice(0, 40)}…` : 'No image set';
        case 'hero':
            return [s.subtitle, s.cta_text].filter(Boolean).join(' · ') || 'No content set';
        case 'product_carousel':
            return s.filter ? `Filter: ${s.filter} · limit ${s.limit ?? 10}` : 'No filter set';
        case 'brand_showcase': {
            const count = s.brand_ids?.length ?? 0;
            return `${count} brand${count !== 1 ? 's' : ''} selected`;
        }
        default:
            return '';
    }
}

// ─── Section Form ──────────────────────────────────────────────────────────────

interface SectionFormData {
    type: HomepageSectionType;
    title: string;
    is_active: boolean;
    settings: HomepageSectionSettings;
}

function SectionFields({
    data,
    setData,
    errors,
    categories,
    brands,
}: {
    data: SectionFormData;
    setData: (key: keyof SectionFormData, value: unknown) => void;
    errors: Partial<Record<string, string>>;
    categories: Props['categories'];
    brands: Props['brands'];
}) {
    function setSetting(key: keyof HomepageSectionSettings, value: unknown) {
        setData('settings', { ...data.settings, [key]: value });
    }

    return (
        <div className="space-y-4">
            <div className="grid gap-2">
                <Label htmlFor="s-type">Section Type</Label>
                <Select value={data.type} onValueChange={(v) => setData('type', v as HomepageSectionType)}>
                    <SelectTrigger id="s-type">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        {(Object.entries(SECTION_LABELS) as [HomepageSectionType, string][]).map(([value, label]) => (
                            <SelectItem key={value} value={value}>{label}</SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>

            <div className="grid gap-2">
                <Label htmlFor="s-title">Title</Label>
                <Input id="s-title" value={data.title} onChange={(e) => setData('title', e.target.value)} required />
                <InputError message={errors.title} />
            </div>

            {data.type === 'featured_category' && (
                <>
                    <div className="grid gap-2">
                        <Label htmlFor="s-category">Category</Label>
                        <Select
                            value={String(data.settings.category_id ?? '')}
                            onValueChange={(v) => setSetting('category_id', parseInt(v))}
                        >
                            <SelectTrigger id="s-category">
                                <SelectValue placeholder="Select a category" />
                            </SelectTrigger>
                            <SelectContent>
                                {categories.map((c) => (
                                    <SelectItem key={c.id} value={String(c.id)}>{c.name}</SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors['settings.category_id']} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="s-limit">Product Limit</Label>
                        <Input
                            id="s-limit"
                            type="number"
                            min={1}
                            max={50}
                            value={data.settings.product_limit ?? 8}
                            onChange={(e) => setSetting('product_limit', parseInt(e.target.value))}
                        />
                    </div>
                </>
            )}

            {data.type === 'banner' && (
                <>
                    <div className="grid gap-2">
                        <Label htmlFor="s-image">Image URL</Label>
                        <Input id="s-image" value={data.settings.image_url ?? ''} onChange={(e) => setSetting('image_url', e.target.value)} required />
                        <InputError message={errors['settings.image_url']} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="s-link">Link URL (optional)</Label>
                        <Input id="s-link" value={data.settings.link_url ?? ''} onChange={(e) => setSetting('link_url', e.target.value)} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="s-subtitle">Subtitle (optional)</Label>
                        <Input id="s-subtitle" value={data.settings.subtitle ?? ''} onChange={(e) => setSetting('subtitle', e.target.value)} />
                    </div>
                </>
            )}

            {data.type === 'hero' && (
                <>
                    <div className="grid gap-2">
                        <Label htmlFor="s-subtitle">Subtitle</Label>
                        <Input id="s-subtitle" value={data.settings.subtitle ?? ''} onChange={(e) => setSetting('subtitle', e.target.value)} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="s-cta-text">CTA Button Text</Label>
                        <Input id="s-cta-text" value={data.settings.cta_text ?? ''} onChange={(e) => setSetting('cta_text', e.target.value)} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="s-cta-url">CTA URL</Label>
                        <Input id="s-cta-url" value={data.settings.cta_url ?? ''} onChange={(e) => setSetting('cta_url', e.target.value)} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="s-image">Background Image URL</Label>
                        <Input id="s-image" value={data.settings.image_url ?? ''} onChange={(e) => setSetting('image_url', e.target.value)} />
                    </div>
                </>
            )}

            {data.type === 'product_carousel' && (
                <>
                    <div className="grid gap-2">
                        <Label htmlFor="s-filter">Filter</Label>
                        <Select
                            value={data.settings.filter ?? ''}
                            onValueChange={(v) => setSetting('filter', v as 'new' | 'sale' | 'featured')}
                        >
                            <SelectTrigger id="s-filter">
                                <SelectValue placeholder="Select a filter" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="new">New Arrivals</SelectItem>
                                <SelectItem value="sale">On Sale</SelectItem>
                                <SelectItem value="featured">Featured</SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError message={errors['settings.filter']} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="s-limit">Product Limit</Label>
                        <Input
                            id="s-limit"
                            type="number"
                            min={1}
                            max={50}
                            value={data.settings.limit ?? 10}
                            onChange={(e) => setSetting('limit', parseInt(e.target.value))}
                        />
                    </div>
                </>
            )}

            {data.type === 'brand_showcase' && (
                <div className="grid gap-2">
                    <Label>Brands</Label>
                    <div className="max-h-48 overflow-y-auto rounded-md border p-3 space-y-2">
                        {brands.map((brand) => {
                            const selected = (data.settings.brand_ids ?? []).includes(brand.id);
                            return (
                                <label key={brand.id} className="flex items-center gap-2 cursor-pointer">
                                    <Checkbox
                                        checked={selected}
                                        onCheckedChange={(checked) => {
                                            const ids = data.settings.brand_ids ?? [];
                                            setSetting('brand_ids', checked ? [...ids, brand.id] : ids.filter((id) => id !== brand.id));
                                        }}
                                    />
                                    <span className="text-sm">{brand.name}</span>
                                </label>
                            );
                        })}
                        {brands.length === 0 && <p className="text-sm text-muted-foreground">No active brands found.</p>}
                    </div>
                    <InputError message={errors['settings.brand_ids']} />
                </div>
            )}

            <div className="flex items-center gap-3">
                <Switch
                    id="s-active"
                    checked={data.is_active}
                    onCheckedChange={(v) => setData('is_active', v)}
                />
                <Label htmlFor="s-active">Active</Label>
            </div>
        </div>
    );
}

// ─── Main Page ─────────────────────────────────────────────────────────────────

function emptySectionForm(): SectionFormData {
    return { type: 'hero', title: '', is_active: true, settings: {} };
}

export default function HomepageEdit({ config, categories, brands }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Admin', href: adminRoutes.dashboard().url },
        { title: 'Homepage Configs', href: adminRoutes.homepage.index().url },
        { title: config.label, href: adminRoutes.homepage.edit(config.id).url },
    ];

    // Config settings form
    const configForm = useForm({
        label: config.label,
        client_key: config.client_key,
        is_active: config.is_active,
    });

    function saveConfig(e: React.FormEvent) {
        e.preventDefault();
        configForm.patch(adminRoutes.homepage.update(config.id).url);
    }

    // Section dialog state
    const [dialogOpen, setDialogOpen] = useState(false);
    const [editingSection, setEditingSection] = useState<HomepageSection | null>(null);
    const sectionForm = useForm<SectionFormData>(emptySectionForm());

    function openAdd() {
        sectionForm.reset();
        sectionForm.setData(emptySectionForm());
        setEditingSection(null);
        setDialogOpen(true);
    }

    function openEdit(section: HomepageSection) {
        sectionForm.setData({
            type: section.type,
            title: section.title,
            is_active: section.is_active,
            settings: section.settings ?? {},
        });
        setEditingSection(section);
        setDialogOpen(true);
    }

    function submitSection(e: React.FormEvent) {
        e.preventDefault();
        if (editingSection) {
            sectionForm.patch(adminRoutes.homepage.sections.update(config.id, editingSection.id).url, {
                onSuccess: () => setDialogOpen(false),
            });
        } else {
            sectionForm.post(adminRoutes.homepage.sections.store(config.id).url, {
                onSuccess: () => setDialogOpen(false),
            });
        }
    }

    function deleteSection(section: HomepageSection) {
        if (!confirm(`Remove section "${section.title}"?`)) return;
        router.delete(adminRoutes.homepage.sections.destroy(config.id, section.id).url);
    }

    function move(section: HomepageSection, direction: 'up' | 'down') {
        const sections = [...(config.sections ?? [])].sort((a, b) => a.sort_order - b.sort_order);
        const idx = sections.findIndex((s) => s.id === section.id);
        const swapIdx = direction === 'up' ? idx - 1 : idx + 1;
        if (swapIdx < 0 || swapIdx >= sections.length) return;

        const reordered = sections.map((s, i) => {
            if (i === idx) return { id: s.id, sort_order: sections[swapIdx].sort_order };
            if (i === swapIdx) return { id: s.id, sort_order: sections[idx].sort_order };
            return { id: s.id, sort_order: s.sort_order };
        });

        router.post(adminRoutes.homepage.sections.reorder(config.id).url, { sections: reordered });
    }

    const sortedSections = [...(config.sections ?? [])].sort((a, b) => a.sort_order - b.sort_order);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${config.label} — Homepage Config`} />
            <div className="flex flex-col gap-8 p-6 max-w-4xl">
                <PageHeader title={config.label} description={`Client key: ${config.client_key}`} />

                {/* Config settings */}
                <section>
                    <h2 className="mb-4 text-sm font-semibold text-muted-foreground uppercase tracking-wide">Config Settings</h2>
                    <form onSubmit={saveConfig} className="rounded-lg border bg-card p-5 space-y-4 max-w-md">
                        <div className="grid gap-2">
                            <Label htmlFor="label">Label</Label>
                            <Input
                                id="label"
                                value={configForm.data.label}
                                onChange={(e) => configForm.setData('label', e.target.value)}
                                required
                            />
                            <InputError message={configForm.errors.label} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="client_key">Client Key</Label>
                            <Input
                                id="client_key"
                                value={configForm.data.client_key}
                                onChange={(e) => configForm.setData('client_key', e.target.value)}
                                required
                            />
                            <InputError message={configForm.errors.client_key} />
                        </div>
                        <div className="flex items-center gap-3">
                            <Switch
                                id="is_active"
                                checked={configForm.data.is_active}
                                onCheckedChange={(v) => configForm.setData('is_active', v)}
                            />
                            <Label htmlFor="is_active">Active</Label>
                        </div>
                        <Button type="submit" size="sm" disabled={configForm.processing}>Save Settings</Button>
                    </form>
                </section>

                {/* Sections */}
                <section>
                    <div className="mb-4 flex items-center justify-between">
                        <h2 className="text-sm font-semibold text-muted-foreground uppercase tracking-wide">Sections</h2>
                        <Button size="sm" onClick={openAdd}>
                            <Plus className="mr-1 h-4 w-4" /> Add Section
                        </Button>
                    </div>

                    <div className="rounded-lg border bg-card">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="w-8">#</TableHead>
                                    <TableHead>Type</TableHead>
                                    <TableHead>Title</TableHead>
                                    <TableHead>Settings</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead className="w-32" />
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {sortedSections.length === 0 && (
                                    <TableRow>
                                        <TableCell colSpan={6} className="py-8 text-center text-muted-foreground">
                                            No sections yet. Click "Add Section" to get started.
                                        </TableCell>
                                    </TableRow>
                                )}
                                {sortedSections.map((section, idx) => (
                                    <TableRow key={section.id}>
                                        <TableCell className="text-muted-foreground text-sm">{idx + 1}</TableCell>
                                        <TableCell>
                                            <Badge className={sectionBadgeColor(section.type)}>
                                                {SECTION_LABELS[section.type]}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="font-medium">{section.title}</TableCell>
                                        <TableCell className="text-sm text-muted-foreground max-w-xs truncate">
                                            {settingsSummary(section, categories, brands)}
                                        </TableCell>
                                        <TableCell>
                                            {section.is_active ? (
                                                <Badge className="bg-green-100 text-green-800 border-green-200 dark:bg-green-900/20 dark:text-green-400">Active</Badge>
                                            ) : (
                                                <Badge variant="secondary">Inactive</Badge>
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex items-center justify-end gap-1">
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="h-7 w-7"
                                                    disabled={idx === 0}
                                                    onClick={() => move(section, 'up')}
                                                    title="Move up"
                                                >
                                                    <ChevronUp className="h-4 w-4" />
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="h-7 w-7"
                                                    disabled={idx === sortedSections.length - 1}
                                                    onClick={() => move(section, 'down')}
                                                    title="Move down"
                                                >
                                                    <ChevronDown className="h-4 w-4" />
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="h-7 w-7"
                                                    onClick={() => openEdit(section)}
                                                    title="Edit"
                                                >
                                                    <Pencil className="h-4 w-4" />
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="h-7 w-7"
                                                    onClick={() => deleteSection(section)}
                                                    title="Delete"
                                                >
                                                    <Trash2 className="h-4 w-4 text-destructive" />
                                                </Button>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                </section>
            </div>

            {/* Section add/edit dialog */}
            <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
                <DialogContent className="max-w-lg max-h-[90vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle>{editingSection ? 'Edit Section' : 'Add Section'}</DialogTitle>
                    </DialogHeader>
                    <form onSubmit={submitSection}>
                        <SectionFields
                            data={sectionForm.data}
                            setData={(key, value) => sectionForm.setData(key, value as never)}
                            errors={sectionForm.errors}
                            categories={categories}
                            brands={brands}
                        />
                        <DialogFooter className="mt-6">
                            <Button type="button" variant="outline" onClick={() => setDialogOpen(false)}>Cancel</Button>
                            <Button type="submit" disabled={sectionForm.processing}>
                                {editingSection ? 'Save Changes' : 'Add Section'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
