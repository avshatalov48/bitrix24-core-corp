<?

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Loader;
use Bitrix\Main\Error;

use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Crm\Exclusion;


if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

Loc::loadMessages(__FILE__);

class CrmExclusionImportComponent extends CBitrixComponent implements Controllerable
{
	/** @var ErrorCollection $errors */
	protected $errors;

	protected function checkRequiredParams()
	{
		if (!Loader::includeModule('crm'))
		{
			$this->errors->setError(new Error('Module `crm` is not installed.'));
			return false;
		}
		if (!Exclusion\Access::current()->canRead())
		{
			$this->errors->setError(new Error(Exclusion\Access::getErrorText(Exclusion\Access::READ)));
			return false;
		}

		return $this->errors->count() == 0;
	}

	protected function initParams()
	{
		$this->arParams['SET_TITLE'] = isset($this->arParams['SET_TITLE']) ? $this->arParams['SET_TITLE'] == 'Y' : true;
		$this->arParams['CAN_EDIT'] = isset($this->arParams['CAN_EDIT']) ? $this->arParams['CAN_EDIT'] : Exclusion\Access::current()->canWrite();
	}

	protected function printErrors()
	{
		foreach ($this->errors as $error)
		{
			ShowError($error->getMessage());
		}
	}

	public function executeComponent()
	{
		if (!$this->errors->isEmpty())
		{
			return;
		}

		$this->includeComponentTemplate();
	}

	public function onPrepareComponentParams($arParams)
	{
		$this->errors = new \Bitrix\Main\ErrorCollection();
		$this->arParams = $arParams;

		if (!$this->checkRequiredParams())
		{
			$this->printErrors();
		}

		$this->initParams();

		/* Set title */
		if ($this->arParams['SET_TITLE'])
		{
			/**@var CAllMain*/
			$GLOBALS['APPLICATION']->SetTitle(Loc::getMessage('CRM_EXCLUSION_IMPORT_TITLE'));
		}

		return $this->arParams;
	}

	public function configureActions()
	{
		return array();
	}

	public function importListAction($list)
	{
		if (!$this->arParams['CAN_EDIT'])
		{
			$this->errors->setError(new Error(Exclusion\Access::getErrorText(Exclusion\Access::WRITE)));
			return;
		}

		if (!$this->errors->isEmpty())
		{
			return;
		}

		$list = is_array($list) ? $list : array();
		Exclusion\Store::import($list);
	}
}