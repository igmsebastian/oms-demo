import { Head, Link, router, usePage } from '@inertiajs/react';
import { useForm } from '@tanstack/react-form';
import { useState } from 'react';
import { z } from 'zod';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { edit } from '@/routes/profile';
import { send } from '@/routes/verification';
import type { ServerFormErrors } from '@/shared/forms/errors';
import { TanStackField } from '@/shared/forms/TanStackField';

const profileSchema = z.object({
    first_name: z.string().trim().min(1, 'Enter first name.'),
    middle_name: z.string(),
    last_name: z.string().trim().min(1, 'Enter last name.'),
    email: z.string().email('Enter a valid email address.'),
});

export default function Profile({
    mustVerifyEmail,
    status,
}: {
    mustVerifyEmail: boolean;
    status?: string;
}) {
    const { auth } = usePage().props;
    const [processing, setProcessing] = useState(false);
    const [serverErrors, setServerErrors] = useState<ServerFormErrors>({});
    const form = useForm({
        defaultValues: {
            first_name: auth.user.first_name ?? '',
            middle_name: auth.user.middle_name ?? '',
            last_name: auth.user.last_name ?? '',
            email: auth.user.email,
        },
        validators: {
            onSubmit: profileSchema,
        },
        onSubmit: ({ value }) => {
            setProcessing(true);
            setServerErrors({});
            const request = ProfileController.update.patch();

            router.visit(request.url, {
                method: request.method,
                data: value,
                preserveScroll: true,
                onError: setServerErrors,
                onFinish: () => setProcessing(false),
            });
        },
    });

    return (
        <>
            <Head title="Profile settings" />

            <h1 className="sr-only">Profile settings</h1>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Profile information"
                    description="Update your name and email address"
                />

                <form
                    className="space-y-6"
                    onSubmit={(event) => {
                        event.preventDefault();
                        void form.handleSubmit();
                    }}
                >
                    <div className="grid gap-2 md:grid-cols-3">
                        <form.Field
                            name="first_name"
                            children={(field) => (
                                <TanStackField
                                    field={field}
                                    label="First name"
                                    serverError={serverErrors.first_name}
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
                                            className="mt-1 block w-full"
                                            name={name}
                                            required
                                            autoComplete="given-name"
                                            placeholder="First name"
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
                            name="middle_name"
                            children={(field) => (
                                <TanStackField
                                    field={field}
                                    label="Middle name"
                                    serverError={serverErrors.middle_name}
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
                                            className="mt-1 block w-full"
                                            name={name}
                                            autoComplete="additional-name"
                                            placeholder="Middle name"
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
                            name="last_name"
                            children={(field) => (
                                <TanStackField
                                    field={field}
                                    label="Last name"
                                    serverError={serverErrors.last_name}
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
                                            className="mt-1 block w-full"
                                            name={name}
                                            required
                                            autoComplete="family-name"
                                            placeholder="Last name"
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
                    </div>

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
                                        className="mt-1 block w-full"
                                        name={name}
                                        required
                                        autoComplete="username"
                                        placeholder="Email address"
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

                    {mustVerifyEmail &&
                        auth.user.email_verified_at === null && (
                            <div>
                                <p className="-mt-4 text-sm text-muted-foreground">
                                    Your email address is unverified.{' '}
                                    <Link
                                        href={send()}
                                        as="button"
                                        className="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                                    >
                                        Click here to resend the verification
                                        email.
                                    </Link>
                                </p>

                                {status === 'verification-link-sent' && (
                                    <div className="mt-2 text-sm font-medium text-green-600">
                                        A new verification link has been sent to
                                        your email address.
                                    </div>
                                )}
                            </div>
                        )}

                    <div className="flex items-center gap-4">
                        <Button
                            disabled={processing}
                            data-test="update-profile-button"
                        >
                            Save
                        </Button>
                    </div>
                </form>
            </div>
        </>
    );
}

Profile.layout = {
    breadcrumbs: [
        {
            title: 'Profile settings',
            href: edit(),
        },
    ],
};
