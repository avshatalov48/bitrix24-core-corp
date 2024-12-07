<?php

namespace Bitrix\Mobile\Feedback;

use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use CBitrix24;

class FeedbackFormProvider
{
	const FORMS = [
		'copilotRoles' => [
			'ru-by-kz' => [
				'portalZones' => ['ru', 'by', 'kz'],
				'formData' => [
					'data-b24-form' => 'inline/746/we50kv',
					'uri' => 'https://cdn.bitrix24.com/b5309667/crm/form/loader_746.js',
				],
			],
			'de' => [
				'portalZones' => ['de'],
				'formData' => [
					'data-b24-form' => 'inline/742/vqqxgr',
					'uri' => 'https://cdn.bitrix24.com/b5309667/crm/form/loader_742.js',
				],
			],
			'com.br' => [
				'portalZones' => ['com.br'],
				'formData' => [
					'data-b24-form' => 'inline/744/nz3zig',
					'uri' => 'https://cdn.bitrix24.com/b5309667/crm/form/loader_744.js',
				],
			],
			'es' => [
				'portalZones' => ['es'],
				'formData' => [
					'data-b24-form' => 'inline/738/77ui4p',
					'uri' => 'https://cdn.bitrix24.com/b5309667/crm/form/loader_738.js',
				],
			],
			'en' => [
				'isDefault' => true,
				'portalZones' => ['en'],
				'formData' => [
					'data-b24-form' => 'inline/740/obza3e',
					'uri' => 'https://cdn.bitrix24.com/b5309667/crm/form/loader_740.js',
				],
			],
		],
	];


	/**
	 * @throws LoaderException
	 */
	public static function getFormData(string $formId): array|null
	{
		$targetForm = self::FORMS[$formId];
		if (!empty($targetForm) && is_array($targetForm))
		{
			$portalZone = \Bitrix\Main\Application::getInstance()->getLicense()->getRegion() ?: "en";
			$defaultFormData = null;
			foreach ($targetForm as $portalZonesData)
			{
				if (in_array($portalZone, $portalZonesData['portalZones']))
				{
					return $portalZonesData['formData'];
				}

				if ($portalZonesData['isDefault'] === true)
				{
					$defaultFormData = $portalZonesData['formData'];
				}
			}

			return $defaultFormData;
		}

		return null;
	}
}