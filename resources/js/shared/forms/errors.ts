export type ServerFormErrors = Record<string, string | undefined>;

export function fieldErrors(
    clientErrors: unknown[] | undefined,
    serverError?: string,
): string[] {
    return errorMessages([...(clientErrors ?? []), serverError]);
}

export function errorMessages(errors: unknown[] | undefined): string[] {
    return Array.from(
        new Set(
            (errors ?? [])
                .map((error) => errorMessage(error))
                .filter((message): message is string => Boolean(message)),
        ),
    );
}

function errorMessage(error: unknown): string | null {
    if (!error) {
        return null;
    }

    if (typeof error === 'string') {
        return error;
    }

    if (
        typeof error === 'object' &&
        'message' in error &&
        typeof error.message === 'string'
    ) {
        return error.message;
    }

    return null;
}
