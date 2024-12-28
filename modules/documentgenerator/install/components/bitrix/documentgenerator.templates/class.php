<?php

use Bitrix\DocumentGenerator\DataProviderManager;
use Bitrix\DocumentGenerator\Driver;
use Bitrix\DocumentGenerator\Model\TemplateProviderTable;
use Bitrix\DocumentGenerator\Model\TemplateTable;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Engine\Response\AjaxJson;
use Bitrix\Main\Error;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Uri;

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

class DocumentsTemplateComponent extends CBitrixComponent implements Controllerable
{
	protected $gridId = 'documentgenerator_templates_grid';
	protected $filterId = 'documentgenerator_templates_filter';
	protected $navParamName = 'page';
	protected $defaultGridSort = [
		'SORT' => 'asc',
	];

	public function onPrepareComponentParams($arParams)
	{
		if(!$arParams['UPLOAD_URI'] && $this->includeModules())
		{
			$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
			$uploadUri = new \Bitrix\Main\Web\Uri($request->getRequestUri());
			$uploadUri->addParams(['UPLOAD' => 'Y']);
			$arParams['UPLOAD_URI'] = $uploadUri->getLocator();
		}

		return parent::onPrepareComponentParams($arParams);
	}

	/**
	 * @return mixed|void
	 */
	public function executeComponent()
	{
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
		if(!$this->includeModules())
		{
			$this->arResult['ERROR'] = Loc::getMessage('DOCGEN_TEMPLATE_DOWNLOAD_ADD_TEMPLATE_ERROR_MODULE');
			$this->includeComponentTemplate();
			return;
		}
		if(!Driver::getInstance()->getUserPermissions()->canModifyTemplates())
		{
			$this->arResult['ERROR'] = Loc::getMessage('DOCGEN_TEMPLATE_DOWNLOAD_PERMISSIONS_ERROR');
			$this->includeComponentTemplate();
			return;
		}
		if(!isset($this->arParams['USER_NAME_FORMAT']))
		{
			$this->arParams['USER_NAME_FORMAT'] = DataProviderManager::getInstance()->getCulture()->getNameFormat();
		}
		if(!isset($this->arParams['USER_PROFILE_URL']))
		{
			$this->arParams['USER_PROFILE_URL'] = \Bitrix\Main\Config\Option::get('intranet', 'path_user', '/company/personal/user/#USER_ID#/', SITE_ID);
		}
		if ($this->getTemplateName() === 'upload')
		{
			$this->arResult['TITLE'] = Loc::getMessage('DOCGEN_TEMPLATE_DOWNLOAD_ADD_TEMPLATE');
			$this->arResult['PROVIDERS'] = $this->getProviders();
			$this->arResult['REGIONS'] = Driver::getInstance()->getRegionsList();
			$this->arResult['PRODUCTS_TABLE_VARIANT'] = TemplateTable::getProductsTableVariantList();
			if($this->arParams['ID'] > 0)
			{
				$template = \Bitrix\DocumentGenerator\Template::loadById($this->arParams['ID']);
				if($template)
				{
					if(!Driver::getInstance()->getUserPermissions()->canModifyTemplate($template->ID))
					{
						$this->arResult['ERROR'] = Loc::getMessage('DOCGEN_TEMPLATE_DOWNLOAD_PERMISSIONS_ERROR_TEMPLATE');
					}
					else
					{
						if($template->CODE)
						{
							$defaultTemplates = \Bitrix\DocumentGenerator\Controller\Template::getDefaultTemplateList(['CODE' => $template->CODE, 'NAME' => $template->NAME]);
							if($defaultTemplates->isSuccess() && isset($defaultTemplates->getData()[$template->CODE]))
							{
								$this->arResult['params']['defaultCode'] = $template->CODE;
							}
						}
						$this->arResult['TITLE'] = Loc::getMessage('DOCGEN_TEMPLATE_DOWNLOAD_EDIT_TEMPLATE').' '.$template->NAME;
						$this->arResult['params']['downloadUrl'] = $template->getDownloadUrl();
						$this->arResult['TEMPLATE']['fileName'] = $template->getFileName();
						$this->arResult['TEMPLATE']['fileSize'] = \Bitrix\DocumentGenerator\Model\FileTable::getSize($template->FILE_ID);
						$this->arResult['TEMPLATE']['ACTIVE'] = $template->ACTIVE;
						$this->arResult['TEMPLATE']['ID'] = $template->ID;
						$this->arResult['TEMPLATE']['REGION'] = $template->REGION;
						$this->arResult['TEMPLATE']['FILE_ID'] = $template->FILE_ID;
						$this->arResult['TEMPLATE']['NAME'] = $template->NAME;
						$this->arResult['TEMPLATE']['NUMERATOR_ID'] = $template->NUMERATOR_ID;
						$this->arResult['TEMPLATE']['WITH_STAMPS'] = $template->WITH_STAMPS;
						$this->arResult['TEMPLATE']['PRODUCTS_TABLE_VARIANT'] = $template->PRODUCTS_TABLE_VARIANT;
						$this->arResult['TEMPLATE']['PROVIDERS'] = [];
						if (empty($this->arParams['MODULE']))
						{
							$this->arParams['MODULE'] = $template->MODULE_ID;
						}
						foreach($template->getDataProviders() as $provider)
						{
							$this->arResult['TEMPLATE']['PROVIDERS'][] = $this->arResult['PROVIDERS'][$provider];
						}
						$users = $template->getUsers();
						$this->arResult['TEMPLATE']['USERS'] = array_values($users);
					}
				}
				else
				{
					$this->arResult['ERROR'] = Loc::getMessage('DOCGEN_TEMPLATE_DOWNLOAD_TEMPLATE_NOT_FOUND');
				}
			}
			else
			{
				$this->arResult['TEMPLATE']['USERS'] = ['UA'];
			}
			if(!$this->arResult['TEMPLATE']['REGION'])
			{
				$this->arResult['TEMPLATE']['REGION'] = Driver::getInstance()->getCurrentRegion()['CODE'];
			}
			$this->arResult['params']['uploadUrl'] = Bitrix\Main\Engine\UrlManager::getInstance()->create('documentgenerator.api.file.upload')->getLocator();
			$this->arResult['userSelectorName'] = 'add-template-users';
			$numeratorList = \Bitrix\Main\Numerator\Numerator::getListByType(Driver::NUMERATOR_TYPE);
			if (empty($numeratorList))
			{
				Driver::getInstance()->getDefaultNumerator();
				$numeratorList = \Bitrix\Main\Numerator\Numerator::getListByType(Driver::NUMERATOR_TYPE);
			}
			$this->arResult['numeratorList'] = $numeratorList;
			$this->arResult['params']['addRegionUrl'] = $this->getRegionEditUrl();
		}
		else
		{
			$this->processGridActions($request);

			$this->arResult['params'] = [];
			$this->arResult['params']['uploadUri'] = $this->arParams['UPLOAD_URI'];
			$this->arResult['params']['settingsMenu'] = [];
			$uri = Driver::getInstance()->getPlaceholdersListUri($this->arParams['PROVIDER'], $this->arParams['MODULE']);
			if($uri)
			{
				$this->arResult['params']['settingsMenu'][] = [
					'uri' => $uri->getLocator(),
					'text' => Loc::getMessage('DOCGEN_TEMPLATE_LIST_PLACEHOLDERS'),
				];
			}
			if(Driver::getInstance()->getUserPermissions()->canModifySettings())
			{
				$uri = $this->getConfigUri();
				if($uri)
				{
					$this->arResult['params']['settingsMenu'][] = [
						'uri' => $uri->getLocator(),
						'text' => Loc::getMessage('DOCGEN_TEMPLATE_LIST_CONFIG'),
					];
				}
				$uri = $this->getPermsUri();
				if($uri)
				{
					$this->arResult['params']['settingsMenu'][] = [
						'uri' => $uri->getLocator(),
						'text' => Loc::getMessage('DOCGEN_TEMPLATE_LIST_PERMS'),
					];
				}
			}
			$menuItems = [];
			$componentPath = \CComponentEngine::makeComponentPath('bitrix:documentgenerator.templates.default');
			$componentPath = getLocalPath('components'.$componentPath.'/slider.php');
			foreach(Driver::getInstance()->getDefaultRegions() as $region)
			{
				$uri = new \Bitrix\Main\Web\Uri($componentPath);
				$uri->addParams(['REGION[]' => $region['CODE'], 'apply_filter' => 'Y']);
				$menuItems[] = [
					'text' => $region['TITLE'],
					'uri' => $uri->getLocator(),
				];
			}
			$this->arResult['params']['settingsMenu'][] = [
				'text' => Loc::getMessage('DOCGEN_TEMPLATE_LIST_LOAD_DEFAULT_TEMPLATES'),
				'items' => $menuItems,
			];
			$this->arResult['TITLE'] = Loc::getMessage('DOCGEN_TEMPLATE_LIST_TITLE');
			$this->arResult['FILTER'] = $this->prepareFilter();
			$this->arResult['GRID'] = $this->prepareGrid();
		}

		$this->includeComponentTemplate();
	}

