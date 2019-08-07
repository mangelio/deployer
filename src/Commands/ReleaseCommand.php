<?php


namespace Agnes\Commands;

use Agnes\Models\Connections\Connection;
use Agnes\Models\Tasks\Task;
use Agnes\Release\GithubService;
use Agnes\Release\Release;
use Agnes\Services\ConfigurationService;
use Agnes\Services\FileService;
use Agnes\Services\PolicyService;
use Agnes\Services\TaskService;
use Http\Client\Exception;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ReleaseCommand extends ConfigurationAwareCommand
{
    /**
     * @var PolicyService
     */
    private $policyService;

    /**
     * @var GithubService
     */
    private $githubService;

    /**
     * @var TaskService
     */
    private $taskExecutionService;

    /**
     * @var FileService
     */
    private $fileService;

    /**
     * ReleaseCommand constructor.
     * @param ConfigurationService $configurationService
     * @param PolicyService $policyService
     * @param GithubService $githubService
     * @param TaskService $taskExecutionService
     * @param FileService $fileService
     */
    public function __construct(ConfigurationService $configurationService, PolicyService $policyService, GithubService $githubService, TaskService $taskExecutionService, FileService $fileService)
    {
        parent::__construct($configurationService);

        $this->policyService = $policyService;
        $this->githubService = $githubService;
        $this->taskExecutionService = $taskExecutionService;
        $this->fileService = $fileService;
    }

    public function configure()
    {
        $this->setName('release')
            ->setDescription('Create a new release.')
            ->setHelp('This command compiles & publishes a new release according to the passed configuration.')
            ->addOption("name", "na", InputOption::VALUE_REQUIRED, "name of the release")
            ->addOption("commitish", "b", InputOption::VALUE_REQUIRED, "branch or commit of the release");

        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     * @throws \Exception
     * @throws Exception
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getOption("name");
        $commitish = $input->getOption("commitish");
        $release = new Release($name, $commitish);

        $this->policyService->ensureCanRelease($release);

        $content = $this->buildRelease($release);

        $this->publishRelease($release, $content);

    }

    /**
     * @param Release $release
     * @param string $content
     * @throws Exception
     * @throws \Exception
     */
    private function publishRelease(Release $release, string $content)
    {
        $this->githubService->publish($release, $release->getArchiveName(), "application/zip", $content);
    }

    /**
     * @param Release $release
     * @return string
     * @throws \Exception
     */
    private function buildRelease(Release $release): string
    {
        $githubConfig = $this->configurationService->getGithubConfig();
        $task = $task = $this->configurationService->getTask("release");

        // clone repo, checkout correct commit & then remove git folder
        $task->addPreCommand("git clone git@github.com:" . $githubConfig->getRepository() . " .");
        $task->addPreCommand("git checkout " . $release->getTargetCommitish());
        $task->addPreCommand("rm -rf .git");

        // after release has been build, compress it to a single folder
        $fileName = $release->getArchiveName();
        $task->addPostCommand("tar -czvf $fileName .");

        // actually execute the task
        $buildConnection = $this->configurationService->getBuildConnection();
        $buildConnection->executeTask($task, $this->taskExecutionService);
        $releaseContent = $buildConnection->readFile($fileName, $this->fileService);

        return $releaseContent;
    }
}
