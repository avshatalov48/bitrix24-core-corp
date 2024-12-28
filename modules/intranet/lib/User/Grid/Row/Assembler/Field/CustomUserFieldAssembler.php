<?php

namespace Bitrix\Intranet\User\Grid\Row\Assembler\Field;

use Bitrix\Intranet\Entity\Collection\UserCollection;
use Bitrix\Intranet\Entity\User;
use Bitrix\Intranet\User\Grid\Settings\UserSettings;
use Bitrix\Main\Grid\Row\FieldAssembler;

/**
 * @method UserSettings getSettings()
 */
abstract class CustomUserFieldAssembler extends FieldAssembler
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
			if ($this->getSettings()->isExcelMode())
			{
				$row['columns'][$columnId] = $this->prepareColumnForExport($row['data']);
			}
			else
			{
				$row['columns'][$columnId] = $this->prepareColumn($row['data']);
			}
		}

		return $row;
	}

	protected function prepareColumnForExport($data): string
	{
		return $this->prepareColumn($data);
	}

	protected function getUserEntityById(int $userId): ?User
	{
		return $this->getSettings()
			->getUserCollection()
			->getByUserId($userId);
	}
}