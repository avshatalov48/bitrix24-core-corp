<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage socialnetwork
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Socialnetwork\Internals\Counter;

use Bitrix\Socialnetwork\Internals\Counter\Exception\UnknownCounterException;
use Bitrix\Socialnetwork\Internals\Counter\Provider;

class CounterController
{
	public static function getValue(string $name = '', int $entityId = 0, int $userId = 0): int
	{
		$instance = self::getInstance($name, $entityId, $userId);

		return $instance->getCounterValue();
	}

	/**
	 * @param string $name
	 * @param integer $entityId
	 * @param integer $userId
	 * @return Provider\Base
	 * @throws UnknownCounterException
	 */
	public static function getInstance(string $name = '', int $entityId = 0, int $userId = 0): Provider\Base
	{
		switch ($name)
		{
			case CounterDictionary::COUNTER_WORKGROUP_REQUESTS_IN:
				$result = new Provider\WorkgroupRequestsIn([
					'workgroupId' => $entityId,
				]);
				break;
			case CounterDictionary::COUNTER_WORKGROUP_REQUESTS_OUT:
				$result = new Provider\WorkgroupRequestsOut([
					'workgroupId' => $entityId,
				]);
				break;
			default:
				throw new UnknownCounterException();
		}

		return $result;
	}
}