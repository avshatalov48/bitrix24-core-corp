<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale;
use Bitrix\Sale\Cashbox;
use Bitrix\SalesCenter\Integration\Bitrix24Manager;
use Bitrix\SalesCenter\Integration\IntranetManager;
use Bitrix\SalesCenter\Integration\SaleManager;

/**
 * Class SalesCenterCashboxComponent
 */
class SalesCenterCashboxComponent extends CBitrixComponent implements Main\Engine\Contract\Controllerable, Main\Errorable
{
	private const OFFLINE_HANDLER_TYPE = 'offline';

	/** @var Cashbox\Cashbox */
	protected $handler;
	protected $kkmId;

	/** @var Main\ErrorCollection */
	protected $errorCollection;
	protected $cashboxData;

	/**
	 * @param $arParams
	 * @return array
	 */
	public function onPrepareComponentParams($arParams)
	{
		$this->arResult = [
			'handler' => $arParams['handler'] ?? null,
			'kkm-id' => $arParams['kkm-id'] ?? null,
			'id' => $arParams['id'] ?? null,
			'page' => $arParams['page'] ?? null,
			'isFrame' => $arParams['isFrame'] ?? null,
			'preview' => $arParams['preview'] ?? null,
			'restHandler' => $arParams['restHandler'] ?? null,
		];

		return parent::onPrepareComponentParams($arParams);
	}

	/**
	 * @return Main\Result
	 * @throws Main\LoaderException
	 */
	private function checkModule()
	{
		$result = new Main\Result();
		$this->errorCollection = new Main\ErrorCollection();

		if (!Loader::includeModule('salescenter'))
		{
			return $result->addError(new Main\Error(Loc::getMessage('SALESCENTER_MODULE_ERROR')));
		}

		if (!SaleManager::getInstance()->isFullAccess(true))
		{
			return $result->addError(new Main\Error(Loc::getMessage("SC_SALESCENTER_SALE_ACCESS_DENIED")));
		}

		return $result;
	}

	/**
	 * @return Main\Result
	 * @throws Main\LoaderException
	 */
	public function prepare()
	{
		$result = new Main\Result();
		$this->errorCollection = new Main\ErrorCollection();

		$checkResult = $this->checkModule();
		if (!$checkResult->isSuccess())
		{
			$this->errorCollection->add($checkResult->getErrors());
			$result->addErrors($checkResult->getErrors());
			return $result;
		}

		/** @noinspection PhpIncludeInspection */
		require_once Main\Application::getDocumentRoot()."/bitrix/modules/sale/lib/cashbox/inputs/file.php";

		if ($this->arResult['id'] > 0)
		{
			$data = $this->getData();
			$this->handler = $data['HANDLER'];
			$this->kkmId = $data['KKM_ID'];

			if ($data['ID'] != $this->arResult['id'])
			{
				return $result->addError(new Main\Error(Loc::getMessage("SC_SALESCENTER_ERROR_NO_CASHBOX")));
			}
		}
		else
		{
			$this->handler = $this->arResult['handler'];
			if (isset($this->arResult['kkm-id']))
			{
				$this->kkmId = $this->arResult['kkm-id'];
			}
		}
		if (!$this->handler)
		{
			return $result->addError(new Main\Error(Loc::getMessage("SC_SALESCENTER_ERROR_NO_HANDLER")));
		}

		if (!is_a($this->handler, Cashbox\Cashbox::class, true))
		{
			return $result->addError(new Main\Error(Loc::getMessage("SC_SALESCENTER_ERROR_NO_HANDLER_EXIST")));
		}

		return $result;
	}

