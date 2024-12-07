<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Entity;
use Bitrix\Main\UI\Filter\NumberType;
use Bitrix\Main\Web\Uri;
use Bitrix\Voximplant;
use Bitrix\Voximplant\Security\Permissions;
use Bitrix\Voximplant\Security\Helper;

Loc::loadMessages(__FILE__);

class CVoximplantStatisticDetailComponent extends \CBitrixComponent implements \Bitrix\Main\Engine\Contract\Controllerable, \Bitrix\Main\Errorable
{
	use Bitrix\Main\ErrorableImplementation;

	const LOCK_OPTION = 'export_statistic_detail_lock';
	const MODULE = 'voximplant';

	const STATUS_SUCCESS = "SUCCESS";
	const STATUS_FAILURE = "FAILURE";

	protected $gridId = "voximplant_statistic_detail";
	protected $filterId = "voximplant_statistic_detail_filter";
	/** @var  \Bitrix\Main\Grid\Options */
	protected $gridOptions;
	protected $userIds = array();
	protected $userData = array();
	protected $showCallCost = true;
	protected $excelMode = false;
	protected $enableExport = true;
	protected $isExternalFilter = false;
	protected $externalQuery = null;

	protected $pageNumber = -1;
	protected $pageSize = -1;

	/** @var Permissions */
	protected $userPermissions;

	public function __construct($component = null)
	{
		parent::__construct($component);

		$this->errorCollection = new \Bitrix\Main\ErrorCollection();
	}

	protected function init()
	{
		Loader::includeModule("voximplant");
		$this->enableExport = \Bitrix\Voximplant\Limits::canExportCalls();
		$this->gridOptions = new \Bitrix\Main\Grid\Options($this->gridId);

		$this->userPermissions = Permissions::createWithCurrentUser();

		$account = new CVoxImplantAccount();
		if (in_array($account->GetAccountLang(), array('ua', 'kz')))
		{
			$this->showCallCost = false;
		}

		$this->arParams['STEXPORT_MODE'] ??= null;
		$this->arParams['EXPORT_TYPE'] ??= null;

		if (
			$this->arParams['STEXPORT_MODE'] === 'Y'
			&& $this->arParams['EXPORT_TYPE'] === 'excel'
			&& $this->enableExport
		)
		{
			if($this->getLock())
			{
				$this->excelMode = true;

				$this->pageNumber = (int)$this->arParams['PAGE_NUMBER'];
				$this->pageSize = (int)$this->arParams['STEXPORT_PAGE_SIZE'];
			}
			else
			{
				$this->arResult['ERROR_TEXT'] = Loc::getMessage("TEL_STAT_EXPORT_LOCK_ERROR");
			}
		}

		$request = Bitrix\Main\Context::getCurrent()->getRequest();
		if($request['from_analytics'] === 'Y')
		{
			$this->arResult['REPORT_PARAMS'] = $request->getValues();
			$reportHandler = Voximplant\Integration\Report\ReportHandlerFactory::createWithReportId($request['report_id']);
			$this->externalQuery = $reportHandler ? $reportHandler->prepareEntityListFilter($request) : null;

			if($this->externalQuery != null)
			{
				$this->filterId = 'voximplant_statistic_detail_slider_analytics_filter';
				$this->isExternalFilter = true;
				$this->arResult['IS_EXTERNAL_FILTER'] = true;
			}
		}
	}

	protected function checkAccess()
	{
		return $this->userPermissions->canPerform(Permissions::ENTITY_CALL_DETAIL, Permissions::ACTION_VIEW);
	}

