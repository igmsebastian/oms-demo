import { ChevronDown } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { cn } from '@/lib/utils';
import { statusColor } from '@/shared/status/statusTheme';

export type StatusFilterOption = {
    name: string;
    label: string;
};

type StatusMultiSelectFilterProps = {
    options: StatusFilterOption[];
    selected: string[];
    counts?: Record<string, number>;
    allLabel?: string;
    className?: string;
    onChange: (selected: string[]) => void;
};

export function StatusMultiSelectFilter({
    options,
    selected,
    counts = {},
    allLabel = 'All statuses',
    className,
    onChange,
}: StatusMultiSelectFilterProps) {
    const selectedSet = new Set(selected);
    const selectedOptions = options.filter((option) =>
        selectedSet.has(option.name),
    );
    const totalCount = Object.values(counts).reduce(
        (total, count) => total + count,
        0,
    );
    const triggerLabel =
        selectedOptions.length === 0
            ? allLabel
            : selectedOptions.length === 1
              ? selectedOptions[0].label
              : `${selectedOptions.length} statuses`;

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    type="button"
                    variant="outline"
                    className={cn('w-full justify-between', className)}
                >
                    <span className="flex min-w-0 items-center gap-2">
                        {selectedOptions.length === 1 && (
                            <span
                                className="size-2 rounded-full"
                                style={{
                                    backgroundColor: statusColor(
                                        selectedOptions[0].name,
                                    ),
                                }}
                            />
                        )}
                        {selectedOptions.length > 1 && (
                            <span className="flex -space-x-1">
                                {selectedOptions
                                    .slice(0, 3)
                                    .map((option) => (
                                        <span
                                            key={option.name}
                                            className="size-2 rounded-full ring-1 ring-background"
                                            style={{
                                                backgroundColor: statusColor(
                                                    option.name,
                                                ),
                                            }}
                                        />
                                    ))}
                            </span>
                        )}
                        <span className="truncate">{triggerLabel}</span>
                    </span>
                    <ChevronDown className="size-4 opacity-60" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-72">
                <DropdownMenuCheckboxItem
                    checked={selected.length === 0}
                    onCheckedChange={() => onChange([])}
                    onSelect={(event) => event.preventDefault()}
                >
                    <span className="flex min-w-0 flex-1 items-center">
                        <span className="truncate">{allLabel}</span>
                    </span>
                    <span className="ml-4 text-xs tabular-nums text-muted-foreground">
                        {totalCount.toLocaleString()}
                    </span>
                </DropdownMenuCheckboxItem>
                <DropdownMenuSeparator />
                {options.map((option) => {
                    const checked = selectedSet.has(option.name);

                    return (
                        <DropdownMenuCheckboxItem
                            key={option.name}
                            checked={checked}
                            onCheckedChange={(nextChecked) =>
                                onChange(
                                    nextChecked
                                        ? [
                                              ...new Set([
                                                  ...selected,
                                                  option.name,
                                              ]),
                                          ]
                                        : selected.filter(
                                              (status) =>
                                                  status !== option.name,
                                          ),
                                )
                            }
                            onSelect={(event) => event.preventDefault()}
                        >
                            <span className="flex min-w-0 flex-1 items-center gap-2">
                                <span
                                    className="size-2 rounded-full"
                                    style={{
                                        backgroundColor: statusColor(
                                            option.name,
                                        ),
                                    }}
                                />
                                <span className="truncate">
                                    {option.label}
                                </span>
                            </span>
                            <span className="ml-4 text-xs tabular-nums text-muted-foreground">
                                {(counts[option.name] ?? 0).toLocaleString()}
                            </span>
                        </DropdownMenuCheckboxItem>
                    );
                })}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
