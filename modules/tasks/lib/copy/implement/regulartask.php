<?php

namespace Bitrix\Tasks\Copy\Implement;

use Bitrix\Main\Copy\Container;

class RegularTask extends Task
{
	public function prepareFieldsToCopy(Container $container, array $fields)
	{
		$dictionary = $container->getDictionary();

		$dictionary['FORUM_TOPIC_ID'] = $fields['FORUM_TOPIC_ID'];
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

		return $this->cleanDataToCopy($fields);
	}
}