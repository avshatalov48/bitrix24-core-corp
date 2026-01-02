<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\Prepare;

use Bitrix\Tasks\V2\Internal\Service\Task\Action\Add\Trait\ConfigTrait;
use Bitrix\Tasks\Kanban\StagesTable;

class PrepareStage implements PrepareFieldInterface
{
	use ConfigTrait;

	public function __invoke(array $fields): array
	{
		if ($fields['GROUP_ID'] <= 0)
		{
			return $fields;
		}

		if (!isset($fields['STAGE_ID']))
		{
			return $fields;
		}

		if ($fields['STAGE_ID'] > 0)
		{
			return $fields;
		}

		$systemStage = StagesTable::getSystemStage($fields['GROUP_ID']);
		if ($systemStage === null)
		{
			return $fields;
		}

		$fields['STAGE_ID'] = $systemStage->getId();

		return $fields;
	}
}