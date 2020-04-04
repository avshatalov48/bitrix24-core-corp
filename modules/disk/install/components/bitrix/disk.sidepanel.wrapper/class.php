<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Disk\Internals\BaseComponent;

class CDiskSidepanelWrapperComponent extends BaseComponent
{
	protected function processActionDefault()
	{
		if (!isset($this->arParams['POPUP_COMPONENT_PARAMS']) || !is_array($this->arParams['POPUP_COMPONENT_PARAMS']))
		{
			$this->arParams['POPUP_COMPONENT_PARAMS'] = array();
		}

		$this->arParams['POPUP_COMPONENT_PARAMS']['IFRAME'] = true;

		$this->restartBuffer();
		$this->includeComponentTemplate();

		require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php');
		exit;
	}
}