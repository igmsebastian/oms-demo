import { format } from 'date-fns';
import {
    CalendarIcon,
    ChevronLeft,
    ChevronRight,
    ChevronsUpDown,
} from 'lucide-react';
import { useMemo, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import { Label } from '@/components/ui/label';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { cn } from '@/lib/utils';

type DatePickerFieldProps = {
    id: string;
    label: string;
    value?: string | null;
    onChange: (value: string) => void;
    minDate?: Date;
    maxDate?: Date;
    placeholder?: string;
    className?: string;
    disabled?: boolean;
    required?: boolean;
};

type CalendarView = 'day' | 'month' | 'year';

const FIRST_REPORT_YEAR = 1990;
const monthLabels = Array.from({ length: 12 }, (_, index) =>
    format(new Date(2024, index, 1), 'MMM'),
);

export function DatePickerField({
    id,
    label,
    value,
    onChange,
    minDate,
    maxDate,
    placeholder = 'Pick a date',
    className,
    disabled = false,
    required = false,
}: DatePickerFieldProps) {
    const selectedDate = parseDateValue(value);
    const today = startOfDay(new Date());
    const [open, setOpen] = useState(false);
    const [view, setView] = useState<CalendarView>('day');
    const [visibleMonth, setVisibleMonth] = useState<Date>(
        selectedDate ?? maxDate ?? today,
    );
    const minDay = minDate ? startOfDay(minDate) : undefined;
    const maxDay = maxDate ? startOfDay(maxDate) : undefined;
    const years = useMemo(() => {
        const currentYear = today.getFullYear();

        return Array.from(
            { length: currentYear - FIRST_REPORT_YEAR + 1 },
            (_, index) => currentYear - index,
        );
    }, [today]);

    const selectedLabel = selectedDate
        ? format(selectedDate, 'PPP')
        : placeholder;
    const headerLabel =
        view === 'year'
            ? 'Select year'
            : view === 'month'
              ? visibleMonth.getFullYear().toString()
              : format(visibleMonth, 'MMM yyyy');
    const previousDisabled =
        view === 'year' ||
        isPreviousNavigationDisabled(visibleMonth, view, minDay);
    const nextDisabled =
        view === 'year' || isNextNavigationDisabled(visibleMonth, view, maxDay);

    const handleOpenChange = (nextOpen: boolean) => {
        setOpen(nextOpen);

        if (nextOpen) {
            setView('day');
            setVisibleMonth(selectedDate ?? maxDate ?? today);
        }
    };

    const handleSelect = (date?: Date) => {
        if (!date || isDateDisabled(date, minDay, maxDay)) {
            return;
        }

        onChange(formatDateValue(date));
        setVisibleMonth(date);
        setOpen(false);
    };

    const handleToday = () => {
        if (isDateDisabled(today, minDay, maxDay)) {
            return;
        }

        handleSelect(today);
    };

    const handleHeaderClick = () => {
        if (view === 'day') {
            setView('year');

            return;
        }

        if (view === 'month') {
            setView('year');

            return;
        }

        setView('year');
    };

    const handlePrevious = () => {
        if (view === 'day') {
            setVisibleMonth(addMonths(visibleMonth, -1));

            return;
        }

        if (view === 'month') {
            setVisibleMonth(addYears(visibleMonth, -1));
        }
    };

    const handleNext = () => {
        if (view === 'day') {
            setVisibleMonth(addMonths(visibleMonth, 1));

            return;
        }

        if (view === 'month') {
            setVisibleMonth(addYears(visibleMonth, 1));
        }
    };

    return (
        <div className={cn('grid gap-2', className)}>
            <Label htmlFor={id} required={required}>
                {label}
            </Label>
            <Popover open={open} onOpenChange={handleOpenChange}>
                <PopoverTrigger asChild>
                    <Button
                        id={id}
                        type="button"
                        variant="outline"
                        data-empty={!selectedDate}
                        disabled={disabled}
                        className="w-full justify-start text-left font-normal data-[empty=true]:text-muted-foreground"
                    >
                        <CalendarIcon className="size-4" />
                        <span className="truncate">{selectedLabel}</span>
                    </Button>
                </PopoverTrigger>
                <PopoverContent align="start" className="w-auto p-0">
                    <div className="flex items-center justify-between gap-2 border-b p-2">
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            className="size-8"
                            onClick={handlePrevious}
                            disabled={previousDisabled}
                        >
                            <ChevronLeft className="size-4" />
                            <span className="sr-only">Previous</span>
                        </Button>
                        <Button
                            type="button"
                            variant="ghost"
                            className="h-8 min-w-36 gap-1 px-2 font-medium"
                            onClick={handleHeaderClick}
                        >
                            {headerLabel}
                            <ChevronsUpDown className="size-3.5 text-muted-foreground" />
                        </Button>
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            className="size-8"
                            onClick={handleNext}
                            disabled={nextDisabled}
                        >
                            <ChevronRight className="size-4" />
                            <span className="sr-only">Next</span>
                        </Button>
                    </div>

                    {view === 'day' && (
                        <>
                            <Calendar
                                mode="single"
                                selected={selectedDate}
                                month={visibleMonth}
                                onMonthChange={setVisibleMonth}
                                onSelect={handleSelect}
                                disabled={(date) =>
                                    isDateDisabled(date, minDay, maxDay)
                                }
                                classNames={{
                                    month_caption: 'hidden',
                                    nav: 'hidden',
                                }}
                            />
                            <div className="border-t p-2">
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    className="w-full"
                                    onClick={handleToday}
                                    disabled={isDateDisabled(
                                        today,
                                        minDay,
                                        maxDay,
                                    )}
                                >
                                    Today
                                </Button>
                            </div>
                        </>
                    )}

                    {view === 'month' && (
                        <div className="grid w-72 grid-cols-4 gap-2 p-3">
                            {monthLabels.map((month, monthIndex) => {
                                const monthDate = new Date(
                                    visibleMonth.getFullYear(),
                                    monthIndex,
                                    1,
                                );
                                const selected =
                                    monthIndex === visibleMonth.getMonth();

                                return (
                                    <Button
                                        key={month}
                                        type="button"
                                        variant={selected ? 'default' : 'ghost'}
                                        size="sm"
                                        disabled={isMonthDisabled(
                                            monthDate,
                                            minDay,
                                            maxDay,
                                        )}
                                        onClick={() => {
                                            setVisibleMonth(monthDate);
                                            setView('day');
                                        }}
                                    >
                                        {month}
                                    </Button>
                                );
                            })}
                        </div>
                    )}

                    {view === 'year' && (
                        <div className="grid max-h-72 w-72 grid-cols-4 gap-2 overflow-y-auto p-3">
                            {years.map((year) => {
                                const selected =
                                    year === visibleMonth.getFullYear();

                                return (
                                    <Button
                                        key={year}
                                        type="button"
                                        variant={selected ? 'default' : 'ghost'}
                                        size="sm"
                                        disabled={isYearDisabled(
                                            year,
                                            minDay,
                                            maxDay,
                                        )}
                                        onClick={() => {
                                            setVisibleMonth(
                                                new Date(
                                                    year,
                                                    visibleMonth.getMonth(),
                                                    1,
                                                ),
                                            );
                                            setView('month');
                                        }}
                                    >
                                        {year}
                                    </Button>
                                );
                            })}
                        </div>
                    )}
                </PopoverContent>
            </Popover>
        </div>
    );
}

function parseDateValue(value?: string | null): Date | undefined {
    if (!value) {
        return undefined;
    }

    const [year, month, day] = value.split('-').map(Number);

    if (!year || !month || !day) {
        return undefined;
    }

    return new Date(year, month - 1, day);
}

function formatDateValue(date: Date): string {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
}

function startOfDay(date: Date): Date {
    return new Date(date.getFullYear(), date.getMonth(), date.getDate());
}

function addMonths(date: Date, months: number): Date {
    return new Date(date.getFullYear(), date.getMonth() + months, 1);
}

function addYears(date: Date, years: number): Date {
    return new Date(date.getFullYear() + years, date.getMonth(), 1);
}

function isDateDisabled(date: Date, minDate?: Date, maxDate?: Date): boolean {
    const day = startOfDay(date).getTime();

    return Boolean(
        (minDate && day < minDate.getTime()) ||
        (maxDate && day > maxDate.getTime()),
    );
}

function isMonthDisabled(date: Date, minDate?: Date, maxDate?: Date): boolean {
    const monthStart = new Date(date.getFullYear(), date.getMonth(), 1);
    const monthEnd = new Date(date.getFullYear(), date.getMonth() + 1, 0);

    return Boolean(
        (minDate && monthEnd.getTime() < minDate.getTime()) ||
        (maxDate && monthStart.getTime() > maxDate.getTime()),
    );
}

function isYearDisabled(year: number, minDate?: Date, maxDate?: Date): boolean {
    return Boolean(
        (minDate && year < minDate.getFullYear()) ||
        (maxDate && year > maxDate.getFullYear()),
    );
}

function isPreviousNavigationDisabled(
    date: Date,
    view: CalendarView,
    minDate?: Date,
): boolean {
    if (!minDate) {
        return false;
    }

    if (view === 'day') {
        return endOfMonth(addMonths(date, -1)).getTime() < minDate.getTime();
    }

    if (view === 'month') {
        return (
            new Date(date.getFullYear() - 1, 11, 31).getTime() <
            minDate.getTime()
        );
    }

    return true;
}

function isNextNavigationDisabled(
    date: Date,
    view: CalendarView,
    maxDate?: Date,
): boolean {
    if (!maxDate) {
        return false;
    }

    if (view === 'day') {
        return addMonths(date, 1).getTime() > maxDate.getTime();
    }

    if (view === 'month') {
        return (
            new Date(date.getFullYear() + 1, 0, 1).getTime() > maxDate.getTime()
        );
    }

    return true;
}

function endOfMonth(date: Date): Date {
    return new Date(date.getFullYear(), date.getMonth() + 1, 0);
}
