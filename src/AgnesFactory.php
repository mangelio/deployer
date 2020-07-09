<?php

namespace Agnes;

use Agnes\Actions\Executor;
use Agnes\Actions\PayloadFactory;
use Agnes\Services\ActionService;
use Agnes\Services\BuildRepository;
use Agnes\Services\ConfigurationService;
use Agnes\Services\GithubService;
use Agnes\Services\InstallationService;
use Agnes\Services\InstanceService;
use Agnes\Services\PolicyService;
use Agnes\Services\ScriptService;
use Agnes\Services\TaskService;
use Http\Client\Common\Plugin\RedirectPlugin;
use Http\Client\Common\PluginClient;
use Http\Discovery\HttpClientDiscovery;
use Symfony\Component\Console\Style\StyleInterface;

class AgnesFactory
{
    /**
     * @var BuildRepository
     */
    private $buildService;

    /**
     * @var ConfigurationService
     */
    private $configurationService;

    /**
     * @var GithubService
     */
    private $githubService;

    /**
     * @var InstanceService
     */
    private $instanceService;

    /**
     * @var InstallationService
     */
    private $installationService;

    /**
     * @var PolicyService
     */
    private $policyService;

    /**
     * @var ScriptService
     */
    private $scriptService;

    /**
     * @var Executor
     */
    private $executor;

    /**
     * @var PayloadFactory
     */
    private $payloadFactory;

    /**
     * @var ActionService
     */
    private $actionService;

    /**
     * @var TaskService
     */
    private $payloadService;

    /**
     * AgnesFactory constructor.
     */
    public function __construct(StyleInterface $io)
    {
        $redirectPlugin = new RedirectPlugin(['preserve_header' => false]);
        $pluginClient = new PluginClient(HttpClientDiscovery::find(), [$redirectPlugin]);

        // construct internal services
        $configurationService = new ConfigurationService();
        $buildService = new BuildRepository($configurationService);
        $githubService = new GithubService($pluginClient, $configurationService);
        $installationService = new InstallationService($io, $configurationService);
        $instanceService = new InstanceService($configurationService, $installationService);
        $policyService = new PolicyService($configurationService, $instanceService, $io);
        $scriptService = new ScriptService($io, $configurationService, $this);

        // set properties
        $this->buildService = $buildService;
        $this->configurationService = $configurationService;
        $this->githubService = $githubService;
        $this->instanceService = $instanceService;
        $this->installationService = $installationService;
        $this->policyService = $policyService;
        $this->scriptService = $scriptService;

        $this->executor = new Executor();
        $this->payloadFactory = new PayloadFactory();
    }

    public function getExecutor(): Executor
    {
        return $this->executor;
    }

    public function getPayloadFactory(): PayloadFactory
    {
        return $this->payloadFactory;
    }

    public function getConfigurationService(): ConfigurationService
    {
        return $this->configurationService;
    }

    public function getActionService(): ActionService
    {
        return $this->actionService;
    }

    public function getPayloadService()
    {
        return $this->payloadService;
    }
}
