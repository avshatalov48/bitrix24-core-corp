<?php

namespace Bitrix\Tasks\Grid\Scope\Types;

use Bitrix\Tasks\Grid\ScopeStrategyInterface;
use Bitrix\Tasks\Helper\Grid;

class RelationStrategy implements ScopeStrategyInterface
{
	public function apply(array &$gridHeaders, array $parameters = []): void
	{
		$defaultFields = ['TITLE', 'RESPONSIBLE_NAME', 'DEADLINE', 'ORIGINATOR_NAME'];

		foreach ($gridHeaders as $name => $header)
		{
			$gridHeaders[$name]['default'] = in_array($name, $defaultFields);
		}

		$options = Grid::getInstance(0, 0, $parameters['GRID_ID'])->getOptions();
		if (empty($options->getOptions()['views']['default']['columns']))
		{
			$options->setColumns(implode(',', $defaultFields));
			$options->save();
		}
	}
}
