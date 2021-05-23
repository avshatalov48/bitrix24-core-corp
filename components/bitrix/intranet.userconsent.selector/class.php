<?

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\UserConsent\Agreement;
use Bitrix\Main\UserConsent\Intl;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

Loc::loadMessages(__FILE__);

class IntranetUserConsentSelectorComponent extends CBitrixComponent
{
	protected function initParams()
	{
		$this->arParams['ID'] = isset($this->arParams['ID']) ? intval($this->arParams['ID']) : null;
		$this->arParams['INPUT_NAME'] = isset($this->arParams['INPUT_NAME']) ? (string) $this->arParams['INPUT_NAME'] : 'AGREEMENT_ID';

		$baseUri = '/settings/configs/userconsent/';
		$this->arParams['PATH_TO_ADD'] = isset($this->arParams['PATH_TO_ADD']) ? $this->arParams['PATH_TO_ADD'] : $baseUri . 'edit/0/';
		$this->arParams['PATH_TO_EDIT'] = isset($this->arParams['PATH_TO_EDIT']) ? $this->arParams['PATH_TO_EDIT'] : $baseUri . 'edit/#id#/';
		$this->arParams['PATH_TO_CONSENT_LIST'] = isset($this->arParams['PATH_TO_CONSENT_LIST']) ? $this->arParams['PATH_TO_CONSENT_LIST'] : $baseUri . 'consents/#id#/?AGREEMENT_ID=#id#&apply_filter=Y';
		$this->arParams['ACTION_REQUEST_URL'] = isset($this->arParams['ACTION_REQUEST_URL']) ? $this->arParams['ACTION_REQUEST_URL'] : $this->getPath() . '/ajax.php';
	}

	protected function prepareResult()
	{
		$this->arResult['CAN_EDIT'] = $GLOBALS['USER']->IsAdmin() || (IsModuleInstalled("bitrix24") && $GLOBALS['USER']->CanDoOperation('bitrix24_config'));
	}

	public function executeComponent()
	{
		$this->initParams();
		$this->prepareResult();
		$this->includeComponentTemplate();
	}
}