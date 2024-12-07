<?php

namespace Bitrix\AI\SharePrompt\Components\Grid\Panel\Action;

use Bitrix\AI\Container;
use Bitrix\AI\SharePrompt\Enums\Category;
use Bitrix\AI\SharePrompt\Events\MultiHideForSelfAnalyticEvent;
use Bitrix\AI\SharePrompt\Request\ChangeDeleteForCurrentUserByCodesRequest;
use Bitrix\AI\SharePrompt\Service\OwnerService;
use Bitrix\Main\Filter\Filter;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\Result;

class HideFromMeSharePromptsAction extends BaseGridAction
{
	/**
	 * @inheritDoc
	 */
	public static function getId(): string
	{
		return 'multiple-hide-from-me';
	}

	/**
	 * @inheritDoc
	 */
	public function processRequestAction(HttpRequest $request, bool $isSelectedAllRows, ?Filter $filter): ?Result
	{
		$requestDTO = $this->getRequest()->getData($request, $this->getCurrentUser());
		list($resultCreate, $resultUpdate) = $this->getService()->changeDeletedForCurrentUserPrompts(
			$requestDTO->userId,
			$requestDTO->ownerIdsForSharingPrompts,
			true
		);

		$result = new Result();

		if (!is_null($resultUpdate) && !$resultUpdate->isSuccess())
		{
			$result->addErrors($resultUpdate->getErrors());
		}

		if (!is_null($resultCreate) && !$resultCreate->isSuccess())
		{
			$result->addErrors($resultCreate->getErrors());
		}

		return $result;
	}

	protected function fillEvent(): void
	{
		$this->event = new MultiHideForSelfAnalyticEvent(Category::LIST);
	}

	protected function getRequest(): ChangeDeleteForCurrentUserByCodesRequest
	{
		return Container::init()->getItem(ChangeDeleteForCurrentUserByCodesRequest::class);
	}

	protected function getService(): OwnerService
	{
		return Container::init()->getItem(OwnerService::class);
	}
}
