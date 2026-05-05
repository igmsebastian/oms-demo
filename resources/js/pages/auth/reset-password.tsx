import { Head, router } from '@inertiajs/react';
import { useForm } from '@tanstack/react-form';
import { useState } from 'react';
import { z } from 'zod';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { update } from '@/routes/password';
import type { ServerFormErrors } from '@/shared/forms/errors';
import { TanStackField } from '@/shared/forms/TanStackField';

type Props = {
    token: string;
    email: string;
};

const resetPasswordSchema = z
    .object({
        email: z.string().email('Enter a valid email address.'),
        password: z.string().min(8, 'Password must be at least 8 characters.'),
        password_confirmation: z.string().min(1, 'Confirm your password.'),
    })
    .refine((value) => value.password === value.password_confirmation, {
        message: 'Password confirmation does not match.',
        path: ['password_confirmation'],
    });

export default function ResetPassword({ token, email }: Props) {
    const [processing, setProcessing] = useState(false);
    const [serverErrors, setServerErrors] = useState<ServerFormErrors>({});
    const form = useForm({
        defaultValues: {
            email,
            password: '',
            password_confirmation: '',
        },
        validators: {
            onSubmit: resetPasswordSchema,
        },
        onSubmit: ({ value }) => {
            setProcessing(true);
            setServerErrors({});
            const request = update.post();

            router.visit(request.url, {
                method: request.method,
                data: { ...value, token },
                onError: (backendErrors) => {
                    setServerErrors(backendErrors);
                    form.setFieldValue('password', '');
                    form.setFieldValue('password_confirmation', '');
                },
                onFinish: () => setProcessing(false),
            });
        },
    });

    return (
        <>
            <Head title="Reset password" />

            <form
                onSubmit={(event) => {
                    event.preventDefault();
                    void form.handleSubmit();
                }}
            >
                <div className="grid gap-6">
                    <form.Field
                        name="email"
                        children={(field) => (
                            <TanStackField
                                field={field}
                                label="Email"
                                serverError={serverErrors.email}
                                required
                            >
                                {({ id, name, value, isInvalid }) => (
                                    <Input
                                        id={id}
                                        type="email"
                                        name={name}
                                        autoComplete="email"
                                        value={value}
                                        className="mt-1 block w-full"
                                        readOnly
                                        aria-invalid={isInvalid}
                                    />
                                )}
                            </TanStackField>
                        )}
                    />

                    <form.Field
                        name="password"
                        children={(field) => (
                            <TanStackField
                                field={field}
                                label="Password"
                                serverError={serverErrors.password}
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
                                    <PasswordInput
                                        id={id}
                                        name={name}
                                        autoComplete="new-password"
                                        className="mt-1 block w-full"
                                        autoFocus
                                        placeholder="Password"
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

                    <form.Field
                        name="password_confirmation"
                        children={(field) => (
                            <TanStackField
                                field={field}
                                label="Confirm password"
                                serverError={serverErrors.password_confirmation}
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
                                    <PasswordInput
                                        id={id}
                                        name={name}
                                        autoComplete="new-password"
                                        className="mt-1 block w-full"
                                        placeholder="Confirm password"
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

                    <Button
                        type="submit"
                        className="mt-4 w-full"
                        disabled={processing}
                        data-test="reset-password-button"
                    >
                        {processing && <Spinner />}
                        Reset password
                    </Button>
                </div>
            </form>
        </>
    );
}

ResetPassword.layout = {
    title: 'Reset password',
    description: 'Please enter your new password below',
    background: 'stars',
};
