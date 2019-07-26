<?php
namespace NZTA\Workplace\Gateways;

use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use GuzzleHttp\Exception\ClientException;
use SilverStripe\Core\Environment;

/**
 * Handles the direct calls to the Workplace API.
 * The calls are made to endpoints using a {@link Client}.
 */
class WorkplaceGateway
{
    /**
     * The amount of groups that will received from Workplace
     * The number is keep growing in 2019, there are 348 groups
     * 
     * @var integer
     */
    private static $workplace_group_limit = 500;

    /**
     * Define return fields from workplace
     * E.g
     * 1. link,email,name
     * 2. name,link,messages{message,attachments}
     *
     * @var string
     */
    private static $workplace_profile_return_fields = 'link';

    /**
     * Get a feed from a specified group from Facebook Workplace.
     *
     * @param int $groupID
     * @param int $limit
     *
     * @return null|string
     */
    public function getWorkplacePostsFromGroup($groupID, $limit)
    {
        return $this->call('get', sprintf(
            '%d/feed?fields=permalink_url,message,description,formatting,created_time,type,from{name,picture,link}%s',
            $groupID,
            $limit ? "&limit={$limit}" : ''
        ));
    }


    /**
     * Get all facebook workplace groups
     *
     * @return null|string
     */
    public function getAllGroups()
    {
        return $this->call('get', sprintf(
            '%d/groups?fields=id,name&limit=%d',
            Environment::getEnv('SS_WORKPLACE_COMMUNITY_ID'),
            Config::inst()->get(WorkplaceGateway::class, 'workplace_group_limit')
        ));
    }

    /**
     * Get the comments from a specified post from Workplace.
     *
     * @param int $postId
     * @param int $limit
     *
     * @return string|null
     */
    public function getPostComments($postId, $limit, $order)
    {
        return $this->call('get', sprintf(
            '%d/comments?fields=id,created_time,message,from{name,picture,link},attachment%s%s',
            $postId,
            $limit ? "&limit={$limit}" : '',
            $order ? "&order={$order}" : ''
        ));
    }

    /**
     * You may able to search workplace users by their workplace email or workplace id
     * using batch request method in facebook workplace api
     *
     * @param array $users
     *
     * @return null|string JSON representation of the response.
     */
    public function getWorkplaceProfileInfo($users)
    {
        if (count($users) == 0) {
            return null;
        }

        $batch = [];
        $returnFields = Config::inst()->get(WorkplaceGateway::class, 'workplace_profile_return_fields');

        // Make a new array and send as a json payload to facebook workplace in the batch request
        // e.g [{"method":"GET", "relative_url":"user@example.org"},{"method":"GET", "relative_url":"user2@example.org?fields=link"}]
        foreach ($users as $userID) {
            array_push($batch, [
                'method'       => 'GET',
                'relative_url' => sprintf('%s?fields=%s', $userID, $returnFields)
            ]);
        }
        $jsonPayload = json_encode($batch);

        return $this->call('post', sprintf(
            '?batch=%s',
            $jsonPayload
        ));
    }

    /**
     * Make a REST call to the Workplace API.
     *
     * @param String $type The type should be post or get
     * @param String $parameters The rest parameters to call.
     *
     * @return null|string JSON representation of the response.
     * @throws \Exception
     */
    public function call($type, $parameters)
    {
        $client = new Client([
            'base_uri' => Environment::getEnv('SS_WORKPLACE_GATEWAY_REST_URL'),
            'headers'  => [
                'Authorization' => sprintf('Bearer %s', Environment::getEnv('SS_WORKPLACE_BEARER_TOKEN'))
            ]
        ]);

        try {
            $proxy = [];

            if (Environment::getEnv('SS_OUTBOUND_PROXY') && Environment::getEnv('SS_OUTBOUND_PROXY_PORT')) {
                $proxy = Environment::getEnv('SS_OUTBOUND_PROXY');
                $proxyPort = Environment::getEnv('SS_OUTBOUND_PROXY_PORT');
                $proxy = [
                    'proxy' => [
                        'http'  => sprintf('tcp://%s:%s', $proxy, $proxyPort), // Use this proxy with "http"
                        'https' => sprintf('tcp://%s:%s', $proxy, $proxyPort), // Use this proxy with "https",
                    ],
                ];
            }

            if ($type == 'get') {
                $response = $client->get($parameters, $proxy);
            } elseif ($type == 'post') {
                $response = $client->post($parameters, $proxy);
            } else {
                return null;
            }

            if ($response->getStatusCode() === 200) {
                return $response->getBody()->getContents();
            } else {
                throw new \Exception(sprintf(
                    'StatusCode: %s. StatusDescription: %s.',
                    $response->getStatusCode(),
                    $response->getStatusDescription()
                ));
            }

        } catch (ClientException $e) {
            $response = json_decode($e->getResponse()->getBody()->getContents());

            // Check exception error code is 100 when user not registered with workplace.
            // And not going to log any exceptions with error code 100
            if (isset($response->error->code) && $response->error->code == 100) {
                return null;
            }

            Injector::inst()->get(LoggerInterface::class)->error(
                sprintf(
                    'Error in WorkplaceGateway::call(%s). %s',
                    $parameters,
                    $e->getMessage()
                ),
                [
                    'Body' => $e
                ]
            );
        } catch (\Exeception $e) {
            Injector::inst()->get(LoggerInterface::class)->error(
                sprintf(
                    'Error in WorkplaceGateway::call(%s). %s',
                    $parameters,
                    $e->getMessage()
                ),
                [
                    'Body' => $e
                ]
            );
        }

        return null;
    }
}
