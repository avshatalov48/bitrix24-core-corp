<?php

namespace Bitrix\Crm\Service\WebForm;

class ScenarioMenuItem
{
	public const FIELDS = [
		'id' => 'fields',
		'data' => ['extension' => 'landing.ui.panel.formsettingspanel.content.fields'],
	];
	public const AGREEMENTS = [
		'id' => 'agreements',
		'important' => true,
		'data' => ['extension' => 'landing.ui.panel.formsettingspanel.content.agreements'],
	];
	public const CRM = [
		'id' => 'crm',
		'data' => ['extension' => 'landing.ui.panel.formsettingspanel.content.crm'],
	];
	public const PAY_SYSTEMS = [
		'id' => 'pay-systems',
		'data' => ['extension' => 'landing.ui.panel.formsettingspanel.content.pay-systems']
	];
	public const BUTTON_AND_HEADER = [
		'id' => 'header-and-button',
		'data' => ['extension' => 'landing.ui.panel.formsettingspanel.content.header-and-button'],
	];
	public const SPAM_PROTECTION = [
		'id' => 'spam-protection',
		'data' => ['extension' => 'landing.ui.panel.formsettingspanel.content.spam-protection'],
	];
	public const FIELDS_RULES = [
		'id' => 'fields-rules',
		'data' => ['extension' => 'landing.ui.panel.formsettingspanel.content.fields-rules'],
	];
	public const ACTIONS = [
		'id' => 'actions',
		'data' => ['extension' => 'landing.ui.panel.formsettingspanel.content.actions'],
	];
	public const DEFAULT_VALUES = [
		'id' => 'default-values',
		'data' => ['extension' => 'landing.ui.panel.formsettingspanel.content.default-values'],
	];
	public const ANALYTICS = [
		'id' => 'analytics',
		'data' => ['extension' => 'landing.ui.panel.formsettingspanel.content.analytics'],
	];
	public const FACEBOOK = [
		'id' => 'facebook',
		'data' => ['extension' => 'landing.ui.panel.formsettingspanel.content.facebook'],
	];
	public const VK = [
		'id' => 'vk',
		'data' => ['extension' => 'landing.ui.panel.formsettingspanel.content.vk'],
	];
	public const CALLBACK = [
		'id' => 'callback',
		'data' => ['extension' => 'landing.ui.panel.formsettingspanel.content.callback'],
	];
	public const OTHER = [
		'id' => 'other',
		'data' => ['extension' => 'landing.ui.panel.formsettingspanel.content.other'],
	];
	public const WHATSAPP = [
		'id' => 'whatsapp',
		'data' => ['extension' => 'landing.ui.panel.formsettingspanel.content.whatsapp'],
	];
	public const DESIGN = [
		'id' => 'design',
		'data' => ['extension' => 'landing.ui.panel.formsettingspanel.content.design'],
	];

	/**
	 * Get Sidebar Menu Items
	 * @return array
	 */
	public static function getMenuItems(): array
	{
		$class = new \ReflectionClass(__CLASS__);
		return $class->getConstants();
	}
}