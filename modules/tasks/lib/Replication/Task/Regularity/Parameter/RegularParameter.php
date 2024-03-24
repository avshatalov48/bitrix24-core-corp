<?php

namespace Bitrix\Tasks\Replication\Task\Regularity\Parameter;

use Bitrix\Tasks\Replication\Template\AbstractParameter;

class RegularParameter extends AbstractParameter
{
	public function getData(): array
	{
		return $this->repository->getEntity()?->getRegularFields()?->getRegularParameters() ?? [];
	}
}