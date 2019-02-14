<?php
namespace NZTA\Workplace\Services;

use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;
use Psr\SimpleCache\CacheInterface;
use SilverStripe\Core\Config\Config;
use SilverStripe\ORM\FieldType\DBText;
use SilverStripe\Core\Injector\Injector;
use NZTA\Workplace\Gateways\WorkplaceGateway;
use NZTA\Workplace\Jobs\GetWorkplaceGroupsJob;

/**
 * Provides common calls to the {@link WorkplaceGateway}.
 *
 * The {@link WorkplaceGateway} endpoints return JSON responses, this service is used
 *
 * @see {@link WorkplaceGateway}.
 *
 * @package workplace-service
 */
class WorkplaceService
{

    /**
     * The cache lifetime for the workplace posts feed in seconds.
     *
     * @var integer
     */
    private static $workplace_posts_feed_lifetime = 60;

    /**
     * The cache lifetime for the Workplace comments displayed on pages.
     *
     * @var integer
     */
    private static $post_comments_lifetime = 60;

    /**
     * The cache lifetime for workplace groups set to less than one day.
     *
     * @var integer
     */
    private static $workplace_groups_lifetime = 86000;

    /**
     * @var WorkplaceService
     */
    public $WorkplaceGateway;

    /**
     * @var array
     */
    private static $dependencies = [
        'WorkplaceGateway' => '%$' . WorkplaceGateway::class,
    ];

    /**
     * Get Homepage group posts in as JSON.
     *
     * @param int $groupID
     * @param int|null $limit
     *
     * @return ArrayList|null representation of workplace posts.
     */
    public function getWorkplacePostsFromGroup($groupID, $limit = null)
    {
        // get the cache for this service
        $cache = Injector::inst()->get(CacheInterface::class . '.nztaWorkplace');

        // base cache key on group id and limit specified
        $cacheKey = md5(sprintf('getWorkplacePostsFromGroup-%d-%d', $groupID, $limit));

        // attempt to retrieve ArrayList of posts from cache
        if (!($posts = $cache->get($cacheKey))) {
            $response = $this->WorkplaceGateway->getWorkplacePostsFromGroup($groupID, $limit);

            if (!$response) {
                return null;
            }

            return $response;

            // otherwise we retrieve the posts and store to the cache as an ArrayList
            $cache->set($cacheKey, $response, Config::inst()->get(WorkplaceService::class, 'workplace_posts_feed_lifetime'));
        }

        return $posts;
    }

    /**
     * Used to retrieve comments from a given Post on Workplace.
     *
     * @param int $postId
     * @param int|null $limit
     *
     * @return ArrayList|null
     */
    public function getPostComments($postId, $limit = null, $order = 'chronological')
    {
        // get the cache for this service
        $cache = Injector::inst()->get(CacheInterface::class . '.nztaWorkplace');


        // base cache key on group id and limit specified
        $cacheKey = md5(sprintf('getPostComments-%d-%d-%s', $postId, $limit, $order));

        // attempt to retrieve ArrayList of posts from cache
        if (!($comments = $cache->get($cacheKey))) {
            $response = $this->WorkplaceGateway->getPostComments($postId, $limit, $order);

            if (!$response) {
                return null;
            }

            $comments = new ArrayList();
            $decoded = json_decode($response);
            $items = (isset($decoded->data)) ? $decoded->data : null;

            if (is_array($items)) {
                foreach ($items as $item) {
                    $message = isset($item->message) ? $item->message : '';

                    if (!$message && isset($item->attachment->type)) {
                        $type = $item->attachment->type;

                        // translate video case into more readable form
                        if ($type == 'video_inline') {
                            $type = 'video';
                        }

                        $message = sprintf('Check out this %s', $type);
                    }

                    // remove all the html tags from message
                    $message = strip_tags($message);

                    // cast to Text so we can use the helper methods to truncate
                    $text = DBText::create();
                    $text->setValue($message);

                    $comments->push(new ArrayData([
                        'ID'                 => isset($item->id) ? $item->id : '',
                        'Created'            => isset($item->created_time) ? $item->created_time : '',
                        'Message'            => $text,
                        'ProfileName'        => isset($item->from->name) ? $item->from->name : '',
                        'ProfilePictureLink' => isset($item->from->picture->data->url) ? $item->from->picture->data->url : '',
                        'ProfileLink'        => isset($item->from->link) ? $item->from->link : '',
                        'ProfileID'          => isset($item->from->id) ? $item->from->id : '',
                    ]));
                }
            }

            // otherwise we retrieve the posts and store to the cache as an ArrayList
            $cache->set($cacheKey, $comments, Config::inst()->get(WorkplaceService::class, 'post_comments_lifetime'));
        }

        return $comments;
    }

