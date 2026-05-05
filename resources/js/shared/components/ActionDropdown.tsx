import type { LucideIcon } from 'lucide-react';
import { MoreHorizontal } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

export type ActionDropdownItem = {
    label: string;
    icon: LucideIcon;
    destructive?: boolean;
    onSelect: () => void;
};

type ActionDropdownProps = {
    items: ActionDropdownItem[];
};

export function ActionDropdown({ items }: ActionDropdownProps) {
    if (items.length === 0) {
        return null;
    }

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button variant="ghost" size="icon" onClick={(event) => event.stopPropagation()}>
                    <MoreHorizontal className="size-4" />
                    <span className="sr-only">Open actions</span>
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
                {items.map((item) => (
                    <DropdownMenuItem
                        key={item.label}
                        className={item.destructive ? 'text-destructive focus:text-destructive' : undefined}
                        onClick={(event) => {
                            event.stopPropagation();
                            item.onSelect();
                        }}
                    >
                        <item.icon className="size-4" />
                        {item.label}
                    </DropdownMenuItem>
                ))}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
