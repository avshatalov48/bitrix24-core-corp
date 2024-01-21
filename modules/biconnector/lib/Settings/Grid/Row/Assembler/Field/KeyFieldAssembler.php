<?php

namespace Bitrix\BiConnector\Settings\Grid\Row\Assembler\Field;

use Bitrix\Main\Grid\Row\FieldAssembler;
use Bitrix\Main\Localization\Loc;

class KeyFieldAssembler extends FieldAssembler
{
	protected function prepareRow(array $row): array
	{
		if (empty($this->getColumnIds()))
		{
			return $row;
		}

		$row['columns'] ??= [];

		foreach ($this->getColumnIds() as $columnId)
		{
			if (!isset($row['data'][$columnId]))
			{
				$row['columns'][$columnId] = null;
				continue;
			}

			$key = '';
			$text = \CUtil::JSEscape(Loc::getMessage('BICONNECTOR_SETTINGS_GRID_RIW_ASSEMBLER_KEY_FIELD'));

			if (isset($row['data']['ACCESS_KEY']))
			{
				$key = \CUtil::JSEscape($row['data']['ACCESS_KEY']);
			}

			if (defined('LANGUAGE_ID'))
			{
				$key .= LANGUAGE_ID;
			}

			$result = '
				<a onclick="BX.BIConnector.KeysGrid.copyKey(this)" style="text-decoration: none" class="ui-btn ui-btn-xs ui-btn-light-border ui-btn-round">
					' . $text . '
					<input data-key-id="" type="hidden" value="' . $key . '"/>
				</a>
			';

			$row['columns'][$columnId] = $result;
		}

		return $row;
	}
}
