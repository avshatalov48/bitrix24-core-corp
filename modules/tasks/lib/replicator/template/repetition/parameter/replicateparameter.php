<?php

namespace Bitrix\Tasks\Replicator\Template\Repetition\Parameter;

use Bitrix\Tasks\Replicator\Template\AbstractParameter;

class ReplicateParameter extends AbstractParameter
{
	public function getData(): array
	{
		$replicateParams = unserialize($this->repository->getEntity()?->getReplicateParams() ?? '', ['allowed_classes' => false]);
		return $replicateParams === false ? [] : $replicateParams;
	}
}