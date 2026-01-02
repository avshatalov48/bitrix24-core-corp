<?php

namespace Bitrix\Crm\Integration\UI\EntityEditor\DefaultEntityConfig;

use Bitrix\Crm\Integration\UI\EntityEditor\AbstractDefaultEntityConfig;
use Bitrix\Crm\Item;
use Bitrix\Crm\Recurring\RecurringFieldEditorAdapter;
use Bitrix\Crm\RelationIdentifier;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\EditorAdapter;
use Bitrix\Crm\Service\ParentFieldManager;
use Bitrix\Main\Localization\Loc;
use CCrmOwnerType;

final class SmartInvoiceDefaultEntityConfig extends AbstractDefaultEntityConfig
{
	public function __construct(
		private readonly array $userFieldNames = [],
	)
	{
		Container::getInstance()->getLocalization()->loadMessages();
	}

	public function get(): array
	{
		$sections = [];

		$mainElements = [
			['name' => Item::FIELD_NAME_STAGE_ID],
			['name' => EditorAdapter::FIELD_OPPORTUNITY],
			['name' => Item::FIELD_NAME_BEGIN_DATE],
			['name' => Item::FIELD_NAME_CLOSE_DATE],
		];

		if ($this->isDealAndSmartInvoiceBound())
		{
			$mainElements[] = [
				'name' => ParentFieldManager::getParentFieldName(CCrmOwnerType::Deal),
			];
		}

		$mainElements[] = ['name' => Item\SmartInvoice::FIELD_NAME_COMMENTS];
		$mainElements[] = ['name' => Item\SmartInvoice::FIELD_NAME_ASSIGNED];

		$sections[] = [
			'name' => 'main',
			'title' => self::getMainSectionTitle(),
			'type' => 'section',
			'elements' => $mainElements,
		];

		$sections[] = [
			'name' => 'payer',
			'title' => Loc::getMessage('CRM_INVOICE_DETAILS_COMPONENT_PAYER_SECTION'),
			'type' => 'section',
			'elements' => [
				['name' => EditorAdapter::FIELD_CLIENT],
			],
		];

		$sections[] = [
			'name' => 'receiver',
			'title' => Loc::getMessage('CRM_INVOICE_DETAILS_COMPONENT_RECEIVER_SECTION'),
			'type' => 'section',
			'elements' => [
				['name' => Item\SmartInvoice::FIELD_NAME_MYCOMPANY_ID],
			],
		];

		$sections[] = [
			'name' => 'products',
			'title' => Loc::getMessage('CRM_COMMON_PRODUCTS'),
			'type' => 'section',
			'elements' => [
				['name' => EditorAdapter::FIELD_PRODUCT_ROW_SUMMARY],
			],
		];

		$sectionAdditional = [
			'name' => 'additional',
			'title' => Loc::getMessage('CRM_TYPE_ITEM_EDITOR_SECTION_ADDITIONAL'),
			'type' => 'section',
			'elements' => [],
		];

		foreach ($this->userFieldNames as $fieldName)
		{
			$sectionAdditional['elements'][] = [
				'name' => $fieldName,
			];
		}

		$sections[] = $sectionAdditional;

		$factory = Container::getInstance()->getFactory(\CCrmOwnerType::SmartInvoice);
		if ($factory?->isRecurringEnabled())
		{
			$sections[] = [
				'name' => 'recurring',
				'title' => self::getRecurringSectionTitle(),
				'type' => 'section',
				'elements' => [
					['name' => RecurringFieldEditorAdapter::FIELD_RECURRING],
				],
			];
		}

		return $sections;
	}

	public static function getMainSectionTitle(): ?string
	{
		return Loc::getMessage('CRM_INVOICE_DETAILS_COMPONENT_MAIN_SECTION');
	}

	private function isDealAndSmartInvoiceBound(): bool
	{
		$identifier = new RelationIdentifier(CCrmOwnerType::Deal, CCrmOwnerType::SmartInvoice);

		return Container::getInstance()
			->getRelationManager()
			->areTypesBound($identifier);
	}

	public static function getRecurringSectionTitle(): string
	{
		return Loc::getMessage('CRM_INVOICE_DETAILS_COMPONENT_RECURRING_SECTION') ?? '';
	}
}
