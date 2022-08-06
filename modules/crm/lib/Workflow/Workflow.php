<?php

namespace Bitrix\Crm\Workflow;

/**
 * Base class for simple business processes (workflows).
 * Usage:
 * 
 * <code>
 * $workflow = new PublishArticleWorkflow;
 * $workflow->getStage(); // ArticleStage::DRAFT
 * if ($workflow->canSwitchToStage(ArticleStage::PUBLISHED)) {
 *     $workflow->setStage(ArticleStage::PUBLISHED);
 * }
 * $workflow->atStage(ArticleStage::PUBLISHED); // true
 * </code>
 */
abstract class Workflow
{
	/**
	 * Returns unique workflow code
	 * @return string
	 */
	public static abstract function getWorkflowCode(): string;

	/**
	 * Returns list of all available stages
	 * @return string[]
	 */
	public static abstract function getStages(): array;

	/**
	 * Returns initial entity stage
	 * @return string
	 */
	public abstract function getInitialStage(): string;

	/**
	 * Returns current entity identifier
	 * @return int
	 */
	public abstract function getEntityId(): int;

	/**
	 * Fetches current stage from database
	 * @return string
	 */
	public function getStage(): string
	{
		$currentStage = EntityStageTable::getStage($this->getEntityId(), static::getWorkflowCode());
		return $currentStage ?? $this->getInitialStage();
	}

	/**
	 * Tries to apply next stage
	 * @param string $nextStage
	 * @return bool true if transition allowed and stage saved, false otherwise
	 */
	public function setStage(string $nextStage): bool
	{
		if ($this->isStageValid($nextStage) && $this->canSwitchToStage($nextStage))
		{
			return $this->persist($nextStage);
		}
		return false;
	}

	/**
	 * Check if transition from current stage to next stage allowed.
	 * By default, all transitions allowed, override it if needed.
	 * 
	 * @param string $nextStage
	 * @return bool
	 */
	public function canSwitchToStage(string $nextStage): bool
	{
		return true;
	}

	/**
	 * @param string $stage
	 * @return bool
	 */
	protected function isStageValid(string $stage): bool
	{
		return in_array($stage, static::getStages(), true);
	}

	/**
	 * Stores stage in database
	 * @param string $nextStage
	 * @return bool true if operation successful, false otherwise
	 */
	protected function persist(string $nextStage): bool
	{
		$result = EntityStageTable::setStage($this->getEntityId(), static::getWorkflowCode(), $nextStage);
		return $result->isSuccess();
	}

	/**
	 * Check if workflow in $currentStage
	 * 
	 * @param string $currentStage
	 * @return bool
	 */
	public function atStage(string $currentStage): bool
	{
		return $currentStage === $this->getStage();
	}

	/**
	 * Removes stage record for current entity, so entity forces initialing state
	 * @return bool true if record removed or already not exists, false otherwise
	 */
	public function resetStage(): bool
	{
		$queryParams = [
			'select' => ['ID'],
			'filter' => ['=ENTITY_ID' => $this->getEntityId(), '=WORKFLOW_CODE' => static::getWorkflowCode()],
			'order' => ['ID' => 'DESC'],
		];

		$row = EntityStageTable::getList($queryParams)->fetch();
		if ($row)
		{
			return EntityStageTable::delete($row['ID'])->isSuccess();
		}

		return true;
	}
}
