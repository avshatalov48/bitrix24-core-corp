<?php

namespace Bitrix\AI\SharePrompt\Components\Grid\Row\Action;

use Bitrix\AI\SharePrompt\Request\AddInFavoriteListFromGridRequest;
use Bitrix\AI\SharePrompt\Request\DeleteFromFavoriteListRequest;
use Bitrix\AI\SharePrompt\Service\ShareService;
use Bitrix\AI\Container;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main\Text\HtmlFilter;
use CUtil;

class ToggleIsFavouriteAction extends BaseGridAction
{
	/**
	 * @inheritDoc
	 */
	public static function getId(): ?string
	{
		return 'toggle-favourite';
	}

	/**
	 * @inheritDoc
	 */
	public function processRequestAction(HttpRequest $request): ?Result
	{
		if ($request->getPost('favourite') === 'true')
		{
			$addDto = $this->getAddRequest()->getData($request, $this->getCurrentUser());
			$this->getService()->addInFavoriteList($addDto->userId, $addDto->promptId);

			return null;
		}

		$deleteDto = $this->getDeleteRequest()->getData($request, $this->getCurrentUser());
		$this->getService()->deleteInFavoriteList($deleteDto->userId, $deleteDto->promptId);

		return null;
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
		if (empty($rawFields['IS_ACTIVE']))
		{
			return null;
		}

		$isFavourite = $rawFields['DATA']['IS_FAVOURITE'];
		$promptCode = $rawFields['DATA']['PROMPT_CODE'];
		$promptName = CUtil::JSEscape($rawFields['DATA']['NAME']);

		$this->onclick = "BX.AI.SharePrompt.Library.Controller.togglePromptFavourite('$promptCode','true', '$promptName')";
		$text = Loc::getMessage('PROMPT_LIBRARY_GRID_ACTION_ADD_TO_FAVOURITE');

		if ($isFavourite)
		{
			$this->onclick = "BX.AI.SharePrompt.Library.Controller.togglePromptFavourite('$promptCode', 'false', '$promptName')";
			$text = Loc::getMessage('PROMPT_LIBRARY_GRID_ACTION_REMOVE_FROM_FAVOURITE');
		}

		return [
			...parent::getControl($rawFields),
			'TEXT' => $text,
		];
	}

	protected function getAddRequest(): AddInFavoriteListFromGridRequest
	{
		return Container::init()->getItem(AddInFavoriteListFromGridRequest::class);
	}

	protected function getDeleteRequest(): DeleteFromFavoriteListRequest
	{
		return Container::init()->getItem(DeleteFromFavoriteListRequest::class);
	}

	protected function getService(): ShareService
	{
		return Container::init()->getItem(ShareService::class);
	}
}
