<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Voximplant;
use Bitrix\Voximplant\Security\Permissions;
use Bitrix\Voximplant\Security\Helper;

Loc::loadMessages(__FILE__);

class CVoximplantStatisticDetailComponent extends \CBitrixComponent
{
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
	protected $exportRequest;
	protected $enableExport = true;
	/** @var Permissions */
	protected $userPermissions;

	protected function init()
	{
		$this->enableExport = CVoxImplantAccount::IsPro();
		$this->gridOptions = new \Bitrix\Main\Grid\Options($this->gridId);

		$this->userPermissions = Permissions::createWithCurrentUser();

		$account = new CVoxImplantAccount();
		if (in_array($account->GetAccountLang(), array('ua', 'kz')))
		{
			$this->showCallCost = false;
		}

		if ($_REQUEST['excel'] === 'Y' && $this->enableExport)
		{
			if($this->getLock())
			{
				$this->excelMode = true;
			}
			else
			{
				$this->arResult['ERROR_TEXT'] = Loc::getMessage("TEL_STAT_EXPORT_LOCK_ERROR");
			}
			$this->exportRequest = $_REQUEST["exportRequest"];
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
				"name" => Loc::getMessage("TELEPHONY_HEADER_PORTAL_PHONE"),
				"type" => "list",
				"items" => CVoxImplantConfig::GetPortalNumbers(true, true),
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
			)
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
			if (strpos($k, "datesel") !== false)
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

		if (strlen($filter["PHONE_NUMBER"]) > 0)
		{
			$result["PHONE_NUMBER"] = CVoxImplantPhone::stripLetters($filter["PHONE_NUMBER"]);
		}

		if (strlen($filter["START_DATE_from"]) > 0)
		{
			try
			{
				$result[">=CALL_START_DATE"] = new \Bitrix\Main\Type\DateTime($filter["START_DATE_from"]);
			} catch (Exception $e)
			{
			}
		}

		if (strlen($filter["START_DATE_to"]) > 0)
		{
			try
			{
				$result["<=CALL_START_DATE"] = new \Bitrix\Main\Type\DateTime($filter["START_DATE_to"]);
			} catch (Exception $e)
			{
			}
		}

		if (intval($filter['CALL_DURATION_from']) > 0)
		{
			$result[">=CALL_DURATION"] = (int)$filter['CALL_DURATION_from'];
		}

		if (intval($filter['CALL_DURATION_to']) > 0)
		{
			$result["<=CALL_DURATION"] = (int)$filter['CALL_DURATION_to'];
		}

		if (floatval($filter['COST_from']) > 0)
		{
			$result[">=COST"] = (float)$filter['COST_from'];
		}

		if (floatval($filter['COST_to']) > 0)
		{
			$result["<=COST"] = (float)$filter['COST_to'];
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
		global $APPLICATION;
		$this->arResult["ENABLE_EXPORT"] = $this->enableExport;
		$this->arResult["EXPORT_HREF"] = $APPLICATION->GetCurPageParam('excel=Y');
		$this->arResult["TRIAL_TEXT"] = CVoxImplantMain::GetTrialText();

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

		$cursor = Voximplant\StatisticTable::getList(array(
			"runtime" => $this->getRuntimeFields($this->arResult['FILTER']),
			"filter" => $this->getFilter($this->arResult['FILTER']),
			"order" => $sorting["sort"],
			"select" => array(
				'*',
				'TRANSCRIPT_COST' => 'TRANSCRIPT.COST'
			),
			"count_total" => true,
			"offset" => ($this->excelMode ? 0 : $nav->getOffset()),
			"limit" => ($this->excelMode ? 0 : $nav->getLimit())
		));

		$rows = array();
		$portalNumbers = CVoxImplantConfig::GetPortalNumbers(true, true);
		$crmEntities = array();
		while ($row = $cursor->fetch())
		{
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
				if (substr($row["PORTAL_NUMBER"], 0, 3) == 'sip')
				{
					$row["PORTAL_NUMBER"] = Loc::getMessage("TELEPHONY_PORTAL_PHONE_SIP_OFFICE", Array('#ID#' => substr($row["PORTAL_NUMBER"], 3)));
				}
				else
				{
					if (substr($row["PORTAL_NUMBER"], 0, 3) == 'reg')
					{
						$row["PORTAL_NUMBER"] = Loc::getMessage("TELEPHONY_PORTAL_PHONE_SIP_CLOUD", Array('#ID#' => substr($row["PORTAL_NUMBER"], 3)));
					}
					else
					{
						if (strlen($row["PORTAL_NUMBER"]) <= 0)
						{
							$row["PORTAL_NUMBER"] = Loc::getMessage("TELEPHONY_PORTAL_PHONE_EMPTY");
						}
					}
				}
			}

			if ($row["PORTAL_USER_ID"] == 0 && strlen($row["PHONE_NUMBER"]) <= 0)
			{
				$row["CALL_DURATION_TEXT"] = '';
				$row["INCOMING_TEXT"] = '';
			}

			if (intval($row["CALL_VOTE"]) == 0)
			{
				$row["CALL_VOTE"] = '-';
			}

			$row['PHONE_NUMBER'] = static::formatPhoneNumber($row['PHONE_NUMBER']);
			$row['CALL_START_DATE'] = $this->formatDate($row['CALL_START_DATE']);

			$t_row = array(
				"data" => $row,
				"columns" => array(),
				"editable" => false,
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

		$crmFields = CVoxImplantCrmHelper::resolveEntitiesFields($crmEntities);
		$this->userData = $this->getUserData($this->userIds);
		$this->arResult["ROWS"] = $this->addCustomColumns($rows, $crmFields);

		$this->arResult["ROWS_COUNT"] = $cursor->getCount();
		$nav->setRecordCount($cursor->getCount());

		$this->arResult["SORT"] = $sorting["sort"];
		$this->arResult["SORT_VARS"] = $sorting["vars"];
		$this->arResult["NAV_OBJECT"] = $nav;

		$this->arResult["HEADERS"] = array(
			array("id" => "USER_NAME", "name" => GetMessage("TELEPHONY_HEADER_USER"), "default" => true, "editable" => false),
			array("id" => "PORTAL_NUMBER", "name" => GetMessage("TELEPHONY_HEADER_PORTAL_PHONE"), "default" => false, "editable" => false),
			array("id" => "PHONE_NUMBER", "name" => GetMessage("TELEPHONY_HEADER_PHONE"), "sort" => "PHONE_NUMBER", "default" => true, "editable" => false),
			array("id" => "INCOMING_TEXT", "name" => GetMessage("TELEPHONY_HEADER_INCOMING"), "default" => true, "editable" => false),
			array("id" => "CALL_DURATION_TEXT", "name" => GetMessage("TELEPHONY_HEADER_DURATION"), "default" => true, "editable" => false),
			array("id" => "CALL_START_DATE", "name" => GetMessage("TELEPHONY_HEADER_START_DATE"), "sort" => "CALL_START_DATE", "default" => true, "editable" => false),
			array("id" => "CALL_FAILED_REASON", "name" => GetMessage("TELEPHONY_HEADER_STATUS"), "default" => true, "editable" => false),
			array("id" => "COST_TEXT", "name" => GetMessage("TELEPHONY_HEADER_COST"), "default" => true, "editable" => false),
			array("id" => "TRANSCRIPT_COST_TEXT", "name" => GetMessage("TELEPHONY_HEADER_TRANSCRIPT_COST"), "default" => false, "editable" => false),
			array("id" => "CALL_VOTE", "name" => GetMessage("TELEPHONY_HEADER_VOTE"), "default" => CVoxImplantAccount::IsPro(), "editable" => false),
			array("id" => "RECORD", "name" => GetMessage("TELEPHONY_HEADER_RECORD"), "default" => false, "editable" => false),
			array("id" => "LOG", "name" => GetMessage("TELEPHONY_HEADER_LOG"), "default" => false, "editable" => false),
			array("id" => "CRM", "name" => GetMessage("TELEPHONY_HEADER_CRM"), "default" => true, "editable" => false),
			array("id" => "COMMENT", "name" => GetMessage("TELEPHONY_HEADER_COMMENT"), "default" => true, "editable" => false),
		);
	}

	function getUserData(array $userIds)
	{
		$arUsers = array();
		if (!empty($userIds))
		{
			$dbUser = CUser::GetList($by = "", $order = "", array("ID" => implode($userIds, " | ")), array("FIELDS" => array("ID", "NAME", "LAST_NAME", "SECOND_NAME", "LOGIN", "PERSONAL_PHOTO")));
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
					$row['data']['CALL_RECORD_HREF'],
					$row['data']['CALL_RECORD_DOWNLOAD_URL'],
					$row['data']['TRANSCRIPT_ID'],
					$row['data']['TRANSCRIPT_PENDING'],
					$row['data']['CALL_ID']
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

	protected function getUserHtml($userId, $phoneNumber, $callIcon)
	{
		if ($userId > 0)
		{
			$userHtml = '<span class="tel-stat-user-name-container">';
			if ($this->userData[$userId]["PHOTO"])
			{
				$userHtml .= '<span class="tel-stat-user-img user-avatar" style="background: url(\'' . $this->userData[$userId]["PHOTO"] . '\') no-repeat center;\"></span>';
			}
			$userHtml .= '<span class="tel-stat-user-name">' . htmlspecialcharsbx($this->userData[$userId]["FIO"]) . '</span></span>';
		}
		else
		{
			$userHtml = "<span class='tel-stat-user-img user-avatar'></span> &mdash;";
		}

		if (strlen($phoneNumber) <= 0)
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
		if (strlen($recordHref) > 0)
		{
			$recordHtml = '<div class="tel-player"><button class="vi-player-button" data-bx-record="'.$recordHref.'"></button></div>';

			if ($recordDownloadUrl != '')
			{
				$recordHtml .= '<a href="'.$recordDownloadUrl.'" target="_blank" class="tel-player-download"></a>';
			}
			else
			{
				$recordHtml .= '<a href="'.$recordHref.'" target="_blank" class="tel-player-download"></a>';
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
		global $APPLICATION;

		if (!Loader::includeModule(self::MODULE))
			return false;

		$this->init();

		if(!$this->checkAccess())
			return false;

		$this->prepareData();

		$cookie = new \Bitrix\Main\Web\Cookie("VOX_STAT_EXPORT_REQUEST", $this->exportRequest, 0);
		$cookie->setSecure(false);
		$cookie->setHttpOnly(false);
		$this->arResult['EXPORT_REQUEST_COOKIE_NAME'] = $cookie->getName();

		if($this->excelMode)
		{
			$this->releaseLock();
			$now = new \Bitrix\Main\Type\Date();
			$filename = 'call_details_'.$now->format('Y_m_d').'.xls';
			$APPLICATION->RestartBuffer();

			\Bitrix\Main\Context::getCurrent()->getResponse()->addCookie($cookie);

			header("Content-Type: application/vnd.ms-excel");
			header("Content-Disposition: filename=".$filename);
			$this->includeComponentTemplate('excel');
			CMain::FinalActions();
			die();
		}
		else
		{
			$this->includeComponentTemplate();
			return $this->arResult;
		}
	}
}