    /**
     * Get workplace groups in as a JSON.
     *
     * @return array representation of workplace groups.
     */
    public function getAllGroups()
    {
        // get the cache for this service
        $cache = Injector::inst()->get(CacheInterface::class . '.nztaWorkplace');
        $cacheKey = md5('workplaceGroups');

        // attempt to retrieve Array of groups from cache
        if (!($groups = $cache->get($cacheKey))) {
            $response = $this->WorkplaceGateway->getAllGroups();
            $groups = [];
            $items = json_decode($response)->data;
            if (is_array($items)) {
                foreach ($items as $item) {
                    if (isset($item->id) && isset($item->name)) {
                        $groups[$item->id] = $item->name;
                    }
                }
            }

            // otherwise we retrieve the groups and store to the cache as an array
            $cache->set($cacheKey, $groups, Config::inst()->get(WorkplaceService::class, 'workplace_groups_lifetime'));
        }
        return $groups;

    }

    /**
     * @return array|mixed
     */
    public function getWorkplaceGroups()
    {
        $filePath = $this->getWorkplaceGroupsFilePath();

        $groups = [];
        if (file_exists($filePath)) {
            $groups = unserialize(file_get_contents($filePath));
        }
        return $groups;
    }

    /**
     * @return string
     */
    public function getWorkplaceGroupsFilePath()
    {
        return sprintf('%s/%s/%s',
            ASSETS_PATH,
            Config::inst()->get(GetWorkplaceGroupsJob::class, 'workplace_secure_folder'),
            Config::inst()->get(GetWorkplaceGroupsJob::class, 'workplace_group_filename')
        );
    }

    /**
     * You may able to search workplace users by their workplace email or workplace ID
     * using batch request method in facebook workplace api
     *
     * e.g $users
     * [
     *      10 => 'user@example.org',
     *      12 => 'user2@example.org'
     * ]
     *
     * @param array $users
     *
     * @return array
     */
    public function getWorkplaceProfileInfo($users)
    {
        // E.g data structure that coming from gateway/workplace.
        // [{
        //      "code": 200,
        //      "headers": [{
        //        "name": "Access-Control-Allow-Origin",
        //        "value": "*"
        //      }],
        //      "body": "{\"link\":\"https:\\/\\/org.facebook.com\\/app_scoped_user_id\\/123\\/\",\"id\":\"123\"}"
        // }]
        $response = $this->WorkplaceGateway->getWorkplaceProfileInfo($users);
        $batchData = json_decode($response);

        if (count($batchData) == 0) {
            return null;
        }

        $returnData = [];

        $userKeys = array_keys($users);
        for ($i = 0; $i < count($userKeys); $i++) {
            $data = $batchData[$i];

            // Check the status code is 200 to
            // make sure its a valid workplace email or workplace ID
            if ($data->code == 200) {
                $userKey = $userKeys[$i];

                // e.g return array
                // [
                //      [10] => stdClass Object ( [link] => https://org.facebook.com/app_scoped_user_id/123/ [id] => 123 ),
                //      [12] => stdClass Object ( [link] => https://org.facebook.com/app_scoped_user_id/142/ [id] => 142 ),
                // ]
                $returnData[$userKey] = json_decode($data->body);

            }
        }

        return $returnData;
    }
}
