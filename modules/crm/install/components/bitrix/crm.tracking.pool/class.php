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
			/**@var $USER \CAllUser*/
			$this->arParams['HAS_ACCESS'] = true;
			/*
			global $USER;
			$crmPerms = new CCrmPerms($USER->GetID());
			$this->arParams['HAS_ACCESS'] = $crmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE');
			*/
		}
	}

	protected function preparePost()
	{
		$sourceList = array_column($this->arResult['SOURCES'], 'ID');
		$items = [];

		$values = $this->request->get('SOURCE') ;
		$values = is_array($values) ? $values : [];
		foreach ($values as $sourceId => $value)
		{
			$sourceId = (int) $sourceId;
			if ($sourceId <= 0)
			{
				continue;
			}

			$value = Communication\Normalizer::normalize($value, $this->arParams['TYPE_ID']);
			if (!Communication\Validator::validate($value, $this->arParams['TYPE_ID']))
			{
				$this->errors->setError(new Error("Wrong value `$value`."));
				break;
			}

			$items[$sourceId] = $value;
		}

		foreach ($sourceList as $sourceId)
		{
			$value = isset($items[$sourceId]) ? $items[$sourceId] : null;
			$result = Tracking\Internals\SourceTable::update($sourceId, [
				$this->arParams['TYPE_NAME'] => $value
			]);
			if (!$result->isSuccess())
			{
				$this->errors->add($result->getErrors());
				break;
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

			$value = $source[$this->arParams['TYPE_NAME']];
			$source['TILES'] = !$value ?
				[]
				:
				[[
					'id' => $value,
					'name' => $value,
					'data' => []
				]];

			$sources[$index] = $source;
		}
		$this->arResult['SOURCES'] = $sources;

		if ($this->request->isPost())
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
						'canRemove' => $item['CAN_REMOVE']
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
			'value' => null
		];
		if ($this->checkAccess())
		{
			$data['value'] = Tracking\Pool::instance()->addItem($typeId, $value);
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