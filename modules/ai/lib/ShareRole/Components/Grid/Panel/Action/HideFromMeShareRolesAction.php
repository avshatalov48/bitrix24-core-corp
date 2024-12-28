<?php

namespace Bitrix\AI\ShareRole\Components\Grid\Panel\Action;

use Bitrix\AI\Container;
use Bitrix\AI\ShareRole\Events\MultiHideForSelfAnalyticEvent;
use Bitrix\AI\ShareRole\Request\ChangeDeleteForCurrentUserByCodesRequest;
use Bitrix\AI\ShareRole\Service\OwnerService;
use Bitrix\Main\Filter\Filter;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\Result;

class HideFromMeShareRolesAction extends BaseGridAction
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
		[$resultCreate, $resultUpdate] = $this->getService()->changeDeletedForCurrentUserRoles(
			$requestDTO->userId,
			$requestDTO->ownerIdsForSharingRoles,
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
		$this->event = new MultiHideForSelfAnalyticEvent();
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
