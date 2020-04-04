<?php
namespace Bitrix\FaceId;


class FaceCard
{
	/**
	 * @return bool
	 */
	static public function isAvailableByUser($userId)
	{
		return (!static::applicationIsInactive() && !static::licenceIsRestricted() && static::agreementIsAccepted($userId));
	}

	/**
	 * @return bool
	 */
	static public function licenceIsRestricted()
	{
		$r = false;
		if (\CModule::IncludeModule('bitrix24'))
		{
			$r = in_array(\CBitrix24::getLicenseType(), array('project', 'tf'));
		}
		return $r;
	}

	/**
	 * @return bool
	 */
	static public function agreementIsAccepted($userId)
	{
		$result = false;

		if(intval($userId)<=0)
			return $result;

		if(\Bitrix\Faceid\AgreementTable::checkUser($userId))
		{
			$result = true;
		}

		return $result;
	}

	/**
	 * @return bool
	 */
	static protected function applicationIsInactive()
	{
		$r = false;
		if (\CModule::IncludeModule('rest'))
		{
			$appInfo = \Bitrix\Rest\AppTable::getByClientId('bitrix.1c');
			if(!$appInfo || $appInfo['ACTIVE'] === \Bitrix\Rest\AppTable::INACTIVE)
			{
				$r = true;
			}
		}
		else
		{
			$r = true;
		}

		return $r;
	}
}