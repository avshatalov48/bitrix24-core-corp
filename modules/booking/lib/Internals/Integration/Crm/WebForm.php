<?php

declare(strict_types=1);

namespace Bitrix\Booking\Internals\Integration\Crm;

use Bitrix\Crm\Service\WebForm\Scenario\BaseScenario;
use Bitrix\Crm\WebForm\Internals\FormTable;
use Bitrix\Crm\WebForm\Preset;
use Bitrix\Crm\WebForm\Script;
use Bitrix\Main\Loader;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\WebForm\Manager;

class WebForm
{
	public static function isAvailable(): bool
	{
		return Loader::includeModule('crm');
	}

	public static function canRead(): bool
	{
		if (!self::isAvailable())
		{
			return false;
		}

		return Container::getInstance()->getUserPermissions()->webForm()->canRead();
	}

	public static function canEdit(): bool
	{
		if (!self::isAvailable())
		{
			return false;
		}

		return Container::getInstance()->getUserPermissions()->webForm()->canEdit();
	}

	public static function getCreateFormLink(): string
	{
		if (!self::isAvailable())
		{
			return '';
		}

		$preset = Preset::getById(BaseScenario::SCENARIO_BOOKING_AUTO_SELECTION);
		if (!$preset)
		{
			return '';
		}

		return '/crm/webform/edit/0/?SCENARIO_ID=' . $preset['XML_ID'];
	}

	public static function getFormsListLink(): string
	{
		if (!self::isAvailable())
		{
			return '';
		}

		return '/crm/webform/?IS_BOOKING_FORM=Y&apply_filter=Y';
	}

	public static function getFormsList(): array
	{
		if (
			!self::isAvailable()
			|| !self::canRead()
		)
		{
			return [];
		}

		$result = [];

		$formsList = FormTable::getDefaultTypeList([
			'select' => [
				'ID',
				'CODE',
				'SECURITY_CODE',
				'NAME',
			],
			'filter' => [
				'=IS_BOOKING_FORM' => 1,
				'=ACTIVE' => 1,
			],
			'order' => [
				'ID' => 'DESC',
			],
			'limit' => 10,
		]);

		while ($form = $formsList->fetch())
		{
			$result[] = [
				'id' => $form['ID'],
				'name' => $form['NAME'],
				'editUrl' => Manager::getEditUrl($form['ID']),
				'publicUrl' => Script::getUrlContext($form),
			];
		}

		return $result;
	}
}
