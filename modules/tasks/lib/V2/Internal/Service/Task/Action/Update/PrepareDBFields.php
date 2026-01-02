<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Update;

use Bitrix\Main\ObjectException;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Prepare\PrepareFieldInterface;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Trait\ConfigTrait;
use Bitrix\Tasks\Internals\TaskTable;
use Throwable;

class PrepareDBFields implements PrepareFieldInterface
{
	use ConfigTrait;

	public function __invoke(array $fields, array $fullTaskData): array
	{
		$tableFields = TaskTable::getEntity()->getFields();

		foreach ($fields as $fieldName => $value)
		{
			if (!array_key_exists($fieldName, $tableFields))
			{
				unset($fields[$fieldName]);
				continue;
			}

			if (str_starts_with($fieldName, "UF_"))
			{
				unset($fields[$fieldName]);
				continue;
			}

			if (
				$tableFields[$fieldName] instanceof DatetimeField
				&& !empty($value)
			)
			{
				try
				{
					$fields[$fieldName] = DateTime::createFromUserTime($value);
				}
				catch (Throwable)
				{
					throw new ObjectException('Incorrect date/time');
				}
			}
		}

		return $fields;
	}
}