	/**
	 * @return mixed|void|null
	 * @throws Main\LoaderException
	 * @throws Main\SystemException
	 */
	public function executeComponent()
	{
		$checkResult = $this->checkModule();
		if (!$checkResult->isSuccess())
		{
			$this->showError(implode('<br />', $checkResult->getErrorMessages()));
			return;
		}

		if ($this->showOfflineInfo())
		{
			$this->includeComponentTemplate('offline');
		}
		else
		{
			$checkResult = $this->prepare();
			$this->arResult['addUrl'] = $this->getCurrentPageWithParams('', ['preview', 'id']);
			if (!$checkResult->isSuccess())
			{
				$this->arResult['errors'] = $checkResult->getErrorMessages();
				$this->arResult['handlerDescription'] = $this->getHandlerDescription();
				$this->includeComponentTemplate('preview');
				return;
			}

			$this->arResult['handlerDescription'] = $this->getHandlerDescription();

			if ($this->arResult['preview'])
			{
				$this->addConnectionInfoUrl();
				$this->includeComponentTemplate('preview');
			}
			else
			{
				$this->arResult['menu'] = $this->getMenu();

				$form = $this->getFormConfigAction();

				$this->arResult['fields'] = $form['fields'];
				$this->arResult['config'] = $form['config'];
				$this->arResult['data'] = $form['data'];

				$this->arResult['signedParameters'] = $this->getSignedParameters();

				$this->arResult['isPaySystemCashbox'] = Cashbox\Manager::isPaySystemCashbox($this->arResult['handler']);

				$this->includeComponentTemplate();
			}
		}
	}

	protected function showError($error)
	{
		ShowError($error);
	}

	/**
	 * @return array
	 */
	protected function getMenu()
	{
		$menu = [];

		foreach($this->getMenuPages() as $id => $page)
		{
			$page['url'] = $this->getMenuPageLink($id);
			$page['path'] = $id.'.php';
			$page['ATTRIBUTES']['onclick'] = 'BX.Salescenter.Cashbox.showPage(\''.$id.'\');';
			$page['ATTRIBUTES']['id'] = 'salescenter-menu-page-'.$id;

			$menu[$id] = $page;
		}

		if ($this->arParams['page'] && isset($menu[$this->arParams['page']]))
		{
			$menu[$this->arParams['page']]['ACTIVE'] = true;
		}
		else
		{
			$menuKeys = array_keys($menu);

			$menu[reset($menuKeys)]['ACTIVE'] = true;
		}

		return $menu;
	}

	/**
	 * @return array
	 */
	protected function getMenuPages()
	{
		$result = [
			'cashbox_params' => [
				'NAME' => Loc::getMessage('SC_MENU_ITEM_CASHBOX_PARAM'),
			],
			'settings' => [
				'NAME' => Loc::getMessage('SC_MENU_ITEM_SETTINGS'),
			],
		];

		if ($this->isOfdFieldNeeded())
		{
			$result['ofd_settings'] = [
				'NAME' => Loc::getMessage('SC_MENU_ITEM_OFD_SETTINGS'),
			];
		}

		return $result;
	}

	/**
	 * @param $pageId
	 * @return string
	 */
	protected function getMenuPageLink($pageId)
	{
		return $this->getCurrentPageWithParams('page='.$pageId, ['page']);
	}

	/**
	 * @param string $params
	 * @param array $deleteParams
	 * @return string
	 */
	protected function getCurrentPageWithParams($params = '', array $deleteParams = [])
	{
		global $APPLICATION;
		return $APPLICATION->GetCurPageParam($params, $deleteParams);
	}

	/**
	 * @return array
	 */
	protected function getOfdItems()
	{
		$items = [];

		$isSelected = false;
		$list = \Bitrix\Sale\Cashbox\Ofd::getHandlerList();
		foreach($list as $handler => $name)
		{
			$item = [
				'NAME' => $name,
				'VALUE' => $handler,
			];
			if (isset($this->arResult['data']['OFD']) && $this->arResult['data']['OFD'] == $handler)
			{
				$item['SELECTED'] = true;
				$isSelected = true;
			}
			$items[] = $item;
		}

		$item = [
			'NAME' => Loc::getMessage('SC_CASHBOX_OFD_ANOTHER'),
			'VALUE' => '',
		];
		if (!$isSelected)
		{
			$item['SELECTED'] = true;
		}
		$items[] = $item;

		return $items;
	}

