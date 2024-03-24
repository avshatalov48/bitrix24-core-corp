<?php

namespace Bitrix\Tasks\Copy\Implement;

use Bitrix\Main\Copy\Container;

class OrdinaryTask extends Task
{
	private int $parentTaskId = 0;

	public function prepareFieldsToCopy(Container $container, array $fields): array
	{
		$dictionary = $container->getDictionary();

		$dictionary['FORUM_TOPIC_ID'] = null;
		$dictionary['REPLICATE'] = ($fields['REPLICATE'] === 'Y' ? 'Y' : 'N');

		$container->setDictionary($dictionary);

		if ($this->targetGroupId)
		{
			$fields['GROUP_ID'] = $this->targetGroupId;
		}

		if (!empty($container->getParentId()))
		{
			$fields['PARENT_ID'] = $container->getParentId();
		}
		elseif ($this->parentTaskId > 0)
		{
			$fields['PARENT_ID'] = $this->parentTaskId;
		}

		return $this->cleanDataToCopy($fields);
	}

	public function setParentTaskId(int $taskId): static
	{
		$this->parentTaskId = $taskId;
		return $this;
	}
}