	protected function getFilterDefinition()
	{
		$result = array(
			"START_DATE" => array(
				"id" => "START_DATE",
				"name" => Loc::getMessage("TELEPHONY_HEADER_START_DATE"),
				"type" => "date",
				"default" => true
			),
			"PORTAL_USER_ID" => array(
				"id" => "PORTAL_USER_ID",
				"name" => Loc::getMessage("TELEPHONY_HEADER_USER"),
				"default" => true,
				"type" => "custom_entity",
				"selector" => array(
					"TYPE" => "user",
					"DATA" => array("ID" => "user_id", "FIELD_ID" => "PORTAL_USER_ID")
				)
			),
			'PORTAL_NUMBER' => array(
				"id" => "PORTAL_NUMBER",
				"name" => Loc::getMessage("TELEPHONY_HEADER_PORTAL_PHONE_MSGVER_1"),
				"type" => "list",
				"items" => array_map(
					function($line){return $line["SHORT_NAME"];},
					CVoxImplantConfig::GetLinesEx([
						"showRestApps" => true,
						"showInboundOnly" => true
					])
				),
				"default" => false,
				"params" => array(
					"multiple" => true
				)
			),
			"PHONE_NUMBER" => array(
				"id" => "PHONE_NUMBER",
				"name" => Loc::getMessage("TELEPHONY_HEADER_PHONE"),
				"default" => false
			),
			"INCOMING" => array(
				"id" => "INCOMING",
				"name" => Loc::getMessage("TELEPHONY_HEADER_INCOMING"),
				"type" => "list",
				"items" => array("" => Loc::getMessage("TELEPHONY_FILTER_STATUS_UNSET")) + CVoxImplantHistory::GetCallTypes(),
				"default" => false
			),
			"STATUS" => array(
				"id" => "STATUS",
				"name" => Loc::getMessage("TELEPHONY_HEADER_STATUS"),
				"type" => "list",
				"items" => array(
					"" => Loc::getMessage("TELEPHONY_FILTER_STATUS_UNSET"),
					self::STATUS_SUCCESS => Loc::getMessage("TELEPHONY_FILTER_STATUS_SUCCESSFUL"),
					self::STATUS_FAILURE => Loc::getMessage("TELEPHONY_FILTER_STATUS_FAILED")
				)
			),
			"HAS_RECORD" => array(
				"id" => "HAS_RECORD",
				"name" => Loc::getMessage("TELEPHONY_FILTER_HAS_RECORD"),
				"type" => "checkbox",
			),
			"CALL_DURATION" => array(
				"id" => "CALL_DURATION",
				"name" => Loc::getMessage("TELEPHONY_HEADER_DURATION"),
				"default" => false,
				"type" => "number"
			),
			"COST" => array(
				"id" => "COST",
				"name" => Loc::getMessage("TELEPHONY_HEADER_COST"),
				"default" => false,
				"type" => "number"
			),
			"COMMENT" => array(
				"id" => "COMMENT",
				"name" => Loc::getMessage("TELEPHONY_FILTER_COMMENT"),
			),
		);

		if(Voximplant\Model\TranscriptLineTable::getEntity()->fullTextIndexEnabled('MESSAGE'))
		{
			$result["TRANSCRIPT_TEXT"] = array(
				"id" => "TRANSCRIPT_TEXT",
				"name" => Loc::getMessage("TELEPHONY_FILTER_TRANSCRIPT_TEXT"),
				"default" => false
			);
		}

		if(Loader::includeModule('crm'))
		{
			$result["CRM_ENTITY"] = array(
				"id" => "CRM_ENTITY",
				"name" => Loc::getMessage("TELEPHONY_FILTER_CRM"),
				"default" => false,
				"type" => "custom_entity",
				"selector" => array(
					"TYPE" => "crm_entity",
					"DATA" => array(
						"ID" => "CRM_ENTITY",
						"FIELD_ID" => "CRM_ENTITY",
						'ENTITY_TYPE_NAMES' => array(CCrmOwnerType::LeadName, CCrmOwnerType::CompanyName, CCrmOwnerType::ContactName),
						'IS_MULTIPLE' => false
					)
				)
			);
		}

		return $result;
	}

