<?php

namespace Bitrix\Tasks\Replicator\Template;

class ReplicateParameter extends Parameter
{
	public function getData(): array
	{
		$replicateParams = unserialize($this->repository->getTemplate()->getReplicateParams(), ['allowed_classes' => false]);
		return $replicateParams === false ? [] : $replicateParams;
	}
}