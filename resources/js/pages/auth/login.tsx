import { Head, router } from '@inertiajs/react';
import { useForm } from '@tanstack/react-form';
import { useState } from 'react';
import { z } from 'zod';
import PasswordInput from '@/components/password-input';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/login';
import { request } from '@/routes/password';
import type { ServerFormErrors } from '@/shared/forms/errors';
import { TanStackField } from '@/shared/forms/TanStackField';

const loginSchema = z.object({
    email: z.string().email('Enter a valid email address.'),
    password: z.string().min(1, 'Enter your password.'),
    remember: z.boolean(),
});

type Props = {
    status?: string;
    canResetPassword: boolean;
};

export default function Login({ status, canResetPassword }: Props) {
    const [processing, setProcessing] = useState(false);
    const [serverErrors, setServerErrors] = useState<ServerFormErrors>({});
    const form = useForm({
        defaultValues: {
            email: '',
            password: '',
            remember: false,
        },
        validators: {
            onSubmit: loginSchema,
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
                },
                onFinish: () => setProcessing(false),
            });
        },
    });

    const fillAdminCredentials = () => {
        form.setFieldValue('email', 'basty@mydemo.com');
        form.setFieldValue('password', 'password');
    };

    return (
        <>
            <Head title="Log in" />

            <form
                className="flex flex-col gap-6"
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
                                        required
                                        autoFocus
                                        tabIndex={1}
                                        autoComplete="email"
                                        placeholder="basty@mydemo.com"
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
                                    <div className="grid gap-2">
                                        <div className="flex justify-end">
                                            {canResetPassword && (
                                                <TextLink
                                                    href={request()}
                                                    className="text-sm"
                                                    tabIndex={6}
                                                >
                                                    Forgot password?
                                                </TextLink>
                                            )}
                                        </div>
                                        <PasswordInput
                                            id={id}
                                            name={name}
                                            required
                                            tabIndex={2}
                                            autoComplete="current-password"
                                            placeholder="Password"
                                            value={value}
                                            onBlur={onBlur}
                                            onChange={(event) =>
                                                onChange(event.target.value)
                                            }
                                            aria-invalid={isInvalid}
                                        />
                                    </div>
                                )}
                            </TanStackField>
                        )}
                    />

                    <Button
                        type="button"
                        variant="outline"
                        className="w-full"
                        tabIndex={3}
                        onClick={fillAdminCredentials}
                    >
                        Use admin account
                    </Button>

                    <form.Field
                        name="remember"
                        children={(field) => (
                            <div className="flex items-center space-x-3">
                                <Checkbox
                                    id={field.name}
                                    name={field.name}
                                    tabIndex={4}
                                    checked={field.state.value}
                                    onCheckedChange={(checked) =>
                                        field.handleChange(checked === true)
                                    }
                                />
                                <Label htmlFor={field.name}>Remember me</Label>
                            </div>
                        )}
                    />

                    <Button
                        type="submit"
                        className="mt-4 w-full"
                        tabIndex={5}
                        disabled={processing}
                        data-test="login-button"
                    >
                        {processing && <Spinner />}
                        Log in
                    </Button>
                </div>
            </form>

            {status && (
                <div className="mb-4 text-center text-sm font-medium text-green-600">
                    {status}
                </div>
            )}
        </>
    );
}

Login.layout = {
    title: 'Log in to your account',
    description: 'Enter your email and password below to log in',
    background: 'stars',
};
