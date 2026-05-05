import { router } from '@inertiajs/react';
import { useState } from 'react';
import { toast } from 'sonner';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { ReportExportButton } from '@/features/report-export/ReportExportButton';
import { index as reportsIndex } from '@/routes/reports';
import { DatePickerField } from '@/shared/components/DatePickerField';

type ReportsFilterBarProps = {
    filters: {
        date_from?: string | null;
        date_to?: string | null;
        type?: string | null;
    };
};

type ReportType = 'orders' | 'inventory' | 'revenue';

type ReportFilters = {
    date_from: string;
    date_to: string;
    type: ReportType;
};

export function ReportsFilterBar({ filters }: ReportsFilterBarProps) {
    const today = todayValue();
    const firstReportDate = '1990-01-01';
    const [dateRange, setDateRange] = useState({
        date_from: filters.date_from ?? firstDayOfLastMonth(),
        date_to: filters.date_to ?? today,
    });
    const [type, setType] = useState<ReportType>(
        isReportType(filters.type) ? filters.type : 'orders',
    );

    const visit = (nextFilters: ReportFilters) => {
        router.get(
            reportsIndex.url({
                query: nextFilters,
            }),
            {},
            {
                only: ['reports', 'filters'],
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    };

    const updateType = (nextType: ReportType) => {
        setType(nextType);
        visit({ ...dateRange, type: nextType });
    };

    const updateDateFrom = (dateFrom: string) => {
        if (compareDateValues(dateFrom, today) > 0) {
            toast.error('Choose a Date From that is today or earlier.');

            return;
        }

        if (compareDateValues(dateFrom, dateRange.date_to) > 0) {
            toast.error('Date From must be on or before Date To.');

            return;
        }

        const nextRange = {
            date_from: dateFrom,
            date_to: dateRange.date_to,
        };

        setDateRange(nextRange);
        visit({ ...nextRange, type });
    };

    const updateDateTo = (dateTo: string) => {
        if (compareDateValues(dateTo, today) > 0) {
            toast.error('Choose a Date To that is today or earlier.');

            return;
        }

        if (compareDateValues(dateTo, dateRange.date_from) < 0) {
            toast.error('Date To must be on or after Date From.');

            return;
        }

        const nextRange = {
            ...dateRange,
            date_to: dateTo,
        };

        setDateRange(nextRange);
        visit({ ...nextRange, type });
    };

    return (
        <div className="flex flex-col gap-3">
            <div className="flex justify-end">
                <ReportExportButton
                    type={type}
                    dateFrom={dateRange.date_from}
                    dateTo={dateRange.date_to}
                    label="Download Report"
                    className="w-full sm:w-fit"
                />
            </div>
            <div className="rounded-xl border bg-card p-4 shadow-xs">
                <div className="grid gap-3 xl:grid-cols-[220px_minmax(180px,220px)_minmax(180px,220px)] xl:items-end">
                    <div className="grid gap-2">
                        <Label htmlFor="report-type" required>
                            Report Type
                        </Label>
                        <Select
                            value={type}
                            onValueChange={(value) => {
                                if (isReportType(value)) {
                                    updateType(value);
                                }
                            }}
                        >
                            <SelectTrigger id="report-type" className="w-full">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="orders">Orders</SelectItem>
                                <SelectItem value="inventory">
                                    Inventory
                                </SelectItem>
                                <SelectItem value="revenue">Revenue</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <DatePickerField
                        id="report-date-from"
                        label="Date From"
                        value={dateRange.date_from}
                        minDate={parseDateValue(firstReportDate)}
                        maxDate={parseDateValue(today)}
                        onChange={updateDateFrom}
                        required
                    />
                    <DatePickerField
                        id="report-date-to"
                        label="Date To"
                        value={dateRange.date_to}
                        minDate={parseDateValue(dateRange.date_from)}
                        maxDate={parseDateValue(today)}
                        onChange={updateDateTo}
                        required
                    />
                </div>
            </div>
        </div>
    );
}

function isReportType(value?: string | null): value is ReportType {
    return value === 'orders' || value === 'inventory' || value === 'revenue';
}

function todayValue(): string {
    const now = new Date();

    return formatDateValue(now);
}

function firstDayOfLastMonth(): string {
    const now = new Date();

    return formatDateValue(new Date(now.getFullYear(), now.getMonth() - 1, 1));
}

function parseDateValue(value: string): Date {
    const [year, month, day] = value.split('-').map(Number);

    return new Date(year, month - 1, day);
}

function formatDateValue(date: Date): string {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
}

function compareDateValues(left: string, right: string): number {
    return parseDateValue(left).getTime() - parseDateValue(right).getTime();
}
