<?php

namespace Agnes\Actions;

use Agnes\Services\PolicyService;
use Exception;
use Symfony\Component\Console\Output\OutputInterface;

class Release extends AbstractPayload
{
    /**
     * @var string|null
     */
    private $name;

    /**
     * @var string
     */
    private $commitish;

    /**
     * Release constructor.
     *
     * @param string $name
     */
    public function __construct(string $commitish, string $name = null)
    {
        $this->commitish = $commitish;
        $this->name = $name;
    }

    public function getCommitish(): string
    {
        return $this->commitish;
    }

    public function getName(): string
    {
        return null !== $this->name ? $this->name : $this->commitish;
    }

    /**
     * @throws Exception
     */
    public function canExecute(PolicyService $policyService, OutputInterface $output): bool
    {
        return $policyService->canRelease($this, $output);
    }

    public function describe(): string
    {
        return 'build '.$this->getCommitish();
    }
}
