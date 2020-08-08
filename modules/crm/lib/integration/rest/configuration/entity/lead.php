<?php

namespace Bitrix\Crm\Integration\Rest\Configuration\Entity;

use Bitrix\Rest\Configuration\Helper;
use CCrmLead;

class Lead
{
	const ENTITY_CODE = 'CRM_ITEMS_LEAD';
	private static $accessManifest = [
		'total',
		'crm'
	];

	/**
	 * @param $option
	 *
	 * @return mixed
	 */
	public static function clear($option)
	{
		if(!Helper::checkAccessManifest($option, static::$accessManifest))
		{
			return null;
		}

		$result = [
			'NEXT' => false
		];
		$clearFull = $option['CLEAR_FULL'];
		if($clearFull)
		{
			$entity = new CCrmLead(true);
			$res = $entity->getList([], [], [], 10);
			while($lead = $res->fetch())
			{
				if(!$entity->delete($lead['ID']))
				{
					$result['NEXT'] = false;
					$result['ERROR_ACTION'] = 'DELETE_ERROR_LEAD';
					break;
				}
				else
				{
					$result['NEXT'] = $lead['ID'];
				}
			}
		}

		return $result;
	}
}