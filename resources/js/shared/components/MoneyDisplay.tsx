type MoneyDisplayProps = {
    value: string | number;
    currency?: string;
};

export function MoneyDisplay({ value, currency = 'USD' }: MoneyDisplayProps) {
    const amount = typeof value === 'number' ? value : Number.parseFloat(value || '0');

    return (
        <span>
            {new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency,
            }).format(Number.isFinite(amount) ? amount : 0)}
        </span>
    );
}
