<?php

namespace Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;

use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;

class Address extends ContentBlock
{
	protected ?string $addressFormatted = null;

	public function getRendererName(): string
	{
		return 'AddressBlock';
	}

	public function getAddressFormatted(): ?string
	{
		return $this->addressFormatted;
	}

	public function setAddressFormatted(?string $addressFormatted): Address
	{
		$this->addressFormatted = $addressFormatted;

		return $this;
	}

	protected function getProperties(): array
	{
		return [
			'addressFormatted' => html_entity_decode($this->getAddressFormatted()),
		];
	}
}
