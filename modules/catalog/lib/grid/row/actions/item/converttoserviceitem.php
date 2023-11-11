<?php

namespace Bitrix\Catalog\Grid\Row\Actions\Item;

use Bitrix\Catalog\Grid\ProductAction;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use CUtil;

final class ConvertToServiceItem extends BaseItem
{
	public static function getId(): ?string
	{
		return 'convert_to_service';
	}

	protected function getText(): string
	{
		return Loc::getMessage('CATALOG_GRID_ROW_ACTIONS_CONVERT_TO_SERVICE_TEXT');
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
		$confirmMessage = \CUtil::JSEscape(
			Loc::getMessage('CATALOG_GRID_ROW_ACTIONS_CONVERT_TO_SERVICE_CONFIRM_MESSAGE')
		);

		$this->onclick = "IblockGridInstance.sendActionWithConfirm('{$actionId}', {$data}, '{$confirmMessage}')";

		return parent::getControl($rawFields);
	}

	public function processRequest(HttpRequest $request): ?Result
	{
		$id = $request->getPost('id');
		if (empty($id) || !is_numeric($id))
		{
			return null;
		}
		$id = (int)$id;

		return ProductAction::convertToServiceElementList($this->getIblockId(), [$id]);
	}
}
