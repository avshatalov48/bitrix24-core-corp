<?php
define('STOP_STATISTICS', true);
define('BX_SECURITY_SHOW_MESSAGE', true);
require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UserConsent\Agreement;

Loc::loadMessages(__FILE__);

class MainUserConsentSelectorAjaxController extends \Bitrix\Main\UserConsent\Internals\ComponentController
{
	protected function getActions()
	{
		return array(
			'getAgreements',
		);
	}

	protected function checkPermissions()
	{
		$canEdit = $GLOBALS['USER']->IsAdmin() || (IsModuleInstalled("bitrix24") && $GLOBALS['USER']->CanDoOperation('bitrix24_config'));
		return $canEdit;
	}

	protected function getAgreements()
	{
		$this->responseData['list'] = array();
		$list = Agreement::getActiveList();
		foreach ($list as $id => $name)
		{
			$this->responseData['list'][] = array(
				'ID' => $id,
				'NAME' => $name
			);
		}
	}
}

$controller = new MainUserConsentSelectorAjaxController();
$controller->exec();