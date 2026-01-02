<?php

namespace Bitrix\Crm\Grid;

final class MenuActionsPreparer
{
	public function prepare(array $actions): array
	{
		$result = [];

		$isPrevActionIsSeparator = false;
		foreach ($actions as $index => $action)
		{
			$isSeparator = !empty($action['SEPARATOR']);

			if (!$isSeparator)
			{
				$result[] = $action;
				$isPrevActionIsSeparator = false;

				continue;
			}

			if (($index === count($actions) - 1) || $isPrevActionIsSeparator)
			{
				continue;
			}

			$result[] = $action;
			$isPrevActionIsSeparator = true;
		}

		return $result;
	}
}
