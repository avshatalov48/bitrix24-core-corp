<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Loader;
use Bitrix\Main\Error;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Bitrix24\Feature;

use Bitrix\Crm\Communication;
use Bitrix\Crm\Tracking;
use Bitrix\Crm\UI\Webpack;


Loc::loadMessages(__FILE__);

/**
 * Class CrmTrackingChannelPoolComponent
 */
class CrmTrackingChannelPoolComponent extends \CBitrixComponent implements Controllerable
{
	/** @var ErrorCollection $errors */
	protected $errors;

	protected function checkRequiredParams()
	{
		if (!Loader::includeModule('crm'))
		{
			$this->errors->setError(new Error('Module `crm` is not installed.'));
		}

		return $this->errors->count() == 0;
	}

	protected function initParams()
	{
		$this->arParams['SET_TITLE'] = isset($this->arParams['SET_TITLE']) ? (bool) $this->arParams['SET_TITLE'] : true;
		$this->arParams['TYPE_ID'] = isset($this->arParams['TYPE_ID']) ?
			(int) $this->arParams['TYPE_ID']
			:
			Communication\Type::PHONE;

		$this->arParams['TYPE_NAME'] = Communication\Type::resolveName($this->arParams['TYPE_ID']);

		if (!isset($this->arParams['HAS_ACCESS']))
		{
			/**@var $USER \CUser*/
			$this->arParams['HAS_ACCESS'] = true;
			/*
			global $USER;
			$crmPerms = new CCrmPerms($USER->GetID());
			$this->arParams['HAS_ACCESS'] = $crmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE');
			*/
		}

		$this->arResult['FEATURE_CODE'] = Loader::includeModule('bitrix24') && !Feature::isFeatureEnabled("crm_tracking_call")
			? "limit_crm_calltracking"
			: null
		;
	}

	protected function preparePost()
	{
		$sourceList = array_column($this->arResult['SOURCES'], 'ID');
		$items = [];

		$valuesBySource = $this->request->get('SOURCE') ;
		$valuesBySource = is_array($valuesBySource) ? $valuesBySource : [];
		foreach ($valuesBySource as $sourceId => $values)
		{
			$sourceId = (int) $sourceId;
			if ($sourceId <= 0)
			{
				continue;
			}

			foreach ($values as $value)
			{
				$value = Communication\Normalizer::normalize($value, $this->arParams['TYPE_ID']);
				if (!Communication\Validator::validate($value, $this->arParams['TYPE_ID']))
				{
					$this->errors->setError(new Error("Wrong value `$value`."));
					break 2;
				}

				$items[$sourceId] = is_array($items[$sourceId]) ? $items[$sourceId] : [];
				$items[$sourceId][] = $value;
			}
		}

		$sourceFieldCode = null;
		switch ($this->arParams['TYPE_ID'])
		{
			case Communication\Type::PHONE:
				$sourceFieldCode = Tracking\Internals\SourceFieldTable::FIELD_PHONE;
				break;
			case Communication\Type::EMAIL:
				$sourceFieldCode = Tracking\Internals\SourceFieldTable::FIELD_EMAIL;
				break;
		}

		if ($sourceFieldCode)
		{
			foreach ($sourceList as $sourceId)
			{

				$values = isset($items[$sourceId]) ? $items[$sourceId] : [];
				Tracking\Internals\SourceFieldTable::setSourceField(
					$sourceId,
					$sourceFieldCode,
					$values
				);
			}
		}

		if ($this->errors->count() === 0)
		{
			Webpack\CallTracker::rebuildEnabled();
			LocalRedirect($this->request->getRequestUri());
		}
	}

	protected function prepareResult()
	{
		if ($this->arParams['SET_TITLE'] == 'Y')
		{
			$GLOBALS['APPLICATION']->SetTitle(
				Loc::getMessage('CRM_TRACKING_CHANNEL_POOL_TITLE_' . $this->arParams['TYPE_NAME'])
			);
		}

		$this->arResult['ORGANIC_SOURCE'] = [];
		$sources = Tracking\Provider::getActualSources();
		foreach ($sources as $index => $source)
		{
			if (!$source['ID'])
			{
				if ($source['CODE'] === 'organic')
				{
					$this->arResult['ORGANIC_SOURCE'] = $source;
				}

				unset($sources[$index]);
				continue;
			}

			$values = $source[$this->arParams['TYPE_NAME']];
			$source['TILES'] = !$values ?
				[]
				:
				array_map(
					function ($value)
					{
						return [
							'id' => $value,
							'name' => $value,
							'data' => []
						];
					},
					$values
				);

			$sources[$index] = $source;
		}
		$this->arResult['SOURCES'] = $sources;

		if ($this->request->isPost() && check_bitrix_sessid() && !$this->arResult['FEATURE_CODE'])
		{
			$this->preparePost();
		}

		$pool = Tracking\Pool::instance()->getItems($this->arParams['TYPE_ID']);
		$this->arResult['POOL_TILES'] = array_map(
			function ($item)
			{
				return [
					'id' => $item['VALUE'],
					'name' => $item['VALUE'],
					'data' => [
						'canRemove' => $item['CAN_REMOVE'],
						'using' => isset($item['USING']) ? $item['USING'] : null
					]
				];
			},
			$pool
		);

		return true;
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
		if (!$this->errors->isEmpty())
		{
			return;
		}

		if (!$this->prepareResult())
		{
			$this->printErrors();
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

		return $this->arParams;
	}

	protected function checkAccess()
	{
		if (!$this->arParams['HAS_ACCESS'])
		{
			$this->errors->setError(new Error('Access denied.'));
			return false;
		}

		return true;
	}

	protected function listKeysSignedParameters()
	{
		return [
			'HAS_ACCESS',
		];
	}

	public function configureActions()
	{
		return [];
	}

	public function removeItemAction($typeId, $value)
	{
		if ($this->checkAccess())
		{
			Tracking\Pool::instance()->removeItem($typeId, $value);
		}

		/** @var Error $error */
		$error = $this->errors->current();

		return [
			'data' => [],
			'error' => !$this->errors->isEmpty(),
			'text' => $error ? $error->getMessage() : ''
		];
	}

	public function addItemAction($typeId, $value)
	{
		$data = [
			'value' => null,
		];
		if ($this->checkAccess())
		{
			$data['value'] = Tracking\Pool::instance()->addItem($typeId, $value);
			if ($typeId == Communication\Type::PHONE)
			{
				$data['using'] = Tracking\Pool::instance()->getUsingByValue($typeId, $value);
			}
		}

		/** @var Error $error */
		$error = $this->errors->current();

		return [
			'data' => $data,
			'error' => !$this->errors->isEmpty(),
			'text' => $error ? $error->getMessage() : ''
		];
	}

	public function startTestingAction($numberFrom)
	{
		$data = [
			'numberFrom' => null,
		];
		if ($this->checkAccess())
		{
			Tracking\Call\Tester::start();
			$data['numberFrom'] = Communication\Normalizer::normalizePhone($numberFrom);
		}

		/** @var Error $error */
		$error = $this->errors->current();

		return [
			'data' => $data,
			'error' => !$this->errors->isEmpty(),
			'text' => $error ? $error->getMessage() : ''
		];
	}
}