<?php

return [
    'ok' => 'Operation completed successfully.',

    'health' => [
        'ok' => 'All systems operational.',
        'degraded' => 'One or more dependencies are unavailable.',
    ],

    'errors' => [
        'bad_request' => 'The request is invalid.',
        'validation_failed' => 'The given data was invalid.',
        'unauthenticated' => 'Authentication is required.',
        'forbidden' => 'You are not allowed to perform this action.',
        'not_found' => 'The requested resource was not found.',
        'method_not_allowed' => 'This HTTP method is not allowed for this endpoint.',
        'conflict' => 'The request conflicts with the current state of the resource.',
        'payload_too_large' => 'The request payload is too large.',
        'unsupported_media_type' => 'The request media type is not supported.',
        'session_expired' => 'Your session has expired. Please refresh and try again.',
        'too_many_requests' => 'Too many requests. Please try again later.',
        'service_unavailable' => 'The service is temporarily unavailable.',
        'server_error' => 'An unexpected error occurred.',
        'http_error' => 'The request could not be completed.',
    ],
];
