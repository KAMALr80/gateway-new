import { router } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import type { PaginatedMeta } from '@/types/admin';

interface DataTablePaginationProps {
    meta: PaginatedMeta;
    filters?: Record<string, string | number | undefined>;
    routeUrl: string;
}

export function DataTablePagination({ meta, filters = {}, routeUrl }: DataTablePaginationProps) {
    const { current_page, last_page, per_page, total, from, to } = meta;

    function goToPage(page: number) {
        router.get(routeUrl, { ...filters, per_page, page }, { preserveScroll: true, preserveState: true });
    }

    function changePerPage(value: string) {
        router.get(routeUrl, { ...filters, per_page: value, page: 1 }, { preserveScroll: true, preserveState: true });
    }

    return (
        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <p className="text-sm text-muted-foreground">
                {total === 0 ? 'No results' : `Showing ${from ?? 0}–${to ?? 0} of ${total}`}
            </p>
            <div className="flex items-center gap-4">
                <div className="flex items-center gap-2">
                    <span className="text-sm text-muted-foreground">Per page</span>
                    <Select value={String(per_page)} onValueChange={changePerPage}>
                        <SelectTrigger className="h-8 w-[70px]">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            {[10, 15, 25, 50, 100].map((n) => (
                                <SelectItem key={n} value={String(n)}>
                                    {n}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>
                <div className="flex items-center gap-1">
                    <Button
                        variant="outline"
                        size="icon"
                        className="h-8 w-8"
                        onClick={() => goToPage(current_page - 1)}
                        disabled={current_page <= 1}
                    >
                        <ChevronLeft className="h-4 w-4" />
                    </Button>
                    <span className="min-w-[80px] text-center text-sm">
                        {current_page} / {last_page}
                    </span>
                    <Button
                        variant="outline"
                        size="icon"
                        className="h-8 w-8"
                        onClick={() => goToPage(current_page + 1)}
                        disabled={current_page >= last_page}
                    >
                        <ChevronRight className="h-4 w-4" />
                    </Button>
                </div>
            </div>
        </div>
    );
}
