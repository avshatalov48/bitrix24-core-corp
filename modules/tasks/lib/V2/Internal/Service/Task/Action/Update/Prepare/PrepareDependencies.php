<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Prepare;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\Collection;
use Bitrix\Tasks\Control\Handler\Exception\TaskFieldValidateException;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Trait\ConfigTrait;

class PrepareDependencies implements PrepareFieldInterface
{
	use ConfigTrait;

	public function __invoke(array $fields, array $fullTaskData): array
	{
		$dependsOn = $fields['DEPENDS_ON'] ?? null;
		if (!is_array($dependsOn))
		{
			return $fields;
		}

		Collection::normalizeArrayValuesByInt($dependsOn, false);
		if (in_array((int)$fullTaskData['ID'], $dependsOn, true))
		{
			throw new TaskFieldValidateException(Loc::getMessage('TASKS_DEPENDS_ON_SELF'));
		}

		return $fields;
	}
}