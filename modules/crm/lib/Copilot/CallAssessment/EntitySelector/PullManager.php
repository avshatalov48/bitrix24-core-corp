<?php

namespace Bitrix\Crm\Copilot\CallAssessment\EntitySelector;

use Bitrix\Crm\Copilot\CallAssessment\CallAssessmentItem;
use Bitrix\Crm\Copilot\CallAssessment\Controller\CopilotCallAssessmentController;
use Bitrix\Main\Loader;
use CPullWatch;

final class PullManager
{
	public const COMMAND_UPDATE = 'update_call_assessment';
	public const COMMAND_SELECT = 'select_call_assessment';

	private const TAG = 'CRM_COPILOT_CALL_ASSESSMENT_SCRIPT_SELECTOR';

	private CPullWatch|string|null $pull = null;

	public function __construct()
	{
		if ($this->isPullIncluded())
		{
			$this->pull = CPullWatch::class;
		}
	}

	public function subscribe(?int $userId): void
	{
		if ($userId === null || !$this->isPullAvailable())
		{
			return;
		}

		$this->pull::Add($userId, self::TAG);
	}

	public function dispatchUpdateById(int $id): void
	{
		$callAssessment = CopilotCallAssessmentController::getInstance()->getById($id);
		if ($callAssessment === null)
		{
			return;
		}

		$this->dispatchUpdate(CallAssessmentItem::createFromEntity($callAssessment));
	}

	public function dispatchUpdate(CallAssessmentItem $callAssessment): void
	{
		$params = [
			'itemOptions' => (new ItemAdapter($callAssessment))
				->addTab(CallScriptProvider::ENTITY_ID)
			,
		];

		$this->addToStack(self::COMMAND_UPDATE, $params);
	}

	public function dispatchSelect(string $selectorId, ?CallAssessmentItem $callAssessmentItem): void
	{
		$itemOptions = $callAssessmentItem !== null
			? (new ItemAdapter($callAssessmentItem))->addTab(CallScriptProvider::ENTITY_ID)
			: null
		;

		$params = [
			'selectorId' => $selectorId,
			'itemOptions' => $itemOptions,
		];

		$this->addToStack(self::COMMAND_SELECT, $params);
	}

	private function isPullAvailable(): bool
	{
		return $this->pull !== null;
	}

	private function isPullIncluded(): bool
	{
		return Loader::includeModule('pull');
	}

	private function addToStack(string $command, array $params): void
	{
		if (!$this->isPullAvailable())
		{
			return;
		}

		$parameters = [
			'module_id' => 'crm',
			'command' => $command,
			'params' => $params,
		];

		$this->pull::AddToStack(self::TAG, $parameters);
	}
}
