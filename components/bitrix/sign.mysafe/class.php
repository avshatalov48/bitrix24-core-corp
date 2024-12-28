<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Context;
use Bitrix\Main\Context\Culture;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Grid\Column\Color;
use Bitrix\Main\Grid\Options as GridOptions;
use Bitrix\Main\Grid\Types;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Main\Text\Encoding;
use Bitrix\Main\UI\Filter\Options as FilterOptions;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Main\UserTable;
use Bitrix\Main\Web\Uri;
use Bitrix\Sign\Access\AccessController;
use Bitrix\Sign\Access\ActionDictionary;
use Bitrix\Sign\Access\Permission\SignPermissionDictionary;
use Bitrix\Sign\Access\Service\RolePermissionService;
use Bitrix\Sign\Document;
use Bitrix\Sign\Document\Entity\Smart;
use Bitrix\Sign\Document\Status;
use Bitrix\Sign\Integration\CRM\Entity;
use Bitrix\Sign\Type\DocumentStatus;
use Bitrix\Sign\Service\Container;

Loc::loadMessages(__FILE__);

CBitrixComponent::includeComponentClass('bitrix:sign.base');

class SignMySafeComponent extends SignBaseComponent
{
	protected static array $requiredParams = [
		'NEED_SET_TITLE',
		'TITLE',
		'PAGE_SIZE',
		'NAVIGATION_KEY',
		'GRID_ID',
		'FILTER_ID',
		'DOCUMENT_RESULT_FILE_DOWNLOAD_URL_TEMPLATE',
		'DOCUMENT_RESULT_FILE_DOWNLOAD_URL_TEMPLATE_HASH_KEY',
		'STUB_TITLE',
		'USE_DEFAULT_STUB',
		'USER_NAME_TEMPLATE',
		'NAVIGATION_KEY',
		'URL_TO_USER_PROFILE_TEMPLATE',
		'URL_TO_USER_PROFILE_TEMPLATE_USER_KEY',
	];

	private const DEFAULT_NAV_KEY = "sign-mysafe-nav";
	private const DEFAULT_PAGE_SIZE = 10;
	private const DEFAULT_NEED_SET_TITLE_VALUE = true;
	private const DEFAULT_TITLE_LOC_CODE = 'SIGN_MYSAFE_TITLE';
	private const DEFAULT_GRID_ID = "SIGN_MYSAFE_GRID";
	private const DEFAULT_FILTER_ID = "SIGN_MYSAFE_FILTER";
	private const DEFAULT_RESULT_FILE_DOWNLOAD_URL_TEMPLATE =
		'/bitrix/services/main/ajax.php?action=sign.document.getFileForSrc&documentHash=#MEMBER_HASH#'
	;
	private const DEFAULT_RESULT_FILE_DOWNLOAD_URL_TEMPLATE_HASH_KEY = 'MEMBER_HASH';
	private const DEFAULT_STUB_TITLE_LOC_CODE = 'SIGN_MYSAFE_STUB_TITLE';

	private const DOCUMENT_CREATOR_ICON_SIZE = ['width' => 42, 'height' => 42];
	private const PATH_TO_USER_PROFILE_TEMPLATE = '/company/personal/user/#USER_ID#/';
	private const URL_TO_USER_PROFILE_TEMPLATE_USER_KEY = 'USER_ID';

	private ErrorCollection $errors;
	private array $usersDataForGridById = [];

	public function __construct($component = null)
	{
		parent::__construct($component);
		$this->errors = new ErrorCollection();
	}

	protected function exec(): void
	{
		$this->prepareParams();
		if (!$this->errors->isEmpty())
		{
			$this->showErrors();
			return;
		}
		$this->prepareResult();
	}

	private function prepareParams(): void
	{
		$this->prepareTitle();
		$this->prepareGridParams();
		$this->prepareNavigationParams();
	}

