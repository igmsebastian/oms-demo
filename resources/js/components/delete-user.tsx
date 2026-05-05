import { router } from '@inertiajs/react';
import { useForm } from '@tanstack/react-form';
import { useRef, useState } from 'react';
import { z } from 'zod';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import Heading from '@/components/heading';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { fieldErrors } from '@/shared/forms/errors';
import type { ServerFormErrors } from '@/shared/forms/errors';

const deleteUserSchema = z.object({
    password: z.string().min(1, 'Enter your password to delete your account.'),
});

export default function DeleteUser() {
    const passwordInput = useRef<HTMLInputElement>(null);
    const [processing, setProcessing] = useState(false);
    const [serverErrors, setServerErrors] = useState<ServerFormErrors>({});
    const form = useForm({
        defaultValues: {
            password: '',
        },
        validators: {
            onSubmit: deleteUserSchema,
        },
        onSubmit: ({ value }) => {
            setProcessing(true);
            setServerErrors({});
            const request = ProfileController.destroy.delete();

            router.visit(request.url, {
                method: request.method,
                data: value,
                preserveScroll: true,
                onError: (backendErrors) => {
                    setServerErrors(backendErrors);
                    passwordInput.current?.focus();
                },
                onSuccess: () => form.reset(),
                onFinish: () => setProcessing(false),
            });
        },
    });

    const resetForm = () => {
        form.reset();
        setServerErrors({});
    };

    return (
        <div className="space-y-6">
            <Heading
                variant="small"
                title="Delete account"
                description="Delete your account and all of its resources"
            />
            <div className="space-y-4 rounded-lg border border-red-100 bg-red-50 p-4 dark:border-red-200/10 dark:bg-red-700/10">
                <div className="relative space-y-0.5 text-red-600 dark:text-red-100">
                    <p className="font-medium">Warning</p>
                    <p className="text-sm">
                        Please proceed with caution, this cannot be undone.
                    </p>
                </div>

                <Dialog>
                    <DialogTrigger asChild>
                        <Button
                            variant="destructive"
                            data-test="delete-user-button"
                        >
                            Delete account
                        </Button>
                    </DialogTrigger>
                    <DialogContent>
                        <DialogTitle>
                            Are you sure you want to delete your account?
                        </DialogTitle>
                        <DialogDescription>
                            Once your account is deleted, all of its resources
                            and data will also be permanently deleted. Please
                            enter your password to confirm you would like to
                            permanently delete your account.
                        </DialogDescription>

                        <form
                            className="space-y-6"
                            onSubmit={(event) => {
                                event.preventDefault();
                                void form.handleSubmit();
                            }}
                        >
                            <form.Field
                                name="password"
                                children={(field) => {
                                    const errors = fieldErrors(
                                        field.state.meta.errors,
                                        serverErrors.password,
                                    );
                                    const isInvalid = errors.length > 0;

                                    return (
                                        <Field data-invalid={isInvalid}>
                                            <FieldLabel
                                                htmlFor={field.name}
                                                className="sr-only"
                                                required
                                            >
                                                Password
                                            </FieldLabel>

                                            <PasswordInput
                                                id={field.name}
                                                name={field.name}
                                                ref={passwordInput}
                                                placeholder="Password"
                                                autoComplete="current-password"
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

                            <DialogFooter className="gap-2">
                                <DialogClose asChild>
                                    <Button
                                        type="button"
                                        variant="secondary"
                                        onClick={resetForm}
                                    >
                                        Cancel
                                    </Button>
                                </DialogClose>

                                <Button
                                    type="submit"
                                    variant="destructive"
                                    disabled={processing}
                                    data-test="confirm-delete-user-button"
                                >
                                    Delete account
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>
        </div>
    );
}
