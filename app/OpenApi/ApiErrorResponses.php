<?php

namespace App\OpenApi;

final class ApiErrorResponses
{
    public const Error = 'array{message: string}';

    public const ValidationError = 'array{message: string, errors: array<string, string[]>}';

    public const Unauthenticated = [
        'message' => 'Please log in to continue.',
    ];

    public const MethodNotAllowed = [
        'message' => 'This action is not available for the requested method.',
    ];

    public const ValidationFailed = [
        'message' => 'Please review the highlighted fields and try again.',
        'errors' => [
            'field' => [
                'Please enter this field.',
            ],
        ],
    ];

    public const ServerError = [
        'message' => 'Something went wrong. Please try again.',
    ];
}
