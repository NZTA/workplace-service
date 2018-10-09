<?php
namespace NZTA\Workplace\Test;

use Phake;
use stdClass;
use NZTA\Workplace\Gateways\WorkplaceGateway;

class MockWorkplaceService
{
    /**
     * @var int
     */
    public $groupID = 123;

    /**
     * @var int
     */
    public $postID = 321;

    /**
     * @var int
     */
    public $limit = 5;

    /**
     * @var array
     */
    public $userIDs = [
        10 => 'testuser@example.org',
        15 => 'testuser2@example.org'
    ];

    /**
     * @return WorkplaceGateway
     */
    public function getGateway()
    {
        $gateway = Phake::mock(WorkplaceGateway::class);

        Phake::when($gateway)
            ->getHomepagePosts($this->groupID, $this->limit)
            ->thenReturn($this->getMockResponse_homepagePosts());

        Phake::when($gateway)
            ->getPostComments($this->postID, $this->limit, 'chronological')
            ->thenReturn($this->getMockResponse_postComments('chronological'));

        Phake::when($gateway)
            ->getPostComments($this->postID, $this->limit, 'reverse_chronological')
            ->thenReturn($this->getMockResponse_postComments('reverse_chronological'));

        Phake::when($gateway)
            ->getAllGroups()
            ->thenReturn($this->getMockResponse_allGroups());

        Phake::when($gateway)
            ->getWorkplaceProfileInfo($this->userIDs)
            ->thenReturn($this->getMockResponse_workplaceProfileInfo());

        return $gateway;
    }

    /**
     * Mocked response when hitting API endpoint used to retrieve posts from a
     * specified group.
     *
     * @return string
     */
    public function getMockResponse_homepagePosts()
    {
        $pictureData['is_silhouette'] = false;
        $pictureData['url'] = 'https://scontent.xx.fbcdn.net/v/t1.0-1/p50x50/1234.png';

        $from['name'] = 'Test User';
        $from['picture']['data'] = $pictureData;
        $from['link'] = 'https://testorg.facebook.com/app_scoped_user_id/1234/';

        $post['permalink_url'] = 'https://testorg.facebook.com/groups/1234/permalink/1234/';
        $post['message'] = '<p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget <span>dolor.</span></p>';
        $post['formatting'] = 'PLAINTEXT';
        $post['created_time'] = '2017-04-24T22:30:39+0000';
        $post['type'] = 'status';
        $post['from'] = $from;
        $post['id'] = '1213179605391598';

        $data['data'][] = $post;

        return json_encode($data);
    }

    /**
     * Mocked response when hitting API endpoint used to retrieve comments from
     * a specified post.
     *
     * @param string $order To specify order in which comments appear
     *
     * @return string
     */
    public function getMockResponse_postComments($order)
    {
        $data = [];

        // create initial comment author picture
        $pictureData['is_silhouette'] = false;
        $pictureData['url'] = 'https://scontent.xx.fbcdn.net/v/t1.0-1/p50x50/1234.png';

        // create initial comment author
        $from['name'] = 'Test User';
        $from['picture']['data'] = $pictureData;
        $from['link'] = 'https://testorg.facebook.com/app_scoped_user_id/1234/';

        // create initial comment data
        $comment['id'] = '123123123123';
        $comment['created_time'] = '2017-04-24T22:30:39+0000';
        $comment['message'] = '<p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget <span>dolor.</span></p>';
        $comment['from'] = $from;

        // create a second comment that is posted later
        $commentTwo = $comment;
        $commentTwo['created_time'] = '2017-04-24T23:30:39+0000';

        // push them into order as defined by the order parameter
        switch ($order) {
            case 'reverse_chronological':
                $data['data'][0] = $commentTwo;
                $data['data'][1] = $comment;
                break;

            case 'chronological':
            default:
                $data['data'][0] = $comment;
                $data['data'][1] = $commentTwo;
                break;
        }

        return json_encode($data);
    }

    /**
     * @return string
     */
    public function getMockResponse_allGroups()
    {
        $data['data'] = [
            [
                'id'   => '1234533',
                'name' => 'Group One'
            ],
            [
                'id'   => '3456673',
                'name' => 'Group Two'
            ],
            [
                'id'   => '5667854',
                'name' => 'Group Three'
            ]
        ];
        return json_encode($data);
    }

    /**
     * Mocked response when hitting API endpoint used to retrieve workplace profile details from
     * a workplace user api.
     *
     * @return string
     */
    public function getMockResponse_workplaceProfileInfo()
    {
        $object = new stdClass();
        $object->id = '63232';
        $object->link = 'https://org.facebook.com/app_scoped_user_id/63232';

        $mockData = [
            [
                'code'    => 200,
                'headers' => [
                    'name'  => 'Content-Typ',
                    'value' => 'text/javascript; charset=UTF-8'
                ],
                'body'    => json_encode($object)
            ],
            [
                'code'    => 400,
                'headers' => [
                    'name'  => 'Content-Typ',
                    'value' => 'text/javascript; charset=UTF-8'
                ],
                'body'    => [
                    'error' => [
                        'message' => 'Unsupported get request',
                        'code'    => 100
                    ]
                ]
            ]
        ];

        return json_encode($mockData);
    }
}
