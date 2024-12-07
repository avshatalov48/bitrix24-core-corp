<?php

namespace Bitrix\Sign\Item\B2e;

class DocumentRequiredField extends RequiredField
{
	public function __construct(
		public string $type,
		public string $role,
		public int $documentId,
		public ?int $id = null,
	)
	{
		parent::__construct($type, $role);
	}
}