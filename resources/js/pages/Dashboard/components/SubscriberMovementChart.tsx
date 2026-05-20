import {
    Bar,
    BarChart,
    CartesianGrid,
    Legend,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';

interface SubscriberMovementChartProps {
    newSubscribers: number;
    churn: number;
}

export default function SubscriberMovementChart({ newSubscribers, churn }: SubscriberMovementChartProps) {
    const data = [
        {
            name: 'Today',
            'New subscribers': newSubscribers,
            Churn: churn,
        },
    ];

    return (
        <div className="p-5" style={{ background: '#FFFFFF', borderRadius: '12px', boxShadow: '0 2px 8px rgba(0,0,0,0.06)' }}>
            <div className="mb-4">
                <h3 className="text-base font-semibold" style={{ color: '#1A1A1A' }}>
                    Subscriber movement
                </h3>
                <p className="text-sm" style={{ color: '#9E9E9E' }}>
                    New subscribers compared with churn
                </p>
            </div>
            <ResponsiveContainer width="100%" height={220}>
                <BarChart data={data} margin={{ top: 4, right: 16, left: 0, bottom: 4 }}>
                    <CartesianGrid strokeDasharray="3 3" stroke="#F0F0F0" />
                    <XAxis dataKey="name" tick={{ fontSize: 12, fill: '#9E9E9E' }} />
                    <YAxis tick={{ fontSize: 12, fill: '#9E9E9E' }} />
                    <Tooltip />
                    <Legend />
                    <Bar dataKey="New subscribers" fill="#4CAF50" radius={[4, 4, 0, 0]} />
                    <Bar dataKey="Churn" fill="#F44336" radius={[4, 4, 0, 0]} />
                </BarChart>
            </ResponsiveContainer>
        </div>
    );
}
