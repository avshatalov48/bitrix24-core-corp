<?php

namespace Bitrix\Crm\Item;

use Bitrix\Crm\Binding\EntityBinding;
use Bitrix\Crm\Binding\QuoteContactTable;
use Bitrix\Crm\EO_Deal;
use Bitrix\Crm\EO_Lead;
use Bitrix\Crm\Item;
use Bitrix\Crm\QuoteTable;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\Date;

/**
 * Class QuoteItem
 * @package Bitrix\Crm\Item
 *
 * @method string|null getContent()
 * @method Item setContent(string $content)
 * @method string|null getTerms()
 * @method Item setTerms(string $terms)
 * @method string|null getComments()
 * @method Item setComments(string $comments)
 * @method string|null getQuoteNumber()
 * @method Item setQuoteNumber(string $number)
 * @method int|null getDealId()
 * @method Item setDealId(int $dealId)
 * @method int|null getLeadId()
 * @method EO_Deal|null getDeal()
 * @method Item setLeadId(int $leadId)
 * @method EO_Lead|null getLead()
 * @method int|null getStorageTypeId()
 * @method Item setStorageTypeId(int $storageTypeId)
 * @method array|null getStorageElementIds()
 * @method Item setStorageElementIds(array $storageElementIds)
 * @method int|null getPersonTypeId()
 * @method Item setPersonTypeId(int $personTypeId)
 * @method int|null getLocationId()
 * @method Item setLocationId(int $locationId)
 */
class Quote extends Item
{
	public const FIELD_NAME_CONTENT = 'CONTENT';
	public const FIELD_NAME_TERMS = 'TERMS';
	public const FIELD_NAME_COMMENTS = 'COMMENTS';
	public const FIELD_NAME_NUMBER = 'QUOTE_NUMBER';
	public const FIELD_NAME_DEAL_ID = 'DEAL_ID';
	public const FIELD_NAME_DEAL = 'DEAL';
	public const FIELD_NAME_LEAD_ID = 'LEAD_ID';
	public const FIELD_NAME_LEAD = 'LEAD';
	public const FIELD_NAME_STORAGE_TYPE = 'STORAGE_TYPE_ID';
	public const FIELD_NAME_STORAGE_ELEMENTS = 'STORAGE_ELEMENT_IDS';
	public const FIELD_NAME_PERSON_TYPE_ID = 'PERSON_TYPE_ID';

	protected function getContactBindingTableClassName(): string
	{
		return QuoteContactTable::class;
	}

	protected function compilePrimaryForBinding(array $contactBinding): array
	{
		return [
			'QUOTE_ID' => $this->getId(),
			'CONTACT_ID' => EntityBinding::prepareEntityID(\CCrmOwnerType::Contact, $contactBinding),
		];
	}

	protected function getItemReferenceFieldNameInProduct(): string
	{
		return 'QUOTE_OWNER';
	}

	protected function transformToExternalValue(string $entityFieldName, $fieldValue)
	{
		$value = parent::transformToExternalValue($entityFieldName, $fieldValue);

		$commonFieldName = $this->getCommonFieldNameByMap($entityFieldName);
		if($commonFieldName === static::FIELD_NAME_STORAGE_ELEMENTS && is_array($value))
		{
			return serialize($value);
		}

		return $value;
	}

	public function getCategoryId(): ?int
	{
		return null;
	}

	protected function disableCheckUserFields(): void
	{
		QuoteTable::disableUserFieldsCheck();
	}

	public function getTitlePlaceholder(): ?string
	{
		return static::getTitlePlaceholderFromData($this->getCompatibleData());
	}

	public static function getTitlePlaceholderFromData(array $data): ?string
	{
		Container::getInstance()->getLocalization()->loadMessages();
		$beginDate = $data['BEGINDATE'] ?? null;
		if ($beginDate)
		{
			if ($beginDate instanceof Date)
			{
				$beginDate = (string)$beginDate;
			}
			else
			{
				$beginDate = \CCrmComponentHelper::TrimDateTimeString(ConvertTimeStamp(MakeTimeStamp($beginDate), 'SHORT', SITE_ID));
			}
		}

		return Loc::getMessage('CRM_QUOTE_TITLE', [
			'#QUOTE_NUMBER#' => $data['QUOTE_NUMBER'] ?? '',
			'#BEGINDATE#' => $beginDate ?? '-',
		]);
	}
}
