import { Head } from '@inertiajs/react';
import { useCallback, useEffect, useRef, useState } from 'react';
import { dashboard } from '@/routes';
import ChargingReachChart from './components/ChargingReachChart';
import ChurnTrendChart from './components/ChurnTrendChart';
import DashboardHeader from './components/DashboardHeader';
import KpiCards from './components/KpiCards';
import NewSubsTrendChart from './components/NewSubsTrendChart';
import RevenueChart from './components/RevenueChart';
import RevenueTrendChart from './components/RevenueTrendChart';
import ServiceTable from './components/ServiceTable';
import SubscriberMovementChart from './components/SubscriberMovementChart';

type Tab = 'daily' | 'revenue' | 'churn' | 'new_subscribers';

interface TrendPoint {
    date: string;
    value: number;
}

interface KpiSummary {
    revenue: number;
    activeSubscribers: number;
    newSubscribers: number;
    churn: number;
    chargeSuccessRate: number;
    uniqueCharged: number;
}

interface KpiCharging {
    uniqueCharged: number;
    successfulCharges: number;
    failedCharges: number;
}

interface KpiResponse {
    date: string;
    currency: string;
    summary: KpiSummary;
    charging: KpiCharging;
}

interface TrendCache {
    revenue?: TrendPoint[];
    churn?: TrendPoint[];
    new_subscribers?: TrendPoint[];
}

const DEFAULT_SUMMARY: KpiSummary = {
    revenue: 0,
    activeSubscribers: 0,
    newSubscribers: 0,
    churn: 0,
    chargeSuccessRate: 0,
    uniqueCharged: 0,
};

const DEFAULT_CHARGING: KpiCharging = {
    uniqueCharged: 0,
    successfulCharges: 0,
    failedCharges: 0,
};

export default function DashboardIndex() {
    const [selectedDate, setSelectedDate] = useState(() => new Date().toISOString().split('T')[0]);
    const [activeTab, setActiveTab] = useState<Tab>('daily');
    const [isLoading, setIsLoading] = useState(false);
    const [isTrendLoading, setIsTrendLoading] = useState(false);
    const [kpi, setKpi] = useState<KpiResponse | null>(null);
    const [trendPoints, setTrendPoints] = useState<TrendPoint[]>([]);
    const trendCache = useRef<TrendCache>({});

    const [kpiVersion, setKpiVersion] = useState(0);
    const [trendVersion, setTrendVersion] = useState(0);

    useEffect(() => {
        let cancelled = false;
        const controller = new AbortController();

        // eslint-disable-next-line react-hooks/set-state-in-effect
        setIsLoading(true);

        fetch(`/api/dashboard/kpi?date=${encodeURIComponent(selectedDate)}`, {
            headers: { Accept: 'application/json' },
            signal: controller.signal,
        })
            .then((res) => res.json() as Promise<KpiResponse>)
            .then((data) => {
                if (!cancelled) {
                    setKpi(data);
                    setIsLoading(false);
                }
            })
            .catch(() => {
                if (!cancelled) {
                    setIsLoading(false);
                }
            });

        return () => {
            cancelled = true;
            controller.abort();
        };
    }, [selectedDate, kpiVersion]);

    useEffect(() => {
        if (activeTab === 'daily') {
            return;
        }

        if (trendCache.current[activeTab]) {
            setTrendPoints(trendCache.current[activeTab]!);

            return;
        }

        let cancelled = false;
        const controller = new AbortController();

         
        setIsTrendLoading(true);

        fetch(`/api/dashboard/trend?metric=${encodeURIComponent(activeTab)}&days=30`, {
            headers: { Accept: 'application/json' },
            signal: controller.signal,
        })
            .then((res) => res.json() as Promise<{ metric: string; points: TrendPoint[] }>)
            .then((data) => {
                if (!cancelled) {
                    trendCache.current[activeTab as keyof TrendCache] = data.points;
                    setTrendPoints(data.points);
                    setIsTrendLoading(false);
                }
            })
            .catch(() => {
                if (!cancelled) {
                    setIsTrendLoading(false);
                }
            });

        return () => {
            cancelled = true;
            controller.abort();
        };
    }, [activeTab, trendVersion]);

    const handleRefresh = useCallback(() => {
        if (activeTab === 'daily') {
            setKpiVersion((v) => v + 1);
        } else {
            trendCache.current[activeTab as keyof TrendCache] = undefined;
            setTrendVersion((v) => v + 1);
        }
    }, [activeTab]);

    const handleExportCsv = useCallback(() => {
        if (!kpi) {
return;
}

        const { summary, charging } = kpi;
        const headers = [
            'Date',
            'Revenue (SDG)',
            'Active Subscribers',
            'New Subscribers',
            'Churn',
            'Charge Success Rate (%)',
            'Unique Charged',
            'Successful Charges',
            'Failed Charges',
        ];
        const row = [
            selectedDate,
            summary.revenue.toFixed(2),
            String(summary.activeSubscribers),
            String(summary.newSubscribers),
            String(summary.churn),
            (summary.chargeSuccessRate * 100).toFixed(2),
            String(summary.uniqueCharged),
            String(charging.successfulCharges),
            String(charging.failedCharges),
        ];
        const csv = [headers.join(','), row.join(',')].join('\n');
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `kpi-${selectedDate}.csv`;
        a.click();
        URL.revokeObjectURL(url);
    }, [kpi, selectedDate]);

    const summary = kpi?.summary ?? DEFAULT_SUMMARY;
    const charging = kpi?.charging ?? DEFAULT_CHARGING;

    return (
        <>
            <Head title="Daily KPI Report" />

            <div className="space-y-6 p-4 md:p-6" style={{ background: '#F5F5F5', minHeight: '100%' }}>
                <DashboardHeader
                    selectedDate={selectedDate}
                    onDateChange={setSelectedDate}
                    activeTab={activeTab}
                    onTabChange={setActiveTab}
                    onRefresh={handleRefresh}
                    onExportCsv={handleExportCsv}
                    isLoading={isLoading || isTrendLoading}
                />

                {activeTab === 'daily' && (
                    <div className="space-y-6">
                        <KpiCards
                            revenue={summary.revenue}
                            activeSubscribers={summary.activeSubscribers}
                            newSubscribers={summary.newSubscribers}
                            churn={summary.churn}
                            chargeSuccessRate={summary.chargeSuccessRate}
                            uniqueCharged={summary.uniqueCharged}
                        />

                        <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                            <RevenueChart revenue={summary.revenue} date={selectedDate} />
                            <SubscriberMovementChart
                                newSubscribers={summary.newSubscribers}
                                churn={summary.churn}
                            />
                        </div>

                        <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                            <ChargingReachChart
                                uniqueCharged={charging.uniqueCharged}
                                successfulCharges={charging.successfulCharges}
                                failedCharges={charging.failedCharges}
                            />
                            <ServiceTable
                                newSubscribers={summary.newSubscribers}
                                churn={summary.churn}
                            />
                        </div>
                    </div>
                )}

                {activeTab === 'revenue' && (
                    <RevenueTrendChart points={trendPoints} isLoading={isTrendLoading} />
                )}

                {activeTab === 'churn' && (
                    <ChurnTrendChart points={trendPoints} isLoading={isTrendLoading} />
                )}

                {activeTab === 'new_subscribers' && (
                    <NewSubsTrendChart points={trendPoints} isLoading={isTrendLoading} />
                )}
            </div>
        </>
    );
}

DashboardIndex.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard.url(),
        },
        {
            title: 'KPI Report',
            href: '/dashboard/kpi',
        },
    ],
};
