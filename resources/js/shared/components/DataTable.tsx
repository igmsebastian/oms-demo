import type {
    ColumnDef,
    OnChangeFn,
    PaginationState,
    RowSelectionState,
    SortingState,
    VisibilityState,
} from '@tanstack/react-table';
import {
    flexRender,
    getCoreRowModel,
    getFacetedRowModel,
    getFacetedUniqueValues,
    getFilteredRowModel,
    getPaginationRowModel,
    getSortedRowModel,
    useReactTable,
} from '@tanstack/react-table';
import {
    ArrowDown,
    ArrowUp,
    ChevronsLeft,
    ChevronsRight,
    ChevronsUpDown,
    ChevronDown,
    ChevronLeft,
    ChevronRight,
    Columns3,
    PackageOpen,
} from 'lucide-react';
import { useMemo, useState } from 'react';
import type { ReactNode } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Skeleton } from '@/components/ui/skeleton';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { cn } from '@/lib/utils';
import type { PaginationMeta } from '@/shared/types/pagination';

type DataTableProps<TData> = {
    data: TData[];
    columns: ColumnDef<TData>[];
    meta?: PaginationMeta;
    sorting?: SortingState;
    onSortingChange?: (sorting: SortingState) => void;
    onPageChange?: (page: number) => void;
    onPageSizeChange?: (pageSize: number) => void;
    onRowClick?: (row: TData) => void;
    getRowId?: (row: TData, index: number) => string;
    emptyTitle?: string;
    toolbar?: ReactNode;
    enableRowSelection?: boolean;
    enableColumnVisibility?: boolean;
    loading?: boolean;
    className?: string;
};

const pageSizeOptions = [10, 20, 30, 40, 50];

