<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\DocumentGenerator\DataProviderManager;
use Bitrix\DocumentGenerator\Model\DocumentTable;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Uri;

class DocumentGeneratorDocumentsComponent extends CBitrixComponent
{
	/** @var \Bitrix\DocumentGenerator\DataProvider */
	protected $provider;
	protected $value;

	protected $gridId = 'documentgenerator-documents-grid';
	protected $filterId = 'documentgenerator-documents-filter';
	protected $navParamName = 'page';

	protected $defaultGridSort = [
		'CREATE_TIME' => 'desc',
	];

	public function onPrepareComponentParams($params)
	{
		$params = parent::onPrepareComponentParams($params);

		return $params;
	}

	/**
	 * @return mixed|void
	 */
	public function executeComponent()
	{
		if(!Loader::includeModule('documentgenerator'))
		{
			$this->showError(Loc::getMessage('DOCGEN_DOCUMENTS_MODULE_DOCGEN_ERROR'));
			return;
		}

		if(!\Bitrix\DocumentGenerator\Driver::getInstance()->getUserPermissions()->canViewDocuments())
		{
			$this->showError(Loc::getMessage('DOCGEN_DOCUMENTS_PERMISSIONS_ERROR'));
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

		$moduleId = $this->arParams['module'];
		if(!$moduleId)
		{
			$this->showError(Loc::getMessage('DOCGEN_DOCUMENTS_MODULE_EMPTY'));
			return;
		}
		if(!Loader::includeModule($moduleId))
		{
			$this->showError(Loc::getMessage('DOCGEN_DOCUMENTS_MODULE_ERROR', ['#MODULE_ID#' => $moduleId]));
			return;
		}
		if(empty($this->arParams['provider']) || !DataProviderManager::checkProviderName($this->arParams['provider'], $moduleId))
		{
			$this->showError(Loc::getMessage('DOCGEN_DOCUMENTS_PROVIDER_DOCGEN_ERROR'));
			return;
		}
		if(empty($this->arParams['value']))
		{
			$this->showError(Loc::getMessage('DOCGEN_DOCUMENTS_VALUE_DOCGEN_ERROR'));
			return;
		}
		$this->provider = DataProviderManager::getInstance()->getDataProvider(
			$this->arParams['provider'],
			$this->arParams['value'],
			[
				'isLightMode' => true,
				'noSubstitution' => true,
			]
		);
		if(!$this->provider)
		{
			$this->showError(Loc::getMessage('DOCGEN_DOCUMENTS_INIT_PROVIDER_ERROR'));
			return;
		}
		if(!$this->provider->hasAccess(\Bitrix\DocumentGenerator\Driver::getInstance()->getUserId()))
		{
			$this->showError(Loc::getMessage('DOCGEN_DOCUMENTS_ACCESS_ERROR'));
			return;
		}

		$this->arResult = [];

		$this->arResult['TOP_VIEW_TARGET_ID'] = false;
		$isIframe = $this->request->get('IFRAME') == 'Y' ? true : false;
		if(SITE_TEMPLATE_ID == "bitrix24")
		{
			if($isIframe)
			{
				$this->arResult['TOP_VIEW_TARGET_ID'] = 'inside_pagetitle';
			}
			else
			{
				$this->arResult['TOP_VIEW_TARGET_ID'] = 'pagetitle';
			}
		}

		$this->arResult['FILTER'] = $this->prepareFilter();
		$this->arResult['GRID'] = $this->prepareGrid();
		$this->arResult['TITLE'] = Loc::getMessage('DOCGEN_DOCUMENTS_TITLE');

		global $APPLICATION;
		$APPLICATION->SetTitle($this->arResult['TITLE']);

		$this->includeComponentTemplate();
	}

	protected function showError($error)
	{
		ShowError($error);
	}

	/**
	 * @return array
	 */
	protected function prepareGrid()
	{
		$grid = [];
		$grid['GRID_ID'] = $this->gridId;
		$grid['ROWS'] = [];
		$grid['COLUMNS'] = [
			[
				'id' => 'ID',
				'name' => 'ID',
				'default' => false,
				'sort' => 'ID',
			],
			[
				'id' => 'TITLE',
				'name' => Loc::getMessage('DOCGEN_DOCUMENTS_NAME_TITLE'),
				'default' => true,
				'sort' => 'TITLE',
			],
			[
				'id' => 'NUMBER',
				'name' => Loc::getMessage('DOCGEN_DOCUMENTS_NUMBER_TITLE'),
				'default' => false,
				'sort' => 'NUMBER',
			],
			[
				'id' => 'CREATE_TIME',
				'name' => Loc::getMessage('DOCGEN_DOCUMENTS_CREATE_TIME_TITLE'),
				'default' => true,
				'sort' => 'CREATE_TIME',
			],
			[
				'id' => 'UPDATE_TIME',
				'name' => Loc::getMessage('DOCGEN_DOCUMENTS_UPDATE_TIME_TITLE'),
				'default' => false,
				'sort' => 'UPDATE_TIME',
			],
			[
				'id' => 'TEMPLATE',
				'name' => Loc::getMessage('DOCGEN_DOCUMENTS_TEMPLATE_TITLE'),
				'default' => true,
				'sort' => 'TEMPLATE_ID',
			],
			[
				'id' => 'CREATED_BY',
				'name' => Loc::getMessage('DOCGEN_DOCUMENTS_CREATED_BY_TITLE'),
				'default' => false,
				'sort' => 'CREATED_BY',
			],
			[
				'id' => 'UPDATED_BY',
				'name' => Loc::getMessage('DOCGEN_DOCUMENTS_UPDATED_BY_TITLE'),
				'default' => false,
				'sort' => 'UPDATED_BY',
			],
		];

		$gridOptions = new Bitrix\Main\Grid\Options($this->gridId);
		$navParams = $gridOptions->getNavParams(['nPageSize' => 10]);
		$pageSize = (int)$navParams['nPageSize'];
		$pageNavigation = new \Bitrix\Main\UI\PageNavigation($this->navParamName);
		$pageNavigation->allowAllRecords(false)->setPageSize($pageSize)->initFromUri();
		$gridSort = $gridOptions->GetSorting(['sort' => $this->defaultGridSort])['sort'];

		$documentList = DocumentTable::getList([
			'order' => $gridSort,
			'filter' => $this->getListFilter(),
			'offset' => $pageNavigation->getOffset(),
			'limit' => $pageNavigation->getLimit(),
			'count_total' => true,
		]);
		$documents = $documentList->fetchAll();
		$templates = $this->getTemplates();
		$users = $this->getUsers($documents);

		$onDocumentClick = '';
		$viewUrl = $this->arParams['viewUrl'];
		if($viewUrl)
		{
			$viewUrl = new \Bitrix\Main\Web\Uri($viewUrl);
		}
		$templateViewUrl = \CComponentEngine::makeComponentPath('bitrix:documentgenerator.templates');
		$templateViewUrl = getLocalPath('components'.$templateViewUrl.'/slider.php');
		$templateViewUrl = (new Uri($templateViewUrl))->addParams(['UPLOAD' => 'Y']);
		if(!empty($documents))
		{
			foreach($documents as $document)
			{
				$actions = [];
				$template = Loc::getMessage('DOCGEN_DOCUMENTS_DELETED_TEMPLATE');
				if(isset($templates[$document['TEMPLATE_ID']]))
				{
					if(\Bitrix\DocumentGenerator\Driver::getInstance()->getUserPermissions()->canModifyTemplate($document['TEMPLATE_ID']))
					{
						$template = '<a href="javascript:void(0);" onclick="BX.DocumentGenerator.DocumentList.viewTemplate(\''.$templateViewUrl->addParams(['ID' => $document['TEMPLATE_ID']])->getLocator().'\')">'.htmlspecialcharsbx($templates[$document['TEMPLATE_ID']]['NAME']).'</a>';
					}
					else
					{
						$template = htmlspecialcharsbx($templates[$document['TEMPLATE_ID']]['NAME']);
					}
				}
				if($viewUrl)
				{
					$viewUrl->addParams(['documentId' => $document['ID']])->deleteParams(['analyticsLabel']);
					$onDocumentClick =
						"BX.DocumentGenerator.Document.onBeforeCreate('{$viewUrl->getLocator()}', {sliderWidth: 1060}, '"
						. htmlspecialcharsbx(CUtil::JSEscape($this->arParams['loaderPath']))
						. "', '{$this->arParams['module']}')"
					;
					$actions[] = [
						'ICONCLASS' => 'edit',
						'TEXT' => Loc::getMessage('DOCGEN_DOCUMENTS_VIEW_ACTION'),
						'ONCLICK' => $onDocumentClick,
						'DEFAULT' => true,
					];
				}
				$actions[] = [
					'ICONCLASS' => 'delete',
					'TEXT' => Loc::getMessage('DOCGEN_DOCUMENTS_DELETE_ACTION'),
					'ONCLICK' => 'BX.DocumentGenerator.DocumentList.delete(\''.$document['ID'].'\')',
				];
				$documentTitle = htmlspecialcharsbx($document['TITLE']);
				if($onDocumentClick)
				{
					$documentTitle = '<a href="javascript:void(0);" onclick="'.$onDocumentClick.'">'.$documentTitle.'</a>';
				}
				$grid['ROWS'][] = [
					'id' => $document['ID'],
					'data' => $document,
					'actions' => $actions,
					'columns' => [
						'ID' => $document['ID'],
						'TITLE' => $documentTitle,
						'NUMBER' => htmlspecialcharsbx($document['NUMBER']),
						'CREATE_TIME' => $document['CREATE_TIME'],
						'TEMPLATE' => $template,
						'UPDATE_TIME' => $document['UPDATE_TIME'],
						'CREATED_BY' =>
							!empty($template['CREATED_BY']) && isset($users[$template['CREATED_BY']])
								? $users[$template['CREATED_BY']]
								: ''
						,
						'UPDATED_BY' =>
							!empty($template['UPDATED_BY']) && isset($users[$template['UPDATED_BY']])
								? $users[$template['UPDATED_BY']]
								: ''
						,
					],
				];
			}
		}

		$fullCount = $documentList->getCount();
		$pageNavigation->setRecordCount($fullCount);
		$grid['TOTAL_ROWS_COUNT'] = $fullCount;
		$grid['NAV_OBJECT'] = $pageNavigation;
		$grid['AJAX_MODE'] = 'Y';
		$grid['ALLOW_ROWS_SORT'] = false;
		$grid['AJAX_OPTION_JUMP'] = "N";
		$grid['AJAX_OPTION_STYLE'] = "N";
		$grid['AJAX_OPTION_HISTORY'] = "N";
		$grid['SHOW_PAGESIZE'] = true;
		$grid['PAGE_SIZES'] = [['NAME' => '10', 'VALUE' => '10'], ['NAME' => '20', 'VALUE' => '20'], ['NAME' => '50', 'VALUE' => '50']];
		$grid['AJAX_ID'] = \CAjax::GetComponentID("bitrix:main.ui.grid", '', '');
		$grid['SHOW_ROW_CHECKBOXES'] = false;
		$grid['SHOW_CHECK_ALL_CHECKBOXES'] = false;
		$grid['SHOW_ACTION_PANEL'] = false;

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
			'DISABLE_SEARCH' => false,
			'ENABLE_LABEL' => true,
			'RESET_TO_DEFAULT_MODE' => false,
			'ENABLE_LIVE_SEARCH' => true,
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
				'id' => 'TITLE',
				'name' => Loc::getMessage('DOCGEN_DOCUMENTS_NAME_TITLE'),
				'default' => true,
			],
			[
				'id' => 'NUMBER',
				'name' => Loc::getMessage('DOCGEN_DOCUMENTS_NUMBER_TITLE'),
				'default' => false,
			],
			[
				"id" => "CREATE_TIME",
				"name" => Loc::getMessage('DOCGEN_DOCUMENTS_CREATE_TIME_TITLE'),
				"default" => true,
				'type' => 'date',
			],
			[
				"id" => "TEMPLATE",
				"name" => Loc::getMessage('DOCGEN_DOCUMENTS_TEMPLATE_TITLE'),
				"default" => true,
				"type" => "list",
				"items" => $this->getTemplates(),
			],
		];
	}

	/**
	 * @return array
	 */
	protected function getTemplates()
	{
		static $templates = false;
		if($templates === false)
		{
			$templates = [];
			$list = \Bitrix\DocumentGenerator\Model\TemplateTable::getListByClassName(get_class($this->provider), \Bitrix\DocumentGenerator\Driver::getInstance()->getUserId(), $this->provider->getSource(), false);
			foreach($list as $template)
			{
				$templates[$template['ID']] = [
					'NAME' => $template['NAME'],
				];
			}
		}

		return $templates;
	}

	/**
	 * @return array
	 */
	protected function getListFilter()
	{
		$providerClassName = mb_strtolower(get_class($this->provider));
		$filter = [
			'=PROVIDER' => $providerClassName,
			'=VALUE' => $this->provider->getSource(),
		];

		$filterOptions = new Bitrix\Main\UI\Filter\Options($this->filterId);
		$requestFilter = $filterOptions->getFilter($this->getDefaultFilterFields());

		if(isset($requestFilter['CREATE_TIME_from']) && $requestFilter['CREATE_TIME_from'])
		{
			$filter['>=CREATE_TIME'] = $requestFilter['CREATE_TIME_from'];
		}
		if(isset($requestFilter['CREATE_TIME_to']) && $requestFilter['CREATE_TIME_to'])
		{
			$filter['<=CREATE_TIME'] = $requestFilter['CREATE_TIME_to'];
		}

		$titleSearch = false;
		if(isset($requestFilter['TITLE']) && !empty($requestFilter['TITLE']))
		{
			$titleSearch = $requestFilter['TITLE'];
		}
		elseif(isset($requestFilter['FIND']) && !empty($requestFilter['FIND']))
		{
			$titleSearch = $requestFilter['FIND'];
		}
		if($titleSearch)
		{
			$filter['TITLE'] = '%' . $titleSearch . '%';
		}
		if(isset($requestFilter['NUMBER']) && !empty($requestFilter['NUMBER']))
		{
			$filter['NUMBER'] = '%' . $requestFilter['NUMBER'] . '%';
		}
		if(isset($requestFilter['TEMPLATE']) && $requestFilter['TEMPLATE'] > 0)
		{
			$filter['TEMPLATE_ID'] = $requestFilter['TEMPLATE'];
		}

		return $filter;
	}

	/**
	 * @param array $documents
	 * @return array
	 */
	protected function getUsers(array $documents)
	{
		$users = [];
		$userIds = [];
		foreach($documents as $document)
		{
			$userIds[] = $document['CREATED_BY'];
			$userIds[] = $document['UPDATED_BY'];
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
}
