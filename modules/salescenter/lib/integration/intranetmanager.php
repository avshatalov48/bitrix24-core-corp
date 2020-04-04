<?php

namespace Bitrix\SalesCenter\Integration;

/**
 * Class IntranetManager
 * @package Bitrix\SalesCenter\Integration
 */
class IntranetManager extends Base
{
	/**
	 * @return string
	 */
	protected function getModuleName()
	{
		return 'intranet';
	}

	/**
	 * @return string
	 */
	public function getPortalZone() :? string
	{
		if ($this->isEnabled)
		{
			return \CIntranetUtils::getPortalZone();
		}

		return null;
	}

	/**
	 * @param array|string $zone
	 * @return bool
	 */
	public function isCurrentZone(string $zone) : bool
	{
		if ($this->isEnabled)
		{
			return $this->getPortalZone() == $zone;
		}

		return false;
	}
}