	protected function getFilter(array $gridFilter)
	{
		$filterOptions = new \Bitrix\Main\UI\Filter\Options($this->filterId);
		$filter = $filterOptions->getFilter($this->getFilterDefinition());

		$result = array();

		foreach ($filter as $k => $v)
		{
			if (mb_strpos($k, "datesel") !== false)
			{
				unset($filter[$k]);
			}
		}

		$allowedUserIds = Helper::getAllowedUserIds(
			Helper::getCurrentUserId(),
			$this->userPermissions->getPermission(Permissions::ENTITY_CALL_DETAIL, Permissions::ACTION_VIEW)
		);

		if(isset($filter["PORTAL_USER_ID"]))
		{
			$filter["PORTAL_USER_ID"] = (int)$filter["PORTAL_USER_ID"];
			if(is_array($allowedUserIds))
			{
				$result["=PORTAL_USER_ID"] = array_intersect($allowedUserIds, array($filter["PORTAL_USER_ID"]));
			}
			else
			{
				$result["=PORTAL_USER_ID"] = $filter["PORTAL_USER_ID"];
			}
		}
		else
		{
			if(is_array($allowedUserIds))
			{
				$result["=PORTAL_USER_ID"] = $allowedUserIds;
			}
		}

		if (($filter["PHONE_NUMBER"] ?? null) <> '')
		{
			$result["PHONE_NUMBER"] = CVoxImplantPhone::stripLetters($filter["PHONE_NUMBER"]);
		}

		if (($filter["START_DATE_from"] ?? null) <> '')
		{
			try
			{
				$result[">=CALL_START_DATE"] = \Bitrix\Main\Type\DateTime::createFromUserTime($filter["START_DATE_from"]);
			} catch (Exception $e)
			{
			}
		}

		if (($filter["START_DATE_to"] ?? null) <> '')
		{
			try
			{
				$result["<=CALL_START_DATE"] = \Bitrix\Main\Type\DateTime::createFromUserTime($filter["START_DATE_to"]);
			} catch (Exception $e)
			{
			}
		}

		if (isset($filter['CALL_DURATION_from']) && $filter['CALL_DURATION_from'] != '')
		{
			$operation = $filter['CALL_DURATION_numsel'] == NumberType::MORE ? '>' : '>=';
			$result[$operation."CALL_DURATION"] = (int)$filter['CALL_DURATION_from'];
		}

		if (isset($filter['CALL_DURATION_to']) && $filter['CALL_DURATION_to'] != '')
		{
			$operation = $filter['CALL_DURATION_numsel'] == NumberType::LESS ? '<' : '<=';
			$result[$operation."CALL_DURATION"] = (int)$filter['CALL_DURATION_to'];
		}

		if (isset($filter['COST_from']) && $filter['COST_from'] != '')
		{
			$operation = $filter['COST_numsel'] == NumberType::MORE ? '>' : '>=';
			$result[$operation."COST"] = (float)$filter['COST_from'];
		}

		if (isset($filter['COST_to']) && $filter['COST_to'] != '')
		{
			$operation = $filter['COST_numsel'] == NumberType::LESS ? '<' : '<=';
			$result[$operation."COST"] = (float)$filter['COST_from'];
		}

		if (isset($filter['PORTAL_NUMBER']))
		{
			$result["=PORTAL_NUMBER"] = $filter["PORTAL_NUMBER"];
		}

		if (isset($filter['STATUS']))
		{
			if ($filter['STATUS'] === self::STATUS_FAILURE)
			{
				$result['!=CALL_FAILED_CODE'] = '200';
			}
			else
			{
				if ($filter['STATUS'] === self::STATUS_SUCCESS)
				{
					$result['=CALL_FAILED_CODE'] = '200';
				}
			}
		}

		if (isset($filter['INCOMING']))
		{
			$result['=INCOMING'] = $filter['INCOMING'];
		}

		if (isset($filter["TRANSCRIPT_TEXT"]) && Voximplant\Model\TranscriptLineTable::getEntity()->fullTextIndexEnabled('MESSAGE'))
		{
			$result['=HAS_TRANSCRIPT'] = 1;
		}

		if(isset($filter['FIND']) && $filter['FIND'] != '')
		{
			if(Voximplant\Model\StatisticIndexTable::getEntity()->fullTextIndexEnabled('CONTENT'))
			{
				$result['*SEARCH_INDEX.CONTENT'] =  Voximplant\Search\Content::prepareToken(Voximplant\Search\Content::normalizePhoneNumbers((string)$filter['FIND']));
			}
			else
			{
				$result['SEARCH_INDEX.CONTENT'] =  '%' . Voximplant\Search\Content::prepareToken(Voximplant\Search\Content::normalizePhoneNumbers((string)$filter['FIND'])) . '%';
			}
		}

		if(isset($filter['CRM_ENTITY']) && $filter['CRM_ENTITY'] != '')
		{
			$crmFilter = array();
			try
			{
				$crmFilter = \Bitrix\Main\Web\Json::decode($filter['CRM_ENTITY']);
			} catch (\Bitrix\Main\ArgumentException $e) {};

			if(count($crmFilter) == 1)
			{
				$entityTypes = array_keys($crmFilter);
				$entityType = $entityTypes[0];
				$entityId = $crmFilter[$entityType][0];
				$result['=CRM_ENTITY_TYPE'] = $entityType;
				$result['=CRM_ENTITY_ID'] = $entityId;
			}
		}

		if(isset($filter['HAS_RECORD']))
		{
			$result['=HAS_RECORD'] = ($filter['HAS_RECORD'] === 'Y' ? 'Y' : 'N');
		}

		if(isset($filter['COMMENT']))
		{
			if(\Bitrix\Voximplant\StatisticTable::getEntity()->fullTextIndexEnabled('COMMENT'))
				$result['*COMMENT'] = trim($filter['COMMENT']);
			else
				$result['COMMENT'] = '%' . trim($filter['COMMENT']) . '%';
		}

		return $result;
	}

	protected function getRuntimeFields(array $gridFilter)
	{
		$filterOptions = new \Bitrix\Main\UI\Filter\Options($this->filterId);
		$filter = $filterOptions->getFilter($this->getFilterDefinition());

		$result = array();

		if (isset($filter["TRANSCRIPT_TEXT"]) && Voximplant\Model\TranscriptLineTable::getEntity()->fullTextIndexEnabled('MESSAGE'))
		{
			$transcriptText = (string)$filter["TRANSCRIPT_TEXT"];
			if($transcriptText != '')
			{
				$result["HAS_TRANSCRIPT"] = new \Bitrix\Main\Entity\ExpressionField(
					"HAS_TRANSCRIPT",
					"(CASE WHEN EXISTS(" . $this->prepareTranscriptFilterQuery($transcriptText, '%s')->getQuery() . ") THEN 1 ELSE 0 END)",
					array("TRANSCRIPT_ID")
				);
			}
		}

		return $result;
	}

	protected function prepareTranscriptFilterQuery($text, $field)
	{
		$query = new Query(\Bitrix\Voximplant\Model\TranscriptLineTable::getEntity());
		$query->addSelect('ID');
		$query->addFilter('=TRANSCRIPT_ID', new SqlExpression($field));
		$query->addFilter('*MESSAGE', $text);
		$query->setLimit(1);

		return $query;
	}

