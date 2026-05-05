import { Head, router } from '@inertiajs/react';
import { useForm } from '@tanstack/react-form';
import { useState } from 'react';
import { z } from 'zod';
import PasswordInput from '@/components/password-input';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { store } from '@/routes/register';
import type { ServerFormErrors } from '@/shared/forms/errors';
import { TanStackField } from '@/shared/forms/TanStackField';

const registerSchema = z
    .object({
        name: z.string().trim().min(1, 'Enter your name.'),
        email: z.string().email('Enter a valid email address.'),
        password: z.string().min(8, 'Password must be at least 8 characters.'),
        password_confirmation: z.string().min(1, 'Confirm your password.'),
    })
    .refine((value) => value.password === value.password_confirmation, {
        message: 'Password confirmation does not match.',
        path: ['password_confirmation'],
    });

export default function Register() {
    const [processing, setProcessing] = useState(false);
    const [serverErrors, setServerErrors] = useState<ServerFormErrors>({});
    const form = useForm({
        defaultValues: {
            name: '',
            email: '',
            password: '',
            password_confirmation: '',
        },
        validators: {
            onSubmit: registerSchema,
        },
        onSubmit: ({ value }) => {
            setProcessing(true);
            setServerErrors({});
            const request = store.post();

            router.visit(request.url, {
                method: request.method,
                data: value,
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
            <Head title="Register" />
            <form
                className="flex flex-col gap-6"
                onSubmit={(event) => {
                    event.preventDefault();
                    void form.handleSubmit();
                }}
            >
                <div className="grid gap-6">
                    <form.Field
                        name="name"
                        children={(field) => (
                            <TanStackField
                                field={field}
                                label="Name"
                                serverError={serverErrors.name}
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
                                        type="text"
                                        required
                                        autoFocus
                                        tabIndex={1}
                                        autoComplete="name"
                                        name={name}
                                        placeholder="Full name"
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
                                        required
                                        tabIndex={2}
                                        autoComplete="email"
                                        name={name}
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
                                        required
                                        tabIndex={3}
                                        autoComplete="new-password"
                                        name={name}
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
                                        required
                                        tabIndex={4}
                                        autoComplete="new-password"
                                        name={name}
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
                        className="mt-2 w-full"
                        tabIndex={5}
                        disabled={processing}
                        data-test="register-user-button"
                    >
                        {processing && <Spinner />}
                        Create account
                    </Button>
                </div>

                <div className="text-center text-sm text-muted-foreground">
                    Already have an account?{' '}
                    <TextLink href={login()} tabIndex={6}>
                        Log in
                    </TextLink>
                </div>
            </form>
        </>
    );
}

Register.layout = {
    title: 'Create an account',
    description: 'Enter your details below to create your account',
};
