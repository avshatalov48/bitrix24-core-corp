<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Integration\CRM\Repository\Mapper;

use Bitrix\Tasks\V2\Internal\Integration\CRM\Entity\CrmItem;
use Bitrix\Tasks\V2\Internal\Integration\CRM\Entity\CrmItemCollection;

class CrmItemMapper
{
	public function __construct(
		private readonly CrmItemTypeMapper $typeMapper,
	)
	{

	}

	public function mapToEntity(array $crmItem): CrmItem
	{
		return new CrmItem(
			id: CrmItem::mapString($crmItem, 'id'),
			entityId: CrmItem::mapInteger($crmItem, 'entityId'),
			type: $this->typeMapper->mapToEnum(CrmItem::mapInteger($crmItem, 'typeId')),
			typeName: CrmItem::mapString($crmItem, 'typeName'),
			title: CrmItem::mapString($crmItem, 'title'),
			link: CrmItem::mapString($crmItem, 'link'),
		);
	}

	public function mapToCollection(array $crmItems): CrmItemCollection
	{
		$entities = [];
		foreach ($crmItems as $crmItem)
		{
			$entities[] = $this->mapToEntity($crmItem);
		}

		return new CrmItemCollection(...$entities);
	}
}