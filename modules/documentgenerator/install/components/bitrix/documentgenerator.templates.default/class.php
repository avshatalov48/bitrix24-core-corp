<?php

use Bitrix\DocumentGenerator\DataProviderManager;
use Bitrix\Main\Localization\Loc;

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

class DocumentsDefaultTemplatesComponent extends CBitrixComponent
{
	protected $gridId = 'documentgenerator_templates_default_grid';
	protected $filterId = 'documentgenerator_templates_default_filter';
	protected $navParamName = 'page';
	protected $defaultGridSort = [
		'SORT' => 'asc',
	];

	public function onPrepareComponentParams($arParams)
	{
		parent::onPrepareComponentParams($arParams);
	}

	public function executeComponent()
	{
		if(!\Bitrix\Main\Loader::includeModule('documentgenerator'))
		{
			ShowError(Loc::getMessage('DOCGEN_TEMPLATES_DEFAULT_ERROR_MODULE'));
			$this->includeComponentTemplate();
			return;
		}
		if(!$this->includeModule())
		{
			ShowError(Loc::getMessage('DOCGEN_TEMPLATES_DEFAULT_MODULE_ERROR', ['MODULE_ID' => $this->arParams['MODULE_ID']]));
			return;
		}
		if(!\Bitrix\DocumentGenerator\Driver::getInstance()->getUserPermissions()->canModifyTemplates())
		{
			ShowError(Loc::getMessage('DOCGEN_TEMPLATES_PERMISSIONS_ERROR'));
			return;
		}
		$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
		if($request->get('IFRAME') === 'Y')
		{
			$this->arResult['IS_SLIDER'] = true;
		}
		else
		{
			$this->arResult['IS_SLIDER'] = false;
			if(SITE_TEMPLATE_ID == "bitrix24")
			{
				$this->arResult['TOP_VIEW_TARGET_ID'] = 'pagetitle';
			}
		}

		$filter = $this->getFilter();
		$result = Bitrix\DocumentGenerator\Controller\Template::getDefaultTemplateList($filter);
		if($result->isSuccess())
		{
			$templates = $result->getData();
			foreach($templates as &$template)
			{
				if($template['IS_DELETED'] === 'Y' && $template['ID'] > 0)
				{
					unset($template['ID']);
				}
			}
			$this->arResult['GRID'] = $this->prepareGrid($templates);
			$this->arResult['FILTER'] = $this->prepareFilter();
			$this->arResult['TITLE'] = Loc::getMessage('DOCGEN_TEMPLATES_DEFAULT_TITLE');

			$this->arResult['params'] = [];
			$this->arResult['params']['gridId'] = $this->gridId;
		}
		else
		{
			ShowError(join('<br />', $result->getErrorMessages()));
		}

		$this->includeComponentTemplate();
	}

	protected function includeModule()
	{
		$moduleId = $this->arParams['MODULE_ID'];
		if($moduleId)
		{
			if(\Bitrix\Main\ModuleManager::isModuleInstalled($moduleId))
			{
				return \Bitrix\Main\Loader::includeModule($moduleId);
			}

			return false;
		}

		return true;
	}

