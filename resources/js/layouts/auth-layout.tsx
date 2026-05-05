import AuthLayoutTemplate from '@/layouts/auth/auth-simple-layout';

export default function AuthLayout({
    title = '',
    description = '',
    background,
    children,
}: {
    title?: string;
    description?: string;
    background?: 'stars';
    children: React.ReactNode;
}) {
    return (
        <AuthLayoutTemplate
            title={title}
            description={description}
            background={background}
        >
            {children}
        </AuthLayoutTemplate>
    );
}
