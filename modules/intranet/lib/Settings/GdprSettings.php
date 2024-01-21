<?php

namespace Bitrix\Intranet\Settings;

use Bitrix\Intranet\Binding\Marketplace;
use Bitrix\Main\Error;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Config\Option;

class GdprSettings extends AbstractSettings
{
	public const TYPE = 'gdpr';

	public static function isAvailable(): bool
	{
		return self::isGdprAvailable();
	}

	public static function isGdprAvailable(): bool
	{
		return (
			Loader::includeModule('bitrix24')
			&& !in_array(\CBitrix24::getLicensePrefix(), array('ru', 'ua', 'kz', 'by'))
		);
	}

	public function validate(): ErrorCollection
	{
		$errors = new ErrorCollection();
		$isFilled = false;

		foreach ($this->data as $field => $data)
		{
			if (!empty($data) && $field !== 'date')
			{
				$isFilled = true;
				break;
			}
		}

		if (!$isFilled)
		{
			return $errors;
		}

		foreach ($this->data as $field => $data)
		{
			if (empty($data))
			{
				$errors->setError(
					new Error(
						Loc::getMessage('SETTINGS_FORMAT_GDPR_EMPTY_ERROR'),
						0,
						[
							'page' => $this->getType(),
							'field' => $field,
						]
					)
				);
			}
		}

		if (!empty($this->data['notificationEmail']) && !check_email($this->data['notificationEmail']))
		{
			$errors->setError(
				new Error(
					Loc::getMessage('SETTINGS_FORMAT_GDPR_EMAIL_ERROR'),
					0,
					[
						'page' => $this->getType(),
						'field' => 'notificationEmail',
					]
				)
			);
		}

		return $errors;
	}


	public function save(): Result
	{
		if (!self::isGdprAvailable())
		{
			return new Result();
		}

		if (
			!empty($this->data['companyTitle'])
			&& !empty($this->data['contactName'])
			&& !empty($this->data['notificationEmail'])
			&& !empty($this->data['date'])
		)
		{
			Option::set('bitrix24', 'gdpr_legal_name', $this->data['companyTitle']);
			Option::set('bitrix24', 'gdpr_contact_name', $this->data['contactName']);
			Option::set('bitrix24', 'gdpr_date', $this->data['date']);
			Option::set('bitrix24', 'gdpr_notification_email', $this->data['notificationEmail']);
		}

		return new Result();
	}

	public function get(): SettingsInterface
	{
		if (!self::isGdprAvailable())
		{
			return new static();
		}

		$data = [];

		$data['companyTitle'] = Option::get('bitrix24', 'gdpr_legal_name');
		$data['contactName'] = Option::get('bitrix24', 'gdpr_contact_name');
		$data['notificationEmail'] = Option::get('bitrix24', 'gdpr_notification_email');
		$data['date'] = Option::get('bitrix24', 'gdpr_date', '');

		if (empty($data['date']))
		{
			$date = new Date();
			$data['date'] = $date->toString();
		}

		$data['dpaLink'] = $this->getDpaLink();
		$data['marketDirectory'] = Marketplace::getMainDirectory();

		return new static($data);
	}

	private function getDpaLink(): string
	{
		$areaConfig = \CBitrix24::getAreaConfig(\CBitrix24::getPortalZone());
		$domain = isset($areaConfig) ? $areaConfig['DEFAULT_DOMAIN'] : '.bitrix24.com';

		return 'https://www' . $domain . '/upload/DPA/BitrixDPA.pdf';
	}
}