interface ServiceTableProps {
    newSubscribers: number;
    churn: number;
}

function NetChangeBadge({ value }: { value: number }) {
    if (value > 0) {
        return (
            <span className="font-semibold" style={{ color: '#4CAF50' }}>
                +{value.toLocaleString('en-US')}
            </span>
        );
    }

    if (value < 0) {
        return (
            <span className="font-semibold" style={{ color: '#F44336' }}>
                {value.toLocaleString('en-US')}
            </span>
        );
    }

    return (
        <span className="font-semibold" style={{ color: '#9E9E9E' }}>
            0
        </span>
    );
}

export default function ServiceTable({ newSubscribers, churn }: ServiceTableProps) {
    const netChange = newSubscribers - churn;

    return (
        <div className="p-5" style={{ background: '#FFFFFF', borderRadius: '12px', boxShadow: '0 2px 8px rgba(0,0,0,0.06)' }}>
            <div className="mb-4">
                <h3 className="text-base font-semibold" style={{ color: '#1A1A1A' }}>
                    Daily service performance
                </h3>
                <p className="text-sm" style={{ color: '#9E9E9E' }}>
                    Use the status badges to quickly spot growth, churn, and charging issues.
                </p>
            </div>
            <div className="overflow-x-auto">
                <table className="w-full text-sm">
                    <thead>
                        <tr className="border-b border-gray-100">
                            <th className="pb-3 text-left font-medium" style={{ color: '#9E9E9E' }}>
                                Service
                            </th>
                            <th className="pb-3 text-right font-medium" style={{ color: '#9E9E9E' }}>
                                New subscribers
                            </th>
                            <th className="pb-3 text-right font-medium" style={{ color: '#9E9E9E' }}>
                                Churn
                            </th>
                            <th className="pb-3 text-right font-medium" style={{ color: '#9E9E9E' }}>
                                Net change
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr className="border-b border-gray-50">
                            <td className="py-3 font-medium" style={{ color: '#1A1A1A' }}>
                                MTN
                            </td>
                            <td className="py-3 text-right" style={{ color: '#1A1A1A' }}>
                                {newSubscribers.toLocaleString('en-US')}
                            </td>
                            <td className="py-3 text-right" style={{ color: '#1A1A1A' }}>
                                {churn.toLocaleString('en-US')}
                            </td>
                            <td className="py-3 text-right">
                                <NetChangeBadge value={netChange} />
                            </td>
                        </tr>
                        <tr>
                            <td className="pt-3 font-semibold" style={{ color: '#1A1A1A' }}>
                                Total
                            </td>
                            <td className="pt-3 text-right font-semibold" style={{ color: '#1A1A1A' }}>
                                {newSubscribers.toLocaleString('en-US')}
                            </td>
                            <td className="pt-3 text-right font-semibold" style={{ color: '#1A1A1A' }}>
                                {churn.toLocaleString('en-US')}
                            </td>
                            <td className="pt-3 text-right">
                                <NetChangeBadge value={netChange} />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    );
}