	private function prepareTitle(): void
	{
		$this->arParams['NEED_SET_TITLE'] = (bool)($this->arParams['NEED_SET_TITLE'] ?? self::DEFAULT_NEED_SET_TITLE_VALUE);

		if ($this->arParams['NEED_SET_TITLE'])
		{
			$this->arParams['TITLE'] ??= Loc::getMessage(self::DEFAULT_TITLE_LOC_CODE);
		}
	}

	private function prepareNavigationParams(): void
	{
		$this->arParams["PAGE_SIZE"] =
			(int)$this->arParams["PAGE_SIZE"] > 0
				? (int)$this->arParams["PAGE_SIZE"]
				: self::DEFAULT_PAGE_SIZE
		;
		$this->arParams["NAVIGATION_KEY"] = $this->arParams["NAVIGATION_KEY"] ?? self::DEFAULT_NAV_KEY;
	}

	private function prepareGridParams(): void
	{
		$this->arParams["GRID_ID"] ??= self::DEFAULT_GRID_ID;
		$this->arParams["FILTER_ID"] ??= self::DEFAULT_FILTER_ID;
		$this->arParams["COLUMNS"] ??= $this->getGridColumns();

		$this->arParams['DOCUMENT_RESULT_FILE_DOWNLOAD_URL_TEMPLATE'] ??= self::DEFAULT_RESULT_FILE_DOWNLOAD_URL_TEMPLATE;
		$this->arParams['DOCUMENT_RESULT_FILE_DOWNLOAD_URL_TEMPLATE_HASH_KEY'] ??=
			self::DEFAULT_RESULT_FILE_DOWNLOAD_URL_TEMPLATE_HASH_KEY;
		$this->arParams['STUB'] = [
			'title' => $this->arParams['STUB_TITLE'] ?? Loc::getMessage(self::DEFAULT_STUB_TITLE_LOC_CODE),
		];

		$this->arParams['USER_NAME_TEMPLATE'] = empty($this->arParams['USER_NAME_TEMPLATE'])
			? CSite::GetNameFormat(false)
			: str_replace(["#NOBR#", "#/NOBR#"], ["", ""], $this->arParams["USER_NAME_TEMPLATE"])
		;
	}

	private function prepareNavigation(): PageNavigation
	{
		$pageNavigation = new PageNavigation($this->arParams["NAVIGATION_KEY"]);
		$pageNavigation
			->setPageSize($this->arParams["PAGE_SIZE"])
			->allowAllRecords(false)
			->initFromUri()
		;
		$this->arResult['NAVIGATION_OBJECT'] = $pageNavigation;

		return $pageNavigation;
	}

	private function getNavigation(): PageNavigation
	{
		if (!isset($this->arResult["NAVIGATION_OBJECT"]))
		{
			return $this->prepareNavigation();
		}

		return $this->arResult['NAVIGATION_OBJECT'];
	}

	private function prepareDocuments(): array
	{
		try
		{
			$documentsData = $this->getDocumentsData();
		}
		catch (ObjectPropertyException $e)
		{
			return [];
		}

		$this->arResult["DOCUMENTS"] = $this->getDocumentsDataForGrid($documentsData['DOCUMENTS_DATA']);
		$this->arResult["TOTAL_COUNT"] = $documentsData['TOTAL_COUNT'];

		return [
			'DOCUMENTS' => $documentsData['DOCUMENTS_DATA'],
			'TOTAL_COUNT' => $documentsData['TOTAL_COUNT'],
		];
	}

	private function prepareFilterData(): void
	{
		$this->arResult['FILTER'] = $this->getFilters();
		$this->arResult['FILTER_PRESETS'] = $this->getFilterPresets();
	}

