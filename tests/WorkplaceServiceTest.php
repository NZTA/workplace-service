<?php

class WorkplaceServiceTest extends SapphireTest
{

    /**
     * @var WorkplaceService
     */
    private $workplaceService;

    /**
     * @var MockWorkplaceService
     */
    private $mockWorkplaceService;

    public function setUpOnce()
    {
        parent::setUpOnce();

        Phockito::include_hamcrest();
    }

    /**
     * Get mock service and workplace service for unit tests
     */
    public function setUp()
    {
        parent::setUp();

        $this->mockWorkplaceService = Injector::inst()->get('MockWorkplaceService');
        $this->workplaceService = Injector::inst()->get('WorkplaceService');
        $this->workplaceService->WorkplaceGateway = $this->mockWorkplaceService->getGateway();
    }

    public function testGetHomepagePosts()
    {
        $response = $this
            ->workplaceService
            ->getHomePagePosts($this->mockWorkplaceService->groupID, $this->mockWorkplaceService->limit);

        $firstItem = $response->items[0];

        // check the first post values
        $this->assertTrue(isset($firstItem->ID));
        $this->assertEquals($firstItem->ID, '1213179605391598');
        $this->assertTrue(isset($firstItem->Message));
        $this->assertEquals($firstItem->Message, 'Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor.');
        $this->assertTrue(isset($firstItem->Link));
        $this->assertEquals($firstItem->Link, 'https://testorg.facebook.com/groups/1234/permalink/1234/');

        // check the profile values
        $this->assertTrue(isset($firstItem->ProfileName));
        $this->assertEquals($firstItem->ProfileName, 'Test User');
        $this->assertTrue(isset($firstItem->ProfilePictureLink));
        $this->assertEquals($firstItem->ProfilePictureLink, 'https://scontent.xx.fbcdn.net/v/t1.0-1/p50x50/1234.png');
        $this->assertTrue(isset($firstItem->ProfileLink));
        $this->assertEquals($firstItem->ProfileLink, 'https://testorg.facebook.com/app_scoped_user_id/1234/');
    }

    public function testGetPostComments()
    {
        $comments = $this
            ->workplaceService
            ->getPostComments($this->mockWorkplaceService->postID, $this->mockWorkplaceService->limit, 'chronological');

        // ensure shape of result
        $this->assertTrue($comments instanceof ArrayList);
        $this->assertEquals(2, $comments->count());

        // ensure expected keys are present
        foreach ($comments as $comment) {
            $this->assertTrue(isset($comment->ID));
            $this->assertTrue(isset($comment->Created));
            $this->assertTrue(isset($comment->Message));
            $this->assertTrue(isset($comment->ProfileName));
            $this->assertTrue(isset($comment->ProfilePictureLink));
            $this->assertTrue(isset($comment->ProfileLink));
        }

        // ensure values are mapped and order is as expected
        $firstComment = $comments->first();

        $this->assertEquals('123123123123', $firstComment->ID);
        $this->assertEquals('2017-04-24T22:30:39+0000', $firstComment->Created);
        $this->assertEquals('Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor.', $firstComment->Message);
        $this->assertEquals('Test User', $firstComment->ProfileName);
        $this->assertEquals('https://scontent.xx.fbcdn.net/v/t1.0-1/p50x50/1234.png', $firstComment->ProfilePictureLink);
        $this->assertEquals('https://testorg.facebook.com/app_scoped_user_id/1234/', $firstComment->ProfileLink);

        // ensure second displays second as expected
        $lastComment = $comments->last();

        $this->assertEquals('2017-04-24T23:30:39+0000', $lastComment->Created);

        // reverse order
        $reversedComments = $this
            ->workplaceService
            ->getPostComments($this->mockWorkplaceService->postID, $this->mockWorkplaceService->limit, 'reverse_chronological');

        $this->assertEquals('2017-04-24T23:30:39+0000', $reversedComments->first()->Created);
        $this->assertEquals('2017-04-24T22:30:39+0000', $reversedComments->last()->Created);
    }

    public function testGetGroups()
    {
        $response = $this->workplaceService->getAllGroups();

        $this->assertEquals(count($response), 3);
        $this->assertEquals('Group One', $response['1234533']);
        $this->assertEquals('Group Two', $response['3456673']);
        $this->assertEquals('Group Three', $response['5667854']);
    }

    public function testGetWorkplaceProfileInfo()
    {
        $response = $this
            ->workplaceService
            ->getWorkplaceProfileInfo($this->mockWorkplaceService->userIDs);

        // ensure you getting only 200 requests
        // in this test return only 1 out of 2 requests
        $this->assertEquals(1, count($response));

        // userID 10 will only return 200 respond 
        $firstProfile =  $response[10];

        $this->assertTrue(isset($firstProfile->id));
        $this->assertTrue(isset($firstProfile->link));

        // Ensure values are mapped is as expected
        $this->assertEquals('63232', $firstProfile->id);
        $this->assertEquals('https://org.facebook.com/app_scoped_user_id/63232', $firstProfile->link);
    }
}