export function DataTable<TData>({
    data,
    columns,
    meta,
    sorting = [],
    onSortingChange,
    onPageChange,
    onPageSizeChange,
    onRowClick,
    getRowId,
    emptyTitle = 'No records found',
    toolbar,
    enableRowSelection = false,
    enableColumnVisibility = true,
    loading = false,
    className,
}: DataTableProps<TData>) {
    const [internalSorting, setInternalSorting] = useState<SortingState>([]);
    const [rowSelection, setRowSelection] = useState<RowSelectionState>({});
    const [columnVisibility, setColumnVisibility] = useState<VisibilityState>(
        {},
    );
    const [localPagination, setLocalPagination] = useState<PaginationState>({
        pageIndex: 0,
        pageSize: Math.min(10, Math.max(data.length, 1)),
    });
    const activeSorting = onSortingChange ? sorting : internalSorting;
    const pagination = meta
        ? {
              pageIndex: Math.max(meta.current_page - 1, 0),
              pageSize: meta.per_page,
          }
        : localPagination;
    const tableColumns = useMemo<ColumnDef<TData>[]>(
        () =>
            enableRowSelection
                ? [
                      {
                          id: 'select',
                          header: ({ table }) => (
                              <div className="flex items-center justify-center">
                                  <Checkbox
                                      checked={
                                          table.getIsAllPageRowsSelected() ||
                                          (table.getIsSomePageRowsSelected() &&
                                              'indeterminate')
                                      }
                                      onCheckedChange={(value) =>
                                          table.toggleAllPageRowsSelected(
                                              Boolean(value),
                                          )
                                      }
                                      aria-label="Select all rows"
                                  />
                              </div>
                          ),
                          cell: ({ row }) => (
                              <div
                                  className="flex items-center justify-center"
                                  onClick={(event) => event.stopPropagation()}
                              >
                                  <Checkbox
                                      checked={row.getIsSelected()}
                                      onCheckedChange={(value) =>
                                          row.toggleSelected(Boolean(value))
                                      }
                                      aria-label="Select row"
                                  />
                              </div>
                          ),
                          enableHiding: false,
                          enableSorting: false,
                      },
                      ...columns,
                  ]
                : columns,
        [columns, enableRowSelection],
    );

    const handlePaginationChange: OnChangeFn<PaginationState> = (updater) => {
        const next =
            typeof updater === 'function' ? updater(pagination) : updater;

        if (meta) {
            if (next.pageSize !== meta.per_page) {
                onPageSizeChange?.(next.pageSize);

                return;
            }

            if (next.pageIndex !== pagination.pageIndex) {
                onPageChange?.(next.pageIndex + 1);
            }

            return;
        }

        setLocalPagination(next);
    };

    // eslint-disable-next-line react-hooks/incompatible-library
    const table = useReactTable({
        data,
        columns: tableColumns,
        state: {
            columnVisibility,
            pagination,
            rowSelection,
            sorting: activeSorting,
        },
        getRowId,
        enableRowSelection,
        manualFiltering: Boolean(meta),
        manualPagination: Boolean(meta),
        manualSorting: Boolean(onSortingChange),
        pageCount: meta?.last_page ?? undefined,
        onColumnVisibilityChange: setColumnVisibility,
        onPaginationChange: handlePaginationChange,
        onRowSelectionChange: setRowSelection,
        onSortingChange: (updater) => {
            const next =
                typeof updater === 'function'
                    ? updater(activeSorting)
                    : updater;

            if (onSortingChange) {
                onSortingChange(next);

                return;
            }

            setInternalSorting(next);
        },
        getCoreRowModel: getCoreRowModel(),
        getFacetedRowModel: getFacetedRowModel(),
        getFacetedUniqueValues: getFacetedUniqueValues(),
        getFilteredRowModel: getFilteredRowModel(),
        getPaginationRowModel: getPaginationRowModel(),
        getSortedRowModel: getSortedRowModel(),
    });
    const rows = table.getRowModel().rows;
    const hideableColumns = enableColumnVisibility
        ? table.getAllColumns().filter((column) => column.getCanHide())
        : [];
    const activePageSizeOptions = useMemo(
        () =>
            Array.from(new Set([...pageSizeOptions, pagination.pageSize])).sort(
                (a, b) => a - b,
            ),
        [pagination.pageSize],
    );
    const visibleColumnCount = table.getVisibleLeafColumns().length;
    const loadingRowCount = Math.min(
        Math.max(rows.length || pagination.pageSize, 5),
        15,
    );

    return (
        <div className={cn('flex w-full flex-col gap-4', className)}>
            {(toolbar || hideableColumns.length > 0) && (
                <div className="flex flex-col gap-3 @3xl/main:flex-row @3xl/main:items-center @3xl/main:justify-between">
                    <div className="min-w-0 flex-1">{toolbar}</div>

                    {hideableColumns.length > 0 && (
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    className="w-full @3xl/main:w-fit"
                                >
                                    <Columns3 className="size-4" />
                                    <span className="hidden sm:inline">
                                        Customize Columns
                                    </span>
                                    <span className="sm:hidden">Columns</span>
                                    <ChevronDown className="size-4" />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" className="w-56">
                                {hideableColumns.map((column) => (
                                    <DropdownMenuCheckboxItem
                                        key={column.id}
                                        className="capitalize"
                                        checked={column.getIsVisible()}
                                        onCheckedChange={(value) =>
                                            column.toggleVisibility(
                                                Boolean(value),
                                            )
                                        }
                                    >
                                        {formatColumnLabel(column.id)}
                                    </DropdownMenuCheckboxItem>
                                ))}
                            </DropdownMenuContent>
                        </DropdownMenu>
                    )}
                </div>
            )}

            <div className="overflow-hidden rounded-lg border bg-card">
                <Table className="min-w-[760px]">
                    <TableHeader className="sticky top-0 z-10 bg-muted/70">
                        {table.getHeaderGroups().map((headerGroup) => (
                            <TableRow
                                key={headerGroup.id}
                                className="hover:bg-transparent"
                            >
                                {headerGroup.headers.map((header) => (
                                    <TableHead
                                        key={header.id}
                                        colSpan={header.colSpan}
                                    >
                                        {header.isPlaceholder ? null : (
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="sm"
                                                className={cn(
                                                    '-ml-2 h-8 px-2 text-muted-foreground hover:bg-transparent hover:text-foreground',
                                                    !header.column.getCanSort() &&
                                                        'pointer-events-none',
                                                )}
                                                onClick={header.column.getToggleSortingHandler()}
                                                disabled={
                                                    !header.column.getCanSort()
                                                }
                                            >
                                                {flexRender(
                                                    header.column.columnDef
                                                        .header,
                                                    header.getContext(),
                                                )}
                                                {header.column.getCanSort() && (
                                                    <SortIcon
                                                        sorted={header.column.getIsSorted()}
                                                    />
                                                )}
                                            </Button>
                                        )}
                                    </TableHead>
                                ))}
                            </TableRow>
                        ))}
                    </TableHeader>
                    <TableBody aria-busy={loading}>
                        {loading ? (
                            <TableLoadingRows
                                columns={visibleColumnCount}
                                rows={loadingRowCount}
                            />
                        ) : rows.length > 0 ? (
                            rows.map((row) => (
                                <TableRow
                                    key={row.id}
                                    data-state={
                                        row.getIsSelected() && 'selected'
                                    }
                                    className={cn(
                                        onRowClick && 'cursor-pointer',
                                    )}
                                    onClick={() => onRowClick?.(row.original)}
                                >
                                    {row.getVisibleCells().map((cell) => (
                                        <TableCell key={cell.id}>
                                            {flexRender(
                                                cell.column.columnDef.cell,
                                                cell.getContext(),
                                            )}
                                        </TableCell>
                                    ))}
                                </TableRow>
                            ))
                        ) : (
                            <TableRow>
                                <TableCell
                                    colSpan={
                                        table.getVisibleLeafColumns().length
                                    }
                                    className="h-32 text-center"
                                >
                                    <div className="flex flex-col items-center justify-center gap-2 text-muted-foreground">
                                        <PackageOpen className="size-8" />
                                        <span className="font-medium text-foreground">
                                            {emptyTitle}
                                        </span>
                                    </div>
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>

                <div className="flex flex-col gap-3 border-t p-3 @3xl/main:flex-row @3xl/main:items-center @3xl/main:justify-between">
                    <div className="flex min-h-8 flex-1 items-center gap-2 text-sm text-muted-foreground">
                        {enableRowSelection && (
                            <>
                                <Badge
                                    variant="secondary"
                                    className="rounded-full"
                                >
                                    {
                                        table.getFilteredSelectedRowModel().rows
                                            .length
                                    }
                                </Badge>
                                of {table.getFilteredRowModel().rows.length}{' '}
                                row(s) selected.
                            </>
                        )}
                        {!enableRowSelection && meta && (
                            <>
                                Showing {meta.from ?? 0} to {meta.to ?? 0} of{' '}
                                {meta.total} results
                            </>
                        )}
                        {!enableRowSelection && !meta && (
                            <>
                                {table.getFilteredRowModel().rows.length} row(s)
                            </>
                        )}
                    </div>

                    <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
                        <div className="hidden items-center gap-2 lg:flex">
                            <Label
                                htmlFor="rows-per-page"
                                className="text-sm font-medium"
                            >
                                Rows per page
                            </Label>
                            <Select
                                value={`${table.getState().pagination.pageSize}`}
                                onValueChange={(value) =>
                                    table.setPageSize(Number(value))
                                }
                            >
                                <SelectTrigger
                                    size="sm"
                                    className="w-20"
                                    id="rows-per-page"
                                >
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent side="top">
                                    {activePageSizeOptions.map((pageSize) => (
                                        <SelectItem
                                            key={pageSize}
                                            value={`${pageSize}`}
                                        >
                                            {pageSize}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="flex items-center justify-between gap-3 sm:justify-end">
                            <div className="flex w-fit items-center justify-center text-sm font-medium whitespace-nowrap">
                                Page {table.getState().pagination.pageIndex + 1}{' '}
                                of {Math.max(table.getPageCount(), 1)}
                            </div>

                            <div className="flex items-center gap-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="icon"
                                    className="hidden size-8 lg:flex"
                                    onClick={() => table.setPageIndex(0)}
                                    disabled={!table.getCanPreviousPage()}
                                >
                                    <span className="sr-only">
                                        Go to first page
                                    </span>
                                    <ChevronsLeft className="size-4" />
                                </Button>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="icon"
                                    className="size-8"
                                    onClick={() => table.previousPage()}
                                    disabled={!table.getCanPreviousPage()}
                                >
                                    <span className="sr-only">
                                        Go to previous page
                                    </span>
                                    <ChevronLeft className="size-4" />
                                </Button>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="icon"
                                    className="size-8"
                                    onClick={() => table.nextPage()}
                                    disabled={!table.getCanNextPage()}
                                >
                                    <span className="sr-only">
                                        Go to next page
                                    </span>
                                    <ChevronRight className="size-4" />
                                </Button>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="icon"
                                    className="hidden size-8 lg:flex"
                                    onClick={() =>
                                        table.setPageIndex(
                                            table.getPageCount() - 1,
                                        )
                                    }
                                    disabled={!table.getCanNextPage()}
                                >
                                    <span className="sr-only">
                                        Go to last page
                                    </span>
                                    <ChevronsRight className="size-4" />
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}

function TableLoadingRows({
    columns,
    rows,
}: {
    columns: number;
    rows: number;
}) {
    return Array.from({ length: rows }, (_, rowIndex) => (
        <TableRow key={`loading-${rowIndex}`} className="hover:bg-transparent">
            {Array.from({ length: columns }, (_, columnIndex) => (
                <TableCell key={`${rowIndex}-${columnIndex}`}>
                    <Skeleton
                        className={cn(
                            'h-5',
                            columnIndex === 0
                                ? 'w-5 rounded-sm'
                                : columnIndex === columns - 1
                                  ? 'ml-auto w-8'
                                  : 'w-full max-w-40',
                        )}
                    />
                </TableCell>
            ))}
        </TableRow>
    ));
}

function SortIcon({ sorted }: { sorted: false | 'asc' | 'desc' }) {
    if (sorted === 'asc') {
        return <ArrowUp className="size-3.5" />;
    }

    if (sorted === 'desc') {
        return <ArrowDown className="size-3.5" />;
    }

    return <ChevronsUpDown className="size-3.5" />;
}

function formatColumnLabel(id: string): string {
    return id.replaceAll('_', ' ').replaceAll('-', ' ');
}