	protected function prepareData()
	{
		$this->arResult["ENABLE_EXPORT"] = $this->enableExport;

		$this->arResult["GRID_ID"] = $this->gridId;
		$this->arResult["FILTER_ID"] = $this->filterId;
		$this->arResult["FILTER"] = $this->getFilterDefinition();

		$sorting = $this->gridOptions->GetSorting(array("sort" => array("ID" => "DESC")));
		$navParams = $this->gridOptions->GetNavParams();
		$pageSize = $navParams['nPageSize'];

		$nav = new \Bitrix\Main\UI\PageNavigation("page");
		$nav->allowAllRecords(false)
			->setPageSize($pageSize)
			->initFromUri();

		$idRows = [];
		$filter = $this->getFilter($this->arResult['FILTER']);

		if ($this->excelMode)
		{
			if ((int)$this->arParams['STEXPORT_LAST_EXPORTED_ID'] !== -1)
			{
				$filter["<ID"] = (int)$this->arParams['STEXPORT_LAST_EXPORTED_ID'];
			}

			\CTimeZone::Disable();
			$idRows = Voximplant\StatisticTable::getList([
				"select" => ["ID"],
				"runtime" => $this->getRuntimeFields($this->arResult['FILTER']),
				"filter" => $filter,
				"order" => ['ID' => 'DESC'],
				"limit" => $this->pageSize
			])->fetchAll();
			\CTimeZone::Enable();

			$this->arResult['LAST_EXPORTED_ID'] = $idRows[(count($idRows) - 1)]["ID"];
			$this->arResult['PROCESSED_ITEMS'] = count($idRows);

			if ($this->pageNumber === 1)
			{
				$this->arResult['FIRST_EXPORT_PAGE'] = true;

				\CTimeZone::Disable();
				$rowsCountRecord = Voximplant\StatisticTable::getList([
					"select" => [
						"CNT" => Query::expr()->count("ID")
					],
					"runtime" => $this->getRuntimeFields($this->arResult['FILTER']),
					"filter" => $filter,
				])->fetch();
				\CTimeZone::Enable();

				$this->arResult['TOTAL_ITEMS'] = $rowsCountRecord["CNT"];

				$lastPage = (int)ceil((int)$this->arResult['TOTAL_ITEMS'] / (int)$this->pageSize);
			}
			else
			{
				$lastPage = (int)ceil((int)$this->arParams['STEXPORT_TOTAL_ITEMS'] / (int)$this->pageSize);
			}

			if ($this->pageNumber === $lastPage)
			{
				$this->arResult['LAST_EXPORT_PAGE'] = true;
			}
		}
		else
		{
			if (!$this->isExternalFilter)
			{
				\CTimeZone::Disable();
				$idRows = Voximplant\StatisticTable::getList(
					[
						"select" => ["ID"],
						"runtime" => $this->getRuntimeFields($this->arResult['FILTER']),
						"filter" => $filter,
						"order" => $sorting["sort"],
						"offset" => $nav->getOffset(),
						"limit" => $nav->getLimit() + 1
					]
				)->fetchAll();
				\CTimeZone::Enable();
			}
			else
			{
				$sliderQuery = $this->createReportSliderQuery();

				$sliderQuery->setOffset($nav->getOffset());
				$sliderQuery->setLimit($nav->getLimit() + 1);

				$idRows = $sliderQuery->exec()->fetchAll();
			}
		}

		$idList = array_column($idRows, "ID");

		$rows = array();
		$portalNumbers = CVoxImplantConfig::GetPortalNumbers(true, true);
		$crmEntities = array();
		$rowCount = 0;

		if(!empty($idList))
		{
			$cursor = Voximplant\StatisticTable::getList(array(
				"select" => array(
					'*',
					'TRANSCRIPT_COST' => 'TRANSCRIPT.COST'
				),
				"filter" => [
					"@ID" => $idList
				],
				"order" => $sorting["sort"],
			));
			while ($row = $cursor->fetch())
			{
				$rowCount++;
				if(!$this->excelMode && $rowCount > $nav->getLimit())
				{
					break;
				}
				if ($row["PORTAL_USER_ID"] > 0 && !in_array($row["PORTAL_USER_ID"], $this->userIds))
				{
					$this->userIds[] = $row["PORTAL_USER_ID"];
				}

				$row = CVoxImplantHistory::PrepereData($row);
				if (!$this->showCallCost)
				{
					$row['COST_TEXT'] = '-';
				}

				if (in_array($row["CALL_FAILED_CODE"], Array(1, 2, 3, 409)))
				{
					$row["CALL_FAILED_REASON"] = Loc::getMessage("TELEPHONY_STATUS_".$row["CALL_FAILED_CODE"]);
				}

				if (isset($portalNumbers[$row["PORTAL_NUMBER"]]))
				{
					$row["PORTAL_NUMBER"] = $portalNumbers[$row["PORTAL_NUMBER"]];
				}
				else
				{
					if (mb_substr($row["PORTAL_NUMBER"], 0, 3) === 'sip')
					{
						$row["PORTAL_NUMBER"] = Loc::getMessage("TELEPHONY_PORTAL_PHONE_SIP_OFFICE", Array('#ID#' => mb_substr($row["PORTAL_NUMBER"], 3)));
					}
					else
					{
						if (mb_substr($row["PORTAL_NUMBER"], 0, 3) === 'reg')
						{
							$row["PORTAL_NUMBER"] = Loc::getMessage("TELEPHONY_PORTAL_PHONE_SIP_CLOUD", Array('#ID#' => mb_substr($row["PORTAL_NUMBER"], 3)));
						}
						else
						{
							if ($row["PORTAL_NUMBER"] == '')
							{
								$row["PORTAL_NUMBER"] = Loc::getMessage("TELEPHONY_PORTAL_PHONE_EMPTY");
							}
						}
					}
				}

				if ($row["PORTAL_USER_ID"] == 0 && $row["PHONE_NUMBER"] == '')
				{
					$row["CALL_DURATION_TEXT"] = '';
					$row["INCOMING_TEXT"] = '';
				}

				if (intval($row["CALL_VOTE"]) == 0)
				{
					$row["CALL_VOTE"] = '-';
				}

				$row['PHONE_NUMBER'] = static::formatPhoneNumber($row['PHONE_NUMBER']);
				$row['CALL_START_DATE_RAW'] = $row['CALL_START_DATE'];
				$row['CALL_START_DATE'] = $this->formatDate($row['CALL_START_DATE']);
				$row['COMMENT'] = htmlspecialcharsbx($row['COMMENT']);

				$t_row = array(
					"data" => $row,
					"columns" => array(),
					"editable" => true,
					"actions" => $this->getActions($row),
				);
				$rows[] = $t_row;
				if(isset($row['CRM_ENTITY_TYPE']) && isset($row['CRM_ENTITY_ID']))
				{
					$crmEntities[] = array(
						'TYPE' => $row['CRM_ENTITY_TYPE'],
						'ID' => $row['CRM_ENTITY_ID']
					);
				}
			}
		}

		$nav->setRecordCount($nav->getOffset() + $rowCount);
		$crmFields = CVoxImplantCrmHelper::resolveEntitiesFields($crmEntities);
		$this->userData = $this->getUserData($this->userIds);
		$this->arResult["ROWS"] = $this->addCustomColumns($rows, $crmFields);
		$this->arResult["SORT"] = $sorting["sort"];
		$this->arResult["SORT_VARS"] = $sorting["vars"];
		$this->arResult["NAV_OBJECT"] = $nav;
		$this->arResult["HEADERS"] = array(
			array("id" => "USER_NAME", "name" => GetMessage("TELEPHONY_HEADER_USER"), "default" => true, "editable" => false),
			array("id" => "PORTAL_NUMBER", "name" => GetMessage("TELEPHONY_HEADER_PORTAL_PHONE_MSGVER_1"), "default" => false, "editable" => false),
			array("id" => "PHONE_NUMBER", "name" => GetMessage("TELEPHONY_HEADER_PHONE"), "sort" => "PHONE_NUMBER", "default" => true, "editable" => false),
			array("id" => "INCOMING_TEXT", "name" => GetMessage("TELEPHONY_HEADER_INCOMING"), "default" => true, "editable" => false),
			array("id" => "CALL_DURATION_TEXT", "name" => GetMessage("TELEPHONY_HEADER_DURATION"), "default" => true, "editable" => false),
			array("id" => "CALL_START_DATE", "name" => GetMessage("TELEPHONY_HEADER_START_DATE"), "sort" => "CALL_START_DATE", "default" => true, "editable" => false),
			array("id" => "CALL_FAILED_REASON", "name" => GetMessage("TELEPHONY_HEADER_STATUS"), "default" => true, "editable" => false),
			array("id" => "COST_TEXT", "name" => GetMessage("TELEPHONY_HEADER_COST"), "default" => true, "editable" => false),
			array("id" => "TRANSCRIPT_COST_TEXT", "name" => GetMessage("TELEPHONY_HEADER_TRANSCRIPT_COST"), "default" => false, "editable" => false),
			array("id" => "CALL_VOTE", "name" => GetMessage("TELEPHONY_HEADER_VOTE"), "default" => Voximplant\Limits::canVote(), "editable" => false),
			array("id" => "RECORD", "name" => GetMessage("TELEPHONY_HEADER_RECORD_2"), "default" => false, "editable" => false),
			array("id" => "LOG", "name" => GetMessage("TELEPHONY_HEADER_LOG"), "default" => false, "editable" => false),
			array("id" => "CRM", "name" => GetMessage("TELEPHONY_HEADER_CRM"), "default" => true, "editable" => false),
			array("id" => "COMMENT", "name" => GetMessage("TELEPHONY_HEADER_COMMENT"), "default" => true, "editable" => false),
		);
	}

