<?php


namespace Agnes\Services\Policy;


use Agnes\Models\Policies\ReleaseWhitelistPolicy;
use Agnes\Models\Tasks\Filter;
use Agnes\Release\Release;

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
     * @param Filter $policy
     * @return bool
     */
    protected function filterApplies(?Filter $policy)
    {
        return true;
    }
}