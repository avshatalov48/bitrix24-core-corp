<?php

namespace Bitrix\Sign\Item\Api\Document\Signing;

use Bitrix\Main\Type\DateTime;
use Bitrix\Sign\Contract;
use Bitrix\Sign\Item\Api\Property;

class ConfigureRequest implements Contract\Item
{
	public function __construct(
		public string $documentUid,
		public string $title,
		public Property\Request\Signing\Configure\Owner $owner,
		public int $parties,
		public string $scenario,
		public Property\Request\Signing\Configure\FieldCollection $fields,
		public Property\Request\Signing\Configure\BlockCollection $blocks,
		public Property\Request\Signing\Configure\MemberCollection $members,
		public string $langId,
		public ?string $companyUid = null,
		public ?string $nameFormat = null,
		public ?string $regionDocumentType = null,
		public ?string $externalId = null,
		public ?string $titleWithoutNumber = null,
		public ?string $scheme = null,
		public string $externalDateCreate = '',
		public ?string $dateFormat = null,
		public ?string $dateTimeFormat = null,
		public ?int $weekStart = null,
	)
	{}

}
