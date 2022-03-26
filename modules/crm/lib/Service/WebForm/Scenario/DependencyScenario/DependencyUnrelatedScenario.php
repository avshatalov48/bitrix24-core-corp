<?php

namespace Bitrix\Crm\Service\WebForm\Scenario\DependencyScenario;

use Bitrix\Main\Localization\Loc;

class DependencyUnrelatedScenario implements DependencyScenario
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
			['name' => 'COMPANY_PHONE', 'autocomplete' => true,],
			['name' => 'COMPANY_EMAIL', 'autocomplete' => true,],
			['name' => 'COMPANY_ADDRESS', 'autocomplete' => true,],
			['name' => 'COMPANY_REG_ADDRESS', 'autocomplete' => true,],
			['name' => 'COMPANY_INDUSTRY', 'autocomplete' => true,],
		];
	}

	/**
	 * @inheritDoc
	 */
	public function getDependencies(): array
	{
		$dependencyItems = [
			DependencyListItem::of(
				DependencyAction::of('COMPANY_ADDRESS', 'show'),
				DependencyCondition::of('change', 'any', 'COMPANY_TITLE')
			),
			DependencyListItem::of(
				DependencyAction::of('COMPANY_REG_ADDRESS', 'show'),
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
		];

		return [
			DependencyItem::of($dependencyItems),
		];
	}
}