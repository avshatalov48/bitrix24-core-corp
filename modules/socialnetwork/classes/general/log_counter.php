<?php

class CAllSocNetLogCounter
{
	public static function GetSubSelect2($entityId, $arParams = array())
	{
		return CSocNetLogCounter::GetSubSelect(
			array(
				"LOG_ID" => $entityId,
				"TYPE" => (is_array($arParams) && !empty($arParams["TYPE"]) ? $arParams["TYPE"] : CSocNetLogCounter::TYPE_LOG_ENTRY),
				"CODE" => (is_array($arParams) && !empty($arParams["CODE"]) ? $arParams["CODE"] : false),
				"DECREMENT" => (is_array($arParams) && $arParams["DECREMENT"]),
				"FOR_ALL_ACCESS" => (is_array($arParams) && $arParams["FOR_ALL_ACCESS"]),
				"FOR_ALL_ACCESS_ONLY" => (is_array($arParams) && $arParams["FOR_ALL_ACCESS_ONLY"]),
				"TAG_SET" => (is_array($arParams) && !empty($arParams["TAG_SET"]) ? $arParams["TAG_SET"] : false),
				"MULTIPLE" => (is_array($arParams) && !empty($arParams["MULTIPLE"]) && $arParams["MULTIPLE"] === "Y" ? "Y" : "N"),
				"SET_TIMESTAMP" => (is_array($arParams) && !empty($arParams["SET_TIMESTAMP"]) && $arParams["SET_TIMESTAMP"] === "Y" ? "Y" : "N"),
				"SEND_TO_AUTHOR" => (!is_array($arParams) || !isset($arParams["SEND_TO_AUTHOR"]) || $arParams["SEND_TO_AUTHOR"] !== "Y" ? "N" : "Y"),
				"USER_ID" => (isset($arParams["USER_ID"]) && is_array($arParams["USER_ID"]) ? $arParams["USER_ID"] : array())
			)
		);
	}