	/**
	 * @return array
	 */
	public function configureActions()
	{
		return [];
	}

	/**
	 * @return bool
	 * @throws \Bitrix\Main\LoaderException
	 */
	protected function includeModules()
	{
		if(Loader::includeModule('documentgenerator') && Loader::includeModule('socialnetwork'))
		{
			if(empty($this->arParams['MODULE']) || Loader::includeModule($this->arParams['MODULE']))
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * @return array
	 */
	protected function prepareGrid()
	{
		$grid = [];
		$grid['GRID_ID'] = $this->gridId;
		$grid['COLUMNS'] = [
			[
				'id' => 'ID',
				'name' => 'ID',
				'default' => false,
				'sort' => 'ID',
			],
			[
				'id' => 'NAME',
				'name' => Loc::getMessage('DOCGEN_TEMPLATE_LIST_NAME'),
				'default' => true,
				'sort' => 'NAME',
				'editable' => true,
			],
			[
				'id' => 'PROVIDERS',
				'name' => Loc::getMessage('DOCGEN_TEMPLATE_LIST_PROVIDERS'),
				'default' => true,
				'sort' => false,
			],
			[
				'id' => 'REGION',
				'name' => Loc::getMessage('DOCGEN_TEMPLATE_LIST_REGION'),
				'default' => false,
				'sort' => false,
			],
			[
				'id' => 'UPDATE_TIME',
				'name' => Loc::getMessage('DOCGEN_TEMPLATE_LIST_UPDATE_TIME'),
				'default' => true,
				'sort' => 'UPDATE_TIME',
			],
			[
				'id' => 'CREATE_TIME',
				'name' => Loc::getMessage('DOCGEN_TEMPLATE_LIST_CREATE_TIME'),
				'default' => false,
				'sort' => 'CREATE_TIME',
			],
			[
				'id' => 'DOWNLOAD',
				'name' => Loc::getMessage('DOCGEN_TEMPLATE_LIST_DOWNLOAD'),
				'default' => true,
				'sort' => false,
			],
			[
				'id' => 'SORT',
				'name' => Loc::getMessage('DOCGEN_TEMPLATE_LIST_SORT'),
				'default' => false,
				'sort' => 'SORT',
				'editable' => true,
			],
			[
				'id' => 'ACTIVE',
				'name' => Loc::getMessage('DOCGEN_TEMPLATE_LIST_ACTIVE'),
				'default' => false,
				'sort' => 'ACTIVE',
				'editable' => true,
			],
			[
				'id' => 'CREATED_BY',
				'name' => Loc::getMessage('DOCGEN_TEMPLATE_LIST_CREATED_BY'),
				'default' => false,
				'sort' => 'CREATED_BY',
			],
			[
				'id' => 'UPDATED_BY',
				'name' => Loc::getMessage('DOCGEN_TEMPLATE_LIST_UPDATED_BY'),
				'default' => false,
				'sort' => 'UPDATED_BY',
			]
		];

		$gridOptions = new Bitrix\Main\Grid\Options($this->gridId);
		$navParams = $gridOptions->getNavParams(['nPageSize' => 10]);
		$pageSize = (int)$navParams['nPageSize'];
		$gridSort = $gridOptions->GetSorting(['sort' => $this->defaultGridSort]);

		$pageNavigation = new \Bitrix\Main\UI\PageNavigation($this->navParamName);
		$pageNavigation->allowAllRecords(false)->setPageSize($pageSize)->initFromUri();

		$this->arResult['GRID']['ROWS'] = $buffer = [];
		$templateList = TemplateTable::getList([
			'order' => $gridSort['sort'],
			'offset' => $pageNavigation->getOffset(),
			'limit' => $pageNavigation->getLimit(),
			'filter' => $this->getListFilter(),
			'count_total' => true,
		]);
		$templates = $templateList->fetchAll();

		if(!empty($templates))
		{
			$users = $this->getUsers($templates);
			$providerTypes = $this->getProviders();
			foreach($templates as $template)
			{
				$buffer[$template['ID']] = $template;
			}
			$templates = $buffer;
			unset($buffer);
			$providers = TemplateProviderTable::getList(['filter' => ['TEMPLATE_ID' => array_keys($templates)]]);
			while($provider = $providers->fetch())
			{
				if(isset($providerTypes[$provider['PROVIDER']]))
				{
					$templates[$provider['TEMPLATE_ID']]['PROVIDERS'][] = $provider['PROVIDER'];
					$templates[$provider['TEMPLATE_ID']]['PROVIDER_NAMES'][] = $providerTypes[$provider['PROVIDER']]['NAME'];
				}
			}
			foreach($templates as $template)
			{
				$templateInstance = \Bitrix\DocumentGenerator\Template::loadFromArray($template);
				$grid['ROWS'][] = [
					'id' => $template['ID'],
					'data' => $template,
					'actions' => [
						[
							'ICONCLASS' => 'edit',
							'TEXT' => Loc::getMessage('DOCGEN_TEMPLATE_LIST_EDIT'),
							'ONCLICK' => 'BX.DocumentGenerator.TemplateList.edit(\''.$template['ID'].'\')',
						],
						[
							'ICONCLASS' => 'delete',
							'TEXT' => Loc::getMessage('DOCGEN_TEMPLATE_LIST_DELETE'),
							'ONCLICK' => 'BX.DocumentGenerator.TemplateList.delete(\''.$template['ID'].'\')',
						],
					],
					'columns' => [
						'ID' => $template['ID'],
						'ACTIVE' => ($template['ACTIVE'] !== 'Y' ? Loc::getMessage('DOCGEN_TEMPLATE_LIST_ACTIVE_NO') : Loc::getMessage('DOCGEN_TEMPLATE_LIST_ACTIVE_YES')),
						'NAME' => htmlspecialcharsbx($template['NAME']),
						'REGION' => htmlspecialcharsbx($this->getRegions()[$template['REGION']]['NAME']),
						'UPDATE_TIME' => $template['UPDATE_TIME'],
						'PROVIDERS' => htmlspecialcharsbx(implode(', ', (array)$template['PROVIDER_NAMES'])),
						'CREATE_TIME' => $template['CREATE_TIME'],
						'SORT' => $template['SORT'],
						'DOWNLOAD' => '<a target="_blank" href="'.$templateInstance->getDownloadUrl()->getLocator().'">'.Loc::getMessage('DOCGEN_TEMPLATE_LIST_DOWNLOAD').'</a>',
						'CREATED_BY' =>
							!empty($template['CREATED_BY']) && isset($users[$template['CREATED_BY']])
								? $users[$template['CREATED_BY']]
								: null
						,
						'UPDATED_BY' =>
							!empty($template['UPDATED_BY']) && isset($users[$template['UPDATED_BY']])
								? $users[$template['UPDATED_BY']]
								: null
						,
					],
				];
			}
		}

		$pageNavigation->setRecordCount($templateList->getCount());
		$grid['NAV_PARAM_NAME'] = $this->navParamName;
		$grid['CURRENT_PAGE'] = $pageNavigation->getCurrentPage();
		$grid['NAV_OBJECT'] = $pageNavigation;
		$grid['TOTAL_ROWS_COUNT'] = $templateList->getCount();
		$grid['AJAX_MODE'] = 'Y';
		if(!empty($templates) && $gridSort['sort'] === $this->defaultGridSort)
		{
			$grid['ALLOW_ROWS_SORT'] = true;
		}
		$grid['AJAX_OPTION_JUMP'] = "N";
		$grid['AJAX_OPTION_STYLE'] = "N";
		$grid['AJAX_OPTION_HISTORY'] = "N";
		$grid['AJAX_ID'] = \CAjax::GetComponentID("bitrix:main.ui.grid", '', '');
		$grid['SHOW_PAGESIZE'] = true;
		$grid['PAGE_SIZES'] = [['NAME' => '10', 'VALUE' => '10'], ['NAME' => '20', 'VALUE' => '20'], ['NAME' => '50', 'VALUE' => '50']];
		$grid['ACTIONS'] = [
			'delete' => true,
		];
		$grid['SHOW_ROW_CHECKBOXES'] = true;
		$grid['SHOW_CHECK_ALL_CHECKBOXES'] = true;
		$grid['SHOW_ACTION_PANEL'] = true;
		$snippet = new \Bitrix\Main\Grid\Panel\Snippet();
		$grid['ACTION_PANEL'] = [
			'GROUPS' => [
				[
					'ITEMS' => [
						$snippet->getRemoveButton(),
						$snippet->getEditButton(),
					],
				],
			]
		];

		return $grid;
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
			'DISABLE_SEARCH' => true,
			'ENABLE_LABEL' => true,
			'RESET_TO_DEFAULT_MODE' => false,
		];

		return $filter;
	}

	protected function getDefaultFilterFields()
	{
		return [
			[
				"id" => "NAME",
				"name" => Loc::getMessage('DOCGEN_TEMPLATE_LIST_NAME'),
				"default" => true
			],
			[
				"id" => "PROVIDER",
				"name" => Loc::getMessage('DOCGEN_TEMPLATE_LIST_PROVIDERS'),
				"type" => "list",
				"items" => $this->getProviders(),
				"default" => true,
				"params" => [
					'multiple' => 'Y',
				]
			],
			[
				'id' => 'REGION',
				'name' => Loc::getMessage('DOCGEN_TEMPLATE_LIST_REGION'),
				'type' => 'list',
				'items' => $this->getRegions(),
				'default' => true,
				'params' => ['multiple' => 'Y'],
			],
			[
				"id" => "UPDATE_TIME",
				"name" => Loc::getMessage('DOCGEN_TEMPLATE_LIST_UPDATE_TIME'),
				"type" => "date",
				"default" => true
			],
			[
				"id" => "CREATE_TIME",
				"name" => Loc::getMessage('DOCGEN_TEMPLATE_LIST_CREATE_TIME'),
				"type" => "date",
				"default" => false
			],
			[
				"id" => "ACTIVE",
				"name" => Loc::getMessage('DOCGEN_TEMPLATE_LIST_ACTIVE'),
				"type" => "list",
				"items" => [
					"Y" => Loc::getMessage('DOCGEN_TEMPLATE_LIST_ACTIVE_YES'),
					"N" => Loc::getMessage('DOCGEN_TEMPLATE_LIST_ACTIVE_NO'),
				],
				"default" => false
			],
		];
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
	protected function getListFilter()
	{
		$filterOptions = new Bitrix\Main\UI\Filter\Options($this->filterId);
		$requestFilter = $filterOptions->getFilter($this->getDefaultFilterFields());

		$filter = ['=IS_DELETED' => 'N'];
		if(isset($requestFilter['UPDATE_TIME_from']) && $requestFilter['UPDATE_TIME_from'])
		{
			$filter['>=UPDATE_TIME'] = $requestFilter['UPDATE_TIME_from'];
		}
		if(isset($requestFilter['UPDATE_TIME_to']) && $requestFilter['UPDATE_TIME_to'])
		{
			$filter['<=UPDATE_TIME'] = $requestFilter['UPDATE_TIME_to'];
		}
		if(isset($requestFilter['CREATE_TIME_from']) && $requestFilter['CREATE_TIME_from'])
		{
			$filter['>=CREATE_TIME'] = $requestFilter['CREATE_TIME_from'];
		}
		if(isset($requestFilter['CREATE_TIME_to']) && $requestFilter['CREATE_TIME_to'])
		{
			$filter['<=CREATE_TIME'] = $requestFilter['CREATE_TIME_to'];
		}
		if(isset($requestFilter['NAME']) && $requestFilter['NAME'])
		{
			$filter['NAME'] = '%' . $requestFilter['NAME'] . '%';
		}
		if(isset($requestFilter['PROVIDER']) && $requestFilter['PROVIDER'])
		{
			$filter['@PROVIDER.PROVIDER'] = $requestFilter['PROVIDER'];
		}
		if(isset($requestFilter['REGION']) && $requestFilter['REGION'])
		{
			$filter['@REGION'] = $requestFilter['REGION'];
		}
		if(isset($requestFilter['ACTIVE']) && $requestFilter['ACTIVE'])
		{
			$filter['=ACTIVE'] = $requestFilter['ACTIVE'];
		}
		if($this->arParams['MODULE'])
		{
			$filter['=MODULE_ID'] = $this->arParams['MODULE'];
		}

		$filter = array_merge($filter, Driver::getInstance()->getUserPermissions()->getFilterForTemplateList());

		return $filter;
	}

	/**
	 * @param array $order
	 * @return AjaxJson|static
	 * @throws Exception
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\LoaderException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function resortListAction(array $order)
	{
		if(!$this->includeModules())
		{
			Loc::loadMessages(__FILE__);
			return AjaxJson::createError(new ErrorCollection([new Error(Loc::getMessage('DOCGEN_TEMPLATE_DOWNLOAD_ADD_TEMPLATE_ERROR_MODULE'))]));
		}
		$gridOptions = new Bitrix\Main\Grid\Options($this->gridId);
		$gridSort = $gridOptions->GetSorting(['sort' => $this->defaultGridSort]);
		$updateTemplates = [];
		if($gridSort['sort'] == $this->defaultGridSort)
		{
			$templates = TemplateTable::getList([
				'select' => ['ID', 'SORT'],
				'order' => $gridSort['sort'],
				'filter' => ['=ID' => $order],
			]);
			$temp = [];
			foreach($templates as $template)
			{
				$temp[$template['ID']] = $template;
			}
			$templates = $temp;
			unset($temp);
			$i = 0;
			$startReorder = $stopReorder = false;
			$startSort = $endSort = 0;
			foreach($templates as $template)
			{
				if($startReorder && $template['ID'] == $order[$i])
				{
					$stopReorder = true;
				}
				if($stopReorder && $template['SORT'] > ($endSort + count($updateTemplates) + 10))
				{
					$endSort = $template['SORT'];
					break;
				}
				if(!$startReorder && $template['ID'] != $order[$i])
				{
					$startReorder = true;
					$startSort = $template['SORT'];
				}
				if($startReorder)
				{
					$updateTemplates[] = $order[$i];
				}
				$endSort = $template['SORT'];
				$i++;
			}
			$prevSort = 0;
			if(!empty($updateTemplates))
			{
				$stepSort = floor(($endSort - $startSort) / (count($updateTemplates) + 1));
				foreach($updateTemplates as $step => $templateId)
				{
					$sort = ($startSort + $stepSort * ($step + 1));
					while($sort <= $prevSort)
					{
						$sort++;
					}
					TemplateTable::update($templateId, ['SORT' => $sort]);
					$prevSort = $sort;
				}
			}
		}
		return new AjaxJson($updateTemplates);
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
			$regions = Driver::getInstance()->getRegionsList();
			foreach($regions as $region)
			{
				$result[$region['CODE']] = ['NAME' => $region['TITLE']];
			}
		}

		return $result;
	}

	/**
	 * @param null $regionId
	 * @return false|string
	 */
	protected function getRegionEditUrl($regionId = null)
	{
		$componentPath = \CComponentEngine::makeComponentPath('bitrix:documentgenerator.region');
		$componentPath = getLocalPath('components'.$componentPath.'/slider.php');
		if(!empty($componentPath))
		{
			if($regionId > 0)
			{
				$uri = new Uri($componentPath);
				$uri->addParams(['id' => $regionId]);
				$componentPath = $uri->getLocator();
			}
			return $componentPath;
		}

		return false;
	}

	/**
	 * @return Uri|bool
	 */
	protected function getConfigUri()
	{
		$componentPath = \CComponentEngine::makeComponentPath('bitrix:documentgenerator.config');
		$componentPath = getLocalPath('components'.$componentPath.'/slider.php');
		if($componentPath)
		{
			return new \Bitrix\Main\Web\Uri($componentPath);
		}

		return false;
	}

	/**
	 * @return Uri|bool
	 */
	protected function getPermsUri()
	{
		$componentPath = \CComponentEngine::makeComponentPath('bitrix:documentgenerator.settings.perms');
		$componentPath = getLocalPath('components'.$componentPath.'/slider.php');
		if($componentPath)
		{
			return new \Bitrix\Main\Web\Uri($componentPath);
		}

		return false;
	}

	/**
	 * @param array $templates
	 * @return array
	 */
	protected function getUsers(array $templates)
	{
		$users = [];
		$userIds = [];
		foreach($templates as $template)
		{
			$userIds[] = $template['CREATED_BY'];
			$userIds[] = $template['UPDATED_BY'];
		}
		if(empty($userIds))
		{
			return $users;
		}

		$userList = \Bitrix\Main\UserTable::getList(['select' => [
			'ID', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'TITLE'
		], 'filter' => [
			'=ID' => $userIds,
		]]);
		while($user = $userList->fetch())
		{
			$users[$user['ID']] = '<a href="'.str_replace('#USER_ID#', $user['ID'], $this->arParams['USER_PROFILE_URL']).'" target="_blank">'.\CUser::FormatName($this->arParams['USER_NAME_FORMAT'], $user).'</a>';
		}

		return $users;
	}

	protected function processGridActions(\Bitrix\Main\Request $request): void
	{
		if(
			$request->getRequestMethod() !== 'POST'
			|| empty($request->getPost('action_button_'.$this->gridId))
			|| !check_bitrix_sessid()
		)
		{
			return;
		}

		$userPermissions = Driver::getInstance()->getUserPermissions();

		$actionName = $request->getPost('action_button_' . $this->gridId);
		if ($actionName === 'delete')
		{
			$ids = $request->getPost('ID');
			\Bitrix\Main\Type\Collection::normalizeArrayValuesByInt($ids);

			foreach ($ids as $id)
			{
				if ($userPermissions->canModifyTemplate($id))
				{
					TemplateTable::delete($id);
				}
			}
		}
		elseif ($actionName === 'edit')
		{
			$data = $request->getPost('FIELDS');
			if (empty($data))
			{
				return;
			}
			$templateIds = array_keys($data);
			\Bitrix\Main\Type\Collection::normalizeArrayValuesByInt($templateIds);
			if (empty($templateIds))
			{
				return;
			}

			$allowedToModifyTemplateIds = array_filter($templateIds, fn(int $id) => $userPermissions->canModifyTemplate($id));
			if (empty($allowedToModifyTemplateIds))
			{
				return;
			}

			$templates = TemplateTable::getList([
				'filter' => [
					'@ID' => $allowedToModifyTemplateIds,
				],
			])->fetchCollection();
			foreach ($templates as $template)
			{
				$templateData = $data[$template->getId()] ?? null;
				if (empty($templateData))
				{
					continue;
				}

				$isChanged = false;
				$sort = (int)($templateData['SORT'] ?? 0);
				if ($sort > 0)
				{
					$template->setSort($sort);
					$isChanged = true;
				}
				$name = (string)($templateData['NAME'] ?? '');
				if (!empty($name))
				{
					$template->setName($name);
					$isChanged = true;
				}
				$active = (string)($templateData['ACTIVE'] ?? '');
				if ($active === 'Y' || $active === 'N')
				{
					$template->setActive($active === 'Y');
					$isChanged = true;
				}

				if ($isChanged)
				{
					$template->save();
				}
			}
		}
	}
}
