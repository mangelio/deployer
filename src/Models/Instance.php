<?php

namespace Agnes\Models;

use Agnes\Models\Connections\Connection;
use Agnes\Services\Configuration\Environment;
use Agnes\Services\Configuration\Server;

class Instance
{
    /**
     * @var Server
     */
    private $server;

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var string
     */
    private $stage;

    /**
     * @var Installation[]
     */
    private $installations = [];

    /**
     * @var Installation
     */
    private $currentInstallation;

    /**
     * Instance constructor.
     *
     * @param Installation[] $installations
     */
    public function __construct(Server $server, Environment $environment, string $stage, array $installations, ?Installation $currentInstallation)
    {
        $this->server = $server;
        $this->environment = $environment;
        $this->stage = $stage;

        foreach ($installations as $installation) {
            $this->installations[$installation->getNumber()] = $installation;
        }
        ksort($this->installations);

        $this->currentInstallation = $currentInstallation;
    }

    public function getServer(): Server
    {
        return $this->server;
    }

    public function getEnvironment(): Environment
    {
        return $this->environment;
    }

    public function getConnection(): Connection
    {
        return $this->server->getConnection();
    }

    public function getServerName(): string
    {
        return $this->server->getName();
    }

    public function getEnvironmentName(): string
    {
        return $this->environment->getName();
    }

    public function getStage(): string
    {
        return $this->stage;
    }

    /**
     * @return Installation[]
     */
    public function getInstallations(): array
    {
        return $this->installations;
    }

    /**
     * @return Installation
     */
    public function getCurrentInstallation(): ?Installation
    {
        return $this->currentInstallation;
    }

    public function isCurrentRelease(string $releaseName): bool
    {
        if (null === $this->getCurrentInstallation()) {
            return false;
        }

        return $this->getCurrentInstallation()->isSameReleaseName($releaseName);
    }

    /**
     * @return Installation[]
     */
    public function getInstallationsByReleaseName(string $releaseName): array
    {
        $installations = [];
        foreach ($this->installations as $installation) {
            if ($installation->isSameReleaseName($releaseName)) {
                $installations[] = $installation;
            }
        }

        return $installations;
    }

    /**
     * @return int
     */
    public function getKeepReleases()
    {
        return $this->server->getKeepReleases();
    }

    /**
     * get previous installation.
     */
    public function getPreviousInstallation(): ?Installation
    {
        if (null === $this->getCurrentInstallation()) {
            return null;
        }

        $currentReleaseNumber = $this->getCurrentInstallation()->getNumber();

        /** @var Installation|null $upperBoundRelease */
        $upperBoundRelease = null;

        foreach ($this->getInstallations() as $installation) {
            if (null !== $installation->getNumber() &&
                $installation->getNumber() < $currentReleaseNumber &&
                (null === $upperBoundRelease || $upperBoundRelease->getNumber() < $installation->getNumber())) {
                $upperBoundRelease = $installation;
            }
        }

        return $upperBoundRelease;
    }

    /**
     * @return string
     */
    public function getCurrentReleaseName()
    {
        if (null != $this->getCurrentInstallation() && null !== $this->getCurrentInstallation()->getRelease()) {
            return $this->getCurrentInstallation()->getRelease()->getName();
        }

        return null;
    }

    /**
     * @param Instance[] $instances
     *
     * @return Instance[]
     */
    public function getSameEnvironmentInstances(array $instances)
    {
        /** @var Instance[] $matching */
        $matching = [];
        foreach ($instances as $instance) {
            if ($this->getEnvironmentName() === $instance->getEnvironmentName()) {
                $matching[] = $instance;
            }
        }

        return $matching;
    }

    public function getRollbackTarget(?string $rollbackTo, ?string $rollbackFrom): ?Installation
    {
        // ensure instance active
        if (null === $this->getCurrentInstallation()) {
            return null;
        }

        // ensure rollbackFrom is what is currently active
        if (null !== $rollbackFrom && !$this->isCurrentRelease($rollbackFrom)) {
            return null;
        }

        // if no rollback target specified, return the previous installation
        if (null === $rollbackTo) {
            return $this->getPreviousInstallation();
        }

        // ensure target is not same than current release
        if ($this->isCurrentRelease($rollbackTo)) {
            return null;
        }

        // find matching installation & ensure it is indeed a previous release
        $targetInstallations = $this->getInstallationsByReleaseName($rollbackTo);
        /** @var Installation $maxTargetInstallation */
        $maxTargetInstallation = null;
        foreach ($targetInstallations as $targetInstallation) {
            if ($targetInstallation->getNumber() < $this->getCurrentInstallation()->getNumber() &&
                (null === $maxTargetInstallation || $targetInstallation->getNumber() > $maxTargetInstallation->getNumber())) {
                $maxTargetInstallation = $targetInstallation;
            }
        }

        return $maxTargetInstallation;
    }

    /**
     * @return bool
     */
    public function equals(Instance $other)
    {
        if ($this === $other) {
            return true;
        }

        if ($this->getServerName() === $other->getServerName() &&
            $this->getEnvironmentName() === $other->getEnvironmentName() &&
            $this->getStage() === $other->getStage()) {
            return true;
        }

        return false;
    }

    /**
     * @return string
     */
    public function describe()
    {
        return $this->getServerName().':'.$this->getEnvironmentName().':'.$this->getStage();
    }
}
