<?php
namespace Bitrix\Sign;

use Bitrix\Bitrix24\Feature;
use Bitrix\Main\Loader;

class Restriction
{
	/**
	 * Features map.
	 *
	 * Array<local_code, bitrix24_code>
	 */
	private const FEATURES = [
		'sign_available' => 'sign',
		'sms_allowed' => 'sign_sms_allowed',
		'new_docs_allowed' => 'sign_document_month_allowed',
		'crm_integration' => 'sign_integration_crm'
	];

	/**
	 * @return bool
	 */
	public static function isSignAvailable(): bool
	{
		if (Loader::includeModule('bitrix24'))
		{
			return
				Feature::isFeatureEnabled(self::FEATURES['sign_available'])
				|| \CBitrix24::getLicenseType() === 'project'
			;
		}

		return true;
	}

	/**
	 * Returns true if SMS is allowed by tariff.
	 *
	 * @return bool
	 */
	public static function isSmsAllowed(): bool
	{
		if (Loader::includeModule('bitrix24'))
		{
			return Feature::isFeatureEnabled(self::FEATURES['sms_allowed']);
		}

		return true;
	}

	public static function isCrmIntegrationAvailable(): bool
	{
		if (Loader::includeModule('bitrix24'))
		{
			return Feature::isFeatureEnabled(self::FEATURES['crm_integration']);
		}

		return true;
	}

	/**
	 * Returns true if user can create new document.
	 *
	 * @return bool
	 */
	public static function isNewDocAllowed(): bool
	{
		if (!Loader::includeModule('bitrix24'))
		{
			return true;
		}

		$monthAllowed = Feature::getVariable(self::FEATURES['new_docs_allowed']);
		if ($monthAllowed <= 0)
		{
			return true;
		}

		$date = new \Bitrix\Main\Type\DateTime;
		$date->add('-1 month');

		$row = Document::getList([
			'select' => [
				'CNT'
			],
			'filter' => [
				'>DATE_CREATE' => $date
			],
			'runtime' => [
				new \Bitrix\Main\Entity\ExpressionField(
					'CNT', 'COUNT(*)'
				)
			]
		])->fetch();

		if (($row['CNT'] ?? null) && $row['CNT'] >= $monthAllowed)
		{
			return false;
		}

		return true;
	}
}
