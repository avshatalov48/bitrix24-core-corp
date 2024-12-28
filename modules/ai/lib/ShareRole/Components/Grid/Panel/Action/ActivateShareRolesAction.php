<?php

namespace Bitrix\AI\ShareRole\Components\Grid\Panel\Action;

use Bitrix\AI\Container;
use Bitrix\AI\ShareRole\Events\MultiActivateAnalyticEvent;
use Bitrix\AI\ShareRole\Request\ChangeActivityByCodesRequest;
use Bitrix\AI\ShareRole\Service\RoleService;
use Bitrix\Main\Filter\Filter;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\Result;

class ActivateShareRolesAction extends BaseGridAction
{

	/**
	 * @inheritDoc
	 */
	public static function getId(): string
	{
		return 'multiple-activate';
	}

	/**
	 * @inheritDoc
	 */
	public function processRequestAction(HttpRequest $request, bool $isSelectedAllRows, ?Filter $filter): Result
	{
		$dto = $this->getRequest()->getData($request, $this->getCurrentUser());

		return $this->getService()->changeActivateRoles($dto->roleIds, true, $dto->userId);
	}

	protected function fillEvent(): void
	{
		$this->event = new MultiActivateAnalyticEvent();
	}

	protected function getRequest(): ChangeActivityByCodesRequest
	{
		return Container::init()->getItem(ChangeActivityByCodesRequest::class);
	}

	protected function getService(): RoleService
	{
		return Container::init()->getItem(RoleService::class);
	}
}
