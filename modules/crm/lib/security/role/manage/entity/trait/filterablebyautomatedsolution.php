<?php

namespace Bitrix\Crm\Security\Role\Manage\Entity\Trait;

trait FilterableByAutomatedSolution
{
	protected ?int $filterByAutomatedSolutionId = null;

	public function filterByAutomatedSolution(?int $automatedSolutionId): self
	{
		$this->filterByAutomatedSolutionId = $automatedSolutionId;

		return $this;
	}
}
