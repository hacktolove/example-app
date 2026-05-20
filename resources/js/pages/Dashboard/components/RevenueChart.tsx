import {
    Bar,
    BarChart,
    CartesianGrid,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';

interface RevenueChartProps {
    revenue: number;
    date: string;
}

function formatYAxis(value: number): string {
    if (value >= 1_000_000) {
return `${(value / 1_000_000).toFixed(1)}M`;
}

    if (value >= 1_000) {
return `${(value / 1_000).toFixed(0)}K`;
}

    return String(value);
}

export default function RevenueChart({ revenue, date }: RevenueChartProps) {
    const data = [{ name: date, revenue }];

    return (
        <div className="p-5" style={{ background: '#FFFFFF', borderRadius: '12px', boxShadow: '0 2px 8px rgba(0,0,0,0.06)' }}>
            <div className="mb-4">
                <h3 className="text-base font-semibold" style={{ color: '#1A1A1A' }}>
                    Revenue by service
                </h3>
                <p className="text-sm" style={{ color: '#9E9E9E' }}>
                    Successful paid charges for {date}
                </p>
            </div>
            <ResponsiveContainer width="100%" height={220}>
                <BarChart data={data} margin={{ top: 4, right: 16, left: 0, bottom: 4 }}>
                    <CartesianGrid strokeDasharray="3 3" stroke="#F0F0F0" />
                    <XAxis dataKey="name" tick={{ fontSize: 12, fill: '#9E9E9E' }} />
                    <YAxis tickFormatter={formatYAxis} tick={{ fontSize: 12, fill: '#9E9E9E' }} />
                    <Tooltip
                        formatter={(value: unknown) =>
                            typeof value === 'number'
                                ? value.toLocaleString('en-US', { minimumFractionDigits: 2 }) + ' SDG'
                                : String(value)
                        }
                    />
                    <Bar dataKey="revenue" fill="#29ABE2" radius={[4, 4, 0, 0]} />
                </BarChart>
            </ResponsiveContainer>
        </div>
    );
}
