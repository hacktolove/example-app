interface KpiCardsProps {
    revenue: number;
    activeSubscribers: number;
    newSubscribers: number;
    churn: number;
    chargeSuccessRate: number;
    uniqueCharged: number;
}

interface CardConfig {
    label: string;
    badge: string;
    value: string;
    description: string;
}

const cardStyle: React.CSSProperties = {
    background: '#FFFFFF',
    borderRadius: '12px',
    boxShadow: '0 2px 8px rgba(0,0,0,0.06)',
};

function KpiCard({ label, badge, value, description }: CardConfig) {
    return (
        <div className="flex flex-col gap-2 p-5" style={cardStyle}>
            <div className="flex items-start justify-between">
                <span className="text-xs font-medium" style={{ color: '#9E9E9E' }}>
                    {label}
                </span>
                <span
                    className="rounded px-2 py-0.5 text-xs font-bold"
                    style={{ background: '#F5F5F5', color: '#1A1A1A' }}
                >
                    {badge}
                </span>
            </div>
            <p className="text-2xl font-bold tracking-tight" style={{ color: '#1A1A1A' }}>
                {value}
            </p>
            <p className="text-xs" style={{ color: '#9E9E9E' }}>
                {description}
            </p>
        </div>
    );
}

function formatRevenue(n: number): string {
    return (
        n.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }) + ' SDG'
    );
}

function formatInt(n: number): string {
    return n.toLocaleString('en-US');
}

function formatPercent(n: number): string {
    return (n * 100).toFixed(2) + '%';
}

export default function KpiCards({
    revenue,
    activeSubscribers,
    newSubscribers,
    churn,
    chargeSuccessRate,
    uniqueCharged,
}: KpiCardsProps) {
    const cards: CardConfig[] = [
        {
            label: 'Revenue today',
            badge: 'Revenue',
            value: formatRevenue(revenue),
            description: 'Total successful billing charges for today',
        },
        {
            label: 'Active subscribers',
            badge: 'Base',
            value: formatInt(activeSubscribers),
            description: 'Cumulative active subscriber base',
        },
        {
            label: 'New subscribers',
            badge: 'New',
            value: formatInt(newSubscribers),
            description: 'Unique activations today',
        },
        {
            label: 'Churn',
            badge: 'Churn',
            value: formatInt(churn),
            description: 'Unique unsubscriptions today',
        },
        {
            label: 'Charge success rate',
            badge: `${formatInt(uniqueCharged)} unique`,
            value: formatPercent(chargeSuccessRate),
            description: 'Unique charged vs active subscribers',
        },
    ];

    return (
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
            {cards.map((card) => (
                <KpiCard key={card.label} {...card} />
            ))}
        </div>
    );
}
