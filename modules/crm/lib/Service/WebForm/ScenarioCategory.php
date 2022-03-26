<?php

namespace Bitrix\Crm\Service\WebForm;

class ScenarioCategory
{
	public const CRM = 'crm';
	public const PRODUCTS = 'products';
	public const SOCIAL = 'social';
	public const CRM_AUTOMATION = 'crm_automation';
	public const DELIVERY = 'delivery';
	public const EVENTS = 'events';
	// public const PREPARE_FORM = 'prepare_form';
	public const CRM_FILLING = 'crm_filling';
	public const DEPENDENCY_FIELD = 'dependency_field';
	public const OTHER = 'other';

	/**
	 * Get Categories for scenario
	 * @return array
	 */
	public static function getCategories(): array
	{
		$class = new \ReflectionClass(__CLASS__);
		$constants = $class->getConstants();

		return array_flip($constants);
	}
}