	private function getDocumentsDataForGrid(array $documentsData): array
	{
		$currentCulture = Context::getCurrent()->getCulture();
		foreach ($documentsData as &$documentData)
		{
			$document = Document::createByRow($documentData);

			$documentData['TITLE_INFO'] = $this->getTitleInfo($document);

			try
			{
				$documentData['DOCUMENT_CREATOR_INFO'] = $this->getUsersInfo((int)$documentData['CREATED_BY_ID']);
			}
			catch (ObjectPropertyException|ArgumentException|SystemException $e)
			{
			}

			$documentData['SIGN_WITH_INFO'] = $this->getSignWithInfo($document);
			$documentData['RESULT_FILE_INFO'] = $this->getResultFileInfo($document);
			$documentData['DATE_SIGN_INFO'] = $this->getDateSignWithInfo($document, $currentCulture);
			$documentData['DATE_CREATE_INFO'] = $this->getDateCreateInfo($document, $currentCulture);
		}

		return $documentsData;
	}

	private function getDateSignWithInfo(Document $document, Culture $culture): ?array
	{
		$signDateTime = $document->getDateSign();
		if ($signDateTime === null)
		{
			return null;
		}

		$signDateTime = clone $signDateTime;

		$signDateTime->setDefaultTimeZone();
		$signDateTimeZone = $signDateTime->getTimezone();

		$dateFormat = $this->getDefaultDateFormat($culture);

		return $this->getTextWithDetailInfoFormat(
			FormatDate($dateFormat, $signDateTime->getTimestamp()),
			$this->getDateTimeWithTimezoneOffsetRepresentation($signDateTime, $signDateTimeZone)
		);
	}

	private function getDefaultDateFormat(Culture $culture): string
	{
		return $culture->getMediumDateFormat();
	}

	private function getSignWithInfo(Document $document): ?array
	{
		$firstThirdPartyMember = $document->getThirdPartyMembers()[0] ?? null;

		if ($firstThirdPartyMember === null)
		{
			$member = Container::instance()->getMemberRepository()->listByDocumentIdWithParty(
				documentId: $document->getId(),
				party: 2
			)->getFirst();

			if ($member?->entityId !== null)
			{
				return [
					'TEXT' => $this->getContactName($member->entityId),
					'LINK' => $this->getContactDetailPageUri($member->entityId)?->getUri(),
				];
			}

			return null;
		}

		$contactUri = $this->getContactDetailPageUri(
			$firstThirdPartyMember->getContactId()
		);

		return [
			'TEXT' => $firstThirdPartyMember->getContactName(),
			'LINK' => $contactUri ? $contactUri->getUri() : null,
		];
	}

	private function getTitleInfo(Document $document): array
	{
		return [
			'TEXT' => $document->getTitle(),
			'DOCUMENT_LINK' => Entity::getDetailPageUri(
				Smart::getEntityTypeId(),
				$document->getEntityId()
			),
		];
	}

