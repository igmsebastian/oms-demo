import { useFlashToast } from '@/hooks/use-flash-toast';
import { useAppearance } from '@/hooks/use-appearance';
import { CheckCircle2, CircleX, Info, TriangleAlert } from 'lucide-react';
import { Toaster as Sonner, type ToasterProps } from 'sonner';

function Toaster({ ...props }: ToasterProps) {
    const { appearance } = useAppearance();

    useFlashToast();

    return (
        <Sonner
            theme={appearance}
            className="toaster group"
            position="top-right"
            richColors
            icons={{
                success: <CheckCircle2 className="size-4" />,
                error: <CircleX className="size-4" />,
                warning: <TriangleAlert className="size-4" />,
                info: <Info className="size-4" />,
            }}
            toastOptions={{
                classNames: {
                    toast: 'shadow-lg',
                    title: 'font-medium',
                    description: 'text-sm',
                },
            }}
            style={
                {
                    '--normal-bg': 'var(--popover)',
                    '--normal-text': 'var(--popover-foreground)',
                    '--normal-border': 'var(--border)',
                } as React.CSSProperties
            }
            {...props}
        />
    );
}

export { Toaster };
