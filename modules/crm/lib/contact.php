<?php

namespace Bitrix\Crm;

use Bitrix\Crm\UserField\UserFieldFilterable;
use Bitrix\Main\Application;
use CCrmContact;
use CCrmUserType;

class Contact extends EO_Contact implements UserFieldFilterable
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

	public function getHeading(): string
	{
		return $this->getFormattedName();
	}

	public function getFormattedName(): string
	{
		return CCrmContact::PrepareFormattedName([
			'HONORIFIC' => $this->getHonorific(),
			'NAME' => $this->getName(),
			'LAST_NAME' => $this->getLastName(),
			'SECOND_NAME' => $this->getSecondName(),
		]);
	}
}
