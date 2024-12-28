<?php

namespace Bitrix\AI\ShareRole\Components\Grid\Row\Field\Assembler;

use Bitrix\Main\Grid\Row\FieldAssembler;
use Bitrix\Main\Text\HtmlFilter;
use CUtil;

class ShareRoleIsDeletedFieldAssembler extends FieldAssembler
{
	protected function prepareColumn($value): string
	{
		$isDeleted = $value['isDeleted'];
		$roleCode = $value['roleCode'];
		$roleName = CUtil::JSEscape($value['roleName']);

		if ($isDeleted)
		{
			$onclick = HtmlFilter::encode(
				"BX.AI.ShareRole.Library.Controller.handleClickOnUndoDeleteRoleSwitcher(event, '$roleCode', '$roleName')"
			);
		}
		else
		{
			$onclick = HtmlFilter::encode(
				"BX.AI.ShareRole.Library.Controller.handleClickOnDeleteRoleSwitcher(event, '$roleCode', '$roleName')"
			);
		}

		$className = 'ui-switcher ui-switcher-color-green';

		if ($isDeleted === true)
		{
			$className .= ' ui-switcher-off';
		}

		return '
			<div class="' . $className . '" onclick="' . $onclick . '">
				<span class="ui-switcher-cursor"></span>
				<span class="ui-switcher-disabled"></span>
				<span class="ui-switcher-enabled"></span>
			</div>
		';
	}

	protected function prepareRow(array $row): array
	{
		if (empty($this->getColumnIds()))
		{
			return $row;
		}

		$row['columns'] ??= [];

		foreach ($this->getColumnIds() as $columnId)
		{
			$data = [
				'isDeleted' => $row['data'][$columnId] ?? null,
				'roleCode' => $row['data']['ID'],
				'roleName' => $row['data']['DATA']['NAME']
			];

			$row['columns'][$columnId] = $this->prepareColumn($data);
		}

		return $row;
	}
}