	public static function GetSubSelect($entityId, $entity_type = false, $entity_id = false, $event_id = false, $created_by_id = false, $arOfEntities = null, $arAdmin = false, $transport = false, $visible = "Y", $type = CSocNetLogCounter::TYPE_LOG_ENTRY, $params = array(), $bDecrement = false, $bForAllAccess = false)
	{
		global $DB;

		if (
			is_array($entityId)
			&& isset($entityId["LOG_ID"])
		)
		{
			$arFields = $entityId;

			$entityId = (int)$arFields["LOG_ID"];
			$entity_type = ($arFields["ENTITY_TYPE"] ?? false);
			$entity_id = ($arFields["ENTITY_ID"] ?? false);
			$event_id = ($arFields["EVENT_ID"] ?? false);
			$created_by_id = ($arFields["CREATED_BY_ID"] ?? false);
			$arOfEntities = ($arFields["ENTITIES"] ?? null);
			$transport  = ($arFields["TRANSPORT"] ?? false);
			$visible  = ($arFields["VISIBLE"] ?? "Y");
			$type  = ($arFields["TYPE"] ?? CSocNetLogCounter::TYPE_LOG_ENTRY);
			$code  = ($arFields["CODE"] ?? false);
			$params  = ($arFields["PARAMS"] ?? []);
			$bDecrement = ($arFields["DECREMENT"] ?? false);
			$bMultiple = (isset($arFields['MULTIPLE']) && $arFields['MULTIPLE'] === 'Y');
			$bSetTimestamp = (isset($arFields['SET_TIMESTAMP']) && $arFields['SET_TIMESTAMP'] === 'Y');

			$IsForAllAccessOnly = false;
			if (isset($arFields["FOR_ALL_ACCESS_ONLY"]))
			{
				$IsForAllAccessOnly = ($arFields["FOR_ALL_ACCESS_ONLY"] ? "Y" : "N");
			}
			$bForAllAccess  = (
				$IsForAllAccessOnly === 'Y'
					? true
					: ($arFields["FOR_ALL_ACCESS"] ?? false)
			);
			$tagSet  = ($arFields["TAG_SET"] ?? false);
			$bSendToAuthor = (
				!isset($arFields["SEND_TO_AUTHOR"])
				|| $arFields['SEND_TO_AUTHOR'] !== 'Y'
					? false
					: true
			);
			$arUserIdToIncrement = (isset($arFields["USER_ID"]) && is_array($arFields["USER_ID"]) ? $arFields["USER_ID"] : array());
		}
		else
		{
			$bSendToAuthor = $IsForAllAccessOnly = $bMultiple = $tagSet = $code = $bSetTimestamp = false;
			$arUserIdToIncrement = array();
		}

		$intranetInstalled = \Bitrix\Main\ModuleManager::isModuleInstalled('intranet');

		if ((int)$entityId <= 0)
		{
			return false;
		}

		$arSocNetAllowedSubscribeEntityTypesDesc = CSocNetAllowed::GetAllowedEntityTypesDesc();

		$bGroupCounters = ($type === "group");

		$params = (
			is_array($params)
				? $params
				: array()
		);

		$params['CODE'] = (
			!empty($params['CODE'])
				? $params['CODE']
				: (
					$code
						? $code
						: (
							$bGroupCounters
								? "SLR0.GROUP_CODE"
								: "'".CUserCounter::LIVEFEED_CODE.($bMultiple ? $type.$entityId : "")."'"
						)
				)
		);

		if (
			$type === CSocNetLogCounter::TYPE_LOG_ENTRY
			&& ($arLog = CSocNetLog::GetByID($entityId))
		)
		{
			$logId = $entityId;
			$entity_type = $arLog["ENTITY_TYPE"];
			$entity_id = $arLog["ENTITY_ID"];
			$event_id = $arLog["EVENT_ID"];
			$created_by_id = $arLog["USER_ID"];
		}
		elseif (
			$type === CSocNetLogCounter::TYPE_LOG_COMMENT
			&& ($arLogComment = CSocNetLogComments::GetByID($entityId))
		)
		{
			$entity_type = $arLogComment["ENTITY_TYPE"];
			$entity_id = $arLogComment["ENTITY_ID"];
			$event_id = $arLogComment["EVENT_ID"];
			$created_by_id = $arLogComment["USER_ID"];
			$logId = $arLogComment["LOG_ID"]; // recalculate log_id
		}
		else
		{
			$logId = $entityId;
		}

		if (
			!in_array($entity_type, CSocNetAllowed::GetAllowedEntityTypes(), true)
			|| (int)$entity_id <= 0
			|| $event_id == ''
		)
		{
			return false;
		}

		if (!$arOfEntities)
		{
			if (
				array_key_exists($entity_type, $arSocNetAllowedSubscribeEntityTypesDesc)
				&& array_key_exists("HAS_MY", $arSocNetAllowedSubscribeEntityTypesDesc[$entity_type])
				&& $arSocNetAllowedSubscribeEntityTypesDesc[$entity_type]["HAS_MY"] === "Y"
				&& array_key_exists("CLASS_OF", $arSocNetAllowedSubscribeEntityTypesDesc[$entity_type])
				&& array_key_exists("METHOD_OF", $arSocNetAllowedSubscribeEntityTypesDesc[$entity_type])
				&& $arSocNetAllowedSubscribeEntityTypesDesc[$entity_type]["CLASS_OF"] <> ''
				&& $arSocNetAllowedSubscribeEntityTypesDesc[$entity_type]["METHOD_OF"] <> ''
				&& method_exists($arSocNetAllowedSubscribeEntityTypesDesc[$entity_type]["CLASS_OF"], $arSocNetAllowedSubscribeEntityTypesDesc[$entity_type]["METHOD_OF"])
			)
			{
				$arOfEntities = call_user_func(array($arSocNetAllowedSubscribeEntityTypesDesc[$entity_type]["CLASS_OF"], $arSocNetAllowedSubscribeEntityTypesDesc[$entity_type]["METHOD_OF"]), $entity_id);
			}
			else
			{
				$arOfEntities = [];
			}
		}

		$useUASubSelect = false;

		if (
			(
				!defined("DisableSonetLogVisibleSubscr")
				|| DisableSonetLogVisibleSubscr !== true
			)
			&& $visible 
			&& $visible <> ''
		)
		{
			$key_res = CSocNetGroup::GetFilterOperation($visible);
			$strField = $key_res["FIELD"];
			$strNegative = $key_res["NEGATIVE"];
			$strOperation = $key_res["OPERATION"];
			$visibleFilter = "AND (".($strNegative === "Y" ? " SLE.VISIBLE IS NULL OR NOT " : "")."(SLE.VISIBLE ".$strOperation." '".$DB->ForSql($strField)."'))";

			$transportFilter = "";
		}
		else
		{
			$visibleFilter = "";

			if (
				$transport
				&& $transport <> ''
			)
			{
				$key_res = CSocNetGroup::GetFilterOperation($transport);
				$strField = $key_res["FIELD"];
				$strNegative = $key_res["NEGATIVE"];
				$strOperation = $key_res["OPERATION"];
				$transportFilter = "AND (".($strNegative === "Y" ? " SLE.TRANSPORT IS NULL OR NOT " : "")."(SLE.TRANSPORT ".$strOperation." '".$DB->ForSql($strField)."'))";
			}
			else
			{
				$transportFilter = "";
			}
		}

		$useFollow = (
			$type === CSocNetLogCounter::TYPE_LOG_COMMENT
			&& (
				!defined('DisableSonetLogFollow')
				|| DisableSonetLogFollow !== true
			)
		);

		$defaultFollowValue = \Bitrix\Main\Config\Option::get('socialnetwork', 'follow_default_type', 'Y');

		$followJoin = '';
		$followWhere = '';

		if ($useFollow)
		{
			if ($defaultFollowValue === 'Y')
			{
				$followWhere = "
					AND (
						NOT EXISTS (SELECT USER_ID FROM b_sonet_log_follow WHERE USER_ID = U.ID AND TYPE='N' AND (CODE = 'L".$logId."' OR CODE = '**'))
						OR EXISTS (SELECT USER_ID FROM b_sonet_log_follow WHERE USER_ID = U.ID AND TYPE='Y' AND CODE = 'L".$logId."')
					)
				";
			}
			else
			{
				$followJoin = " 
					INNER JOIN b_sonet_log_follow LFW ON LFW.USER_ID = U.ID AND (LFW.CODE = 'L".$logId."' OR LFW.CODE = '**')
					LEFT JOIN b_sonet_log_follow LFW2 ON LFW2.USER_ID = U.ID AND (LFW2.CODE = 'L".$logId."' AND LFW2.TYPE = 'N')
				";
				$followWhere = "
					AND (LFW.USER_ID IS NOT NULL AND LFW.TYPE = 'Y')
					AND LFW2.USER_ID IS NULL
				";
			}
		}

		$viewJoin = " LEFT JOIN b_sonet_log_view LFV ON LFV.USER_ID = U.ID AND LFV.EVENT_ID = '".$DB->ForSql($event_id)."'";
		$viewWhere = "AND (LFV.USER_ID IS NULL OR LFV.TYPE = 'Y')";

		$strOfEntities = (
			is_array($arOfEntities)
			&& count($arOfEntities) > 0
				? "U.ID IN (".implode(",", $arOfEntities).")"
				: ""
		);

		$logRightFilterValue = [];

		if (!empty($arUserIdToIncrement))
		{
			$userWhere = "AND U.ID IN (".implode(",", $arUserIdToIncrement).")";
		}
		else
		{
			if (!$bGroupCounters && !$intranetInstalled)
			{
				if (\Bitrix\Main\Config\Option::get('socialnetwork', 'sonet_log_smart_filter', 'N') === 'Y')
				{
					$userWhere = "
						AND (
							0=1
							OR (
								(
									SLSF.USER_ID IS NULL
									OR SLSF.TYPE = 'Y'
								)
								" . (!$bForAllAccess ? ' AND (UA.ACCESS_CODE = SLR.GROUP_CODE)' : '') . "
								AND (
									SLR.GROUP_CODE LIKE 'SG%'
									OR SLR.GROUP_CODE = " . $DB->Concat("'U'", 'U.ID') . "
								)
							)
							OR (
								SLSF.TYPE <> 'Y'
								AND (
									SLR.GROUP_CODE IN ('AU', 'G2')
									" . (!$bForAllAccess ? ' OR (UA.ACCESS_CODE = SLR.GROUP_CODE)' : '') . "
								)
							)
						)
					";
				}
				else
				{
					$userWhere = "
						AND (
							0=1
							OR (
								(
									SLSF.USER_ID IS NULL
									OR SLSF.TYPE <> 'Y'
								)
								AND (
									SLR.GROUP_CODE IN ('AU', 'G2')
									" . ($bForAllAccess ? '' : ' OR (UA.ACCESS_CODE = SLR.GROUP_CODE)') . "
								)
							)
							OR (
								SLSF.TYPE = 'Y'
								" . ($bForAllAccess ? '' : ' AND (UA.ACCESS_CODE = SLR.GROUP_CODE)') . "
								AND (
									SLR.GROUP_CODE LIKE 'SG%'
									OR SLR.GROUP_CODE = " . $DB->Concat("'U'", 'U.ID') . "
								)
							)
						)
					";
				}
			}
			else
			{
				$userLogRightsIntersectCondition = '';
				if (!$bForAllAccess && $IsForAllAccessOnly !== 'Y')
				{
					if (!$bGroupCounters)
					{
						$res = \Bitrix\Socialnetwork\LogRightTable::getList([
							'filter' => [
								'=LOG_ID' => $logId
							],
							'select' => [ 'GROUP_CODE' ],
						]);
						while ($logRightFields = $res->fetch())
						{
							if (in_array($logRightFields['GROUP_CODE'], [ 'AU', 'G2' ]))
							{
								continue;
							}

							$logRightFilterValue[] = $logRightFields['GROUP_CODE'];
						}
					}

					$userLogRightsIntersectCondition = (
						!empty($logRightFilterValue)
							? ' OR UA.ACCESS_CODE IN (' . implode(', ', array_map(static function($item) use ($DB) { return "'" . $DB->forSql($item) . "'"; }, $logRightFilterValue)) . ') '
							: ' OR (UA.ACCESS_CODE = SLR.GROUP_CODE) '
						);
				}

				if (
					$useFollow
					&& $defaultFollowValue !== 'Y'
					&& !$bForAllAccess
					&& $IsForAllAccessOnly !== 'Y'
					&& !empty($logRightFilterValue)
				)
				{
					$useUASubSelect = true;

					$userWhere = "
						AND U.ID IN (
							SELECT DISTINCT UA.USER_ID
							FROM
							b_user_access UA
							INNER JOIN b_sonet_log_follow LFW ON LFW.USER_ID = UA.USER_ID
							WHERE
								UA.ACCESS_CODE IN (" . implode(', ', array_map(static function($item) use ($DB) { return "'" . $DB->forSql($item) . "'"; }, $logRightFilterValue)) . ")
								AND LFW.TYPE = 'Y'
								AND (LFW.CODE = 'L" . $logId . "' OR LFW.CODE = '**')
						)
					";

					$followJoin = " 
						LEFT JOIN b_sonet_log_follow LFW2 ON LFW2.USER_ID = U.ID AND (LFW2.CODE = 'L" . $logId . "' AND LFW2.TYPE = 'N')
					";
					$followWhere = "
						AND LFW2.USER_ID IS NULL
					";
				}
				else
				{
					$userWhere = "
						AND (
							0=1
							" . (
								$IsForAllAccessOnly !== 'N' || $bForAllAccess
									? "OR (SLR.GROUP_CODE IN ('AU', 'G2'))"
									: ''
							) . "
							" . $userLogRightsIntersectCondition . "
						)
					";
				}


			}
		}

