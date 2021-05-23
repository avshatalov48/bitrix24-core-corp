<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Loader;
use Bitrix\Main\Error;

use Bitrix\Crm\Tracking;
use Bitrix\Crm\UI\Webpack;


/**
 * Class CrmTrackingChannelComponent
 */
class CrmTrackingOrderComponent extends \CBitrixComponent
{
	/** @var ErrorCollection $errors */
	protected $errors;

	protected function checkRequiredParams()
	{
		return $this->errors->count() == 0;
	}

	protected function initParams()
	{
		$this->arParams['SET_TITLE'] = isset($this->arParams['SET_TITLE']) ? (bool) $this->arParams['SET_TITLE'] : true;
	}

	protected function preparePost()
	{
		$fieldCode = $this->request->get('FIELD_CODE');
		$fields = array_column($this->arResult['FIELDS'], 'id');
		$fieldCode = in_array($fieldCode, $fields, true) ? $fieldCode : null;
		$oldFieldCode = Tracking\Channel\Order::getDealField();
		Tracking\Channel\Order::setDealField($fieldCode);
		if ($fieldCode != $oldFieldCode)
		{
			Webpack\Guest::instance()->build();
			Webpack\CallTracker::instance()->build();
		}

		$uri = (new \Bitrix\Main\Web\Uri($this->arParams['PATH_TO_ORDER']));
		if ($this->arParams['IFRAME'])
		{
			$uri->addParams(['IFRAME' => 'Y']);
		}
		LocalRedirect($uri->getLocator());
	}

	protected function prepareResult()
	{
		$channels = Tracking\Provider::getChannels();
		$channels = array_combine(array_column($channels, 'CODE'), $channels);
		$channel = isset($channels[$this->arParams['ID']]) ? $channels[$this->arParams['ID']] : [];
		$this->arResult['ROW'] = $channel;

		$this->arResult['FIELD_CODE'] = Tracking\Channel\Order::getDealField();
		$this->arResult['FIELDS'] = $this->getDealUserFieldList();

		if ($this->request->isPost() && check_bitrix_sessid())
		{
			$this->preparePost();
		}

		if ($this->arParams['SET_TITLE'] == 'Y')
		{
			$GLOBALS['APPLICATION']->SetTitle($channel['NAME']);
		}

		return true;
	}

	protected function getDealUserFieldList()
	{
		$list = array();
		$ufManager = is_object($GLOBALS['USER_FIELD_MANAGER']) ? $GLOBALS['USER_FIELD_MANAGER'] : null;
		if (!$ufManager)
		{
			return $list;
		}

		$ufEntityId = \CCrmOwnerType::ResolveUserFieldEntityID(\CCrmOwnerType::Deal);
		$crmUserType = new \CCrmUserType($ufManager, $ufEntityId);
		$logicFilter = array();
		$crmUserType->PrepareListFilterFields($list, $logicFilter);
		$originalList = $crmUserType->GetFields();

		$list = array_filter(
			$list,
			function ($field) use ($originalList)
			{
				if (empty($originalList[$field['id']]))
				{
					return false;
				}

				$type = $originalList[$field['id']]['USER_TYPE']['USER_TYPE_ID'];
				return in_array($type, ['string', 'integer'], true);
			}
		);

		return $list;
	}

	protected function printErrors()
	{
		foreach ($this->errors as $error)
		{
			ShowError($error);
		}
	}

	public function executeComponent()
	{
		$this->errors = new \Bitrix\Main\ErrorCollection();
		if (!Loader::includeModule('crm'))
		{
			$this->errors->setError(new Error('Module `crm` is not installed.'));
			$this->printErrors();
			return;
		}
		if (!Loader::includeModule('landing'))
		{
			$this->errors->setError(new Error('Module `landing` is not installed.'));
			$this->printErrors();
			return;
		}

		$this->initParams();
		if (!$this->checkRequiredParams())
		{
			$this->printErrors();
			return;
		}

		if (!$this->prepareResult())
		{
			$this->printErrors();
			return;
		}

		$this->printErrors();
		$this->includeComponentTemplate();
	}
}