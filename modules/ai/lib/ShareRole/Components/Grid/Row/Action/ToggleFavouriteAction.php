<?php

namespace Bitrix\AI\ShareRole\Components\Grid\Row\Action;

use Bitrix\AI\ShareRole\Request\AddInFavoriteListFromGridRequest;
use Bitrix\AI\ShareRole\Request\DeleteFromFavoriteListFromGridRequest;
use Bitrix\AI\ShareRole\Service\ShareService;
use Bitrix\AI\Container;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use CUtil;

class ToggleFavouriteAction extends BaseGridAction
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
			$this->getService()->addInFavoriteList($addDto->userId, $addDto->roleCode);

			return null;
		}

		$deleteDto = $this->getDeleteRequest()->getData($request, $this->getCurrentUser());
		$this->getService()->deleteInFavoriteList($deleteDto->userId, $deleteDto->roleCode);

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
		$isActive = $rawFields['IS_ACTIVE'];

		if (!$isActive)
		{
			return null;
		}

		$isFavourite = $rawFields['DATA']['IS_FAVOURITE'];
		$roleCode = $rawFields['DATA']['ROLE_CODE'];
		$roleName = CUtil::JSEscape($rawFields['DATA']['NAME']);;

		$this->onclick = "BX.AI.ShareRole.Library.Controller.toggleRoleFavourite('$roleCode','true', '$roleName')";
		$text = Loc::getMessage('ROLE_LIBRARY_GRID_ACTION_ADD_TO_FAVOURITE');

		if ($isFavourite)
		{
			$this->onclick = "BX.AI.ShareRole.Library.Controller.toggleRoleFavourite('$roleCode', 'false', '$roleName')";
			$text = Loc::getMessage('ROLE_LIBRARY_GRID_ACTION_REMOVE_FROM_FAVOURITE');
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

	protected function getDeleteRequest(): DeleteFromFavoriteListFromGridRequest
	{
		return Container::init()->getItem(DeleteFromFavoriteListFromGridRequest::class);
	}

	protected function getService(): ShareService
	{
		return Container::init()->getItem(ShareService::class);
	}
}
