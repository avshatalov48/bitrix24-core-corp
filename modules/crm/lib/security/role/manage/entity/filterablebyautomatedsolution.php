<?php

namespace Bitrix\Crm\Security\Role\Manage\Entity;

interface FilterableByAutomatedSolution
{
	public function filterByAutomatedSolution(?int $automatedSolutionId): self;
}
