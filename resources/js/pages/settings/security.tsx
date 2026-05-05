import { Head, router } from '@inertiajs/react';
import { useForm } from '@tanstack/react-form';
import { ShieldCheck } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { z } from 'zod';
import SecurityController from '@/actions/App/Http/Controllers/Settings/SecurityController';
import Heading from '@/components/heading';
import PasswordInput from '@/components/password-input';
import TwoFactorRecoveryCodes from '@/components/two-factor-recovery-codes';
import TwoFactorSetupModal from '@/components/two-factor-setup-modal';
import { Button } from '@/components/ui/button';
import { useTwoFactorAuth } from '@/hooks/use-two-factor-auth';
import { edit } from '@/routes/security';
import { disable, enable } from '@/routes/two-factor';
import type { ServerFormErrors } from '@/shared/forms/errors';
import { TanStackField } from '@/shared/forms/TanStackField';

type Props = {
    canManageTwoFactor?: boolean;
    requiresConfirmation?: boolean;
    twoFactorEnabled?: boolean;
};

const passwordSchema = z
    .object({
        current_password: z.string().min(1, 'Enter your current password.'),
        password: z.string().min(8, 'Password must be at least 8 characters.'),
        password_confirmation: z.string().min(1, 'Confirm your new password.'),
    })
    .refine((value) => value.password === value.password_confirmation, {
        message: 'Password confirmation does not match.',
        path: ['password_confirmation'],
    });

export default function Security({
    canManageTwoFactor = false,
    requiresConfirmation = false,
    twoFactorEnabled = false,
}: Props) {
    const passwordInput = useRef<HTMLInputElement>(null);
    const currentPasswordInput = useRef<HTMLInputElement>(null);

    const {
        qrCodeSvg,
        hasSetupData,
        manualSetupKey,
        clearSetupData,
        clearTwoFactorAuthData,
        fetchSetupData,
        recoveryCodesList,
        fetchRecoveryCodes,
        errors,
    } = useTwoFactorAuth();
    const [showSetupModal, setShowSetupModal] = useState<boolean>(false);
    const [passwordProcessing, setPasswordProcessing] = useState(false);
    const [twoFactorProcessing, setTwoFactorProcessing] = useState(false);
    const [serverErrors, setServerErrors] = useState<ServerFormErrors>({});
    const prevTwoFactorEnabled = useRef(twoFactorEnabled);
    const form = useForm({
        defaultValues: {
            current_password: '',
            password: '',
            password_confirmation: '',
        },
        validators: {
            onSubmit: passwordSchema,
        },
        onSubmit: ({ value }) => {
            setPasswordProcessing(true);
            setServerErrors({});
            const request = SecurityController.update.put();

            router.visit(request.url, {
                method: request.method,
                data: value,
                preserveScroll: true,
                onError: (backendErrors) => {
                    setServerErrors(backendErrors);

                    if (backendErrors.password) {
                        passwordInput.current?.focus();
                    }

                    if (backendErrors.current_password) {
                        currentPasswordInput.current?.focus();
                    }
                },
                onSuccess: () => form.reset(),
                onFinish: () => setPasswordProcessing(false),
            });
        },
    });

    useEffect(() => {
        if (prevTwoFactorEnabled.current && !twoFactorEnabled) {
            clearTwoFactorAuthData();
        }

        prevTwoFactorEnabled.current = twoFactorEnabled;
    }, [twoFactorEnabled, clearTwoFactorAuthData]);

    const disableTwoFactor = () => {
        setTwoFactorProcessing(true);
        const request = disable.delete();

        router.visit(request.url, {
            method: request.method,
            preserveScroll: true,
            onFinish: () => setTwoFactorProcessing(false),
        });
    };

    const enableTwoFactor = () => {
        setTwoFactorProcessing(true);
        const request = enable.post();

        router.visit(request.url, {
            method: request.method,
            preserveScroll: true,
            onSuccess: () => setShowSetupModal(true),
            onFinish: () => setTwoFactorProcessing(false),
        });
    };

    return (
        <>
            <Head title="Security settings" />

            <h1 className="sr-only">Security settings</h1>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Update password"
                    description="Ensure your account is using a long, random password to stay secure"
                />

                <form
                    className="space-y-6"
                    onSubmit={(event) => {
                        event.preventDefault();
                        void form.handleSubmit();
                    }}
                >
                    <form.Field
                        name="current_password"
                        children={(field) => (
                            <TanStackField
                                field={field}
                                label="Current password"
                                serverError={serverErrors.current_password}
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
                                        ref={currentPasswordInput}
                                        name={name}
                                        className="mt-1 block w-full"
                                        autoComplete="current-password"
                                        placeholder="Current password"
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
                                label="New password"
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
                                        ref={passwordInput}
                                        name={name}
                                        className="mt-1 block w-full"
                                        autoComplete="new-password"
                                        placeholder="New password"
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
                                        className="mt-1 block w-full"
                                        autoComplete="new-password"
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

                    <div className="flex items-center gap-4">
                        <Button
                            disabled={passwordProcessing}
                            data-test="update-password-button"
                        >
                            Save password
                        </Button>
                    </div>
                </form>
            </div>

            {canManageTwoFactor && (
                <div className="space-y-6">
                    <Heading
                        variant="small"
                        title="Two-factor authentication"
                        description="Manage your two-factor authentication settings"
                    />
                    {twoFactorEnabled ? (
                        <div className="flex flex-col items-start justify-start space-y-4">
                            <p className="text-sm text-muted-foreground">
                                You will be prompted for a secure, random pin
                                during login, which you can retrieve from the
                                TOTP-supported application on your phone.
                            </p>

                            <div className="relative inline">
                                <Button
                                    variant="destructive"
                                    type="button"
                                    disabled={twoFactorProcessing}
                                    onClick={disableTwoFactor}
                                >
                                    Disable 2FA
                                </Button>
                            </div>

                            <TwoFactorRecoveryCodes
                                recoveryCodesList={recoveryCodesList}
                                fetchRecoveryCodes={fetchRecoveryCodes}
                                errors={errors}
                            />
                        </div>
                    ) : (
                        <div className="flex flex-col items-start justify-start space-y-4">
                            <p className="text-sm text-muted-foreground">
                                When you enable two-factor authentication, you
                                will be prompted for a secure pin during login.
                                This pin can be retrieved from a TOTP-supported
                                application on your phone.
                            </p>

                            <div>
                                {hasSetupData ? (
                                    <Button
                                        onClick={() => setShowSetupModal(true)}
                                    >
                                        <ShieldCheck />
                                        Continue setup
                                    </Button>
                                ) : (
                                    <Button
                                        type="button"
                                        disabled={twoFactorProcessing}
                                        onClick={enableTwoFactor}
                                    >
                                        Enable 2FA
                                    </Button>
                                )}
                            </div>
                        </div>
                    )}

                    <TwoFactorSetupModal
                        isOpen={showSetupModal}
                        onClose={() => setShowSetupModal(false)}
                        requiresConfirmation={requiresConfirmation}
                        twoFactorEnabled={twoFactorEnabled}
                        qrCodeSvg={qrCodeSvg}
                        manualSetupKey={manualSetupKey}
                        clearSetupData={clearSetupData}
                        fetchSetupData={fetchSetupData}
                        errors={errors}
                    />
                </div>
            )}
        </>
    );
}

Security.layout = {
    breadcrumbs: [
        {
            title: 'Security settings',
            href: edit(),
        },
    ],
};
