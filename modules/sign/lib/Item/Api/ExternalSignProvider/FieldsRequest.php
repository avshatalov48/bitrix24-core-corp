<?php

namespace Bitrix\Sign\Item\Api\ExternalSignProvider;

use Bitrix\Main\Type\Contract\Arrayable;
use Bitrix\Sign\Contract\Item;

class FieldsRequest implements Item, Arrayable
{
	public function __construct(
		public ?string  $title = null,
		public ?string $description = null,
		public ?string $iconUri = null,
		public ?string $companyRegUri = null,
		public ?string $documentSignUri = null,
		public ?string $publicKeyUri = null,
	)
	{
	}

	public function toArray(): array
	{
		return [
			'title' => $this->title,
			'description' => $this->description,
			'iconUri' => $this->iconUri,
			'companyRegUri' => $this->companyRegUri,
			'documentSignUri' => $this->documentSignUri,
			'publicKeyUri' => $this->publicKeyUri,
		];
	}
}