	public function createReportSliderQuery(): Query
	{
		$sliderQuery = $this->externalQuery;
		$sliderQuery->setSelect(['ID']);

		$filterDefinition = $this->getFilterDefinition();
		$filter = $this->getFilter($filterDefinition);
		if ($filter)
		{
			$sliderQuery->setFilter($filter);
		}

		if (!$filterDefinition)
		{
			return $sliderQuery;
		}

		$runtimeFields = $this->getRuntimeFields($filterDefinition);
		if (!$runtimeFields)
		{
			return $sliderQuery;
		}

		foreach ($runtimeFields as $field)
		{
			$sliderQuery->registerRuntimeField($field);
		}

		return $sliderQuery;
	}

	function getUserData(array $userIds)
	{
		$arUsers = array();
		if (!empty($userIds))
		{
			$dbUser = CUser::GetList("", "", array("ID" => implode(" | ", $userIds)), array("FIELDS" => array("ID", "NAME", "LAST_NAME", "SECOND_NAME", "LOGIN", "PERSONAL_PHOTO")));
			while ($arUser = $dbUser->Fetch())
			{
				$arUsers[$arUser["ID"]]["FIO"] = CUser::FormatName("#NAME# #LAST_NAME#", array(
					"NAME" => $arUser["NAME"],
					"LAST_NAME" => $arUser["LAST_NAME"],
					"SECOND_NAME" => $arUser["SECOND_NAME"],
					"LOGIN" => $arUser["LOGIN"]
				));

				if (intval($arUser["PERSONAL_PHOTO"]) > 0)
				{
					$imageFile = CFile::GetFileArray($arUser["PERSONAL_PHOTO"]);
					if ($imageFile !== false)
					{
						$arFileTmp = CFile::ResizeImageGet(
							$imageFile,
							array("width" => "30", "height" => "30"),
							BX_RESIZE_IMAGE_EXACT,
							false
						);
						$arUsers[$arUser["ID"]]["PHOTO"] = $arFileTmp["src"];
					}
				}
			}
		}

		return $arUsers;
	}

