<?php

namespace Bitrix\Tasks\Replicator\Template\Regularity\Parameter;

use Bitrix\Tasks\Replicator\Template\AbstractParameter;

class RegularParameter extends AbstractParameter
{
	public function getData(): array
	{
		return $this->repository->getEntity()?->getRegularFields()?->getRegularParameters() ?? [];
	}
}