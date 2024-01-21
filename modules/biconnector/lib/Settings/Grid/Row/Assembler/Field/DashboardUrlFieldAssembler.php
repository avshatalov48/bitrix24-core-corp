<?php

namespace Bitrix\BiConnector\Settings\Grid\Row\Assembler\Field;

use Bitrix\Main\Grid\Row\FieldAssembler;
use CUtil;

class DashboardUrlFieldAssembler extends FieldAssembler
{
	protected const DASHBOARD_VIEW_URL = 'dashboard.php?id=#ID#';

	protected function prepareRow(array $row): array
	{
		if (empty($this->getColumnIds()))
		{
			return $row;
		}

		$row['columns'] ??= [];

		foreach ($this->getColumnIds() as $columnId)
		{
			if (isset($row['data'][$columnId], $row['data']['ID']))
			{
				$value = $row['data'][$columnId];
				$url = str_replace('#ID#', urlencode($row['data']['ID']), self::DASHBOARD_VIEW_URL);
				$displayUrl = mb_strlen($value) > 100 ? mb_substr($value, 0, 97) . '...' : $value;
				$row['columns'][$columnId] = '<a href="'
					. htmlspecialcharsbx('javascript:BX.SidePanel.Instance.open(\''
					. CUtil::JSEscape($url) . '\')')
					. '">' . htmlspecialcharsEx($displayUrl)
					. '</a>';
			}
			else
			{
				$row['columns'][$columnId] = null;
			}
		}

		return $row;
	}
}