	function addCustomColumns(array $data, array $crmFields)
	{
		$allowedUserIdsToViewRecord = Helper::getAllowedUserIds(
			Helper::getCurrentUserId(),
			$this->userPermissions->getPermission(Permissions::ENTITY_CALL_RECORD, Permissions::ACTION_LISTEN)
		);

		$result = array();
		foreach ($data as $key => $row)
		{
			$recordHtml = '-';
			if (
				!is_array($allowedUserIdsToViewRecord)
				|| in_array($row['data']['PORTAL_USER_ID'], $allowedUserIdsToViewRecord))
			{
				$recordHtml = $this->getRecordHtml(
					$row['data']['ID'],
					$row['data']['CALL_RECORD_HREF'] ?? null,
					$row['data']['CALL_RECORD_DOWNLOAD_URL'] ?? null,
					$row['data']['TRANSCRIPT_ID'] ?? null,
					$row['data']['TRANSCRIPT_PENDING'] ?? null,
					$row['data']['CALL_ID'] ?? null
				);
			}

			$row["columns"] = array(
				"USER_NAME" => $this->getUserHtml($row['data']['PORTAL_USER_ID'], $row["data"]["PHONE_NUMBER"], $row['data']['CALL_ICON']),
				"LOG" => $row["data"]["CALL_LOG"] ? '<a href="'.$row["data"]["CALL_LOG"].'" target="_blank" class="tel-icon-log"></a>' : '-',
				"RECORD" => $recordHtml,
				"CRM" => $this->getCrmHtml($row['data'], $crmFields)
			);
			$result[$key] = $row;
		}

		return $result;
	}

	protected function getActions(array $row)
	{
		$result = array();

		if(isset($row['CALL_RECORD_DOWNLOAD_URL']) && $row['CALL_RECORD_DOWNLOAD_URL'] != '')
		{
			$result[] = array(
				"TITLE" => GetMessage("TEL_STAT_ACTION_DOWNLOAD"),
				"TEXT" => GetMessage("TEL_STAT_ACTION_DOWNLOAD"),
				"ONCLICK" => "window.open('".CUtil::JSEscape($row["CALL_RECORD_DOWNLOAD_URL"])."')",
			);

			$this->addDeleteRecordAction($result, $row);
		}
		elseif (
			!is_null($row['CALL_START_DATE_RAW']) &&
			isset($row['CALL_RECORD_URL']) &&
			$row['CALL_RECORD_URL'] != '' &&
			$this->isDownloadLinkActual($row['CALL_START_DATE_RAW'])
		)
		{
			$result[] = array(
				"TITLE" => GetMessage("TEL_STAT_ACTION_VOX_DOWNLOAD_RECORD"),
				"TEXT" => GetMessage("TEL_STAT_ACTION_VOX_DOWNLOAD_RECORD"),
				"ONCLICK" =>
					"BX.VoximplantStatisticDetail.Instance.downloadVoxRecords([{historyId: ".CUtil::JSEscape($row["ID"])."}]);"
			);

			$this->addDeleteRecordAction($result, $row);
		}

		if($row["TRANSCRIPT_ID"] > 0)
		{
			$result[] = array(
				"TITLE" => GetMessage("TEL_STAT_ACTION_SHOW_TRANSCRIPTION"),
				"TEXT" => GetMessage("TEL_STAT_ACTION_SHOW_TRANSCRIPTION"),
				"ONCLICK" => "BX.Voximplant.Transcript.create({callId: '".CUtil::JSEscape($row["CALL_ID"])."'}).show();"
			);
		}

		if($row["CALL_LOG"] != '')
		{
			$result[] = array(
				"TITLE" => GetMessage("TEL_STAT_ACTION_SHOW_LOG"),
				"TEXT" => GetMessage("TEL_STAT_ACTION_SHOW_LOG"),
				"ONCLICK" => "window.open('".CUtil::JSEscape($row["CALL_LOG"])."')",
			);
		}

		return $result;
	}

	protected function checkModifyAccess($callHistory)
	{
		$allowedUserIdsToModifyRecord = Helper::getAllowedUserIds(
			Helper::getCurrentUserId(),
			$this->userPermissions->getPermission(Permissions::ENTITY_CALL_RECORD, Permissions::ACTION_MODIFY)
		);

		return !is_array($allowedUserIdsToModifyRecord) || in_array($callHistory['PORTAL_USER_ID'], $allowedUserIdsToModifyRecord);
	}

	protected function addDeleteRecordAction(&$result, $row)
	{
		if ($this->checkModifyAccess($row))
		{
			$result[] = array(
				"TITLE" => Loc::getMessage("TEL_STAT_ACTION_DELETE_RECORD"),
				"TEXT" => Loc::getMessage("TEL_STAT_ACTION_DELETE_RECORD"),
				"ONCLICK" => "BX.VoximplantStatisticDetail.Instance.openDeleteConfirm(false, [{historyId: ".CUtil::JSEscape($row["ID"])."}]);"
			);
		}
	}

