<?php
// WORKPLACE API Config
define('SS_WORKPLACE_BEARER_TOKEN', 'DQVJ0X284YzNTMGNTUTlEV0I3b1puTF9KSjk3OE5ZAVlNZAUFdjZA3hqY2NUVnNZAdUFVdV9HUk44NTFZAdFA0VmN6ODZAXcWtyVjFJV0ZAHZAmtqYW55VEthV3dmUnBHUjJXUWVnakRfZAXVpMWctZA1JNWWMwenlRMERDc1BodjVfQWRMMDRmRVBrTGtWV08xNFVzNWdLZAGNQbkp5N2RfMEZAmQXF1UkY5VjAtWHRoeHpzejNESTk3cEwxcTZAJLXR4NXV2a1BXcmNRZA2JB');
define('SS_WORKPLACE_COMMUNITY_ID', '533904803469467');

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

