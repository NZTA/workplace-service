# workplace-service
Provides the integration with Workplace facebook service.

## Requirements
SilverStripe 4.x

## Installation

`composer require nzta/silverstripe-workplace`

## Environment Variables

- `SS_WORKPLACE_BEARER_TOKEN` (required)
- `SS_WORKPLACE_COMMUNITY_ID` (required)
- `SS_WORKPLACE_GATEWAY_REST_URL` (default is `"https://graph.facebook.com"`)
- `SS_WORKPLACE_HOMEPAGE_GATEWAY_CACHE_LIFETIME` (default value is _60_)