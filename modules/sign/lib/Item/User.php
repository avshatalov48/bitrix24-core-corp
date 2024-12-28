<?php

namespace Bitrix\Sign\Item;

use Bitrix\Sign\Contract;

final class User implements Contract\Item
{
	public function __construct(
		public readonly ?int $id = null,
		public readonly ?string $name = null,
		public readonly ?string $lastName = null,
		public readonly ?string $secondName = null,
		public readonly ?int $personalPhotoId = null,
		public readonly ?string $notificationLanguageId	= null,
	)
	{
	}
}
