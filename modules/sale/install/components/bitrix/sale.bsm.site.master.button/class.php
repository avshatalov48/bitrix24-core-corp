<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use \Bitrix\Main\ModuleManager,
	\Bitrix\Main\Config\Option;

/**
 * Class SaleBsmSiteMasterButton
 */
class SaleBsmSiteMasterButton extends \CBitrixComponent
{
	/**
	 * @return mixed|void
	 * @throws \Bitrix\Main\ArgumentNullException
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	public function executeComponent()
	{
		if (ModuleManager::isModuleInstalled('extranet')
			&& !ModuleManager::isModuleInstalled('bitrix24')
			&& LANGUAGE_ID === "ru"
		)
		{
			$this->prepareResult();
			$this->includeComponentTemplate();
		}
	}

	private function prepareResult()
	{
		$this->arResult["MASTER_PATH"] = $this->getMasterPath();
	}

	/**
	 * @return bool|string
	 */
	private function getMasterPath()
	{
		$bsmSiteMasterPath = \CComponentEngine::makeComponentPath('bitrix:sale.bsm.site.master');
		$bsmSiteMasterPath = getLocalPath('components'.$bsmSiteMasterPath.'/slider.php');

		return $bsmSiteMasterPath;
	}
}