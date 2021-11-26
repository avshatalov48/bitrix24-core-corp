<?php

namespace Bitrix\Crm;

class Contact extends EO_Contact
{
	public function getFormattedName(): string
	{
		return \CCrmContact::PrepareFormattedName([
			'HONORIFIC' => $this->getHonorific(),
			'NAME' => $this->getName(),
			'LAST_NAME' => $this->getLastName(),
			'SECOND_NAME' => $this->getSecondName(),
		]);
	}
}
