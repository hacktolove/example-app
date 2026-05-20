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

interface ChargingReachChartProps {
    uniqueCharged: number;
    successfulCharges: number;
    failedCharges: number;
}

export default function ChargingReachChart({
    uniqueCharged,
    successfulCharges,
    failedCharges,
}: ChargingReachChartProps) {
    const data = [
        {
            name: 'Today',
            'Unique charged users': uniqueCharged,
            'Successful charges': successfulCharges,
            'Failed charges': failedCharges,
        },
    ];

    return (
        <div className="p-5" style={{ background: '#FFFFFF', borderRadius: '12px', boxShadow: '0 2px 8px rgba(0,0,0,0.06)' }}>
            <div className="mb-4">
                <h3 className="text-base font-semibold" style={{ color: '#1A1A1A' }}>
                    Charging reach
                </h3>
                <p className="text-sm" style={{ color: '#9E9E9E' }}>
                    Unique charged users compared with all successful charge attempts
                </p>
            </div>
            <ResponsiveContainer width="100%" height={220}>
                <BarChart data={data} margin={{ top: 4, right: 16, left: 0, bottom: 4 }}>
                    <CartesianGrid strokeDasharray="3 3" stroke="#F0F0F0" />
                    <XAxis dataKey="name" tick={{ fontSize: 12, fill: '#9E9E9E' }} />
                    <YAxis tick={{ fontSize: 12, fill: '#9E9E9E' }} />
                    <Tooltip />
                    <Legend />
                    <Bar dataKey="Unique charged users" fill="#FF9800" radius={[4, 4, 0, 0]} />
                    <Bar dataKey="Successful charges" fill="#5C6BC0" radius={[4, 4, 0, 0]} />
                    <Bar dataKey="Failed charges" fill="#B0BEC5" radius={[4, 4, 0, 0]} />
                </BarChart>
            </ResponsiveContainer>
        </div>
    );
}
