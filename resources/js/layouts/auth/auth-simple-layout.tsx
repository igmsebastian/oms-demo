import { Link } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import { dashboard } from '@/routes';
import type { AuthLayoutProps } from '@/types';
import { StarsBackground } from '../../../../components/animate-ui/components/backgrounds/stars';

export default function AuthSimpleLayout({
    children,
    title,
    description,
    background,
}: AuthLayoutProps) {
    const content = (
        <div className="w-full max-w-sm">
            <div className="flex flex-col gap-8">
                <div className="flex flex-col items-center gap-4">
                    <Link
                        href={dashboard()}
                        className="flex flex-col items-center gap-2 font-medium"
                    >
                        <div className="mb-1 flex h-9 w-9 items-center justify-center rounded-md">
                            <AppLogoIcon className="size-9 fill-current text-[var(--foreground)] dark:text-white" />
                        </div>
                        <span className="sr-only">{title}</span>
                    </Link>

                    <div className="space-y-2 text-center">
                        <h1 className="text-xl font-medium">{title}</h1>
                        <p className="text-center text-sm text-muted-foreground">
                            {description}
                        </p>
                    </div>
                </div>
                {children}
            </div>
        </div>
    );

    if (background !== 'stars') {
        return (
            <div className="flex min-h-svh flex-col items-center justify-center gap-6 bg-background p-6 md:p-10">
                {content}
            </div>
        );
    }

    return (
        <StarsBackground
            pointerEvents={false}
            className="flex min-h-svh flex-col items-center justify-center p-6 md:p-10"
        >
            <div className="relative z-10 w-full max-w-sm rounded-xl border border-white/15 bg-card/95 p-6 shadow-2xl shadow-black/30 backdrop-blur-md md:p-8 dark:bg-card/90">
                {content}
            </div>
        </StarsBackground>
    );
}
