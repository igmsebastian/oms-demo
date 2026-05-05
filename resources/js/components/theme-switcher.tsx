import type { LucideIcon } from 'lucide-react';
import { Monitor, Moon, Sun } from 'lucide-react';
import type { Appearance } from '@/hooks/use-appearance';
import { useAppearance } from '@/hooks/use-appearance';
import { cn } from '@/lib/utils';
import { Button } from './ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuLabel,
    DropdownMenuRadioGroup,
    DropdownMenuRadioItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from './ui/dropdown-menu';
import { Tooltip, TooltipContent, TooltipTrigger } from './ui/tooltip';

const themeOptions: { value: Appearance; icon: LucideIcon; label: string }[] = [
    { value: 'light', icon: Sun, label: 'Light' },
    { value: 'dark', icon: Moon, label: 'Dark' },
    { value: 'system', icon: Monitor, label: 'System Theme' },
];

const isAppearance = (value: string): value is Appearance => {
    return themeOptions.some((option) => option.value === value);
};

export function ThemeSwitcher() {
    const { appearance, updateAppearance } = useAppearance();

    return (
        <DropdownMenu>
            <Tooltip>
                <TooltipTrigger asChild>
                    <DropdownMenuTrigger asChild>
                        <Button
                            variant="ghost"
                            size="icon"
                            className="size-9 overflow-hidden"
                            aria-label="Theme"
                        >
                            <span className="relative size-4">
                                <Sun
                                    className={cn(
                                        'absolute inset-0 size-4 transition-all duration-300 ease-out',
                                        appearance === 'light'
                                            ? 'scale-100 rotate-0 opacity-100'
                                            : 'scale-50 -rotate-90 opacity-0',
                                    )}
                                />
                                <Moon
                                    className={cn(
                                        'absolute inset-0 size-4 transition-all duration-300 ease-out',
                                        appearance === 'dark'
                                            ? 'scale-100 rotate-0 opacity-100'
                                            : 'scale-50 rotate-90 opacity-0',
                                    )}
                                />
                                <Monitor
                                    className={cn(
                                        'absolute inset-0 size-4 transition-all duration-300 ease-out',
                                        appearance === 'system'
                                            ? 'scale-100 rotate-0 opacity-100'
                                            : 'scale-50 rotate-180 opacity-0',
                                    )}
                                />
                            </span>
                        </Button>
                    </DropdownMenuTrigger>
                </TooltipTrigger>
                <TooltipContent>Theme</TooltipContent>
            </Tooltip>
            <DropdownMenuContent align="end" className="w-48">
                <DropdownMenuLabel>Theme</DropdownMenuLabel>
                <DropdownMenuSeparator />
                <DropdownMenuRadioGroup
                    value={appearance}
                    onValueChange={(value) => {
                        if (isAppearance(value)) {
                            updateAppearance(value);
                        }
                    }}
                >
                    {themeOptions.map(({ value, icon: Icon, label }) => (
                        <DropdownMenuRadioItem key={value} value={value}>
                            <Icon className="size-4" />
                            {label}
                        </DropdownMenuRadioItem>
                    ))}
                </DropdownMenuRadioGroup>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
