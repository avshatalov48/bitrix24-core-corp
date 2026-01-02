<?php

namespace Bitrix\Crm\Controller\Copilot\CallAssessment;

use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Crm\Copilot\CallAssessment\EntitySelector\PullManager;
use Bitrix\Crm\Integration\AI\AIManager;
use Bitrix\Crm\Integration\AI\Enum\GlobalSetting;
use \Bitrix\Crm\Integration\AI\ErrorCode as AIErrorCode;
use Bitrix\Crm\Copilot\CallAssessment\CallAssessmentItem;
use Bitrix\Crm\Copilot\CallAssessment\Controller\CopilotCallAssessmentController;
use Bitrix\Crm\Copilot\CallAssessment\Entity\CopilotCallAssessmentTable;
use Bitrix\Crm\Copilot\CallAssessment\ItemFactory;
use Bitrix\Crm\Integration\VoxImplantManager;
use Bitrix\Crm\MultiValueStoreService;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\Engine\ActionFilter\Scope;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Request;
use Bitrix\Voximplant\Model\CallTable;
use Bitrix\Voximplant\StatisticTable;

final class CallCardPlacement extends Controller
{
	private readonly MultiValueStoreService $valueStoreService;
	private readonly PullManager $pull;

	public function __construct(Request $request = null)
	{
		parent::__construct($request);

		$this->valueStoreService = MultiValueStoreService::getInstance();
		$this->pull = new PullManager();
	}

	protected function getDefaultPreFilters(): array
	{
		return [
			...parent::getDefaultPreFilters(),
			new Scope(Scope::NOT_REST),
		];
	}

	/**
	 * @throws LoaderException
	 * @throws ArgumentOutOfRangeException
	 */
	public function attachCallAssessmentAction(string $callId, int $id): ?bool
	{
		if (!$this->isCallAssessmentEnabled())
		{
			$this->addCopilotCallAssessmentNotAvailableError();

			return null;
		}

		if (!$this->canEditCallAssessment())
		{
			$this->addError(ErrorCode::getAccessDeniedError());

			return null;
		}

		$entity = CopilotCallAssessmentController::getInstance()->getById($id);
		if ($entity === null)
		{
			$this->addAssessmentNotFoundError();

			return null;
		}

		if (!$this->isCallExists($callId))
		{
			$this->addCallNotFoundError();

			return null;
		}

		$callOriginId = VoxImplantManager::insertPrefix($callId);
		$this->valueStoreService->set($callOriginId, $id);

		$callAssessment = CallAssessmentItem::createFromEntity($entity);
		$this->pull->dispatchSelect($callId, $callAssessment);

		return true;
	}

	/**
	 * @throws ArgumentOutOfRangeException
	 */
	public function resolveCallAssessmentAction(string $callId): ?array
	{
		if (!$this->isCallAssessmentEnabled())
		{
			$this->addCopilotCallAssessmentNotAvailableError();

			return null;
		}

		if (!$this->canReadCallAssessment())
		{
			$this->addError(ErrorCode::getAccessDeniedError());

			return null;
		}


		$hasAvailableSelectorItems = $this->hasAvailableSelectorItems();
		$assessment = ItemFactory::getByCallId($callId);

		if ($hasAvailableSelectorItems)
		{
			$this->pull->subscribe($this->getCurrentUser()?->getId());
		}

		return [
			'callAssessment' => $assessment?->toArray() ?? [],
			'hasAvailableSelectorItems' => $hasAvailableSelectorItems,
		];
	}

	private function canEditCallAssessment(): bool
	{
		return Container::getInstance()->getUserPermissions()->copilotCallAssessment()->canEdit();
	}

	private function canReadCallAssessment(): bool
	{
		return Container::getInstance()->getUserPermissions()->copilotCallAssessment()->canRead();
	}

	/**
	 * @throws ArgumentOutOfRangeException
	 */
	private function isCallAssessmentEnabled(): bool
	{
		return AIManager::isAiCallProcessingEnabled()
			&& AIManager::isEnabledInGlobalSettings(GlobalSetting::CallAssessment)
		;
	}

	/**
	 * @throws LoaderException
	 */
	private function isCallExists(string $callId): bool
	{
		if (!Loader::includeModule('voximplant'))
		{
			return false;
		}

		return
			CallTable::getByCallId($callId) !== false
			|| StatisticTable::getByCallId($callId) !== false
		;
	}

	private function addAssessmentNotFoundError(): void
	{
		$this->addError(new Error('Copilot call assessment is not found', ErrorCode::NOT_FOUND));
	}

	private function addCallNotFoundError(): void
	{
		$this->addError(new Error('Call is not found', ErrorCode::NOT_FOUND));
	}

	private function addCopilotCallAssessmentNotAvailableError(): void
	{
		$this->addError(new Error('Copilot call assessment is not available', AIErrorCode::AI_NOT_AVAILABLE));
	}

	private function hasAvailableSelectorItems(): bool
	{
		$activeCallAssessment = CopilotCallAssessmentTable::query()
			->setSelect(['ID'])
			->where('IS_ENABLED', true)
			->setLimit(1)
			->setCacheTtl(3600)
			->fetchObject()
		;

		return $activeCallAssessment !== null;
	}
}
