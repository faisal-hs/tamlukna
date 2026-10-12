<?php

return [
    'ok' => 'Operation completed successfully.',

    'health' => [
        'ok' => 'All systems operational.',
        'degraded' => 'One or more dependencies are unavailable.',
    ],

    'auth' => [
        'registered' => 'Account created successfully.',
        'logged_in' => 'Logged in successfully.',
        'logged_out' => 'Logged out successfully.',
        'logged_out_all' => 'Logged out from all devices.',
        'reset_link_sent' => 'If an account exists for this email address, a password reset link will be sent to it.',
        'password_reset' => 'Your password has been reset. Please log in again.',
        'email_verified' => 'Your email address has been verified.',
        'email_already_verified' => 'Your email address is already verified.',
        'verification_sent' => 'A new verification link will be sent to your email address.',
    ],

    'properties' => [
        'created' => 'Listing created as a draft.',
        'updated' => 'Listing updated.',
        'deleted' => 'Listing deleted.',
        'submitted' => 'Listing submitted for review.',
        'paused' => 'Listing paused.',
        'resumed' => 'Listing is live again.',
    ],

    'errors' => [
        'bad_request' => 'The request is invalid.',
        'validation_failed' => 'The given data was invalid.',
        'unauthenticated' => 'Authentication is required.',
        'forbidden' => 'You are not allowed to perform this action.',
        'account_inactive' => 'This account is not active. Please contact support.',
        'invalid_verification_link' => 'This verification link is invalid or has expired.',
        'invalid_status_transition' => 'This action is not allowed for the current listing status.',
        'listing_not_editable' => 'This listing cannot be edited while it is under review.',
        'email_not_verified' => 'Please verify your email address first.',
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
