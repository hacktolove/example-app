import { RefreshCw } from 'lucide-react';

type Tab = 'daily' | 'revenue' | 'churn' | 'new_subscribers';

interface DashboardHeaderProps {
    selectedDate: string;
    onDateChange: (date: string) => void;
    activeTab: Tab;
    onTabChange: (tab: Tab) => void;
    onRefresh: () => void;
    onExportCsv: () => void;
    isLoading: boolean;
}

const TABS: { key: Tab; label: string }[] = [
    { key: 'daily', label: 'Daily summary' },
    { key: 'revenue', label: 'Revenue' },
    { key: 'churn', label: 'Churn' },
    { key: 'new_subscribers', label: 'New subscribers' },
];

export default function DashboardHeader({
    selectedDate,
    onDateChange,
    activeTab,
    onTabChange,
    onRefresh,
    onExportCsv,
    isLoading,
}: DashboardHeaderProps) {
    return (
        <div className="space-y-4">
            <div className="flex flex-col gap-1">
                <h1 className="text-2xl font-bold tracking-tight" style={{ color: '#1A1A1A' }}>
                    Daily KPI Report
                </h1>
                <p className="text-sm font-medium" style={{ color: '#9E9E9E' }}>
                    Business dashboard — Performance reports
                </p>
                <p className="text-sm" style={{ color: '#9E9E9E' }}>
                    Revenue, subscribers, churn, and charging performance across all services.
                </p>
            </div>

            <div className="flex flex-wrap items-center justify-between gap-3">
                <div className="flex flex-wrap items-center gap-3">
                    <div className="flex items-center gap-2">
                        <label htmlFor="report-date" className="text-sm font-medium" style={{ color: '#1A1A1A' }}>
                            Daily report:
                        </label>
                        <input
                            id="report-date"
                            type="date"
                            value={selectedDate}
                            onChange={(e) => onDateChange(e.target.value)}
                            className="rounded-md border border-gray-300 px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                            style={{ color: '#1A1A1A' }}
                        />
                    </div>
                    <span
                        className="rounded-full px-3 py-1 text-xs font-semibold"
                        style={{ background: '#E3F2FD', color: '#1565C0' }}
                    >
                        Currency: SDG
                    </span>
                </div>

                <div className="flex items-center gap-2">
                    <button
                        onClick={onRefresh}
                        disabled={isLoading}
                        className="flex items-center gap-2 rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium transition-colors hover:bg-gray-50 disabled:opacity-60"
                        style={{ color: '#1A1A1A' }}
                    >
                        <RefreshCw
                            className={`size-4 ${isLoading ? 'animate-spin' : ''}`}
                        />
                        Refresh
                    </button>
                    <button
                        onClick={onExportCsv}
                        disabled={isLoading}
                        className="flex items-center gap-2 rounded-md px-4 py-2 text-sm font-semibold text-white transition-opacity hover:opacity-90 disabled:opacity-60"
                        style={{ background: '#F5A623' }}
                    >
                        Export CSV
                    </button>
                </div>
            </div>

            <div className="flex gap-1 border-b border-gray-200">
                {TABS.map(({ key, label }) => (
                    <button
                        key={key}
                        onClick={() => onTabChange(key)}
                        className={`px-4 py-2 text-sm font-medium transition-colors ${
                            activeTab === key
                                ? 'border-b-2 border-blue-500 text-blue-600'
                                : 'text-gray-500 hover:text-gray-700'
                        }`}
                        style={
                            activeTab === key
                                ? { borderBottomColor: '#29ABE2', color: '#29ABE2' }
                                : {}
                        }
                    >
                        {label}
                    </button>
                ))}
            </div>
        </div>
    );
}
