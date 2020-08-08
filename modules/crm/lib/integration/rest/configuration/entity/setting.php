<?php

namespace Bitrix\Crm\Integration\Rest\Configuration\Entity;

use Bitrix\Rest\Configuration\Helper;
use Bitrix\Crm\Settings\LeadSettings;

class Setting
{
	const ENTITY_CODE = 'CRM_SETTING';
	const CRM_LEAD_MODE = 'LEAD_MODE';

	private static $accessManifest = [
		'total',
		'crm',
		'crm_setting'
	];

	private static $settingList = [
		self::CRM_LEAD_MODE
	];

	/**
	 * @param $param
	 *
	 * @return mixed
	 */
	public static function export($param)
	{
		$result = null;
		if(Helper::checkAccessManifest($param, static::$accessManifest))
		{
			if(!empty(static::$settingList[$param['STEP']]) && static::$settingList[$param['STEP']] == static::CRM_LEAD_MODE)
			{
				$result = [
					'FILE_NAME' => static::CRM_LEAD_MODE,
					'CONTENT' => [
						'TYPE' => static::CRM_LEAD_MODE,
						'ENABLED' => LeadSettings::isEnabled() ? 'Y' : 'N'
					],
					'NEXT' => false
				];
			}

		}

		return $result;
	}

	/**
	 * @param $params
	 *
	 * @return mixed
	 */
	public static function import($params)
	{
		$result = null;
		if(Helper::checkAccessManifest($params, static::$accessManifest))
		{
			$result = [];
			if(!isset($params['CONTENT']['DATA']))
			{
				return $result;
			}
			$data = $params['CONTENT']['DATA'];

			if(isset($data['TYPE']) && $data['TYPE'] == static::CRM_LEAD_MODE)
			{
				if($data['ENABLED'] == 'Y')
				{
					LeadSettings::enableLead(true);
				}
				elseif($data['ENABLED'] == 'N')
				{
					LeadSettings::enableLead(false);
				}
			}
		}

		return $result;
	}
}