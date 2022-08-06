<?php

namespace Bitrix\Crm;

use Bitrix\Crm\UserField\UserFieldFilterable;
use Bitrix\Main\Application;
use CCrmUserType;

class Company extends EO_Company implements UserFieldFilterable
{
	private ?array $filteredUserFields = null;

	public function getFilteredUserFields(): ?array
	{
		if (!$this->filteredUserFields)
		{
			$crmUserType = new CCrmUserType(
				Application::getUserTypeManager(),
				$this->entity->getUfId(),
				[
					'categoryId' => $this->getCategoryId(),
				]
			);

			$this->filteredUserFields = array_keys($crmUserType->GetEntityFields($this->getId()));
		}

		return $this->filteredUserFields;
	}
}
