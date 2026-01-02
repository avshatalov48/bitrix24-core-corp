<?php

namespace Bitrix\BIConnector\Superset\Grid\Row\Assembler\Field\Dashboard;

use Bitrix\BIConnector\Access\AccessController;
use Bitrix\BIConnector\Access\ActionDictionary;
use Bitrix\BIConnector\Superset\Grid\Row\Assembler\Field\Base\FieldAssemblerPencilTrait;
use Bitrix\Main\Grid\Row\FieldAssembler;
use Bitrix\Main\UI\Extension;

class TagTitleFieldAssembler extends FieldAssembler
{
	use FieldAssemblerPencilTrait;

	protected function prepareColumn($value): string
	{
		$id = (int)$value['ID'];
		$title = htmlspecialcharsbx($value['TITLE']);

		return $this->addPencil('TITLE', $title, $id);
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
			if ($row['data'][$columnId])
			{
				$value = [
					'TITLE' => $row['data']['TITLE'],
					'ID' => $row['data']['ID'],
				];
			}
			else
			{
				$value = [];
			}
			$row['columns'][$columnId] = $this->prepareColumn($value);
		}

		return $row;
	}
}