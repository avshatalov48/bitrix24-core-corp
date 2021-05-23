<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Crm\Activity\CustomType;

Loc::loadMessages(__FILE__);

class CCrmActivityCustomTypeComponent extends CBitrixComponent
{
	//region Fields
	/** @var int */
	protected $userID = 0;
	/** @var \CCrmPerms|null */
	protected $userPermissions = null;
	/** @var bool */
	protected $hasEditPermission = false;
	/** @var bool */
	protected $hasDeletePermission = false;
	/** @var \CAllMain|null */
	protected $application = null;
	/** @var \CSite|null */
	protected $site = null;
	/** @var string */
	protected $formID = '';
	/** @var string */
	protected $tabID = '';
	/** @var string */
	protected $gridID = '';
	/** @var  array|null */
	protected $actionData = null;
	/** @var array|null */
	protected $headers = null;
	/** @var array|null */
	protected $sort;
	/** @var array|null */
	protected $sortVars;
	/** @var array|null */
	protected $items = null;
	/** @var int */
	protected $itemCount = 0;
	/** @var int */
	protected $totalCount = 0;
	/** @var Bitrix\Main\UI\PageNavigation|null */
	protected $navigation = null;
	/** @var string  */
	protected $navigationID = 'nav-activity-type';
	/** @var string */
	protected $ajaxMode = '';
	/** @var array|null  */
	protected $errors = null;
	//endregion

