<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main;

/**
 * Class IntranetApps
 */
class IntranetApps extends \CBitrixComponent
{
	public function onPrepareComponentParams($params = [])
	{
		global $USER;

		$params['USER_ID'] = (int)(!empty($params['USER_ID']) ? $params['USER_ID'] : $USER->getId());

		return $params;
	}

	/**
	 * Execute component.
	 */
	public function executeComponent()
	{
		$this->initialize();

		$this->includeComponentTemplate();
	}

	protected function initialize()
	{
		global $USER;

		$currentUserId = $USER->getId();

		$this->arResult = \Bitrix\Intranet\Util::getAppsInstallationConfig($currentUserId);
		$this->arResult['DESKTOP_DOWNLOAD_LINKS'] = \Bitrix\Intranet\Portal::getInstance()->getSettings()->getDesktopDownloadLinks();

		$this->arResult['PERSONAL_MOBILE'] = '';
		if ($this->arParams['USER_ID'] > 0)
		{
			$res = Main\UserTable::getList([
				'filter' => [
					'=ID' => $this->arParams['USER_ID'],
				],
				'select' => [ 'PERSONAL_MOBILE' ],
			]);
			if ($userFields = $res->fetch())
			{
				$this->arResult['PERSONAL_MOBILE'] = $userFields['PERSONAL_MOBILE'];
			}
		}
	}

}
