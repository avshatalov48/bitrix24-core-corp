<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2014 Bitrix
 */

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;

use Bitrix\Sale\Location\Admin\LocationHelper;

CBitrixComponent::includeComponentClass("bitrix:sale.location.import");

Loc::loadMessages(__FILE__);

class CBitrixCrmConfigLocationImport2Component extends CBitrixSaleLocationImportComponent
{
	protected function checkRequiredModules()
	{
		$result = true;

		if(!Loader::includeModule('sale'))
		{
			$this->errors['FATAL'][] = Loc::getMessage("SALE_SLI_SALE_MODULE_NOT_INSTALL");
			$result = false;
		}

		if(!Loader::includeModule('crm'))
		{
			$this->errors['FATAL'][] = Loc::getMessage("SALE_CCLI2_CRM_MODULE_NOT_INSTALL");
			$result = false;
		}

		return $result;
	}

	protected static function checkAccessPermissions($parameters = array())
	{
		if(!is_array($parameters))
			$parameters = array();

		$errors = array();

		$CCrmPerms = new CCrmPerms($GLOBALS['USER']->GetID());
		if ($CCrmPerms->HavePerm('CONFIG', BX_CRM_PERM_NONE, 'WRITE'))
		{
			$errors[] = Loc::getMessage("SALE_CCLI2_CRM_MODULE_WRITE_ACCESS_DENIED");
		}

		if(!LocationHelper::checkLocationEnabled())
			$errors[] = 'Locations were disabled or data has not been converted';

		if($parameters['CHECK_CSRF'])
		{
			$post = \Bitrix\Main\Context::getCurrent()->getRequest()->getPostList();
			if(!strlen($post['csrf']) || bitrix_sessid() != $post['csrf'])
				$errors[] = 'CSRF token is not valid';
		}

		return $errors;
	}

	protected static function getClassName()
	{
		return __CLASS__;
	}
}