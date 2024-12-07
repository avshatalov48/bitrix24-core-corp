<?php

namespace Bitrix\AI\SharePrompt\Components\Grid\Row\Action;

use Bitrix\AI\SharePrompt\Enums\Category;
use Bitrix\AI\SharePrompt\Events\Enums\ShareType;
use Bitrix\AI\SharePrompt\Events\HideForSelfAnalyticEvent;
use Bitrix\AI\SharePrompt\Events\ShowForSelfAnalyticEvent;
use Bitrix\AI\SharePrompt\Request\ChangeDeleteForCurrentUserRequest;
use Bitrix\AI\SharePrompt\Service\OwnerService;
use Bitrix\AI\Container;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main\Text\HtmlFilter;
use CUtil;

class ToggleIsDeletedAction extends BaseGridAction
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
			$requestDTO->promptId,
			$requestDTO->hasInOwnerTable,
			$requestDTO->needDeleted
		);

		$result = new Result();
		$result->setData([
			'id' => $requestDTO->promptCode,
			'IS_DELETED' => $requestDTO->needDeleted,
		]);

		return $result;
	}

	protected function fillEvent(bool $needDeleted, ShareType $shareType): void
	{
		if ($needDeleted)
		{
			$this->event = new HideForSelfAnalyticEvent(Category::LIST, $shareType);

			return;
		}

		$this->event = new ShowForSelfAnalyticEvent(Category::LIST, $shareType);
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
		$promptCode = $rawFields['ID'];
		$promptName = CUtil::JSEscape($rawFields['DATA']['NAME']);

		if ($isDeleted)
		{
			$text = Loc::getMessage('PROMPT_LIBRARY_GRID_ACTION_UNDO_DELETE');
			$this->onclick = "BX.AI.SharePrompt.Library.Controller.handleClickOnUndoDeletePromptSwitcher(event, '$promptCode', '$promptName')";
		}
		else
		{
			$text = Loc::getMessage('PROMPT_LIBRARY_GRID_ACTION_DELETE');
			$this->onclick = "BX.AI.SharePrompt.Library.Controller.handleClickOnDeletePromptSwitcher(event, '$promptCode', '$promptName')";
		}

		return [
			...parent::getControl($rawFields),
			'TEXT' => $text,
		];
	}
}
