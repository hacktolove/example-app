import {
    CartesianGrid,
    Line,
    LineChart,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';

interface TrendPoint {
    date: string;
    value: number;
}

interface ChurnTrendChartProps {
    points: TrendPoint[];
    isLoading: boolean;
}

function abbreviateDate(date: string): string {
    const d = new Date(date + 'T00:00:00');

    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
}

function LoadingSkeleton() {
    return (
        <div className="flex h-[220px] animate-pulse items-center justify-center rounded-lg bg-gray-100">
            <span className="text-sm" style={{ color: '#9E9E9E' }}>Loading…</span>
        </div>
    );
}

export default function ChurnTrendChart({ points, isLoading }: ChurnTrendChartProps) {
    const data = points.map((p) => ({ ...p, label: abbreviateDate(p.date) }));

    return (
        <div className="p-5" style={{ background: '#FFFFFF', borderRadius: '12px', boxShadow: '0 2px 8px rgba(0,0,0,0.06)' }}>
            <div className="mb-4">
                <h3 className="text-base font-semibold" style={{ color: '#1A1A1A' }}>
                    Churn trend — last 30 days
                </h3>
            </div>
            {isLoading ? (
                <LoadingSkeleton />
            ) : (
                <ResponsiveContainer width="100%" height={220}>
                    <LineChart data={data} margin={{ top: 4, right: 16, left: 0, bottom: 4 }}>
                        <CartesianGrid strokeDasharray="3 3" stroke="#F0F0F0" />
                        <XAxis
                            dataKey="label"
                            tick={{ fontSize: 11, fill: '#9E9E9E' }}
                            interval="preserveStartEnd"
                        />
                        <YAxis tick={{ fontSize: 11, fill: '#9E9E9E' }} />
                        <Tooltip formatter={(value: unknown) => typeof value === 'number' ? value.toLocaleString('en-US') : String(value)} />
                        <Line
                            type="monotone"
                            dataKey="value"
                            stroke="#F44336"
                            strokeWidth={2}
                            dot={false}
                            activeDot={{ r: 4 }}
                        />
                    </LineChart>
                </ResponsiveContainer>
            )}
        </div>
    );
}
