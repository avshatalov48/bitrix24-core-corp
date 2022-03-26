<?php

namespace Bitrix\Crm\Service\WebForm\Scenario\DependencyScenario;

use Bitrix\Crm\WebForm\Internals\FieldDepGroupTable;
use Bitrix\Main\Localization\Loc;

class DependencyExcludingScenario implements DependencyScenario
{
	/**
	 * @inheritDoc
	 */
	public function getFields(): array
	{
		return [
			[
				'type' => 'layout',
				'label' => Loc::getMessage('CRM_SERVICE_FORM_SCENARIO_PHYSICS'),
				'name' => 'section_' . mt_rand(1000000, 9999999),
				'content' => ['type' => 'section'],
			],
			['name' => 'CONTACT_NAME', 'autocomplete' => true],
			['name' => 'CONTACT_PHONE', 'autocomplete' => true,],
			[
				'type' => 'hr',
				'name' => 'hr_' . mt_rand(1000000, 9999999),
			],
			[
				'type' => 'layout',
				'label' => Loc::getMessage('CRM_SERVICE_FORM_SCENARIO_JURIDIC'),
				'name' => 'section_' . mt_rand(1000000, 9999999),
				'content' => ['type' => 'section'],
			],
			['name' => 'COMPANY_TITLE', 'autocomplete' => true,],
			['name' => 'COMPANY_ADDRESS', 'autocomplete' => true,],
			['name' => 'COMPANY_REG_ADDRESS', 'autocomplete' => true,],
			['name' => 'COMPANY_INDUSTRY', 'autocomplete' => true,],
			['name' => 'CONTACT_POST', 'autocomplete' => true,],
			['name' => 'COMPANY_PHONE', 'autocomplete' => true,],
			['name' => 'COMPANY_EMAIL', 'autocomplete' => true,],
		];
	}

	/**
	 * @inheritDoc
	 */
	public function getDependencies(): array
	{
		$dependencyItems = [
			DependencyListItem::of(
				DependencyAction::of('CONTACT_POST', 'show'),
				DependencyCondition::of('change', 'any', 'COMPANY_TITLE')
			),
			DependencyListItem::of(
				DependencyAction::of('COMPANY_PHONE', 'show'),
				DependencyCondition::of('change', 'any', 'COMPANY_TITLE')
			),
			DependencyListItem::of(
				DependencyAction::of('COMPANY_EMAIL', 'show'),
				DependencyCondition::of('change', 'any', 'COMPANY_TITLE')
			),
			DependencyListItem::of(
				DependencyAction::of('CONTACT_POST', 'show'),
				DependencyCondition::of('change', 'any', 'COMPANY_REG_ADDRESS')
			),
			DependencyListItem::of(
				DependencyAction::of('COMPANY_PHONE', 'show'),
				DependencyCondition::of('change', 'any', 'COMPANY_REG_ADDRESS')
			),
			DependencyListItem::of(
				DependencyAction::of('COMPANY_EMAIL', 'show'),
				DependencyCondition::of('change', 'any', 'COMPANY_REG_ADDRESS')
			),
			DependencyListItem::of(
				DependencyAction::of('CONTACT_POST', 'show'),
				DependencyCondition::of('change', 'any', 'COMPANY_ADDRESS')
			),
			DependencyListItem::of(
				DependencyAction::of('COMPANY_PHONE', 'show'),
				DependencyCondition::of('change', 'any', 'COMPANY_ADDRESS')
			),
			DependencyListItem::of(
				DependencyAction::of('COMPANY_EMAIL', 'show'),
				DependencyCondition::of('change', 'any', 'COMPANY_ADDRESS')
			),
		];

		return [
			DependencyItem::of($dependencyItems, 'and', FieldDepGroupTable::TYPE_OR)
		];
	}
}