	/**
	 * @param int $userId
	 * @return array
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	private function getUsersInfo(int $userId): array
	{
		if (isset($this->usersDataForGridById[$userId]))
		{
			return $this->usersDataForGridById[$userId];
		}

		$userTableQueryResult = UserTable::getRowById($userId);
		$userFullName = CUser::FormatName(
			$this->arParams['USER_NAME_TEMPLATE'],
			[
				'LOGIN' => $userTableQueryResult['LOGIN'],
				'NAME' => $userTableQueryResult['NAME'],
				'LAST_NAME' => $userTableQueryResult['LAST_NAME'],
				'SECOND_NAME' => $userTableQueryResult['SECOND_NAME'],
			],
			true,
			false
		);

		$userIconFileTmp = CFile::ResizeImageGet(
			$userTableQueryResult['PERSONAL_PHOTO'],
			self::DOCUMENT_CREATOR_ICON_SIZE,
			BX_RESIZE_IMAGE_EXACT,
			false,
			false,
			true
		);
		$userIcon = ($userIconFileTmp && isset($userIconFileTmp['src'])) ? $userIconFileTmp['src'] : null;

		$userPageUrlLink = CComponentEngine::makePathFromTemplate(
			self::PATH_TO_USER_PROFILE_TEMPLATE,
			[self::URL_TO_USER_PROFILE_TEMPLATE_USER_KEY => $userId],
		);

		$this->usersDataForGridById[$userId] = [
			'ID' => $userId,
			'FULL_NAME' => $userFullName,
			'ICON' => $userIcon,
			'LINK' => $userPageUrlLink,
		];

		return $this->usersDataForGridById[$userId];
	}

	private function getSelectedDataFields(): array
	{
		return ['*'];
	}

	private function getDataFilters(): array
	{
		$filterOptions = $this->getFilterOptions();
		$requestFilter = $this->getRequestFilters($filterOptions);
		$searchString = Encoding::convertEncodingToCurrent(trim($filterOptions->getSearchString()));

		$result = [];

		if (!empty($searchString))
		{
			$result['TITLE'] = "%$searchString%";
		}
		if (isset($requestFilter['DATE_SIGN_from']) && $requestFilter['DATE_SIGN_from'])
		{
			$result['>=DATE_SIGN'] = $requestFilter['DATE_SIGN_from'];
		}
		if (isset($requestFilter['DATE_SIGN_to']) && $requestFilter['DATE_SIGN_to'])
		{
			$result['<=DATE_SIGN'] = $requestFilter['DATE_SIGN_to'];
		}
		if (isset($requestFilter['DATE_CREATE_from']) && $requestFilter['DATE_CREATE_from'])
		{
			$result['>=DATE_CREATE'] = $requestFilter['DATE_CREATE_from'];
		}
		if (isset($requestFilter['DATE_CREATE_to']) && $requestFilter['DATE_CREATE_to'])
		{
			$result['<=DATE_CREATE'] = $requestFilter['DATE_CREATE_to'];
		}

		$permission = (new RolePermissionService)->getValueForPermission(
			$this->accessController->getUser()->getRoles(),
			SignPermissionDictionary::SIGN_MY_SAFE_DOCUMENTS
		);

		$result['=CREATED_BY_ID'] = null;

		if($permission === CCrmPerms::PERM_ALL || $this->accessController->getUser()->isAdmin())
		{
			unset($result['=CREATED_BY_ID']);
		}
		elseif ($permission === CCrmPerms::PERM_SUBDEPARTMENT)
		{
			unset($result['=CREATED_BY_ID']);
			$result['@CREATED_BY_ID'] = $this->accessController->getUser()->getUserDepartmentMembers(true);
		}
		elseif ($permission === CCrmPerms::PERM_DEPARTMENT)
		{
			unset($result['=CREATED_BY_ID']);
			$result['@CREATED_BY_ID'] = $this->accessController->getUser()->getUserDepartmentMembers();
		}
		elseif ($permission === CCrmPerms::PERM_SELF)
		{
			$result['=CREATED_BY_ID'] = $this->accessController->getUser()->getUserId();
		}

		return array_merge($result, $this->getDocumentWithReadyConditionFilters());
	}

	private function getFilters(): array
	{
		return [
			[
				'id' => 'ID',
				'name' => 'ID',
				'default' => false,
			],
			[
				'id' => 'DATE_SIGN',
				'name' => Loc::getMessage('SIGN_MYSAFE_TITLE_FILTER_DATE_SIGN_NAME'),
				'type' => 'date',
				'default' => true
			],
			[
				'id' => 'DATE_CREATE',
				'name' => Loc::getMessage('SIGN_MYSAFE_TITLE_FILTER_DATE_CREATE_NAME'),
				'type' => 'date',
				'default' => true
			],
		];
	}

	/**
	 * @return array{TEXT: string, DETAIL: string}
	 */
	private function getTextWithDetailInfoFormat(string $text, string $detailInfo): array
	{
		return [
			'TEXT' => $text,
			'DETAIL' => $detailInfo
		];
	}

	private function getFilterPresets(): array
	{
		return [];
	}

