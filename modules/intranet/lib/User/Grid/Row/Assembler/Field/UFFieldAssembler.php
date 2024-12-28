<?php

namespace Bitrix\Intranet\User\Grid\Row\Assembler\Field;

use Bitrix\Intranet\User\Grid\Settings\UserSettings;
use Bitrix\Main\Grid\Row\FieldAssembler;
use Bitrix\Main\Grid\Settings;

class UFFieldAssembler extends FieldAssembler
{
	public function __construct(array $columnIds, ?Settings $settings = null)
	{
		parent::__construct($columnIds, $settings);
	}

	protected function prepareColumn($value): mixed
	{
		$field = new \Bitrix\Main\UserField\Renderer($value, [
			'mode' => $this->getSettings()->isExcelMode()
				? 'main.public_text'
				: \Bitrix\Main\UserField\Types\BaseType::MODE_VIEW,
		]);

		return $field->render();
	}

	protected function prepareRow(array $row): array
	{
		if (empty($this->getColumnIds()))
		{
			return $row;
		}

		$row['columns'] ??= [];
		$userFields = $this->getSettings()->getUserFields();

		foreach ($this->getColumnIds() as $columnId)
		{
			if (isset($row['data'][$columnId]))
			{
				$userField = $userFields[$columnId];
				$userField['VALUE'] = $row['data'][$columnId];
				$row['columns'][$columnId] = $this->prepareColumn($userField);
			}
		}

		return $row;
	}
}