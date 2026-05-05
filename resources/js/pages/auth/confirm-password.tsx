import { Head, router } from '@inertiajs/react';
import { useForm } from '@tanstack/react-form';
import { useState } from 'react';
import { z } from 'zod';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/password/confirm';
import type { ServerFormErrors } from '@/shared/forms/errors';
import { TanStackField } from '@/shared/forms/TanStackField';

const confirmPasswordSchema = z.object({
    password: z.string().min(1, 'Enter your password.'),
});

export default function ConfirmPassword() {
    const [processing, setProcessing] = useState(false);
    const [serverErrors, setServerErrors] = useState<ServerFormErrors>({});
    const form = useForm({
        defaultValues: {
            password: '',
        },
        validators: {
            onSubmit: confirmPasswordSchema,
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

    return (
        <>
            <Head title="Confirm password" />

            <form
                onSubmit={(event) => {
                    event.preventDefault();
                    void form.handleSubmit();
                }}
            >
                <div className="space-y-6">
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
                                        placeholder="Password"
                                        autoComplete="current-password"
                                        autoFocus
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

                    <div className="flex items-center">
                        <Button
                            className="w-full"
                            disabled={processing}
                            data-test="confirm-password-button"
                        >
                            {processing && <Spinner />}
                            Confirm password
                        </Button>
                    </div>
                </div>
            </form>
        </>
    );
}

ConfirmPassword.layout = {
    title: 'Confirm your password',
    description:
        'This is a secure area of the application. Please confirm your password before continuing.',
};