	private function getDocumentWithReadyConditionFilters(): array
	{
		return [
			'!==RESULT_FILE_ID' => null,
			[
				'LOGIC' => 'OR',
				'=STATUS' => DocumentStatus::DONE,
				'=PROCESSING_STATUS' => Status::READY
			]
		];
	}

	private function getDataOrder(): array
	{
		$gridSorting = $this->getGridOptions()->getSorting();

		$result = [];
		$sortByList = $this->getGridSortList();
		foreach (($gridSorting["sort"] ?? []) as $sortBy => $sortOrder)
		{
			$sortOrderUpper = mb_strtoupper($sortOrder);
			if (in_array($sortBy, $sortByList, true) && in_array(mb_strtoupper($sortOrderUpper), ['ASC', 'DESC'], true))
			{
				$result[$sortBy] = $sortOrderUpper;
			}
		}

		return $result ?: $this->getGridDefaultSort();
	}

	private function getDataOffset()
	{
		return $this->getNavigation()->getOffset();
	}

	private function getDataLimit(): ?int
	{
		return $this->getNavigation()->getLimit();
	}

	private function prepareResult(): void
	{
		$this->prepareFilterData();
		$navigationObject = $this->getNavigation();
		['TOTAL_COUNT' => $totalCount] = $this->prepareDocuments();

		$navigationObject->setRecordCount($totalCount);

		$this->setResult(
			'USE_DEFAULT_STUB',
			$this->getParam('USE_DEFAULT_STUB') ?? $this->getUseDefaultStubParamValue()
		);
		\Bitrix\Sign\Agent\FixB2bDoneDocumentsAgent::installOnce();
	}

	/**
	 * @return array{DOCUMENTS_DATA: array, TOTAL_COUNT: int}
	 * @throws ObjectPropertyException
	 */
	private function getDocumentsData(): array
	{
		$documentQueryResult = Document::getList([
			"select" => $this->getSelectedDataFields(),
			"filter" => $this->getDataFilters(),
			"limit" => $this->getDataLimit(),
			"offset" => $this->getDataOffset(),
			"order" => $this->getDataOrder(),
			"count_total" => true,
		]);

		return [
			'DOCUMENTS_DATA' => $documentQueryResult->fetchAll(),
			'TOTAL_COUNT' => $documentQueryResult->getCount(),
		];
	}

	private function getGridColumns(): array
	{
		$gridColumns = [
			[
				"id" => "ID",
				"name" => Loc::getMessage("SIGN_MYSAFE_ID_GRID_COLUMN_NAME"),
				"sort" => "ID",
				'editable' => false,
				'type' => Types::GRID_INT
			],
			[
				"id" => "TITLE",
				"name" => Loc::getMessage("SIGN_MYSAFE_TITLE_GRID_COLUMN_NAME"),
				"default" => true,
				'editable' => false,
			],
			[
				"id" => "DOWNLOAD_DOCUMENT",
				"name" => Loc::getMessage("SIGN_MYSAFE_DOWNLOAD_DOCUMENT_GRID_COLUMN_NAME"),
				"default" => true,
				'editable' => false,
			],
			[
				"id" => "SIGN_WITH",
				"name" => Loc::getMessage("SIGN_MYSAFE_SIGN_WITH_GRID_COLUMN_NAME"),
				"default" => true,
				'editable' => false,
			],
			[
				"id" => "DATE_SIGN",
				"name" => Loc::getMessage("SIGN_MYSAFE_DATE_SIGN_GRID_COLUMN_NAME"),
				"sort" => "DATE_SIGN",
				"default" => true,
				'editable' => false,
			],
			[
				"id" => "DATE_CREATE",
				"name" => Loc::getMessage("SIGN_MYSAFE_DATE_CREATE_GRID_COLUMN_NAME"),
				"sort" => "DATE_CREATE",
				"default" => false,
				'editable' => false,
			],
			[
				"id" => "CREATED_BY",
				"name" => Loc::getMessage("SIGN_MYSAFE_CREATED_BY_GRID_COLUMN_NAME"),
				"default" => true,
				'editable' => false,
			],
		];

		$gridSorting = $this->getGridOptions()->getSorting();
		if (empty($gridSorting['sort']))
		{
			return $gridColumns;
		}

		$gridSortingIds = array_keys($gridSorting['sort']);
		foreach ($gridColumns as &$gridColumn)
		{
			if (in_array($gridColumn['id'], $gridSortingIds, true))
			{
				$gridColumn['color'] = Color::BLUE;
			}
		}

		return $gridColumns;
	}

