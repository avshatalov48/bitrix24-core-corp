<?php

namespace Bitrix\Crm\Security\Controller;


class RegisterOptions
{
	protected $entityAttributes = [];
	protected $entityFields = [];

	public function getEntityAttributes(): array
	{
		return $this->entityAttributes;
	}

	public function setEntityAttributes(array $entityAttributes): RegisterOptions
	{
		$this->entityAttributes = $entityAttributes;

		return $this;
	}

	public function getEntityFields(): array
	{
		return $this->entityFields;
	}

	public function setEntityFields(array $entityFields): RegisterOptions
	{
		$this->entityFields = $entityFields;

		return $this;
	}
}
