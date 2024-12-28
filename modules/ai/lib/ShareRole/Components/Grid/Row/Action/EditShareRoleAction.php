<?php

namespace Bitrix\AI\ShareRole\Components\Grid\Row\Action;

use Bitrix\Main\HttpRequest;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main\Text\HtmlFilter;

class EditShareRoleAction extends BaseGridAction
{
	/**
	 * @inheritDoc
	 */
	public static function getId(): ?string
	{
		return 'edit-role';
	}

	/**
	 * @inheritDoc
	 */
	protected function getText(): string
	{
		return Loc::getMessage('ROLE_LIBRARY_GRID_ACTION_EDIT_ROLE');
	}

	/**
	 * @param $request
	 *
	 * @return Result|null
	 */
	public function processRequestAction(HttpRequest $request): ?Result
	{
		return null;
	}

	public function getControl(array $rawFields): ?array
	{
		$roleCode = $rawFields['ID'];
		$this->onclick = HtmlFilter::encode("BX.AI.ShareRole.Library.Controller.editRole('$roleCode')");

		return parent::getControl($rawFields);
	}
}