	/**
	 * @return array
	 */
	protected function getFields()
	{
		$fields = [
			[
				'name' => 'id',
				'type' => 'hidden',
			],
			[
				'name' => 'NAME',
				'title' => Loc::getMessage('SC_CASHBOX_FIELDS_NAME'),
				'type' => 'text',
			],
			[
				'name' => 'EMAIL',
				'title' => Loc::getMessage('SC_CASHBOX_FIELDS_EMAIL'),
				'type' => 'text',
				'hint' => Loc::getMessage('SC_CASHBOX_FIELDS_EMAIL_HINT'),
			],
		];

		if (!Cashbox\Manager::isPaySystemCashbox($this->arParams['handler']))
		{
			$fields[] = [
				'name' => 'USE_OFFLINE',
				'title' => Loc::getMessage('SC_CASHBOX_FIELDS_USE_OFFLINE'),
				'type' => 'boolean',
			];
		}

		if (($this->arParams['handler']::getSupportedKkmModels()))
		{
			$fields[] = [
				'name' => 'KKM_ID',
				'title' => Loc::getMessage('SC_CASHBOX_FIELDS_KKM_ID'),
				'type' => 'text',
				'attribute' => [
					'disabled'
				],
			];
		}

		if ($this->isOfdFieldNeeded())
		{
			$field = [
				'name' => 'OFD',
				'title' => Loc::getMessage('SC_CASHBOX_FIELDS_OFD'),
				'type' => 'list',
				'data' => [
					'items' => $this->getOfdItems(),
				],
			];

			if (is_a($this->arParams['handler'], Cashbox\CashboxYooKassa::class, true))
			{
				$field['subtext'] = Loc::getMessage('SC_CASHBOX_FIELDS_OFD_YOOKASSA_HINT');
				$field['subtextLinkOnClick'] = 'BX.Salescenter.Manager.openHowToConfigYooKassaCashBox(event);';
				$field['subtextLinkText'] = Loc::getMessage('SC_CASHBOX_FIELDS_OFD_YOOKASSA_HINT_MORE');
			}

			$fields[] = $field;
		}

		if ($this->areAdditionalFieldsNeeded())
		{
			$fields[] = [
				'name' => 'NUMBER_KKM',
				'title' => Loc::getMessage('SC_CASHBOX_FIELDS_NUMBER_KKM'),
				'type' => 'text',
				'hint' => Loc::getMessage('SC_CASHBOX_FIELDS_NUMBER_KKM_HINT'),
			];
		}

		$requireFields = $this->handler::getGeneralRequiredFields();
		foreach($fields as &$field)
		{
			if (isset($requireFields[$field['name']]))
			{
				$field['required'] = true;
			}
		}

		return $fields;
	}

	protected function areAdditionalFieldsNeeded(): bool
	{
		if (Cashbox\Manager::isPaySystemCashbox($this->arParams['handler']))
		{
			return false;
		}

		return $this->isCurrentZoneRu();
	}

	protected function isOfdFieldNeeded(): bool
	{
		if (!$this->isCurrentZoneRu())
		{
			return false;
		}

		return $this->arParams['handler']::isOfdSettingsNeeded();
	}

