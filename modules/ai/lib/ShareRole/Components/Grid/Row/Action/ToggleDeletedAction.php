<?php

namespace Bitrix\AI\ShareRole\Components\Grid\Row\Action;

use Bitrix\AI\ShareRole\Events\Enums\ShareType;
use Bitrix\AI\ShareRole\Events\HideForSelfAnalyticEvent;
use Bitrix\AI\ShareRole\Events\ShowForSelfAnalyticEvent;
use Bitrix\AI\ShareRole\Request\ChangeDeleteForCurrentUserRequest;
use Bitrix\AI\ShareRole\Service\OwnerService;
use Bitrix\AI\Container;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use CUtil;

class ToggleDeletedAction extends BaseGridAction
{
	/**
	 * @inheritDoc
	 */
	public static function getId(): ?string
	{
		return 'toggle-deleted';
	}

	/**
	 * @inheritDoc
	 */
	public function processRequestAction(HttpRequest $request): ?Result
	{
		$requestDTO = $this->getRequest()->getData($request, $this->getCurrentUser());
		$this->fillEvent($requestDTO->needDeleted, $requestDTO->shareType);

		$this->getService()->deleteForCurrentUser(
			$requestDTO->userId,
			$requestDTO->roleId,
			$requestDTO->hasInOwnerTable,
			$requestDTO->needDeleted
		);

		$result = new Result();
		$result->setData([
			'id' => $requestDTO->roleCode,
			'IS_DELETED' => $requestDTO->needDeleted,
		]);

		return $result;
	}

	protected function fillEvent(bool $needDeleted, ShareType $shareType): void
	{
		if ($needDeleted)
		{
			$this->event = new HideForSelfAnalyticEvent($shareType);

			return;
		}

		$this->event = new ShowForSelfAnalyticEvent($shareType);
	}

	protected function getService(): OwnerService
	{
		return Container::init()->getItem(OwnerService::class);
	}

	protected function getRequest(): ChangeDeleteForCurrentUserRequest
	{
		return Container::init()->getItem(ChangeDeleteForCurrentUserRequest::class);
	}

	/**
	 * @inheritDoc
	 */
	protected function getText(): string
	{
		return '';
	}

	public function getControl(array $rawFields): ?array
	{
		$this->onclick = '';

		$isDeleted = $rawFields['IS_DELETED'];
		$roleCode = $rawFields['ID'];
		$roleName = CUtil::JSEscape($rawFields['DATA']['NAME']);

		if ($isDeleted)
		{
			$text = Loc::getMessage('ROLE_LIBRARY_GRID_ACTION_UNDO_DELETE');
			$this->onclick = "BX.AI.ShareRole.Library.Controller.handleClickOnUndoDeleteRoleSwitcher(event, '$roleCode', '$roleName')";
		}
		else
		{
			$text = Loc::getMessage('ROLE_LIBRARY_GRID_ACTION_DELETE');
			$this->onclick = "BX.AI.ShareRole.Library.Controller.handleClickOnDeleteRoleSwitcher(event, '$roleCode', '$roleName')";
		}

		return [
			...parent::getControl($rawFields),
			'TEXT' => $text,
		];
	}
}