	/**
	 * @param array $templates
	 * @return array
	 */
	protected function prepareGrid(array $templates)
	{
		$grid = [];
		$grid['GRID_ID'] = $this->gridId;
		$grid['COLUMNS'] = [
			[
				'id' => 'NAME',
				'name' => Loc::getMessage('DOCGEN_TEMPLATES_DEFAULT_NAME'),
				'default' => true,
				'sort' => false,
			],
			[
				'id' => 'PROVIDERS',
				'name' => Loc::getMessage('DOCGEN_TEMPLATES_DEFAULT_ENTITIES'),
				'default' => true,
				'sort' => false,
			],
			[
				'id' => 'REGION',
				'name' => Loc::getMessage('DOCGEN_TEMPLATES_DEFAULT_REGION'),
				'default' => true,
				'sort' => false,
			],
			[
				'id' => 'INSTALL',
				'name' => Loc::getMessage('DOCGEN_TEMPLATES_DEFAULT_INSTALL'),
				'default' => true,
				'sort' => false,
			],
			[
				'id' => 'SORT',
				'name' => Loc::getMessage('DOCGEN_TEMPLATES_DEFAULT_SORT'),
				'default' => false,
				'sort' => false,
			],
		];

		if(!empty($templates))
		{
			foreach($templates as $template)
			{
				if(isset($template['ID']) && $template['ID'] > 0)
				{
					$install = '<a class="docs-template-link-action" onclick="BX.DocumentGenerator.TemplatesDefault.reinstall(\''.htmlspecialcharsbx(CUtil::JSEscape($template['CODE'])).'\', \''.htmlspecialcharsbx(CUtil::JSEscape($template['NAME'])).'\', this);">'.Loc::getMessage('DOCGEN_TEMPLATES_DEFAULT_REINSTALL').'</a>';
				}
				else
				{
					$install = '<a class="docs-template-link-action" onclick="BX.DocumentGenerator.TemplatesDefault.install(\''.htmlspecialcharsbx(CUtil::JSEscape($template['CODE'])).'\', \''.htmlspecialcharsbx(CUtil::JSEscape($template['NAME'])).'\', this);">'.Loc::getMessage('DOCGEN_TEMPLATES_DEFAULT_INSTALL').'</a>';
				}

				$grid['ROWS'][] = [
					'id' => htmlspecialcharsbx($template['CODE']),
					'data' => $template,
					'columns' => [
						'NAME' => htmlspecialcharsbx($template['NAME']),
						'PROVIDERS' => htmlspecialcharsbx(implode(', ', $template['PROVIDER_NAMES'])),
						'REGION' => htmlspecialcharsbx($this->getRegions()[$template['REGION']]['NAME']),
						'SORT' => (int)$template['SORT'],
						'INSTALL' => $install,
					],
				];
			}
		}

		$grid['TOTAL_ROWS_COUNT'] = count($templates);
		$grid['AJAX_MODE'] = 'Y';
		$grid['ALLOW_ROWS_SORT'] = false;
		$grid['AJAX_OPTION_JUMP'] = "N";
		$grid['AJAX_OPTION_STYLE'] = "N";
		$grid['AJAX_OPTION_HISTORY'] = "N";
		$grid['AJAX_ID'] = \CAjax::GetComponentID("bitrix:main.ui.grid", '', '');
		$grid['SHOW_ROW_CHECKBOXES'] = false;
		$grid['SHOW_CHECK_ALL_CHECKBOXES'] = false;
		$grid['SHOW_ACTION_PANEL'] = false;

		return $grid;
	}

	/**
	 * @return array|null
	 */
	protected function getProviders()
	{
		static $providers = null;
		if($providers === null)
		{
			$providers = DataProviderManager::getInstance()->getList(['filter' => ['MODULE' => $this->arParams['MODULE']]]);
		}
		return $providers;
	}

	/**
	 * @return array
	 */
	protected function prepareFilter()
	{
		$filter = [
			'FILTER_ID' => $this->filterId,
			'GRID_ID' => $this->gridId,
			'FILTER' => $this->getDefaultFilterFields(),
			'DISABLE_SEARCH' => false,
			'ENABLE_LABEL' => true,
			'RESET_TO_DEFAULT_MODE' => false,
		];

		return $filter;
	}

	/**
	 * @return array
	 */
	protected function getDefaultFilterFields()
	{
		return [
			[
				'id' => 'REGION',
				'name' => Loc::getMessage('DOCGEN_TEMPLATES_DEFAULT_REGION'),
				'type' => 'list',
				'items' => $this->getRegions(),
				'default' => true,
				'params' => ['multiple' => 'Y'],
			],
			[
				"id" => "NAME",
				"name" => Loc::getMessage('DOCGEN_TEMPLATES_DEFAULT_NAME'),
				"default" => true
			],
			[
				"id" => "PROVIDER",
				"name" => Loc::getMessage('DOCGEN_TEMPLATES_DEFAULT_ENTITIES'),
				"type" => "list",
				"items" => $this->getProviders(),
				"default" => true,
				"params" => [
					'multiple' => 'Y',
				]
			],
		];
	}

	/**
	 * @return array
	 */
	protected function getFilter()
	{
		$filter = [];

		$filterOptions = new Bitrix\Main\UI\Filter\Options($this->filterId);
		$requestFilter = $filterOptions->getFilter($this->getDefaultFilterFields());
		if(isset($this->arParams['MODULE_ID']))
		{
			$filter['MODULE_ID'] = $this->arParams['MODULE_ID'];
		}
		if(isset($requestFilter['REGION']))
		{
			$filter['REGION'] = $requestFilter['REGION'];
		}
		if(isset($requestFilter['NAME']))
		{
			$filter['NAME'] = $requestFilter['NAME'];
		}
		elseif(isset($requestFilter['FIND']) && !empty($requestFilter['FIND']))
		{
			$filter['NAME'] = $requestFilter['FIND'];
		}
		if(isset($requestFilter['PROVIDER']))
		{
			$filter['PROVIDER'] = $requestFilter['PROVIDER'];
		}

		return $filter;
	}

	/**
	 * @return array
	 */
	protected function getRegions()
	{
		static $result = null;

		if($result === null)
		{
			$result = [];
			$regions = \Bitrix\DocumentGenerator\Driver::getInstance()->getRegionsList();
			foreach($regions as $region)
			{
				$result[$region['CODE']] = ['NAME' => $region['TITLE']];
			}
		}

		return $result;
	}
}