	public function deleteRecordAction(int $historyId)
	{
		if (!isset($historyId))
		{
			return;
		}

		$this->init();

		$callHistory = \Bitrix\Voximplant\StatisticTable::getRow([
			'select' => [
				'CALL_RECORD_ID',
				'PORTAL_USER_ID',
			],
			'filter' => [
				'=ID' => $historyId
			]
		]);

		if (!$this->checkModifyAccess($callHistory))
		{
			return;
		}

		if (!empty($callHistory['CALL_RECORD_ID']))
		{
			\CFile::Delete($callHistory['CALL_RECORD_ID']);
		}

		\Bitrix\Voximplant\StatisticTable::update($historyId, [
			'CALL_RECORD_ID' => null,
			'CALL_RECORD_URL' => null,
			'RECORD_DURATION' => null,
			'CALL_WEBDAV_ID' => null,
		]);
	}

	public function isRecordsAlreadyUploadedAction(array $historyIds): bool
	{
		$this->init();

		$query = Voximplant\StatisticTable::query();
		$query->addSelect('CALL_RECORD_ID');
		$query->addSelect('CALL_WEBDAV_ID');
		$query->whereIn('ID', array_column($historyIds, 'historyId'));
		$query->whereNotNull('CALL_RECORD_ID');
		$query->whereNotNull('CALL_WEBDAV_ID');
		$recordsInfo = $query->exec()->fetchAll();

		return count($recordsInfo) === count($historyIds);
	}

	public function downloadRecordAction(int $historyId)
	{
		$this->init();

		$recordInfo = Voximplant\StatisticTable::getList([
			'select' => [
				'CALL_RECORD_URL',
				'CALL_START_DATE',
				'CALL_RECORD_ID',
				'CALL_WEBDAV_ID'
			],
			'filter' => [
				'=ID' => $historyId,
			],
		])->fetch();

		if ($recordInfo['CALL_RECORD_ID'] && $recordInfo['CALL_WEBDAV_ID'])
		{
			return true;
		}

		$callStartDate = $recordInfo['CALL_START_DATE'];
		$recordUrl = $recordInfo['CALL_RECORD_URL'];

		if (!$recordUrl || !$this->isDownloadLinkActual($callStartDate))
		{
			$this->errorCollection[] = new Bitrix\Main\Error('Link to call recording is missing or expired.');
			return null;
		}
		$recordUrl = Uri::urnEncode($recordUrl);

		return \CVoxImplantHistory::DownloadAgent($historyId, $recordUrl, false, false);
	}

	protected function isDownloadLinkActual($callStartDate)
	{
		if ($callStartDate == null)
		{
			return false;
		}

		$callStartDate = new \Bitrix\Main\Type\DateTime($callStartDate);

		$expireLinkDate = (clone $callStartDate)->add('2 months');

		$now = new \Bitrix\Main\Type\DateTime();

		if ($now->getTimestamp() > $expireLinkDate->getTimestamp())
		{
			return false;
		}

		return true;
	}

	protected function getUserHtml($userId, $phoneNumber, $callIcon)
	{
		if ($userId > 0)
		{
			$userHtml = '<span class="tel-stat-user-name-container">';
			if ($this->userData[$userId]["PHOTO"] ?? null)
			{
				$userHtml .= '<span class="ui-icon ui-icon-sm"><i style="background: url(\'' . Uri::urnEncode($this->userData[$userId]["PHOTO"]) . '\') no-repeat center;\"></i></span>';
			}
			else
			{
				$userHtml .= '<span class="ui-icon ui-icon-common-user ui-icon-sm"><i></i></span>';
			}
			$userHtml .= '<span class="tel-stat-user-name">' . htmlspecialcharsbx($this->userData[$userId]["FIO"]) . '</span></span>';
		}
		else
		{
			$userHtml = "<span class='ui-icon ui-icon-common-user ui-icon-sm'><i></i></span> &mdash;";
		}

		if ($phoneNumber == '')
		{
			$userHtml = Loc::getMessage('TELEPHONY_BILLING');
		}
		else
		{
			//$userHtml = '<span class="tel-stat-icon tel-stat-icon-'.$callIcon.'"></span><span style="white-space: nowrap">'.$userHtml.'</span>';
		}

		return $userHtml;
	}

	protected function getRecordHtml($id, $recordHref, $recordDownloadUrl, $transcriptId, $transcriptPending, $callId)
	{
		if ($recordHref <> '' || $transcriptId > 0)
		{
			$recordHtml = '';
			if($recordHref <> '')
			{
				$recordHtml .= '<div class="tel-player"><button class="vi-player-button" data-bx-record="'.$recordHref.'"></button></div>';

				if ($recordDownloadUrl != '')
				{
					$recordHtml .= '<a href="'.$recordDownloadUrl.'" target="_blank" class="tel-player-download"></a>';
				}
				else
				{
					$recordHtml .= '<a href="'.$recordHref.'" target="_blank" class="tel-player-download"></a>';
				}
			}

			if($transcriptId > 0)
			{
				$recordHtml .= '<span class="tel-show-transcript" title="'.GetMessage('TEL_STAT_SHOW_TRANSCRIPT').'" onclick="BX.Voximplant.Transcript.create({callId: \''.CUtil::JSEscape($callId).'\'}).show();"></span>';
			}
			else if($transcriptPending === 'Y')
			{

			}
			$result = '<span style="white-space: nowrap">'.$recordHtml.'</span>';
		}
		else
		{
			$result = '-';
		}
		return $result;
	}

