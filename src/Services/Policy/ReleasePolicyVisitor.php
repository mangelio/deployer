<?php


namespace Agnes\Services\Policy;


use Agnes\Models\Filter;
use Agnes\Models\Policies\ReleaseWhitelistPolicy;
use Agnes\Services\Release\Release;

class ReleasePolicyVisitor extends PolicyVisitor
{
    /**
     * @var Release
     */
    private $release;

    /**
     * ReleasePolicyVisitor constructor.
     * @param Release $release
     */
    public function __construct(Release $release)
    {
        $this->release = $release;
    }

    /**
     * @param ReleaseWhitelistPolicy $releaseWhitelistPolicy
     * @return bool
     */
    public function visitReleaseWhitelist(ReleaseWhitelistPolicy $releaseWhitelistPolicy): bool
    {
        return in_array($this->release->getCommitish(), $releaseWhitelistPolicy->getCommitishes());
    }

    /**
     * checks if the policy has to be checked for
     *
     * @param Filter $filter
     * @return bool
     */
    protected function filterApplies(?Filter $filter)
    {
        return true;
    }
}