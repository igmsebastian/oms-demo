// Components
import { Head, router } from '@inertiajs/react';
import { useForm } from '@tanstack/react-form';
import { LoaderCircle } from 'lucide-react';
import { useState } from 'react';
import { z } from 'zod';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { login } from '@/routes';
import { email } from '@/routes/password';
import type { ServerFormErrors } from '@/shared/forms/errors';
import { TanStackField } from '@/shared/forms/TanStackField';

const forgotPasswordSchema = z.object({
    email: z.string().email('Enter a valid email address.'),
});

export default function ForgotPassword({ status }: { status?: string }) {
    const [processing, setProcessing] = useState(false);
    const [serverErrors, setServerErrors] = useState<ServerFormErrors>({});
    const form = useForm({
        defaultValues: {
            email: '',
        },
        validators: {
            onSubmit: forgotPasswordSchema,
        },
        onSubmit: ({ value }) => {
            setProcessing(true);
            setServerErrors({});
            const request = email.post();

            router.visit(request.url, {
                method: request.method,
                data: value,
                onError: setServerErrors,
                onFinish: () => setProcessing(false),
            });
        },
    });

    return (
        <>
            <Head title="Forgot password" />

            {status && (
                <div className="mb-4 text-center text-sm font-medium text-green-600">
                    {status}
                </div>
            )}

            <div className="space-y-6">
                <form
                    onSubmit={(event) => {
                        event.preventDefault();
                        void form.handleSubmit();
                    }}
                >
                    <form.Field
                        name="email"
                        children={(field) => (
                            <TanStackField
                                field={field}
                                label="Email address"
                                serverError={serverErrors.email}
                                required
                            >
                                {({
                                    id,
                                    name,
                                    value,
                                    isInvalid,
                                    onBlur,
                                    onChange,
                                }) => (
                                    <Input
                                        id={id}
                                        type="email"
                                        name={name}
                                        autoComplete="off"
                                        autoFocus
                                        placeholder="email@mydemo.com"
                                        value={value}
                                        onBlur={onBlur}
                                        onChange={(event) =>
                                            onChange(event.target.value)
                                        }
                                        aria-invalid={isInvalid}
                                    />
                                )}
                            </TanStackField>
                        )}
                    />

                    <div className="my-6 flex items-center justify-start">
                        <Button
                            className="w-full"
                            disabled={processing}
                            data-test="email-password-reset-link-button"
                        >
                            {processing && (
                                <LoaderCircle className="h-4 w-4 animate-spin" />
                            )}
                            Email password reset link
                        </Button>
                    </div>
                </form>

                <div className="space-x-1 text-center text-sm text-muted-foreground">
                    <span>Or, return to</span>
                    <TextLink href={login()}>log in</TextLink>
                </div>
            </div>
        </>
    );
}

ForgotPassword.layout = {
    title: 'Forgot password',
    description: 'Enter your email to receive a password reset link',
    background: 'stars',
};
