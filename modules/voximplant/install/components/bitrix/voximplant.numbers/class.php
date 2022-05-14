<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Voximplant\Security\Permissions;

Loc::loadMessages(__FILE__);


class VoximplantNumbersComponent extends \CBitrixComponent
{
	protected $gridId = "vox_numbers_grid";
	protected $filterId = "vox_numbers_filter";
	protected $userPermissions;

	public function __construct($component = null)
	{
		\Bitrix\Main\Loader::includeModule("voximplant");

		parent::__construct($component);

		$this->gridOptions = new CGridOptions($this->gridId);
		$this->userPermissions = Permissions::createWithCurrentUser();
	}

	public function executeComponent()
	{
		if (!CModule::IncludeModule('voximplant'))
			return false;

		if(!$this->userPermissions->canPerform(Permissions::ENTITY_USER, Permissions::ACTION_MODIFY))
		{
			ShowError(GetMessage("COMP_VI_ACCESS_DENIED"));
			return false;
		}

		$this->arResult["GRID_ID"] = $this->gridId;
		$this->arResult["FILTER_ID"] = $this->filterId;
		$this->arResult["FILTER"] = $this->getFilterDefinition();
		$this->arResult["USERS"] = [];
		$this->arResult["IS_PHONE_ALLOWED"] = !\Bitrix\Voximplant\Limits::isRestOnly();

		$gridOptions = new CGridOptions($this->gridId);
		$sorting = $gridOptions->GetSorting(array("sort" => array("ID" => "DESC")));
		$navParams = $gridOptions->GetNavParams();
		$pageSize = $navParams['nPageSize'];

		$nav = new \Bitrix\Main\UI\PageNavigation("page");
		$nav->allowAllRecords(false)
			->setPageSize($pageSize)
			->initFromUri();

		$cursor = \Bitrix\Main\UserTable::getList([
			'select' => [
				'ID',
				'LOGIN',
				'NAME',
				'SECOND_NAME',
				'LAST_NAME',
				'PERSONAL_PHOTO',
				'WORK_POSITION',
				'UF_PHONE_INNER',
				'UF_VI_BACKPHONE',
				'UF_VI_PHONE'
			],
			'filter' => $this->getFilter(),
			'order' => ['ID' => 'asc'],
			'limit' => $nav->getLimit(),
			'offset' => $nav->getOffset(),
			'count_total' => true,
		]);

		while ($user = $cursor->fetch())
		{
			$this->arResult['USERS'][$user['ID']] = $this->prepareUserData($user);
		}

		$nav->setRecordCount($cursor->getCount());
		$this->arResult["ROWS_COUNT"] = $cursor->getCount();
		$this->arResult['NAV_OBJECT'] = $nav;
		$this->arResult["SORT"] = $sorting["sort"];

		$this->arResult['IFRAME'] = $_REQUEST['IFRAME'] === 'Y';

		if (!(isset($this->arParams['TEMPLATE_HIDE']) && $this->arParams['TEMPLATE_HIDE'] == 'Y'))
			$this->IncludeComponentTemplate();

		return $this->arResult;
	}

	public function prepareUserData($user)
	{
		$user['DETAIL_URL'] = COption::getOptionString('intranet', 'search_user_url', '/user/#ID#/');
		$user['DETAIL_URL'] = str_replace(array('#ID#', '#USER_ID#'), array($user['ID'], $user['ID']), $user['DETAIL_URL']);

		$user['PHOTO_THUMB'] = '<img src="/bitrix/components/bitrix/main.user.link/templates/.default/images/nopic_30x30.gif" border="0" alt="" width="32" height="32">';
		if (intval($user['PERSONAL_PHOTO']) > 0)
		{
			$imageFile = CFile::getFileArray($user['PERSONAL_PHOTO']);
			if ($imageFile !== false)
			{
				$arFileTmp = CFile::resizeImageGet(
					$imageFile, array('width' => 42, 'height' => 42),
					BX_RESIZE_IMAGE_EXACT, false
				);
				$user['PHOTO_THUMB'] = CFile::showImage($arFileTmp['src'], 32, 32);
			}
		}
		return $user;
	}

	public function getFilterDefinition()
	{
		$result = [
			"USER_NAME" => [
				"id" => "USER_NAME",
				"name" => Loc::getMessage("VI_NUMBERS_FILTER_NAME"),
				"type" => "string",
				"default" => true
			],
			"EXTENSION" => [
				"id" => "EXTENSION",
				"name" => Loc::getMessage("VI_NUMBERS_FILTER_EXTENSION"),
				"default" => true,
				"type" => "string",
			],
		];

		return $result;
	}

	public function getFilter()
	{
		$filterOptions = new \Bitrix\Main\UI\Filter\Options($this->filterId);
		$filter = $filterOptions->getFilter($this->getFilterDefinition());

		$allowedUserIds = \Bitrix\Voximplant\Security\Helper::getAllowedUserIds(
			\Bitrix\Voximplant\Security\Helper::getCurrentUserId(),
			$this->userPermissions->getPermission(Permissions::ENTITY_USER, Permissions::ACTION_MODIFY)
		);

		$possibleNumbers = [];
		$searchString = "";
		if($filter['FIND'] != '')
		{
			$tokens = explode(' ', $filter['FIND']);

			foreach ($tokens as $index => $token)
			{
				if(preg_match('/^\d+$/', $token))
				{
					$possibleNumbers[] = $token . '%';
					unset($tokens[$index]);
				}
			}

			$searchString = implode(" ", $tokens);
		}

		if($searchString != '')
		{
			$result = \Bitrix\Main\UserUtils::getUserSearchFilter([
				'FIND' => $searchString
			]);
		}
		else
		{
			$result = [];
		}

		if(isset($filter['USER_NAME']) && $filter['USER_NAME'] != '')
		{
			$searchTokens = explode(' ', $filter['USER_NAME']);
			foreach ($searchTokens as $searchToken)
			{
				$preparedToken = '%' . trim($searchToken) . '%';
				$result[] = array(
					'LOGIC' => 'OR',
					'%=NAME' => $preparedToken,
					'%=LAST_NAME' => $preparedToken,
				);
			}
		}

		if(isset($filter['EXTENSION']) && $filter['EXTENSION'] != '')
		{
			$possibleNumbers[] = trim($filter['EXTENSION']) .'%';
		}

		if(!empty($possibleNumbers))
		{
			$result['%=UF_PHONE_INNER'] = $possibleNumbers;
		}

		$result['=ACTIVE'] = 'Y';
		$result['!=UF_DEPARTMENT'] = false;
		$result['=IS_REAL_USER'] = 'Y';


		if(is_array($allowedUserIds))
		{
			$result['ID'] = $allowedUserIds;
		}

		return $result;
	}


}