	protected function isCurrentZoneRu(): bool
	{
		if (Bitrix24Manager::getInstance()->isEnabled())
		{
			if (Bitrix24Manager::getInstance()->isCurrentZone('ru'))
			{
				return true;
			}
		}
		elseif (IntranetManager::getInstance()->isEnabled())
		{
			if (IntranetManager::getInstance()->isCurrentZone('ru'))
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * @return array
	 */
	protected function getConfig()
	{
		$config = [
			[
				'name' => 'parameters',
				'type' => 'section',
				'elements' => [
					['name' => 'KKM_ID'],
					['name' => 'NAME'],
					['name' => 'OFD'],
					['name' => 'NUMBER_KKM'],
					['name' => 'USE_OFFLINE'],
					['name' => 'EMAIL'],
				]
			]
		];

		return $config;
	}

	/**
	 * @return array
	 */
	protected function getOfdSettings()
	{
		$ofdHandler = $this->arResult['data']['OFD'] ?? '';
		if (is_string($ofdHandler) && Cashbox\Ofd::doesHandlerExist($ofdHandler))
		{
			/** @var Cashbox\Ofd $ofdHandler */
			return $ofdHandler::getSettings();
		}

		return [];
	}

	/**
	 * @param array $data
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	protected function getData(array $data = [])
	{
		if ($this->cashboxData === null || !empty($data))
		{
			if (isset($data['id']))
			{
				$cashboxId = (int)$data['id'];
			}
			else
			{
				$cashboxId = (int)$this->arResult['id'];
			}

			$result = [];

			if ($cashboxId > 0)
			{
				$result = Cashbox\Internals\CashboxTable::getById($cashboxId)->fetch();
				if ($result)
				{
					$result['id'] = $cashboxId;
				}
			}
			else
			{
				if (is_a($this->handler, Cashbox\CashboxOrangeData::class, true))
				{
					$result['OFD'] = '\\'.Cashbox\TaxcomOfd::class;
				}

				$result['KKM_ID'] = $this->kkmId;

				$result['ACTIVE'] = 'Y';
			}

			$this->cashboxData = array_merge($result, $data);
		}

		return $this->cashboxData;
	}

	/**
	 * @return array
	 */
	protected function getCashboxSettings()
	{
		if ($this->handler === '\Bitrix\Sale\Cashbox\CashboxRest')
		{
			return Cashbox\CashboxRest::getConfigStructure($this->arParams['restHandler']);
		}
		return $this->handler::getSettings();
	}

	/**
	 * @param array $cashboxSettings
	 * @param string $prefix
	 * @return array
	 * @throws Main\SystemException
	 */
	protected function extractCashboxSettings(array $cashboxSettings, $prefix = 'SETTINGS')
	{
		$fields = $sections = $data = [];

		foreach($cashboxSettings as $sectionName => $cashboxSetting)
		{
			$section = [
				'type' => 'section',
				'name' => $prefix.'_'.$sectionName,
				'title' => $cashboxSetting['LABEL'],
				'elements' => [],
			];
			$isFieldsRequired = (isset($cashboxSetting['REQUIRED']) && $cashboxSetting['REQUIRED'] === 'Y');
			if (is_array($cashboxSetting['ITEMS']))
			{
				foreach($cashboxSetting['ITEMS'] as $itemName => $item)
				{
					$name = $prefix.'['.$sectionName.']['.$itemName.']';
					$value = $item['VALUE'] ?? null;
					if (isset($this->arResult['data'][$prefix][$sectionName][$itemName]))
					{
						$value = $this->arResult['data'][$prefix][$sectionName][$itemName];
					}
					elseif (!empty($item['VALUE']))
					{
						$data[$name] = $item['VALUE'];
					}
					$item['CLASS'] = 'ui-ctl-element';
					$field = [
						'name' => $name,
						'title' => $item['LABEL'],
						'required' => ($isFieldsRequired || (isset($item['REQUIRED']) && $item['REQUIRED'] === 'Y')),
					];
					if ($item['TYPE'] == 'Y/N')
					{
						$field['type'] = 'boolean';
					}
					elseif ($item['TYPE'] == 'DATABASE_FILE')
					{
						$field['type'] = 'file';
						$field['label'] = Loc::getMessage('SC_CASHBOX_FILE_LABEL');
						if (!empty($value))
						{
							$field['required'] = false;
						}
						$field['addHidden'] = true;
						$field['value'] = $value;
					}
					else
					{
						$field['html'] = Sale\Internals\Input\Manager::getEditHtml($this->addPrefixToInputName('fields', $name), $item, $value);
					}
					$fields[] = $field;
					$section['elements'][] = [
						'name' => $name,
					];
				}
			}
			$sections[] = $section;
		}

		return [
			'config' => $sections,
			'fields' => $fields,
			'data' => $data,
		];
	}

	/**
	 * @param array $cashbox
	 * @return Main\Result
	 */
	protected function prepareFields(array $cashbox)
	{
		$result = new Main\Result();

		if (isset($cashbox['ACTIVE']) && $cashbox['ACTIVE']  !== 'Y')
		{
			$cashbox['ACTIVE'] = 'N';
		}
		if (isset($cashbox['USE_OFFLINE']) && $cashbox['USE_OFFLINE'] !== 'Y')
		{
			$cashbox['USE_OFFLINE'] = 'N';
		}
		if (empty($cashbox['SORT']))
		{
			$cashbox['SORT'] = 100;
		}
		if (empty($cashbox['OFD_SETTINGS']))
		{
			$cashbox['OFD_SETTINGS'] = [];
		}
		$cashbox['ENABLED'] = 'Y';
		$cashbox['HANDLER'] = $this->handler;
		$cashbox['KKM_ID'] = $this->kkmId;

		$handlerList = Cashbox\Cashbox::getHandlerList();
		/** @noinspection PhpIllegalArrayKeyTypeInspection */
		if (!isset($handlerList[$cashbox['HANDLER']]))
		{
			$result->addError(new Main\Error(Loc::getMessage("SC_SALESCENTER_ERROR_NO_HANDLER_EXIST")));
		}

		if (!$result->isSuccess())
		{
			return $result;
		}

		if (
			isset($cashbox['OFD'])
			&& is_string($cashbox['OFD'])
			&& !empty($cashbox['OFD'])
			&& !Cashbox\Ofd::doesHandlerExist($cashbox['OFD']))
		{
			$result->addError(new Main\Error(Loc::getMessage("SC_SALESCENTER_ERROR_NO_OFD_EXIST")));
		}

		if (!$result->isSuccess())
		{
			return $result;
		}

		$cashbox['SETTINGS'] = array_merge($cashbox['SETTINGS'], $this->addFileSettings($cashbox));

		$cashboxObject = Cashbox\Cashbox::create($cashbox);
		$validateResult = $cashboxObject->validate();
		if (!$validateResult->isSuccess())
		{
			$result->addErrors($validateResult->getErrors());
		}

		$result->setData($cashbox);
		return $result;
	}

	/**
	 * @param array $fields
	 * @return Main\Entity\AddResult|Main\Entity\UpdateResult|Sale\Result|false
	 * @throws Main\LoaderException
	 */
	public function saveAction(array $fields)
	{
		$result = $this->prepare();
		if (!$result->isSuccess())
		{
			$this->errorCollection->add($result->getErrors());
			return false;
		}
		$result = $this->prepareFields($fields);
		if (!$result->isSuccess())
		{
			$this->errorCollection->add($result->getErrors());
			return false;
		}

		$cashbox = $result->getData();
		$id = (int)$this->arResult['id'];
		if ($id > 0)
		{
			$result = $this->updateCashbox($id, $cashbox);
		}
		else
		{
			$result = $this->saveCashbox($cashbox);
		}

		return $result;
	}

	/**
	 * @param $cashbox
	 * @return Main\Entity\AddResult
	 */
	protected function saveCashbox($cashbox)
	{
		$result = Cashbox\Manager::add($cashbox);
		if ($result->isSuccess())
		{
			$service = Cashbox\Manager::getObjectById($result->getId());
			AddEventToStatFile('salescenter', 'addCashbox', $result->getId(), $service::getCode());
		}

		return $result;
	}

	/**
	 * @param $id
	 * @param $cashbox
	 * @return Main\Entity\UpdateResult
	 */
	protected function updateCashbox($id, $cashbox)
	{
		$result = Cashbox\Manager::update($id, $cashbox);
		if ($result->isSuccess())
		{
			$service = Cashbox\Manager::getObjectById($id);
			AddEventToStatFile('salescenter', 'updateCashbox', $id, $service::getCode());
		}

		return $result;
	}

	/**
	 * @return array
	 */
	public function configureActions()
	{
		return [];
	}

	/**
	 * @param array $fields
	 * @return array
	 * @throws Main\SystemException
	 */
	public function getFormConfigAction(array $fields = [])
	{
		$result = $this->prepare();
		if (!$result->isSuccess())
		{
			$this->errorCollection->add($result->getErrors());
			return [];
		}

		$form = [];
		$this->arResult['data'] = $form['data'] = $this->getData($fields);
		$form['fields'] = $this->getFields();
		$form['config'] = $this->getConfig();

		$extractedCashboxSettings = $this->extractCashboxSettings($this->getCashboxSettings(), 'SETTINGS');
		$extractedOfdSettings = $this->extractCashboxSettings($this->getOfdSettings(), 'OFD_SETTINGS');
		$form['fields'] = array_merge($form['fields'], $extractedCashboxSettings['fields'], $extractedOfdSettings['fields']);
		$form['config'] = array_merge($form['config'], $extractedCashboxSettings['config'], $extractedOfdSettings['config']);
		$form['data'] = array_merge($form['data'], $extractedCashboxSettings['data'], $extractedOfdSettings['data']);

		foreach($form['fields'] as &$field)
		{
			$field['name'] = $this->addPrefixToInputName('fields', $field['name']);
		}
		foreach($form['config'] as &$section)
		{
			if ($section['elements'] && is_array($section['elements']))
			{
				foreach($section['elements'] as &$element)
				{
					$element['name'] = $this->addPrefixToInputName('fields', $element['name']);
				}
			}
		}
		$data = [];
		foreach($form['data'] as $key => $value)
		{
			if (is_array($value))
			{
				foreach($value as $k1 => $v1)
				{
					if (is_array($v1))
					{
						foreach($v1 as $k2 => $v2)
						{
							$data[$this->addPrefixToInputName('fields', $this->addPrefixToInputName($key, $this->addPrefixToInputName($k1, $k2)))] = $v2;
						}
					}
					else
					{
						$data[$this->addPrefixToInputName('fields', $this->addPrefixToInputName($key, $k1))] = $v1;
					}
				}
			}
			$data[$this->addPrefixToInputName('fields', $key)] = $value;
		}
		$form['data'] = $data;

		return $form;
	}

	/**
	 * @param $prefix
	 * @param $name
	 * @return mixed|string
	 */
	protected function addPrefixToInputName($prefix, $name)
	{
		$pos = mb_strpos($name, '[');
		if ($pos === false)
		{
			return $prefix.'['.$name.']';
		}
		else
		{
			$firstPart = mb_substr($name, 0, $pos);
			return str_replace($firstPart, $prefix.'['.$firstPart.']', $name);
		}
	}

	/**
	 * @return array|null
	 */
	protected function listKeysSignedParameters()
	{
		return [
			'handler', 'id', 'kkm-id'
		];
	}

	/**
	 * Getting array of errors.
	 * @return \Bitrix\Main\Error[]
	 */
	public function getErrors()
	{
		return $this->errorCollection->toArray();
	}

	/**
	 * Getting once error with the necessary code.
	 * @param string $code Code of error.
	 * @return \Bitrix\Main\Error
	 */
	public function getErrorByCode($code)
	{
		return $this->errorCollection->getErrorByCode($code);
	}

	/**
	 * @param array $fields
	 * @return array
	 */
	protected function addFileSettings(array $fields)
	{
		$settings = [];

		if (is_a($this->handler, Bitrix\Sale\Cashbox\CashboxOrangeData::class, true))
		{
			global $APPLICATION;
			$files = $this->request->getFile('fields');

			foreach($fields['SETTINGS']['SECURITY'] as $fieldId => $field)
			{
				if ($files['error']['SETTINGS']['SECURITY'][$fieldId] === 0
					&& $files['tmp_name']['SETTINGS']['SECURITY'][$fieldId]
				)
				{
					$settings['SECURITY'][$fieldId] = '';
					$content = $APPLICATION->GetFileContent($files['tmp_name']['SETTINGS']['SECURITY'][$fieldId]);
					if ($content)
					{
						$settings['SECURITY'][$fieldId] = $content;
					}
				}
				else
				{
					$settings['SECURITY'][$fieldId] = $field;
				}
			}
		}

		return $settings;
	}

	/**
	 * @return array|null
	 */
	protected function getHandlerDescription()
	{
		$code = $this->handler::getCode();
		if ($this->kkmId && !Cashbox\Manager::isPaySystemCashbox($this->arParams['handler']))
		{
			$code .= '_'.$this->kkmId;
		}

		switch ($code)
		{
			case 'cashboxorangedata':
				return [
					'code' => 'orange',
					'title' => 'SC_CASHBOX_ORANGE_TITLE',
					'description' => 'SC_CASHBOX_ORANGE_DESCRITION',
				];
			case Sale\Cashbox\CashboxOrangeDataFfd12::getCode():
				return [
					'code' => 'orange',
					'title' => Sale\Cashbox\CashboxOrangeDataFfd12::getName(),
					'description' => 'SC_CASHBOX_ORANGE_DESCRITION',
				];
				break;
			case 'cashboxcheckbox':
				return [
					'code' => 'checkbox',
					'title' => 'SC_CASHBOX_CHECKBOX_TITLE',
					'description' => 'SC_CASHBOX_CHECKBOX_DESCRIPTION',
				];
			case 'cashboxbusinessru_atol':
				return [
					'code' => 'businessru-atol',
					'title' => 'SC_CASHBOX_BUSINESSRU_ATOL_TITLE',
					'description' => 'SC_CASHBOX_BUSINESSRU_DESCRIPTION',
				];
			case 'cashboxbusinessru_shtrih-m':
				return [
					'code' => 'businessru-shtrihm',
					'title' => 'SC_CASHBOX_BUSINESSRU_SHTRIHM_TITLE',
					'description' => 'SC_CASHBOX_BUSINESSRU_DESCRIPTION',
				];
			case 'cashboxbusinessru_evotor':
				return [
					'code' => 'businessru-evotor',
					'title' => 'SC_CASHBOX_BUSINESSRU_EVOTOR_TITLE',
					'description' => 'SC_CASHBOX_BUSINESSRU_DESCRIPTION',
				];
			case Sale\Cashbox\CashboxBusinessRuV5::getCode() . '_atol':
				return [
					'code' => 'businessru-atol',
					'title' => Sale\Cashbox\CashboxBusinessRuV5::getName() . ': ' . Loc::getMessage('SC_CASHBOX_KKM_ATOL'),
					'description' => 'SC_CASHBOX_BUSINESSRU_DESCRIPTION',
				];
			case Sale\Cashbox\CashboxBusinessRuV5::getCode() . '_shtrih-m':
				return [
					'code' => 'businessru-shtrihm',
					'title' => Sale\Cashbox\CashboxBusinessRuV5::getName() . ': ' . Loc::getMessage('SC_CASHBOX_KKM_SHTRIHM'),
					'description' => 'SC_CASHBOX_BUSINESSRU_DESCRIPTION',
				];
			case Sale\Cashbox\CashboxBusinessRuV5::getCode() . '_evotor':
				return [
					'code' => 'businessru-evotor',
					'title' => Sale\Cashbox\CashboxBusinessRuV5::getName() . ': ' . Loc::getMessage('SC_CASHBOX_KKM_EVOTOR'),
					'description' => 'SC_CASHBOX_BUSINESSRU_DESCRIPTION',
				];

			case 'cashboxrest':
				$handlerName = Cashbox\Manager::getRestHandlersList()[$this->arParams['restHandler']]["NAME"];
				return [
					'code' => 'rest',
					'title' => $handlerName,
					'description' => 'SC_CASHBOX_REST_DESCRITION',
				];
			case 'cashboxrobokassa':
				return [
					'code' => Cashbox\CashboxRobokassa::getCode(),
					'title' => Cashbox\CashboxRobokassa::getName(),
					'description' => 'SC_CASHBOX_ROBOKASSA_DESCRITION',
				];
			case Sale\Cashbox\CashboxAtolFarmV4::getCode():
				return [
					'code' => 'atol',
					'title' => 'SC_CASHBOX_ATOL_TITLE',
					'description' => 'SC_CASHBOX_ATOL_DESCRITION',
				];
			case Sale\Cashbox\CashboxAtolFarmV5::getCode():
				return [
					'code' => 'atol',
					'title' => 'SC_CASHBOX_ATOL_FFD_12_TITLE',
					'description' => 'SC_CASHBOX_ATOL_DESCRITION',
				];
			case Sale\Cashbox\CashboxYooKassa::getCode():
				return [
					'code' => Cashbox\CashboxYooKassa::getCode(),
					'title' => Cashbox\CashboxYooKassa::getName(),
					'description' => 'SC_CASHBOX_YOOKASSA_DESCRIPTION_MSGVER_1',
				];
		}

		return null;
	}

	/**
	 * @param $id
	 * @throws Main\LoaderException
	 */
	public function deleteAction($id)
	{
		$this->arResult['id'] = $id;
		$result = $this->prepare();
		if (!$result->isSuccess())
		{
			$this->errorCollection->add($result->getErrors());
			return;
		}

		$cashbox = Cashbox\Manager::getObjectById($id);

		$deleteResult = Cashbox\Manager::delete($id);
		if (!$deleteResult->isSuccess())
		{
			$this->errorCollection->add($deleteResult->getErrors());
		}
		elseif ($cashbox)
		{
			AddEventToStatFile('salescenter', 'deleteCashbox', '', $cashbox::getCode());
		}
	}

	/**
	 * @return bool
	 */
	private function showOfflineInfo()
	{
		return $this->arResult['handler'] === self::OFFLINE_HANDLER_TYPE;
	}

	private function addConnectionInfoUrl()
	{
		if ($this->handler == '\Bitrix\Sale\Cashbox\CashboxCheckbox')
		{
			$this->arResult['connectionInfoUrl'] = 'https://checkbox.in.ua/#get';
		}
	}
}