<?php

namespace Bitrix\Crm\Integration\UI\EntityEditor\DefaultEntityConfig;

use Bitrix\Crm\Integration\UI\EntityEditor\AbstractDefaultEntityConfig;
use Bitrix\Crm\Item;
use Bitrix\Crm\Recurring\RecurringFieldEditorAdapter;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\EditorAdapter;
use Bitrix\Crm\Service\Factory;
use Bitrix\Main\Localization\Loc;

final class DynamicDefaultEntityConfig extends AbstractDefaultEntityConfig
{
	public function __construct(
		private readonly Factory $factory,
		private readonly array $userFieldNames = [],
		private readonly array $skipFields = [],
	)
	{
		Container::getInstance()->getLocalization()->loadMessages();
	}

	public function get(): array
	{
		$sectionMain = [
			'name' => 'main',
			'title' => Loc::getMessage('CRM_TYPE_ITEM_EDITOR_SECTION_MAIN'),
			'type' => 'section',
			'elements' => [],
		];

		if ($this->factory->isStagesEnabled() && !$this->isSkipField(Item::FIELD_NAME_STAGE_ID))
		{
			$sectionMain['elements'][] = ['name' => Item::FIELD_NAME_STAGE_ID];
		}

		if ($this->factory->isLinkWithProductsEnabled())
		{
			$sectionMain['elements'][] = ['name' => EditorAdapter::FIELD_OPPORTUNITY];
		}

		$sectionMain['elements'][] = ['name' => Item::FIELD_NAME_TITLE];

		$sections[] = $sectionMain;

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

		if ($this->factory->isLinkWithProductsEnabled())
		{
			$sections[] = [
				'name' => 'products',
				'title' => Loc::getMessage('CRM_COMMON_PRODUCTS'),
				'type' => 'section',
				'elements' => [
					['name' => EditorAdapter::FIELD_PRODUCT_ROW_SUMMARY],
				],
			];
		}

		if ($this->factory->isRecurringEnabled() && !$this->isSkipField(Item::FIELD_NAME_RECURRING))
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

	protected function isSkipField(string $fieldName): bool
	{
		return in_array($fieldName, $this->skipFields, true);
	}

	public static function getRecurringSectionTitle(): string
	{
		return Loc::getMessage('CRM_TYPE_ITEM_EDITOR_SECTION_RECURRING') ?? '';
	}
}
