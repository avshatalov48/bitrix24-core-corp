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
			'hcmLinkCompanyId',
			'hcmLinkDocumentTypeSettingId',
			'hcmLinkExternalIdSettingId',
			'hcmLinkDateSettingId' => empty($value) ? null : $value,
			'scenario' => $this->documentRepository->getScenarioIdByName($value),
			'scheme' => $this->documentRepository->getSchemeIdByType($value),
			'initiator' => $this->documentRepository->getModelMetaByItem($this->item),
			default => parent::convertItemValueToModelValue($value, $itemPropertyName),
		};
	}

	protected function getModelFieldByItemProperty(string $itemProperty): string
	{
		return match ($itemProperty)
		{
			'hcmLinkDocumentTypeSettingId' => 'HCMLINK_DOCUMENT_TYPE_SETTING_ID',
			'hcmLinkExternalIdSettingId' => 'HCMLINK_EXTERNAL_ID_SETTING_ID',
			'hcmLinkDateSettingId' => 'HCMLINK_DATE_SETTING_ID',
			'hcmLinkCompanyId' => 'HCMLINK_COMPANY_ID',
			'initiator' => 'META',
			default => parent::getModelFieldByItemProperty($itemProperty),
		};
	}

	private function isAllowNullValue(string $name): bool
	{
		$properties = [
			'groupId',
			'externalId',
			'externalDateCreate',
		];

		return in_array($name, $properties, true);
	}
}