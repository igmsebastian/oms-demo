import { Button } from '@/components/ui/button';
import { DatePickerField } from '@/shared/components/DatePickerField';

type DateRangeFilterProps = {
    dateFrom: string;
    dateTo: string;
    onChange: (value: { date_from: string; date_to: string }) => void;
    onSubmit: () => void;
    minDate?: Date;
    maxFromDate?: Date;
    maxToDate?: Date;
    submitLabel?: string;
};

export function DateRangeFilter({
    dateFrom,
    dateTo,
    onChange,
    onSubmit,
    minDate,
    maxFromDate,
    maxToDate,
    submitLabel = 'Apply',
}: DateRangeFilterProps) {
    return (
        <div className="grid gap-3 md:grid-cols-[1fr_1fr_auto] md:items-end">
            <DatePickerField
                id="date-from"
                label="Date From"
                value={dateFrom}
                minDate={minDate}
                maxDate={maxFromDate}
                onChange={(value) =>
                    onChange({ date_from: value, date_to: dateTo })
                }
            />
            <DatePickerField
                id="date-to"
                label="Date To"
                value={dateTo}
                minDate={dateFrom ? parseDateValue(dateFrom) : minDate}
                maxDate={maxToDate}
                onChange={(value) =>
                    onChange({ date_from: dateFrom, date_to: value })
                }
            />
            <Button type="button" onClick={onSubmit}>
                {submitLabel}
            </Button>
        </div>
    );
}

function parseDateValue(value: string): Date {
    const [year, month, day] = value.split('-').map(Number);

    return new Date(year, month - 1, day);
}
