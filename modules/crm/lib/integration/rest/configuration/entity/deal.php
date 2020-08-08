<?php

namespace Bitrix\Crm\Integration\Rest\Configuration\Entity;

use Bitrix\Rest\Configuration\Helper;
use CCrmDeal;

class Deal
{
	const ENTITY_CODE = 'CRM_ITEMS_DEAL';
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
			$entity = new CCrmDeal(true);
			$res = $entity->getList([], [], [], 10);
			while($deal = $res->fetch())
			{
				if(!$entity->delete($deal['ID']))
				{
					$result['NEXT'] = false;
					$result['ERROR_ACTION'] = 'DELETE_ERROR_DEAL';
					break;
				}
				else
				{
					$result['NEXT'] = $deal['ID'];
				}
			}
		}

		return $result;
	}
}