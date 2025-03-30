<?php

namespace Bitrix\Sign\Model\ItemBinder;

use Bitrix\Main\ORM\Objectify\EntityObject;
use Bitrix\Sign\Item\Document;
use Bitrix\Sign\Repository\DocumentRepository;

class DocumentBinder extends BaseItemToModelBinder
{
	public function __construct(
		private readonly Document $item,
		private readonly EntityObject $model,
		private readonly DocumentRepository $documentRepository,
	)
	{
		parent::__construct($this->item, $this->model);
	}

	protected function isItemPropertyShouldSetToItem(mixed $currentValue, mixed $originalValue, string $name): bool
	{
		if ($currentValue === null && !$this->isAllowNullValue($name))
		{
			return false;
		}

		return parent::isItemPropertyShouldSetToItem($currentValue, $originalValue,	$name);
	}

	protected function convertItemValueToModelValue(mixed $value, string $itemPropertyName): mixed
	{
		return match ($itemPropertyName)
		{
			'scenario' => $this->documentRepository->getScenarioIdByName($value),
			'scheme' => $this->documentRepository->getSchemeIdByType($value),
			'hcmLinkCompanyId' => empty($value) ? null : $value,
			'initiator' => $this->documentRepository->getModelMetaByItem($this->item),
			default => parent::convertItemValueToModelValue($value, $itemPropertyName),
		};
	}

	protected function getModelFieldByItemProperty(string $itemProperty): string
	{
		return match ($itemProperty)
		{
			'hcmLinkCompanyId' => 'HCMLINK_COMPANY_ID',
			'initiator' => 'META',
			default => parent::getModelFieldByItemProperty($itemProperty),
		};
	}

	private function isAllowNullValue(string $name): bool
	{
		$properties = [
			'groupId',
		];

		return in_array($name, $properties, true);
	}
}