	/**
	 * @param $statisticRecord
	 * @param $crmFields
	 * @return string
	 */
	protected function getCrmHtml($statisticRecord, $crmFields)
	{
		$hasEntity = false;
		$hasActivity = false;
		$result = '';
		static $activityDescription =  null;

		if(is_null($activityDescription))
			$activityDescription = CVoxImplantCrmHelper::getActivityDescription();

		if(isset($statisticRecord['CRM_ENTITY_TYPE']) && isset($statisticRecord['CRM_ENTITY_ID']) && $statisticRecord['CRM_ENTITY_ID'] > 0)
		{
			$key = $statisticRecord['CRM_ENTITY_TYPE'] . ":" . $statisticRecord['CRM_ENTITY_ID'];
			if(isset($crmFields[$key]))
			{
				$entity = $crmFields[$key];
				$hasEntity = true;
				if($this->excelMode)
				{
					$result .= htmlspecialcharsbx($entity['DESCRIPTION']) . ': ' . htmlspecialcharsbx($entity['NAME']);
				}
				else
				{
					$result .= '<div>' . htmlspecialcharsbx($entity['DESCRIPTION']) . ': <a href="'.$entity['SHOW_URL'].'" target="_blank">' . htmlspecialcharsbx($entity['NAME']) . '</a></div>';
				}
			}
		}

		if(isset($statisticRecord['CRM_ACTIVITY_ID']) && $statisticRecord['CRM_ACTIVITY_ID'] > 0 && !$this->excelMode)
		{
			$hasActivity = true;
			$result .= '<div><a href="javascript:void(0);" onclick="'.$this->getActivityShowCode($statisticRecord['CRM_ACTIVITY_ID']).'">' . $activityDescription . '</a></div>';
		}

		if(!$hasEntity && !$hasActivity)
		{
			$result = '-';
		}
		return $result;
	}

	protected function getActivityShowCode($activityId)
	{
		if(!Loader::includeModule('crm'))
			return '';

		$activityId = (int)$activityId;
		return $activityId ? "(new BX.Crm.Activity.Planner()).showEdit({'ID':$activityId});" : "";
	}

	protected function getLock()
	{
		if(!\Bitrix\Main\ModuleManager::isModuleInstalled('bitrix24'))
			return true;

		$currentTimestamp = time();
		$lockTimestamp = (int)\Bitrix\Main\Config\Option::get(self::MODULE, self::LOCK_OPTION);

		if($lockTimestamp > 0)
		{
			if($currentTimestamp - $lockTimestamp > 60)
			{
				\Bitrix\Main\Config\Option::set(self::MODULE, self::LOCK_OPTION, $currentTimestamp);
				return true;
			}
			else
			{
				return false;
			}
		}
		else
		{
			\Bitrix\Main\Config\Option::set(self::MODULE, self::LOCK_OPTION, $currentTimestamp);
			return true;
		}
	}

	protected function releaseLock()
	{
		if(!\Bitrix\Main\ModuleManager::isModuleInstalled('bitrix24'))
			return;

		\Bitrix\Main\Config\Option::set(self::MODULE, self::LOCK_OPTION);
	}

	protected static function formatPhoneNumber($number)
	{
		return \Bitrix\Main\PhoneNumber\Parser::getInstance()->parse($number)->format();
	}

	protected function formatDate(\Bitrix\Main\Type\DateTime $date)
	{
		if (!$date)
		{
			return '-';
		}

		if($this->excelMode)
			return $date->toString();
		else
			return formatDate('x', $date->toUserTime()->getTimestamp(), (time() + \CTimeZone::getOffset()));
	}

	/**
	 * Executes component
	 */
	public function executeComponent()
	{
		if (!Loader::includeModule(self::MODULE))
		{
			return false;
		}

		$this->init();

		if(!$this->checkAccess())
		{
			return false;
		}

		$this->prepareData();

		if($this->excelMode)
		{
			$this->releaseLock();

			$this->includeComponentTemplate('excel');
		}
		else
		{
			$this->arResult['EXPORT_PARAMS'] = [
				'componentName' => $this->getName(),
			];

			$this->includeComponentTemplate();
		}

		return $this->arResult;
	}

	public function configureActions()
	{
		return [];
	}

	public function getRowsCountAction()
	{
		$this->init();

		if (!$this->isExternalFilter)
		{
			$filterDefinition = $this->getFilterDefinition();
			\CTimeZone::Disable();
			$cursor = Voximplant\StatisticTable::getList([
				"select" => [
					"CNT" => Query::expr()->count("ID")
				],
				"runtime" => $this->getRuntimeFields($filterDefinition),
				"filter" => $this->getFilter($filterDefinition),
			]);
			\CTimeZone::Enable();
			$row = $cursor->fetch();
		}
		else
		{
			$row["CNT"] = $this->createReportSliderQuery()
				->setSelect([
					"CNT" => Query::expr()->count("ID")
				])
				->exec()
				->fetch()["CNT"]
			;
		}

		return [
			"rowsCount" => $row["CNT"],
			"sql" => Query::getLastQuery()
		];
	}
}
