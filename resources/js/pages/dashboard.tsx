import { Head, Link } from '@inertiajs/react';
import { ArrowRight, LayoutTemplate, ShieldCheck, Sparkles } from 'lucide-react';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import adminRoutes from '@/routes/admin';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
    },
];

export default function Dashboard() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="mx-auto flex w-full max-w-[1500px] flex-1 flex-col gap-7 p-4 sm:p-6 lg:p-8">
                <div className="relative isolate overflow-hidden rounded-2xl border bg-gradient-to-br from-slate-950 via-slate-900 to-slate-800 px-6 py-10 text-white shadow-sm sm:px-10 sm:py-12">
                    <div className="pointer-events-none absolute -right-16 -top-24 -z-10 h-80 w-80 rounded-full bg-orange-500/20 blur-3xl" />
                    <span className="mb-4 inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-medium text-slate-200"><Sparkles className="h-3.5 w-3.5" />New England Distribution</span>
                    <h1 className="max-w-3xl text-3xl font-bold tracking-tight sm:text-4xl">Content management, without the clutter.</h1>
                    <p className="mt-3 max-w-2xl text-sm leading-6 text-slate-300 sm:text-base">Publish banners, products, brands, catalogs and global storefront settings from one focused workspace.</p>
                    <Button asChild size="lg" className="mt-7 bg-white text-slate-950 hover:bg-slate-100"><Link href={adminRoutes.homepage.index().url}>Open homepage manager<ArrowRight className="ml-2 h-4 w-4" /></Link></Button>
                </div>
                <div className="grid gap-4 md:grid-cols-2">
                    <Link href={adminRoutes.homepage.index().url} className="group rounded-2xl border bg-card p-6 shadow-sm transition-all hover:-translate-y-0.5 hover:shadow-md"><span className="inline-flex rounded-xl bg-blue-500/10 p-3 text-blue-600 dark:text-blue-400"><LayoutTemplate className="h-6 w-6" /></span><h2 className="mt-5 text-lg font-semibold">Homepage manager</h2><p className="mt-2 text-sm leading-6 text-muted-foreground">Manage page structure, promotional banners, product selections, brand logos and catalog content.</p><span className="mt-5 inline-flex items-center text-sm font-semibold text-blue-600 dark:text-blue-400">Manage content<ArrowRight className="ml-1.5 h-4 w-4 transition-transform group-hover:translate-x-1" /></span></Link>
                    <div className="rounded-2xl border bg-card p-6 shadow-sm"><span className="inline-flex rounded-xl bg-emerald-500/10 p-3 text-emerald-600 dark:text-emerald-400"><ShieldCheck className="h-6 w-6" /></span><h2 className="mt-5 text-lg font-semibold">Production-ready controls</h2><p className="mt-2 text-sm leading-6 text-muted-foreground">Changes are stored centrally and delivered to the storefront through the managed homepage API.</p><div className="mt-5 inline-flex items-center gap-2 rounded-full bg-emerald-500/10 px-3 py-1 text-xs font-semibold text-emerald-700 dark:text-emerald-400"><span className="h-2 w-2 rounded-full bg-emerald-500" />System ready</div></div>
                </div>
            </div>
        </AppLayout>
    );
}
