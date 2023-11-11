<?php

namespace Bitrix\Iblock\Grid\Row\Actions\Item;

use Bitrix\Iblock\Grid\Row\Actions\Item\Helpers\ChangeActiveHandler;
use Bitrix\Main\Localization\Loc;
use CUtil;

final class ActivateElementItem extends BaseItem
{
	use ChangeActiveHandler;

	protected function getSetActiveValue(): string
	{
		return 'Y';
	}

	public static function getId(): ?string
	{
		return 'activate_element';
	}

	protected function getText(): string
	{
		return Loc::getMessage('IBLOCK_GRID_ROW_ACTIONS_ACTIVE_ELEMENT_NAME');
	}

	public function getControl(array $rawFields): ?array
	{
		$id = (int)($rawFields['ID'] ?? 0);
		if ($id <= 0)
		{
			return null;
		}

		$actionId = self::getId();
		$data = CUtil::PhpToJSObject([
			'id' => $id,
		]);

		$this->onclick = "IblockGridInstance.sendRowAction('{$actionId}', {$data})";

		return parent::getControl($rawFields);
	}
}
