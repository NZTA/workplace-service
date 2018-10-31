<?php
namespace NZTA\Workplace\Jobs;

use Symbiote\QueuedJobs\Services\QueuedJob;
use Symbiote\QueuedJobs\Services\AbstractQueuedJob;
use SilverStripe\Assets\Folder;
use NZTA\Workplace\Services\WorkplaceService;
use SilverStripe\Core\Config\Config;
use SilverStripe\Security\Permission;
use Symbiote\QueuedJobs\Services\QueuedJobService;

/**
 * This job will call all every day to get all the groups in the facebook workplace
 *
 * Class GetWorkplaceGroupsJob
 */
class GetWorkplaceGroupsJob extends AbstractQueuedJob implements QueuedJob
{
    /**
     * @var WorkplaceService
     */
    public $WorkplaceService;

    /**
     * Save workplace groups text file  under secure folder called 'workplace'
     * @var string
     */
    private static $workplace_secure_folder = 'Workplace';

    /**
     * @var string
     */
    private static $workplace_group_filename = 'workplace-groups.txt';

    /**
     * @var array
     */
    private static $dependencies = [
        'WorkplaceService' => '%$' . WorkplaceService::class
    ];

    /**
     * Time after now to run the job.
     * so setting to 1 day (86400 seconds) ensures it is called in the next queue.
     * @var int
     */
    private static $reschedule_time = 86400;

    /**
     * @return string
     */
    public function getTitle()
    {
        return 'Get facebook workplace groups';
    }

    /**
     *  Get all the groups from workplace and save in under secure folder as a txt file.
     *  And schedule next job
     */
    public function process()
    {
        $groups = $this->WorkplaceService->getAllGroups();
        if (is_array($groups)) {
            $groups = serialize($groups);

            // Use a secure folder for storing the workplace group file
            $folder = Folder::find_or_make(sprintf('/%s/', Config::inst()->get(GetWorkplaceGroupsJob::class, 'workplace_secure_folder')));
            $folder->CanViewType = 'OnlyTheseUsers';
            $folder->ViewerGroups()->add($this->findAdminGroup());
            $folder->write();

            $filePath = $this->WorkplaceService->getWorkplaceGroupsFilePath();
            file_put_contents($filePath, $groups);

            $this->scheduleNextExecution();
            $this->markJobAsDone();
        }
    }

    /**
     * Queue up the next job to run.
     */
    private function scheduleNextExecution()
    {
        $groupsJob = new GetWorkplaceGroupsJob();
        singleton(QueuedJobService::class)->queueJob($groupsJob, date('Y-m-d H:i:s', time() + self::$reschedule_time));
    }

    /**
     * complete the job
     */
    private function markJobAsDone()
    {
        $this->totalSteps = 0;
        $this->isComplete = true;
    }

    /**
     * Find target group to record
     *
     * @return Group
     */
    protected function findAdminGroup()
    {
        return Permission::get_groups_by_permission('ADMIN')->first();
    }
}
