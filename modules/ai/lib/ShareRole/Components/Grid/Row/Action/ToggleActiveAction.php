<?php

namespace Bitrix\AI\ShareRole\Components\Grid\Row\Action;

use Bitrix\AI\ShareRole\Events\ActivateAnalyticEvent;
use Bitrix\AI\ShareRole\Events\DeactivateAnalyticEvent;
use Bitrix\AI\ShareRole\Events\Enums\ShareType;
use Bitrix\AI\ShareRole\Request\ChangeActivityRequest;
use CUtil;
use Bitrix\AI\ShareRole\Service\RoleService;
use Bitrix\AI\Container;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;

class ToggleActiveAction extends BaseGridAction
{
	/**
	 * @inheritDoc
	 */
	protected function getText(): string
	{
		return Loc::getMessage('ROLE_LIBRARY_GRID_ACTION_ACTIVATE');
	}

	/**
	 * @inheritDoc
	 */
	public static function getId(): ?string
	{
		return 'toggle-active';
	}

	/**
	 * @inheritDoc
	 */
	public function processRequestAction(HttpRequest $request): ?Result
	{
		$requestDTO = $this->getRequest()->getData($request, $this->getCurrentUser());

		$this->fillEvent($requestDTO->needActivate, $requestDTO->shareType);

		$this->getService()->changeActivateRole(
			$requestDTO->roleId,
			$requestDTO->needActivate,
			$requestDTO->userId
		);

		$result = new Result();
		$result->setData([
			'id' => $requestDTO->roleCode,
			'IS_ACTIVE' => $requestDTO->needActivate,
		]);

		return $result;
	}

	protected function fillEvent(bool $needActivate, ShareType $shareType)
	{
		if ($needActivate)
		{
			$this->event = new ActivateAnalyticEvent($shareType);

			return;
		}

		$this->event = new DeactivateAnalyticEvent($shareType);
	}

	protected function getService(): RoleService
	{
		return Container::init()->getItem(RoleService::class);
	}

	protected function getRequest(): ChangeActivityRequest
	{
		return Container::init()->getItem(ChangeActivityRequest::class);
	}

	public function getControl(array $rawFields): array
	{
		$this->default = true;
		$roleCode = $rawFields['ID'];

		$isActive = $rawFields['IS_ACTIVE'];
		$name = CUtil::JSEscape($rawFields['DATA']['NAME']);

		if ($isActive)
		{
			$this->onclick = "BX.AI.ShareRole.Library.Controller.handleClickOnDeactivateRoleMenuItem(event, '$roleCode', '$name')";
			$text = Loc::getMessage('ROLE_LIBRARY_GRID_ACTION_DEACTIVATE');
		}
		else
		{
			$text = Loc::getMessage('ROLE_LIBRARY_GRID_ACTION_ACTIVATE');
			$this->onclick = "BX.AI.ShareRole.Library.Controller.handleClickOnActivateRoleMenuItem(event, '$roleCode', '$name')";
		}

		$rawFields['id'] = $roleCode;

		return [
			...parent::getControl($rawFields),
			'TEXT' => $text,
		];
	}

}
