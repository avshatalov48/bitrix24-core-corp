<?php

namespace Bitrix\Mobile\Feedback;

use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use CBitrix24;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\ModuleManager;

class FeedbackFormProvider
{
	public const ALLOWED_HIDDEN_FIELDS = [
		'c_email' => FILTER_VALIDATE_EMAIL,
		'c_name' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
		'from_domain' => FILTER_VALIDATE_URL,
		'b24_plan' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
		'sender_page' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
		'os_phone' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
		'app_version' => FILTER_VALIDATE_INT,
		'back_version' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
		'b24_type' => FILTER_VALIDATE_BOOLEAN,
		'os_version' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
		'region_model' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
		'phone_model' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
	];

	public const FORMS = [
		'copilotRoles' => [
			'ru-by-kz' => [
				'portalZones' => ['ru', 'by', 'kz'],
				'formId' => 746,
				'sec' => 'we50kv',
				'lang' => 'ru',
			],
			'de' => [
				'portalZones' => ['de'],
				'formId' => 742,
				'sec' => 'vqqxgr',
				'lang' => 'de',
			],
			'com.br' => [
				'portalZones' => ['com.br'],
				'formId' => 744,
				'sec' => 'nz3zig',
				'lang' => 'br',
			],
			'es' => [
				'portalZones' => ['es'],
				'formId' => 738,
				'sec' => '77ui4p',
				'lang' => 'es',
			],
			'en' => [
				'isDefault' => true,
				'portalZones' => ['en'],
				'formId' => 740,
				'sec' => 'obza3e',
				'lang' => 'en',
			],
		],
		'aiAssistant' => [
			'ru-kz-by-uz' => [
				'portalZones' => ['ru', 'kz', 'by', 'uz'],
				'formId' => 2982,
				'sec' => 'vqmcxn',
				'lang' => 'ru',
			],
			'com.br' => [
				'portalZones' => ['com.br'],
				'formId' => 840,
				'sec' => 'ufjnte',
				'lang' => 'br',
			],
			'es' => [
				'portalZones' => ['es'],
				'formId' => 838,
				'sec' => 'm82wkx',
				'lang' => 'es',
			],
			'de' => [
				'portalZones' => ['de'],
				'formId' => 836,
				'sec' => 'frcsm3',
				'lang' => 'de',
			],
			'en' => [
				'isDefault' => true,
				'portalZones' => ['en'],
				'formId' => 834,
				'sec' => 'qnauno',
				'lang' => 'en',
			],
		],
		'appFeedbackLight' => [
			'ru-by-kz' => [
				'portalZones' => ['ru', 'by', 'kz'],
				'formId' => 586,
				'sec' => 'nkupl5',
				'lang' => 'ru',
			],
			'de' => [
				'portalZones' => ['de'],
				'formId' => 802,
				'sec' => 'lgshoi',
				'lang' => 'de',
			],
			'com.br' => [
				'portalZones' => ['com.br'],
				'formId' => 806,
				'sec' => '4lxtzv',
				'lang' => 'br',
			],
			'es' => [
				'portalZones' => ['es'],
				'formId' => 804,
				'sec' => '6vz7b5',
				'lang' => 'es',
			],
			'en' => [
				'isDefault' => true,
				'portalZones' => ['en'],
				'formId' => 582,
				'sec' => 'dyd2ut',
				'lang' => 'en',
			],
		],
		'appFeedbackDark' => [
			'ru-by-kz' => [
				'portalZones' => ['ru', 'by', 'kz'],
				'formId' => 810,
				'sec' => 'z0e29c',
				'lang' => 'ru',
			],
			'de' => [
				'portalZones' => ['de'],
				'formId' => 812,
				'sec' => 'n27x9c',
				'lang' => 'de',
			],
			'com.br' => [
				'portalZones' => ['com.br'],
				'formId' => 816,
				'sec' => 'v35xqq',
				'lang' => 'br',
			],
			'es' => [
				'portalZones' => ['es'],
				'formId' => 814,
				'sec' => '30eaek',
				'lang' => 'es',
			],
			'en' => [
				'isDefault' => true,
				'portalZones' => ['en'],
				'formId' => 808,
				'sec' => '8al21c',
				'lang' => 'en',
			],
		],
	];

	public static function getFormConfig(string $formId): array
	{
		$targetForm = self::FORMS[$formId] ?? null;
		if (!isset($targetForm) || !is_array($targetForm))
		{
			return [];
		}

		$result = [];
		foreach ($targetForm as $regionData)
		{
			if (empty($regionData['portalZones'])
				|| empty($regionData['formId'])
				|| empty($regionData['lang'])
				|| empty($regionData['sec']))
			{
				continue;
			}

			$result[] = [
				'zones' => $regionData['portalZones'],
				'id' => (int)$regionData['formId'],
				'lang' => $regionData['lang'],
				'sec' => $regionData['sec'],
			];
		}

		return $result;
	}

	public static function getFormData(string $formId): array|null
	{
		$targetForm = self::FORMS[$formId] ?? null;
		if (!empty($targetForm) && is_array($targetForm))
		{
			$portalZone = \Bitrix\Main\Application::getInstance()->getLicense()->getRegion() ?: "en";
			$defaultFormData = null;
			foreach ($targetForm as $portalZonesData)
			{
				if (in_array($portalZone, $portalZonesData['portalZones']))
				{
					return $portalZonesData;
				}
				if (($portalZonesData['isDefault'] ?? false) === true)
				{
					$defaultFormData = $portalZonesData;
				}
			}

			return $defaultFormData;
		}

		return null;
	}

	/**
	 * @throws LoaderException
	 * @throws \JsonException
	 */
	public static function getHiddenFieldsParams(?string $hiddenFields): ?array
	{
		$userFields = self::getUserFields();
		$systemFields = self::getPortalFields();

		if ($hiddenFields === null)
		{
			return array_merge($userFields, $systemFields);
		}

		$filteredFields = self::filterHiddenFields($hiddenFields);

		return array_merge($userFields, $systemFields, $filteredFields);
	}

	private static function getUserFields(): array
	{
		return [
			'c_name' => CurrentUser::get()->getFullName(),
			'c_email' => CurrentUser::get()->getEmail(),
		];
	}

	/**
	 * @throws LoaderException
	 */
	private static function getPortalFields(): array
	{
		$result = [];
		if (Loader::includeModule('bitrix24'))
		{
			$result['b24_type'] = true;
			$result['b24_plan'] = CBitrix24::getLicenseType();
		}
		else
		{
			$result['b24_type'] = false;
		}

		$result['mobile_module_ver'] = (string)ModuleManager::getVersion('mobile');

		return $result;
	}

	/**
	 * @throws \JsonException
	 */
	private static function filterHiddenFields(string $hiddenFields): array
	{
		$formValues = json_decode($hiddenFields, true, 512, JSON_THROW_ON_ERROR);
		if (!is_array($formValues))
		{
			return [];
		}

		$fieldValues = filter_var_array($formValues, self::ALLOWED_HIDDEN_FIELDS);

		if ($fieldValues === null)
		{
			return [];
		}

		$fieldValues = array_filter($fieldValues, static fn ($value) => $value !== null && $value !== false);

		if (isset($fieldValues['app_version']))
		{
			$fieldValues['app_version'] = (int)$fieldValues['app_version'];
		}

		return $fieldValues;
	}
}