	private function getGridDefaultSort(): array
	{
		return ["ID" => "DESC"];
	}

	private function getGridOptions(): GridOptions
	{
		return new GridOptions($this->arParams["GRID_ID"]);
	}

	private function getFilterOptions(): FilterOptions
	{
		return new FilterOptions($this->arParams['FILTER_ID']);
	}

	private function getGridSortList(): array
	{
		$result = [];
		foreach ($this->getGridColumns() as $column)
		{
			if (isset($column["sort"]) && $column["sort"])
			{
				$result[] = $column["sort"];
			}
		}

		return $result;
	}

	protected function showErrors(): void
	{
		foreach ($this->errors as $error)
		{
			ShowError($error);
		}
	}

	private function getResultFileInfo(Document $document): array
	{
		$resultFile = $document->getResultFile();
		if ($resultFile === null)
		{
			return [
				'ID' => null,
			];
		}

		return [
			'ID' => $resultFile->getId(),
			'EXTENSION' => $resultFile->getExtension(),
			'DOWNLOAD_URL' => $this->getDownloadResultFileUrl($document),
		];
	}

	private function getDownloadResultFileUrl(Document $document): string
	{
		$operation = new Bitrix\Sign\Operation\GetSignedFilePdfUrl($document->getHash());
		$result = $operation->launch();
		if (!$result->isSuccess() || !$operation->ready)
		{
			return '';
		}

		return $operation->url;
	}

	private function getDateTimeWithTimezoneOffsetRepresentation(
		\Bitrix\Main\Type\DateTime $dateTime,
		DateTimeZone $timezone
	): string
	{
		$dateTime = clone $dateTime;
		$dateTime->setTimeZone($timezone);

		return $dateTime . ' (UTC' . $dateTime->format('P') . ')';
	}

	private function getContactDetailPageUri(int $contactId): ?Uri
	{
		try
		{
			if (!Loader::includeModule('crm'))
			{
				return null;
			}
		}
		catch (LoaderException $e)
		{
			return null;
		}

		return Entity::getDetailPageUri(CCrmOwnerType::Contact, $contactId);
	}

	private function getContactName(int $contactId): ?string
	{
		try
		{
			if (!Loader::includeModule('crm'))
			{
				return null;
			}
		}
		catch (LoaderException $e)
		{
			return null;
		}

		return Entity::getContactName($contactId);
	}

	private function getRequestFilters(FilterOptions $filterOptions): array
	{
		return $filterOptions->getFilter($this->arResult['FILTER']);
	}

	private function getUseDefaultStubParamValue(): bool
	{
		$filterOptions = $this->getFilterOptions();
		if (trim($filterOptions->getSearchString()))
		{
			return true;
		}

		if (count($this->getRequestFilters($filterOptions)) > 0)
		{
			return true;
		}

		return false;
	}

	private function getDateCreateInfo(Document $document, Culture $culture): array
	{
		$dateCreate = clone $document->getDateCreate();

		$dateCreate->setDefaultTimeZone();
		$dateFormat = $this->getDefaultDateFormat($culture);

		return $this->getTextWithDetailInfoFormat(
			FormatDate($dateFormat, $dateCreate->getTimestamp()),
			(string)$dateCreate
		);
	}
	
	public function getAction(): array
	{
		return [
			AccessController::RULE_AND => [
				ActionDictionary::ACTION_MY_SAFE,
			],
		];
	}
}
