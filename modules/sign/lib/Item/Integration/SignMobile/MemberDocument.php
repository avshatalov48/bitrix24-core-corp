<?php


namespace Bitrix\Sign\Item\Integration\SignMobile;

use Bitrix\Main\Type\DateTime;
use Bitrix\Sign\Contract\Item;

final class MemberDocument implements Item
{
	public function __construct(
		public ?int $memberId = null,
		public ?string $memberRole = null,
		public ?DateTime $dateSigned = null,
		public ?int $documentId = null,
		public ?string $documentTitle = null,
		public ?string $documentExternalId = null,
	)
	{
	}
}