		$strSQL = "
			SELECT DISTINCT
				U.ID as ID
				,".($bDecrement ? "-1" : "1")." as CNT
				,".$DB->IsNull("SLS.SITE_ID", "'**'")." as SITE_ID
				,".$params['CODE']." as CODE,
				0 as SENT
				".($tagSet ? ", '".$DB->ForSQL($tagSet)."' as TAG" : "")."
				" . ($bSetTimestamp ? ', ' . CDatabase::currentTimeFunction() . ' as TIMESTAMP_X' : '') . "
			FROM
				b_user U
				INNER JOIN b_sonet_log_right SLR ON SLR.LOG_ID = ".$logId."
				".($bGroupCounters ? "INNER JOIN b_sonet_log_right SLR0 ON SLR0.LOG_ID = SLR.LOG_ID ": "")."
				".(
					!$bForAllAccess && !$useUASubSelect
						? 'INNER JOIN b_user_access UA ON UA.USER_ID = U.ID' . ($logRightFilterValue ? ' AND (UA.ACCESS_CODE = SLR.GROUP_CODE)' : '')
						: ''
				)."
				LEFT JOIN b_sonet_log_site SLS ON SLS.LOG_ID = SLR.LOG_ID
				".($followJoin !== '' ? $followJoin : "")."
				" . $viewJoin . "
				".(!$bGroupCounters && !$intranetInstalled ? "LEFT JOIN b_sonet_log_smartfilter SLSF ON SLSF.USER_ID = U.ID " : "")."

			WHERE
				U.ACTIVE = 'Y'
				AND U.LAST_ACTIVITY_DATE IS NOT NULL
				AND U.LAST_ACTIVITY_DATE > ".CSocNetLogCounter::dbWeeksAgo(2)."
				AND CASE WHEN U.EXTERNAL_AUTH_ID IN ('".implode("','", \Bitrix\Main\UserTable::getExternalUserTypes())."') THEN 'N' ELSE 'Y' END = 'Y'
				".(
					(
						$type === CSocNetLogCounter::TYPE_LOG_COMMENT
						||
						(	array_key_exists("USE_CB_FILTER", $arSocNetAllowedSubscribeEntityTypesDesc[$entity_type])
							&& $arSocNetAllowedSubscribeEntityTypesDesc[$entity_type]["USE_CB_FILTER"] === "Y"
						)
					)
					&& (int)$created_by_id > 0
					&& !$bSendToAuthor
						? "AND U.ID <> ".$created_by_id
						: ""
				)."
				".($bGroupCounters ? "AND (SLR0.GROUP_CODE like 'SG%' AND SLR0.GROUP_CODE NOT LIKE 'SG%\_%')": "").
				$userWhere."
				".
				($followWhere !== '' ? $followWhere : "").
				$viewWhere . "
		";

		if($bGroupCounters)
		{
			return $strSQL;
		}

		if (
			$visibleFilter <> ''
			|| $transportFilter <> ''
		)
		{
			$strSQL .= "
				AND	
				(
					EXISTS(
						SELECT ID
						FROM b_sonet_log_events SLE
						WHERE
							SLE.USER_ID = U.ID
							AND SLE.ENTITY_TYPE = '".$entity_type."'
							AND SLE.ENTITY_CB = 'N'
							AND SLE.ENTITY_ID = ".$entity_id."
							AND SLE.EVENT_ID = '".$event_id."'
							".$transportFilter."
							".$visibleFilter."
					)";

			if (
				array_key_exists("USE_CB_FILTER", $arSocNetAllowedSubscribeEntityTypesDesc[$entity_type])
				&& $arSocNetAllowedSubscribeEntityTypesDesc[$entity_type]["USE_CB_FILTER"] === "Y"
				&& (int)$created_by_id > 0
			)
			{
				$strSQL .= "
				OR
				(
					EXISTS(
						SELECT ID
						FROM b_sonet_log_events SLE
						WHERE
							SLE.USER_ID = U.ID
							AND SLE.ENTITY_CB = 'Y'
							AND SLE.ENTITY_ID = ".$created_by_id."
							AND SLE.EVENT_ID = '".$event_id."'
							".$transportFilter."
							".$visibleFilter."
					)
				)";
			}

			$strSQL .= "
			OR
			(
				(
					NOT EXISTS(
						SELECT ID
						FROM b_sonet_log_events SLE
						WHERE
							SLE.USER_ID = U.ID
							AND SLE.ENTITY_TYPE = '".$entity_type."'
							AND SLE.ENTITY_CB = 'N'
							AND SLE.ENTITY_ID = ".$entity_id."
							AND SLE.EVENT_ID = '".$event_id."'
					)
					OR
					EXISTS(
						SELECT ID
						FROM b_sonet_log_events SLE
						WHERE
							SLE.USER_ID = U.ID
							AND SLE.ENTITY_TYPE = '".$entity_type."'
							AND SLE.ENTITY_CB = 'N'
							AND SLE.ENTITY_ID = ".$entity_id."
							AND SLE.EVENT_ID = '".$event_id."'
							AND ".($visibleFilter ? "SLE.VISIBLE = 'I'" : "SLE.TRANSPORT = 'I'")."
					)
				)";

			if (
				array_key_exists("USE_CB_FILTER", $arSocNetAllowedSubscribeEntityTypesDesc[$entity_type])
				&& $arSocNetAllowedSubscribeEntityTypesDesc[$entity_type]["USE_CB_FILTER"] === "Y"
				&& (int)$created_by_id > 0
			)
			{
				$strSQL .= "
				AND
				(
					NOT EXISTS(
						SELECT ID
						FROM b_sonet_log_events SLE
						WHERE
							SLE.USER_ID = U.ID
							AND SLE.ENTITY_CB = 'Y'
							AND SLE.ENTITY_ID = ".$created_by_id."
							AND SLE.EVENT_ID = '".$event_id."'
					)
					OR
					EXISTS(
						SELECT ID
						FROM b_sonet_log_events SLE
						WHERE
							SLE.USER_ID = U.ID
							AND SLE.ENTITY_CB = 'Y'
							AND SLE.ENTITY_ID = ".$created_by_id."
							AND SLE.EVENT_ID = '".$event_id."'
							AND ".($visibleFilter ? "SLE.VISIBLE = 'I'" : "SLE.TRANSPORT = 'I'")."
					)

				)";
			}

			$strSQL .= "
				AND
				(
					EXISTS(
						SELECT ID
						FROM b_sonet_log_events SLE
						WHERE
							SLE.USER_ID = U.ID
							AND SLE.ENTITY_TYPE = '".$entity_type."'
							AND SLE.ENTITY_CB = 'N'
							AND SLE.ENTITY_ID = ".$entity_id."
							AND SLE.EVENT_ID = 'all'
							".$transportFilter."
							".$visibleFilter."
					)";

			if (
				array_key_exists("USE_CB_FILTER", $arSocNetAllowedSubscribeEntityTypesDesc[$entity_type])
				&& $arSocNetAllowedSubscribeEntityTypesDesc[$entity_type]["USE_CB_FILTER"] === "Y"
				&& (int)$created_by_id > 0
			)
			{
				$strSQL .= "
					OR
					(
						EXISTS(
							SELECT ID
							FROM b_sonet_log_events SLE
							WHERE
								SLE.USER_ID = U.ID
								AND SLE.ENTITY_CB = 'Y'
								AND SLE.ENTITY_ID = ".$created_by_id."
								AND SLE.EVENT_ID = 'all'
								".$transportFilter."
								".$visibleFilter."
						)
					)";
			}

			$strSQL .= "
					OR
					(
						(
							NOT EXISTS(
								SELECT ID
								FROM b_sonet_log_events SLE
								WHERE
									SLE.USER_ID = U.ID
									AND SLE.ENTITY_TYPE = '".$entity_type."'
									AND SLE.ENTITY_CB = 'N'
									AND SLE.ENTITY_ID = ".$entity_id."
									AND SLE.EVENT_ID = 'all'
							)
							OR
							EXISTS(
								SELECT ID
								FROM b_sonet_log_events SLE
								WHERE
									SLE.USER_ID = U.ID
									AND SLE.ENTITY_TYPE = '".$entity_type."'
									AND SLE.ENTITY_CB = 'N'
									AND SLE.ENTITY_ID = ".$entity_id."
									AND SLE.EVENT_ID = 'all'
									AND ".($visibleFilter ? "SLE.VISIBLE = 'I'" : "SLE.TRANSPORT = 'I'")."
							)
						)
						AND ";

			if (
				array_key_exists("USE_CB_FILTER", $arSocNetAllowedSubscribeEntityTypesDesc[$entity_type])
				&& $arSocNetAllowedSubscribeEntityTypesDesc[$entity_type]["USE_CB_FILTER"] === "Y"
				&& (int)$created_by_id > 0
			)
			{
				$strSQL .= "
						(
							NOT EXISTS(
								SELECT ID
								FROM b_sonet_log_events SLE
								WHERE
									SLE.USER_ID = U.ID
									AND SLE.ENTITY_CB = 'Y'
									AND SLE.ENTITY_ID = ".$created_by_id."
									AND SLE.EVENT_ID = 'all'
							)
							OR
							EXISTS(
								SELECT ID
								FROM b_sonet_log_events SLE
								WHERE
									SLE.USER_ID = U.ID
									AND SLE.ENTITY_CB = 'Y'
									AND SLE.ENTITY_ID = ".$created_by_id."
									AND SLE.EVENT_ID = 'all'
									AND ".($visibleFilter ? "SLE.VISIBLE = 'I'" : "SLE.TRANSPORT = 'I'")."
							)
						)
						AND
						(
						";
			}

			if ($strOfEntities <> '')
			{
					$strSQL .= "
						(
							".$strOfEntities."
							AND
							(
								EXISTS(
									SELECT ID
									FROM b_sonet_log_events SLE
									WHERE
										SLE.USER_ID = U.ID
										AND SLE.ENTITY_TYPE = '".$entity_type."'
										AND SLE.ENTITY_ID = 0
										AND SLE.ENTITY_MY = 'Y'
										AND SLE.EVENT_ID = '".$event_id."'
										".$transportFilter."
										".$visibleFilter."
								)
								OR
								(
									(
										EXISTS(
											SELECT ID
											FROM b_sonet_log_events SLE
											WHERE
												SLE.USER_ID = U.ID
												AND SLE.ENTITY_TYPE = '".$entity_type."'
												AND SLE.ENTITY_ID = 0
												AND SLE.ENTITY_MY = 'Y'
												AND SLE.EVENT_ID = '".$event_id."'
												AND ".($visibleFilter ? "SLE.VISIBLE = 'I'" : "SLE.TRANSPORT = 'I'")."
										)
										OR
										NOT EXISTS(
											SELECT ID
											FROM b_sonet_log_events SLE
											WHERE
												SLE.USER_ID = U.ID
												AND SLE.ENTITY_TYPE = '".$entity_type."'
												AND SLE.ENTITY_ID = 0
												AND SLE.ENTITY_MY = 'Y'
												AND SLE.EVENT_ID = '".$event_id."'
										)
									)
									AND
									(
										EXISTS(
											SELECT ID
											FROM b_sonet_log_events SLE
											WHERE
												SLE.USER_ID = U.ID
												AND SLE.ENTITY_TYPE = '".$entity_type."'
												AND SLE.ENTITY_ID = 0
												AND SLE.ENTITY_MY = 'Y'
												AND SLE.EVENT_ID = 'all'
												".$transportFilter."
												".$visibleFilter."
										)
									)
								)
							)
						)
						OR
					";
			}

			$strSQL .=	"
							(
								EXISTS(
									SELECT ID
									FROM b_sonet_log_events SLE
									WHERE
										SLE.USER_ID = U.ID
										AND SLE.ENTITY_TYPE = '".$entity_type."'
										AND SLE.ENTITY_ID = 0
										AND SLE.ENTITY_MY = 'N'
										AND SLE.EVENT_ID = '".$event_id."'
										".$transportFilter."
										".$visibleFilter."
								)
								OR
								(
									(
										EXISTS(
											SELECT ID
											FROM b_sonet_log_events SLE
											WHERE
												SLE.USER_ID = U.ID
												AND SLE.ENTITY_TYPE = '".$entity_type."'
												AND SLE.ENTITY_ID = 0
												AND SLE.ENTITY_MY = 'N'
												AND SLE.EVENT_ID = '".$event_id."'
												AND ".($visibleFilter ? "SLE.VISIBLE = 'I'" : "SLE.TRANSPORT = 'I'")."
											)
										OR
										NOT EXISTS(
											SELECT ID
											FROM b_sonet_log_events SLE
											WHERE
												SLE.USER_ID = U.ID
												AND SLE.ENTITY_TYPE = '".$entity_type."'
												AND SLE.ENTITY_ID = 0
												AND SLE.ENTITY_MY = 'N'
												AND SLE.EVENT_ID = '".$event_id."'
										)
									)
									AND
									(
										EXISTS(
											SELECT ID
											FROM b_sonet_log_events SLE
											WHERE
												SLE.USER_ID = U.ID
												AND SLE.ENTITY_TYPE = '".$entity_type."'
												AND SLE.ENTITY_ID = 0
												AND SLE.ENTITY_MY = 'N'
												AND SLE.EVENT_ID = 'all'
										".$transportFilter."
										".$visibleFilter."
										)
										OR
										EXISTS(
											SELECT ID
											FROM b_sonet_log_events SLE
											WHERE
												SLE.USER_ID = U.ID
												AND SLE.ENTITY_TYPE = '".$entity_type."'
												AND SLE.ENTITY_ID = 0
												AND SLE.ENTITY_MY = 'N'
												AND SLE.EVENT_ID = 'all'
												AND ".($visibleFilter ? "SLE.VISIBLE = 'I'" : "SLE.TRANSPORT = 'I'")."
										)
										OR
										NOT EXISTS(
											SELECT ID
											FROM b_sonet_log_events SLE
											WHERE
												SLE.USER_ID = U.ID
												AND SLE.ENTITY_TYPE = '".$entity_type."'
												AND SLE.ENTITY_ID = 0
												AND SLE.ENTITY_MY = 'N'
												AND SLE.EVENT_ID = 'all'
										)
									)
								)
							)";

			if (
				array_key_exists("USE_CB_FILTER", $arSocNetAllowedSubscribeEntityTypesDesc[$entity_type])
				&& $arSocNetAllowedSubscribeEntityTypesDesc[$entity_type]["USE_CB_FILTER"] === "Y"
				&& (int)$created_by_id > 0
			)
				$strSQL .="
						)";

			$strSQL .="
					)
				)
			)

			)";
		}

		return $strSQL;
	}

	/** @deprecated */
	public static function GetValueByUserID($user_id, $site_id = SITE_ID)
	{
		global $DB;
		$user_id = (int)$user_id;

		if ($user_id <= 0)
			return false;

		$strSQL = "
			SELECT SUM(CNT) CNT
			FROM b_sonet_log_counter
			WHERE USER_ID = ".$user_id."
			AND (SITE_ID = '".$site_id."' OR SITE_ID = '**')
			AND CODE = '**'
		";

		$dbRes = $DB->Query($strSQL, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		if ($arRes = $dbRes->Fetch())
		{
			return $arRes["CNT"];
		}

		return 0;
	}

	/** @deprecated */
	public static function GetCodeValuesByUserID($user_id, $site_id = SITE_ID)
	{
		global $DB;
		$result = array();
		$user_id = (int)$user_id;

		if($user_id > 0)
		{
			$strSQL = "
				SELECT CODE, SUM(CNT) CNT
				FROM b_sonet_log_counter
				WHERE USER_ID = ".$user_id."
				AND (SITE_ID = '".$site_id."' OR SITE_ID = '**')
				GROUP BY CODE
			";

			$dbRes = $DB->Query($strSQL, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			while ($arRes = $dbRes->Fetch())
				$result[$arRes["CODE"]] = $arRes["CNT"];
		}

		return $result;
	}

	/** @deprecated */
	public static function GetLastDateByUserAndCode($user_id, $site_id = SITE_ID, $code = "**")
	{
		global $DB;
		$result = 0;
		$user_id = (int)$user_id;

		if($user_id > 0)
		{
			$strSQL = "
				SELECT ".$DB->DateToCharFunction("LAST_DATE", "FULL")." LAST_DATE
				FROM b_sonet_log_counter
				WHERE USER_ID = ".$user_id."
				AND (SITE_ID = '".$DB->ForSql($site_id)."' OR SITE_ID = '**')
				AND CODE = '".$DB->ForSql($code)."'
			";

			$dbRes = $DB->Query($strSQL, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if ($arRes = $dbRes->Fetch())
			{
				$result = MakeTimeStamp($arRes["LAST_DATE"]);
			}
		}

		return $result;
	}

	/** @deprecated */
	public static function GetList($arFilter = Array(), $arSelectFields = [])
	{
		global $DB;

		if (count($arSelectFields) <= 0)
		{
			$arSelectFields = array("LAST_DATE", "PAGE_SIZE", "PAGE_LAST_DATE_1");
		}

		// FIELDS -->
		$arFields = array(
			"USER_ID" => Array("FIELD" => "SLC.USER_ID", "TYPE" => "int"),
			"SITE_ID" => Array("FIELD" => "SLC.SITE_ID", "TYPE" => "string"),
			"CODE" => Array("FIELD" => "SLC.CODE", "TYPE" => "string"),
			"LAST_DATE" => Array("FIELD" => "SLC.LAST_DATE", "TYPE" => "datetime"),
			"PAGE_SIZE" => array("FIELD" => "SLC.PAGE_SIZE", "TYPE" => "int"),
			"PAGE_LAST_DATE_1" => Array("FIELD" => "SLC.PAGE_LAST_DATE_1", "TYPE" => "datetime"),
		);
		// <-- FIELDS

		$arSqls = CSocNetGroup::PrepareSql($arFields, array(), $arFilter, false, $arSelectFields);

		$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", "", $arSqls["SELECT"]);

		$strSql =
			"SELECT ".$arSqls["SELECT"]." ".
			"FROM b_sonet_log_counter SLC ".
			"	".$arSqls["FROM"]." ";

		if ($arSqls["WHERE"] <> '')
		{
			$strSql .= "WHERE ".$arSqls["WHERE"]." ";
		}

		return $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
	}
}