	public function __construct($component = null)
	{
		/** @var \CAllMain $APPLICATION */
		global $APPLICATION;
		$this->application = $APPLICATION;

		parent::__construct($component);
	}
	public function executeComponent()
	{
		if(!\Bitrix\Main\Loader::includeModule('crm'))
		{
			ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
			return;
		}

		$this->initialize();

		if(!\CCrmAuthorizationHelper::CheckConfigurationUpdatePermission($this->userPermissions))
		{
			ShowError(GetMessage('CRM_PERMISSION_DENIED'));
			return;
		}
		$this->hasEditPermission = $this->hasDeletePermission = true;

		$this->prepareActions();

		if($this->actionData['ACTIVE'])
		{
			$this->processAction();

			if (!empty($this->errors))
			{
				$this->showErrors();
			}
			elseif (!$this->actionData['AJAX_CALL'])
			{
				LocalRedirect($this->application->getCurPageParam('', array('action', 'sessid')));
			}
		}
		$this->prepareGridData();
		$this->prepareResult();
		$this->includeComponentTemplate();
	}
	protected function initialize()
	{
		$this->userID = \CCrmSecurityHelper::GetCurrentUserID();
		$this->userPermissions = CCrmPerms::GetCurrentUserPermissions();
		$this->site = new \CSite();

		$this->arParams['PATH_TO_ACTIVITY_CUSTOM_TYPE_LIST'] = CrmCheckPath('PATH_TO_ACTIVITY_CUSTOM_TYPE_LIST', $this->arParams['PATH_TO_ACTIVITY_CUSTOM_TYPE_LIST'], $this->application->GetCurPage());
		$this->arParams['PATH_TO_USER_FIELD_EDIT'] = CrmCheckPath('PATH_TO_USER_FIELD_EDIT', $this->arParams['PATH_TO_USER_FIELD_EDIT'], SITE_DIR.'crm/configs/fields/#ENTITY_ID#/');

		$this->arParams['NAME_TEMPLATE'] = empty($this->arParams['NAME_TEMPLATE']) ? $this->site->GetNameFormat(false) : str_replace(array('#NOBR#','#/NOBR#'), array('',''), $this->arParams['NAME_TEMPLATE']);

		$this->ajaxMode = isset($this->arParams['AJAX_MODE']) ? $this->arParams['AJAX_MODE'] : 'Y';
		$this->arParams['AJAX_OPTION_JUMP'] = isset($this->arParams['AJAX_OPTION_JUMP']) ? $this->arParams['AJAX_OPTION_JUMP'] : 'N';
		$this->arParams['AJAX_OPTION_HISTORY'] = isset($this->arParams['AJAX_OPTION_HISTORY']) ? $this->arParams['AJAX_OPTION_HISTORY'] : 'N';

		$this->itemCount = isset($this->arParams['ITEM_COUNT']) ? (int)$this->arParams['ITEM_COUNT'] : 10;
		$this->formID = isset($this->arParams['FORM_ID']) ? $this->arParams['FORM_ID'] : '';
		$this->tabID = isset($this->arParams['TAB_ID']) ? $this->arParams['TAB_ID'] : '';
		$this->gridID = 'CRM_ACTIVITY_CUSTOM_TYPE_LIST';

		$this->errors = array();
	}
	protected function prepareActions()
	{
		$this->actionData = array('ACTIVE' => false);

		if(!check_bitrix_sessid())
		{
			return;
		}

		$postAction = "action_button_{$this->gridID}";
		$getAction = "action_{$this->gridID}";
		$allRows = "action_all_rows_{$this->gridID}";

		if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST[$postAction]))
		{
			$this->actionData['METHOD'] = 'POST';

			$this->actionData['NAME'] = $_POST[$postAction];
			unset($_POST[$postAction], $_REQUEST[$postAction]);

			$this->actionData['ALL_ROWS'] = false;
			if(isset($_POST[$allRows]))
			{
				$this->actionData['ALL_ROWS'] = $_POST[$allRows] == 'Y';
				unset($_POST[$allRows], $_REQUEST[$allRows]);
			}

			if(isset($_POST['ID']))
			{
				$this->actionData['ID'] = $_POST['ID'];
				unset($_POST['ID'], $_REQUEST['ID']);
			}

			$this->actionData['AJAX_CALL'] = isset($_POST['AJAX_CALL']) ? ($_POST['AJAX_CALL'] === 'Y') : false;
			$this->actionData['ACTIVE'] = true;
		}
		elseif($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET[$getAction]))
		{
			$this->actionData['METHOD'] = 'GET';

			$this->actionData['NAME'] = $_GET[$getAction];
			unset($_GET[$getAction], $_REQUEST[$getAction]);

			$this->actionData['ALL_ROWS'] = false;
			if(isset($_GET['ID']))
			{
				$this->actionData['ID'] = $_GET['ID'];
				unset($_GET['ID'], $_REQUEST['ID']);
			}

			$this->actionData['AJAX_CALL'] = isset($_GET['AJAX_CALL']) ? ($_GET['AJAX_CALL'] === 'Y') : false;
			$this->actionData['ACTIVE'] = true;
		}
	}
	protected function prepareGridData()
	{
		$this->headers = array(
			array('id' => 'ID', 'name' => GetMessage('CRM_COLUMN_ACT_CUST_TYPE_ID'), 'sort' => 'ID', 'default' => false, 'editable' => false),
			array('id' => 'NAME', 'name' => GetMessage('CRM_COLUMN_ACT_CUST_TYPE_NAME'), 'sort' => 'NAME', 'default' => true, 'editable' => true, 'params' => array('size' => 60)),
			array('id' => 'SORT', 'name' => GetMessage('CRM_COLUMN_ACT_CUST_TYPE_SORT'), 'sort' => 'SORT', 'default' => true, 'editable' => true),
			array('id' => 'CREATED_DATE', 'name' => GetMessage('CRM_COLUMN_ACT_CUST_TYPE_CREATED_DATE'), 'sort' => 'CREATED_DATE', 'default' => false, 'editable' => false)
		);

		$gridOptions = new \CCrmGridOptions($this->gridID);
		$gridSorting = $gridOptions->GetSorting(
			array(
				'sort' => array('SORT' => 'asc', 'ID' => 'asc'),
				'vars' => array('by' => 'by', 'order' => 'order')
			)
		);
		$this->sort = $gridSorting['sort'];
		$this->sortVars = $gridSorting['vars'];

		if(isset($this->sort['CREATED_DATE']))
		{
			$this->sort['ID'] = $this->sort['CREATED_DATE'];
			unset($this->sort['CREATED_DATE']);
		}

		$this->navigation = new PageNavigation($this->navigationID);
		$this->navigation->allowAllRecords(true)
			->setPageSize($this->itemCount)
			->initFromUri();

		$dbResult = CustomType::getList(
			array(
				'order' => $this->sort,
				'limit' => $this->navigation->getLimit(),
				'offset' => $this->navigation->getOffset(),
				'count_total' => true
			)
		);

		$this->totalCount = $dbResult->getCount();
		$this->navigation->setRecordCount($this->totalCount);

		$urlParams = array(
			"action_{$this->gridID}" => 'delete',
			'sessid' => bitrix_sessid()
		);
		$currentPage = $this->navigation->getCurrentPage();
		if($currentPage > 1)
		{
			$urlParams[$this->navigationID] = "page-{$currentPage}";
		}

		while($fields = $dbResult->fetch())
		{
			$ID = (int)$fields['ID'];
			$fields['CAN_EDIT'] = $fields['CAN_DELETE'] = true;

			$fields['PATH_TO_DELETE'] =
				\CHTTP::urlAddParams(
					\CComponentEngine::MakePathFromTemplate($this->arParams['PATH_TO_ACTIVITY_CUSTOM_TYPE_LIST']),
					array_merge($urlParams, array('ID' => $ID))
				);

			$fields['PATH_TO_USER_FIELD_EDIT'] =
				\CComponentEngine::MakePathFromTemplate(
					$this->arParams['PATH_TO_USER_FIELD_EDIT'],
					array('ENTITY_ID' => CustomType::prepareUserFieldEntityID($ID))
				);

			//HACK for interface grid inline editor
			$fields['~NAME'] = $fields['NAME'];
			$fields['NAME'] = htmlspecialcharsbx($fields['NAME']);
			$fields['~SORT'] = $fields['SORT'];

			$this->items[] = $fields;
		}
	}
	protected function prepareResult()
	{
		$this->arResult['GRID_ID'] = $this->gridID;
		$this->arResult['FORM_ID'] = $this->formID;
		$this->arResult['TAB_ID'] = $this->tabID;
		$this->arResult['HEADERS'] = $this->headers;
		$this->arResult['SORT'] = $this->sort;
		$this->arResult['SORT_VARS'] = $this->sortVars;
		$this->arResult['ITEMS'] = $this->items;
		$this->arResult['ROWS_COUNT'] = $this->totalCount;
		$this->arResult['NAV_OBJECT'] = $this->navigation;

		$this->arResult['CAN_EDIT'] = $this->hasEditPermission;
		$this->arResult['CAN_DELETE'] = $this->hasDeletePermission;

		$this->arResult['AJAX_MODE'] = $this->ajaxMode;
		$this->arResult['AJAX_OPTION_JUMP'] = $this->arParams['AJAX_OPTION_JUMP'];
		$this->arResult['AJAX_OPTION_HISTORY'] = $this->arParams['AJAX_OPTION_HISTORY'];
	}
	protected function processAction()
	{
		if(!$this->actionData['ACTIVE'])
		{
			return;
		}

		if($this->actionData['METHOD'] === 'POST' && $this->actionData['NAME'] === 'delete')
		{
			if(!$this->hasDeletePermission)
			{
				$this->errors[] = GetMessage('CRM_PERMISSION_DENIED');
			}
			else
			{
				$IDs = array();
				if(isset($this->actionData['ID']) && is_array($this->actionData['ID']))
				{
					$IDs = $this->actionData['ID'];
				}

				$allRows = $this->actionData['ALL_ROWS'];
				if(!empty($IDs) || $allRows)
				{
					foreach($IDs as $ID)
					{
						try
						{
							CustomType::delete($ID);
						}
						catch(Bitrix\Crm\Entry\DeleteException $e)
						{
							$e->getLocalizedMessage();
						}
					}
				}
			}
		}
		elseif($this->actionData['METHOD'] === 'GET' && $this->actionData['NAME'] === 'delete')
		{
			if(!$this->hasDeletePermission)
			{
				$this->errors[] = GetMessage('CRM_PERMISSION_DENIED');
			}
			else
			{
				$IDs = array();
				if(isset($this->actionData['ID']))
				{
					if(isset($this->actionData['ID']) && is_array($this->actionData['ID']))
					{
						$IDs = $this->actionData['ID'];
					}
					else
					{
						$IDs[] = $this->actionData['ID'];
					}
				}

				if (!empty($IDs))
				{
					foreach($IDs as $ID)
					{
						try
						{
							CustomType::delete($ID);
						}
						catch(Bitrix\Crm\Entry\DeleteException $e)
						{
							$e->getLocalizedMessage();
						}
					}
				}
			}
		}
	}
	protected function showErrors()
	{
		foreach ($this->errors as $error)
		{
			ShowError($error);
		}
	}
}