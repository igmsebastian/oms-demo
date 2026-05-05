import { Head, router, setLayoutProps } from '@inertiajs/react';
import { useForm } from '@tanstack/react-form';
import { REGEXP_ONLY_DIGITS } from 'input-otp';
import { useMemo, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Field, FieldError } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import {
    InputOTP,
    InputOTPGroup,
    InputOTPSlot,
} from '@/components/ui/input-otp';
import { OTP_MAX_LENGTH } from '@/hooks/use-two-factor-auth';
import { store } from '@/routes/two-factor/login';
import { fieldErrors } from '@/shared/forms/errors';
import type { ServerFormErrors } from '@/shared/forms/errors';

export default function TwoFactorChallenge() {
    const [showRecoveryInput, setShowRecoveryInput] = useState<boolean>(false);
    const [processing, setProcessing] = useState(false);
    const [serverErrors, setServerErrors] = useState<ServerFormErrors>({});
    const form = useForm({
        defaultValues: {
            code: '',
            recovery_code: '',
        },
        onSubmit: ({ value }) => {
            setProcessing(true);
            setServerErrors({});
            const request = store.post();

            router.visit(request.url, {
                method: request.method,
                data: showRecoveryInput
                    ? { recovery_code: value.recovery_code }
                    : { code: value.code },
                onError: (backendErrors) => {
                    setServerErrors(backendErrors);
                    form.reset();
                },
                onFinish: () => setProcessing(false),
            });
        },
    });

    const authConfigContent = useMemo<{
        title: string;
        description: string;
        toggleText: string;
    }>(() => {
        if (showRecoveryInput) {
            return {
                title: 'Recovery code',
                description:
                    'Please confirm access to your account by entering one of your emergency recovery codes.',
                toggleText: 'login using an authentication code',
            };
        }

        return {
            title: 'Authentication code',
            description:
                'Enter the authentication code provided by your authenticator application.',
            toggleText: 'login using a recovery code',
        };
    }, [showRecoveryInput]);

    setLayoutProps({
        title: authConfigContent.title,
        description: authConfigContent.description,
    });

    const toggleRecoveryMode = (): void => {
        setShowRecoveryInput(!showRecoveryInput);
        setServerErrors({});
        form.reset();
    };

    return (
        <>
            <Head title="Two-factor authentication" />

            <div className="space-y-6">
                <form
                    className="space-y-4"
                    onSubmit={(event) => {
                        event.preventDefault();
                        void form.handleSubmit();
                    }}
                >
                    {showRecoveryInput ? (
                        <form.Field
                            name="recovery_code"
                            children={(field) => {
                                const errors = fieldErrors(
                                    field.state.meta.errors,
                                    serverErrors.recovery_code,
                                );
                                const isInvalid = errors.length > 0;

                                return (
                                    <Field data-invalid={isInvalid}>
                                        <Input
                                            name={field.name}
                                            type="text"
                                            placeholder="Enter recovery code"
                                            autoFocus={showRecoveryInput}
                                            required
                                            value={field.state.value}
                                            onBlur={field.handleBlur}
                                            onChange={(event) =>
                                                field.handleChange(
                                                    event.target.value,
                                                )
                                            }
                                            aria-invalid={isInvalid}
                                        />
                                        <FieldError errors={errors} />
                                    </Field>
                                );
                            }}
                        />
                    ) : (
                        <form.Field
                            name="code"
                            children={(field) => {
                                const errors = fieldErrors(
                                    field.state.meta.errors,
                                    serverErrors.code,
                                );
                                const isInvalid = errors.length > 0;

                                return (
                                    <Field
                                        data-invalid={isInvalid}
                                        className="flex flex-col items-center justify-center space-y-3 text-center"
                                    >
                                        <div className="flex w-full items-center justify-center">
                                            <InputOTP
                                                name={field.name}
                                                maxLength={OTP_MAX_LENGTH}
                                                value={field.state.value}
                                                onChange={(value) =>
                                                    field.handleChange(value)
                                                }
                                                disabled={processing}
                                                pattern={REGEXP_ONLY_DIGITS}
                                                autoFocus
                                                aria-invalid={isInvalid}
                                            >
                                                <InputOTPGroup>
                                                    {Array.from(
                                                        {
                                                            length: OTP_MAX_LENGTH,
                                                        },
                                                        (_, index) => (
                                                            <InputOTPSlot
                                                                key={index}
                                                                index={index}
                                                            />
                                                        ),
                                                    )}
                                                </InputOTPGroup>
                                            </InputOTP>
                                        </div>
                                        <FieldError errors={errors} />
                                    </Field>
                                );
                            }}
                        />
                    )}

                    <Button
                        type="submit"
                        className="w-full"
                        disabled={processing}
                    >
                        Continue
                    </Button>

                    <div className="text-center text-sm text-muted-foreground">
                        <span>or you can </span>
                        <button
                            type="button"
                            className="cursor-pointer text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                            onClick={toggleRecoveryMode}
                        >
                            {authConfigContent.toggleText}
                        </button>
                    </div>
                </form>
            </div>
        </>
    );
}
