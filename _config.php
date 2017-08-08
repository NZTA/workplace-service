<?php
// WORKPLACE API Config

// Sets the base API endpoint for all api calls.
if (!defined('SS_WORKPLACE_GATEWAY_REST_URL')) {
    define('SS_WORKPLACE_GATEWAY_REST_URL', 'https://graph.facebook.com');
}

// The community id,token parameter used in the Workplace API link endpoint should be defined.
if (!defined('SS_WORKPLACE_BEARER_TOKEN') || !defined('SS_WORKPLACE_COMMUNITY_ID')) {
    user_error('Make sure that SS_WORKPLACE_BEARER_TOKEN and SS_WORKPLACE_COMMUNITY_ID constants are defined',E_USER_ERROR);
}

// Default cache lifetime for {@link workplaceHpmageGateway} ->call().
if (!defined('SS_WORKPLACE_HOMEPAGE_GATEWAY_CACHE_LIFETIME')) {
    define('SS_WORKPLACE_HOMEPAGE_GATEWAY_CACHE_LIFETIME', 60);
}

