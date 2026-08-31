import { router } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import { index } from '@/routes/ivr';

export interface IvrService {
    id: number;
    package: string;
    englishName: string;
    arabicName: string;
}

export default function ServiceTabs({
    services,
    selectedServiceId,
}: {
    services: IvrService[];
    selectedServiceId: number;
}) {
    return (
        <div
            role="tablist"
            aria-label="Service"
            className="flex flex-wrap items-center gap-1 rounded-lg border border-sidebar-border/70 bg-muted/40 p-1 dark:border-sidebar-border"
        >
            {services.map((service) => {
                const active = service.id === selectedServiceId;

                return (
                    <button
                        key={service.id}
                        type="button"
                        role="tab"
                        aria-selected={active}
                        onClick={() =>
                            router.visit(
                                index({ query: { service: service.id } }).url,
                                { preserveScroll: true },
                            )
                        }
                        className={cn(
                            'rounded-md px-3 py-1.5 text-sm font-medium transition-colors motion-reduce:transition-none',
                            'focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none',
                            active
                                ? 'bg-background text-foreground shadow-sm'
                                : 'text-muted-foreground hover:text-foreground',
                        )}
                    >
                        {service.englishName}
                        <span className="ms-2 font-mono text-xs text-muted-foreground">
                            /{service.package}
                        </span>
                    </button>
                );
            })}
        </div>
    );
}
