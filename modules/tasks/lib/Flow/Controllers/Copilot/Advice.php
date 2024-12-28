<?php

namespace Bitrix\Tasks\Flow\Controllers\Copilot;

use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Tasks\Flow\Access\FlowAccessController;
use Bitrix\Tasks\Flow\Access\FlowAction;
use Bitrix\Tasks\Flow\Controllers\Trait\ControllerTrait;
use Bitrix\Tasks\Flow\Controllers\Trait\MessageTrait;
use Bitrix\Tasks\Flow\Integration\AI\AdvicePreparer;
use Bitrix\Tasks\Flow\Integration\AI\Provider\AdviceProvider;

class Advice extends Controller
{
	use ControllerTrait;
	use MessageTrait;

	protected int $userId;

	protected function init(): void
	{
		parent::init();

		$this->userId = (int)CurrentUser::get()->getId();
	}

	public function getAction(int $flowId): ?array
	{
		if (!FlowAccessController::can($this->userId, FlowAction::READ, $flowId))
		{
			return $this->buildErrorResponse($this->getAccessDeniedError());
		}

		$adviceProvider = new AdviceProvider();
		$advicePreparer = new AdvicePreparer();

		$advice = $adviceProvider->get($flowId);
		if (empty($advice))
		{
			return null;
		}

		$advicesCreatedDate = $advice->getUpdatedDate();
		$advicesData = $advice->getAdvicesData();

		if (empty($advicesData))
		{
			return null;
		}

		$resultAdvices = [];
		foreach ($advicesData as $adviceData)
		{
			$resultAdvices[] = $advicePreparer->prepare($adviceData);
		}

		return [
			'advices' => $resultAdvices,
			'createDateTime' => $advicesCreatedDate,
		];
	}
}