<?php

namespace Agnes\Services\Policy;

use Agnes\Actions\CopyShared;
use Agnes\Models\Filter;
use Agnes\Models\Policies\SameReleasePolicy;
use Agnes\Models\Policies\StageWriteDownPolicy;
use Exception;
use Symfony\Component\Console\Style\StyleInterface;

class CopySharedPolicyVisitor extends PolicyVisitor
{
    /**
     * @var CopyShared
     */
    private $copyShared;

    /**
     * CopySharedPolicyVisitor constructor.
     */
    public function __construct(StyleInterface $io, CopyShared $copyShared)
    {
        parent::__construct($io);

        $this->copyShared = $copyShared;
    }

    public function visitSameRelease(SameReleasePolicy $sameReleasePolicy): bool
    {
        $sourceInstallation = $this->copyShared->getSource()->getCurrentInstallation();
        $targetInstallation = $this->copyShared->getTarget()->getCurrentInstallation();

        if (null === $sourceInstallation) {
            return $this->preventExecution($this->copyShared, 'source has no active installation.');
        }

        if (null === $targetInstallation) {
            return $this->preventExecution($this->copyShared, 'target has no active installation.');
        }

        $sourceIdentification = $sourceInstallation->getReleaseOrCommitish();
        $targetIdentification = $targetInstallation->getReleaseOrCommitish();
        if ($sourceIdentification !== $targetIdentification) {
            return $this->preventExecution($this->copyShared, "source has a different version deployed as target. source: $sourceIdentification target: $targetIdentification.");
        }

        return true;
    }

    /**
     * @throws Exception
     */
    public function visitStageWriteDown(StageWriteDownPolicy $stageWriteDownPolicy): bool
    {
        $targetStage = $this->copyShared->getTarget()->getStage();
        $sourceStage = $this->copyShared->getSource()->getStage();

        $stageIndex = $stageWriteDownPolicy->getLayerIndex($sourceStage);
        if (false === $stageIndex) {
            return $this->preventExecution($this->copyShared, "stage $targetStage not found in specified layers; policy undecidable.");
        }

        // if the stageIndex is the highest layer, we are allowed to write
        if ($stageWriteDownPolicy->isHighestLayer($stageIndex) || $stageWriteDownPolicy->isLowestLayer($stageIndex)) {
            return true;
        }

        // get the next lower layer & the current layer and check if the target is contained in there
        $stagesToCheck = array_merge($stageWriteDownPolicy->getNextLowerLayer($stageIndex), $stageWriteDownPolicy->getLayer($stageIndex));

        if (!in_array($targetStage, $stagesToCheck)) {
            return $this->preventExecution($this->copyShared, "target stage not within same or next lower stage as source stage. target stage $targetStage, source stage $sourceStage.");
        }

        return true;
    }

    /**
     * checks if the policy has to be checked for.
     *
     * @param Filter $filter
     *
     * @return bool
     */
    protected function filterApplies(?Filter $filter)
    {
        return null === $filter ||
            $filter->instanceMatches($this->copyShared->getSource()) ||
            $filter->instanceMatches($this->copyShared->getTarget());
    }
}
