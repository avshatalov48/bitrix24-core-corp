<?
define('BX_SECURITY_SHOW_MESSAGE', 1);
define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_FILE_PERMISSIONS", true);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

global $APPLICATION, $USER, $DB;

if (!CBXFeatures::IsFeatureEnabled('timeman'))
	die();

if(check_bitrix_sessid() && $USER->IsAuthorized())
{
	IncludeModuleLangFile(__FILE__);

	$action = $_REQUEST['action'];
	if (!CModule::IncludeModule('timeman'))
	{
		echo "{error: 'timeman module not installed', type: 'fatal'}";
	}
	else
	{
		if ($action == 'clock')
		{
			$start_time = intval($_REQUEST['start_time']);

			if ($start_time > 0)
				$start_time = CTimeMan::FormatTime($start_time, true);
			else
				$start_time = '';

			require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/tools/clock.php");

			$clock_input_id_1 = 'tm_'.rand(0, 100000); $clock_input_id_2 = 'tm_'.rand(0, 100000);
			$clock1 = ''; $clock2 = '';
			ob_start();
			CClock::Show(
				array(
					'inputId' => $clock_input_id_1,
					'inputName' => $_REQUEST['clock_id'],
					'view' => 'inline',
					'showIcon' => false,
					'initTime' => $start_time
				)
			);

			$clock1 = ob_get_contents();
			ob_end_clean();

			if ($_REQUEST['clock_id_1'])
			{
				$start_time_1 = intval($_REQUEST['start_time_1']);

				if ($start_time_1 > 0)
					$start_time_1 = CTimeMan::FormatTime($start_time_1, true);
				else
					$start_time_1 = '';

				ob_start();
				CClock::Show(
					array(
						'inputId' => $clock_input_id_2,
						'inputName' => $_REQUEST['clock_id_1'],
						'view' => 'inline',
						'showIcon' => false,
						'initTime' => $start_time_1
					)
				);
				$clock2 = ob_get_contents();
				ob_end_clean();
			}

			if (!$clock2)
			{
				echo $clock1;
?><script type="text/javascript">BX.onCustomEvent('onTMClockRegister', [{<?=CUtil::JSEscape($_REQUEST['clock_id'])?>:'<?=$clock_input_id_1?>'}])</script><?
			}
			else
			{
				echo '<table class="tm-double-clock-table" align="center"><tr><td class="tm-double-clock-table-row tm-double-clock-table-first">'.$clock1.'</td><td class="tm-double-clock-table-row tm-double-clock-table-second">'.$clock2.'</td></tr></table>';
?><script type="text/javascript">BX.onCustomEvent('onTMClockRegister', [{<?=CUtil::JSEscape($_REQUEST['clock_id'])?>:'<?=$clock_input_id_1?>',<?=CUtil::JSEscape($_REQUEST['clock_id_1'])?>:'<?=$clock_input_id_2?>'}])</script><?
			}
		}
		elseif ($action == 'tasks')
		{
			if (!CModule::IncludeModule('tasks'))
				die;

			$APPLICATION->ShowAjaxHead();

			/*
			related to http://jabber.bx/view.php?id=19527
			// TODO: needs good synchronization first
			$info = CTimeMan::GetRuntimeInfo(true);
			$arTasksIds = array();
			if (is_array($info) && isset($info['TASKS']))
			{
				foreach ($info['TASKS'] as $arTask)
					$arTasksIds[] = (int) $arTask['ID'];
			}
			*/

			$APPLICATION->IncludeComponent(
				"bitrix:tasks.task.selector",
				".default",
				array(
					// TODO: needs good synchronization first "MULTIPLE" => "Y",
					"MULTIPLE" => "N",
					"NAME" => "TIMEMAN_TASKS",
					// TODO: needs good synchronization first "VALUE" => $arTasksIds,
					"VALUE" => '',
					"POPUP" => "N",
					"ON_SELECT" => "TIMEMAN_ADD_TASK_" . $_REQUEST['suffix'],
					"PATH_TO_TASKS_TASK" => str_replace('#USER_ID#', $USER->GetID(), COption::GetOptionString('intranet', 'path_task_user_entry', '/company/personal/user/#USER_ID#/tasks/task/view/#TASK_ID#/', $_REQUEST['site_id'])),
					"SITE_ID" => $_REQUEST['site_id'],
					"FILTER" => array(
						'DOER' => $USER->GetID(),
						'STATUS' => array(
							-2,
							-1,
							CTasks::STATE_NEW,
							CTasks::STATE_PENDING,
							CTasks::STATE_IN_PROGRESS,
							CTasks::STATE_DEFERRED
						)
					),
					"SELECT" => array('ID', 'TITLE', 'STATUS'),
					'HIDE_ADD_REMOVE_CONTROLS' => 'Y'
				),
				null,
				array("HIDE_ICONS" => "Y")
			);

		}
		elseif($action == "editor")
		{
			$APPLICATION->ShowAjaxHead();

			CModule::IncludeModule("fileman");
			$LHE = new CLightHTMLEditor();

			$LHE->Show(array(
				'id' => ((strlen($_REQUEST["obname"])>0)?$_REQUEST["obname"]:"oReportLHE"),
				'content' => "",
				'inputName' => "ITEM_DESCRIPTION",
				'inputId' => "",
				'width' => "100%",
				'height' => "200px",
				'bUseFileDialogs' => false,
				'jsObjName' => ((strlen($_REQUEST["obname"])>0)?$_REQUEST["obname"]:"oReportLHE"),
				'toolbarConfig' => Array(
					'Bold', 'Italic', 'Underline', 'Strike',
					'ForeColor','FontList', 'FontSizeList',
					'RemoveFormat',
					//'Quote', 'Code',
					'Image',
					'Table',
					'InsertOrderedList',
					'InsertUnorderedList',
					'Source'
				),
				//'smileCountInToolbar' => 4,
				'bResizable' => false,
				'bAutoResize' => false
			));
		}
		elseif ($action == "upload_attachment")
		{
			$report = null;
			$report_id = intval($_POST["report_id"]);
			if ($report_id > 0)
			{
				$user_id = intval($_REQUEST['user_id']);
				if($user_id <= 0)
				{
					$user_id = $USER->GetID();
				}

				$bCanReadUser = $user_id == $USER->GetID();
				if(!$bCanReadUser)
				{
					$arAccessUsers = CTimeMan::GetAccess();
					$bCanReadAll = in_array('*', $arAccessUsers['READ']);
					$bCanReadUser = $bCanReadAll || in_array($user_id, $arAccessUsers["READ"]);
				}

				if($bCanReadUser)
				{
					$dbreport = CTimeManReportFull::GetList(
						array("ID"=>"asc"),
						array("ID"=>$report_id,"USER_ID"=>$user_id)
					);
					$report = $dbreport->fetch();
				}
			}
			elseif (!is_array($_SESSION['report_files']))
			{
				$_SESSION['report_files'] = array();
			}

			if ($_POST["mode"] == "upload")
			{
				$arResult = array();
					$count = sizeof($_FILES["report-attachments"]["name"]);

				for($i = 0; $i < $count; $i++)
				{
					$arFile = array(
						"name" => $_FILES["report-attachments"]["name"][$i],
						"size" => $_FILES["report-attachments"]["size"][$i],
						"tmp_name" => $_FILES["report-attachments"]["tmp_name"][$i],
						"type" => $_FILES["report-attachments"]["type"][$i],
						"MODULE_ID" => "timeman"
					);

					$fileID = CFile::SaveFile($arFile, "timeman");
					$tmp = array(
						"name" => $_FILES["report-attachments"]["name"][$i],
						"fileID" => $fileID
					);
					if ($fileID)
					{
						$arResult[] = $tmp;
					}

				}

				if(count($arResult)>0)
				{
					if ($report)
					{
						$arCurFiles = unserialize($report["FILES"]);
						$arFiles = (is_array($arCurFiles) && count($arCurFiles)>0)?array_merge($arCurFiles,$arResult):$arResult;
						CTimeManReportFull::Update($report["ID"],array("FILES"=>$arFiles));
					}
					else
					{
						$_SESSION['report_files'] = array_merge($_SESSION['report_files'], $arResult);
					}
				}

				$APPLICATION->RestartBuffer();
				Header('Content-Type: text/html; charset='.LANG_CHARSET);
				?>
					<script type="text/javascript">
						window.parent.window.<?echo $_POST["form_id"]?>.RefreshUpload(<?php echo CUtil::PhpToJsObject($arResult);?>, <?php echo intval($_POST["uniqueID"])?>);
					</script>
				<?
			}
			elseif ($_POST["mode"] == "delete")
			{
				if ($report)
				{
					$arFiles = unserialize($report["FILES"]);
				}
				else
				{
					$arFiles = $_SESSION['report_files'];
				}

				if(is_array($arFiles))
				{
					foreach($arFiles as $key=>$file)
					{
						if($file["fileID"] == $_POST["fileID"])
						{
							CFile::Delete(intval($_POST["fileID"]));
							unset($arFiles[$key]);

							if(isset($_SESSION['report_files']))
							{
								unset($_SESSION['report_files'][$key]);
								$_SESSION['report_files'] = array_values($_SESSION['report_files']);
							}

							if($report)
							{
								CTimeManReportFull::Update($report["ID"], array("FILES"=>array_values($arFiles)));
							}

							break;
						}
					}
				}
			}
		}
		elseif ($action == "get_attachment")
		{
			$result['FILE'] = null;
			$report_id = intval($_REQUEST["report_id"]);

			$arFiles = null;

			if($report_id > 0)
			{
				$report = null;
				$user_id = intval($_REQUEST['user_id']);
				if($user_id <= 0)
				{
					$user_id = $USER->GetID();
				}

				$bCanReadUser = $user_id == $USER->GetID();
				if(!$bCanReadUser)
				{
					$arAccessUsers = CTimeMan::GetAccess();
					$bCanReadAll = in_array('*', $arAccessUsers['READ']);
					$bCanReadUser = $bCanReadAll || in_array($user_id, $arAccessUsers["READ"]);
				}

				if($bCanReadUser)
				{
					$dbreport = CTimeManReportFull::GetList(
						array("ID"=>"asc"),
						array("ID"=>$report_id,"USER_ID"=>$user_id)
					);
					$report = $dbreport->fetch();
				}

				if(is_array($report) && strlen($report['FILES']) > 0)
				{
					$arFiles = unserialize($report['FILES']);
				}
			}
			elseif(isset($_SESSION['report_files']))
			{
				$arFiles = $_SESSION['report_files'];
			}

			if(is_array($arFiles))
			{
				$fileId = intval($_REQUEST["fid"]);

				if(is_array($arFiles))
				{
					foreach($arFiles as $file)
					{
						if($fileId == $file['fileID'])
						{
							$result["FILE"] = CFile::GetFileArray($fileId);
							break;
						}
					}
				}
			}

			if (!is_array($result["FILE"]))
			{
				require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/prolog_after.php");
				ShowError("File not found");
				require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog.php");
				die();
			}

			set_time_limit(0);

			CFile::ViewByUser($result["FILE"]);
		}
		else
		{
			$bAdminAction = substr($action, 0, 6) == 'admin_' || $action == 'calendar_show' || $action == 'add_comment_entry' || $action == 'add_comment_full_report' || $action == 'user_report_edit' || $action == 'report_full_setting';
			if (
				!CTimeMan::CanUse($bAdminAction)
			)
			{
				echo "{error: 'access denied', type: 'fatal'}";
			}
			else
			{
				CUtil::JSPostUnescape();

				$error = false;
				$bReturnRes = false;

				$bGetFullInfo = $_REQUEST['full'] == 'Y';

				$obUser = CTimeManUser::instance();
				$obUser->SITE_ID = $_REQUEST['site_id'];

				switch($action)
				{
					case "report_full_setting":
						$bReturnRes = true;
						if ($_POST["id"])
							$ID = intval($_POST["id"]);

						if ($_POST["object"] == 'user')
						{
							$arAccessUsers = CTimeMan::GetAccess();
							$bCanEditAll = in_array('*', $arAccessUsers['WRITE']);
							$bCanReadAll = in_array('*', $arAccessUsers['READ']);
							if($bCanReadAll || in_array($ID,$arAccessUsers["READ"]))
							{
								$tmr = new CUserReportFull($ID);
								$res = $tmr->GetSettings(true);
							}
						}
						elseif ($_POST["object"] == 'dep')
						{
							$current_user = $USER->GetID();
							$arSubordination = CIntranetUtils::GetSubordinateDepartments($current_user,true);
							if (in_array($ID,$arSubordination) || CTimeMan::IsAdmin())
							{
								$res = CReportSettings::GetSectionSettings($ID, true);
							}
						}

					break;
					case 'user_report_edit':
						$bReturnRes = true;

						$ID = intval($_POST["report_id"]);

						$sanitizer = new CBXSanitizer();
						$sanitizer->ApplyDoubleEncode(false);
						$sanitizer->SetLevel(CBXSanitizer::SECURE_LEVEL_LOW);

						$REPORT = $sanitizer->SanitizeHtml($_POST["report_text"]);
						$PLAN = $sanitizer->SanitizeHtml($_POST["plan_text"]);

						$dbReport = CTimeManReportFull::GetByID($ID);
						$arReport = $dbReport->Fetch();
						$USER_ID = $arReport['USER_ID'];

						$arAccessUsers = CTimeMan::GetAccess();

						$bCanEditAll = in_array('*', $arAccessUsers['WRITE']);
						$bCanEditUser = in_array($USER_ID, $arAccessUsers['WRITE']);

						$res = array("success"=>false);

						if (
							$arReport["MARK"]=="X"
							&& ($bCanEditAll || $bCanEditUser || CTimeMan::IsAdmin() || $USER_ID == $USER->GetID())
						)
						{
							if (CTimeManReportFull::Update($ID, array("REPORT"=>$REPORT,"PLANS"=>$PLAN)))
							{
								CReportNotifications::MessageUpdate($ID);

								$CACHE_MANAGER->Clean(CUserReportFull::getInfoCacheId($USER_ID), 'timeman_report_info');

								$res = array("success"=>true);
							}
						}

					break;
					case 'admin_report_full':
						$bReturnRes = true;

						$ID = intval($_POST["report_id"]);
						$USER_ID = intval($_POST["user_id"]);
						$OBJID = intval($_POST["id"]);

						$arAccessUsers = CTimeMan::GetAccess();

						$bCanEditAll = in_array('*', $arAccessUsers['WRITE']);
						$bCanReadAll = in_array('*', $arAccessUsers['READ']);
						$bCanReadUser = (in_array($USER_ID,$arAccessUsers["READ"]) || $bCanReadAll);
						$bCanWriteUser = (in_array($USER_ID,$arAccessUsers["WRITE"]) || $bCanEditAll);

						if ($USER_ID>0 && $bCanWriteUser && ($_POST["approve"]))
						{
							if ($_POST["approve"] == "Y")
							{
								$dbrep = CTimeManReportFull::GetByID($ID);
								$rep = $dbrep->Fetch();
								if(is_array($rep) && $USER_ID == $rep['USER_ID'])
								{
									$arFields = array(
										"MARK" => (in_array($_POST["mark"], array("G","B","N")) !==false) ? $_POST["mark"] : "X"
									);

									if ($arFields["MARK"] != "X")
									{
										$arFields["APPROVER"] = $USER->GetID();
										$arFields["APPROVE"] = "Y";
										$arFields["APPROVE_DATE"] = ConvertTimeStamp(time(),"FULL");
									}
									else
									{
										$arFields["APPROVE"] = "N";
										$arFields["APPROVER"] = 0;
										$arFields["APPROVE_DATE"] = "";
									}

									CTimeManReportFull::Update($ID, $arFields);

									$CACHE_MANAGER->Clean(CUserReportFull::getInfoCacheId($USER_ID), 'timeman_report_info');

									CReportNotifications::MessageUpdate($ID, $rep, $arFields);
								}
							}
						}
						elseif($_POST["mode"] && $OBJID>0)
						{
							$arFields = array(
								"UF_REPORT_PERIOD"=>$_POST["mode"],
								"UF_TM_TIME"=>(IsAmPmMode()?convertTimeToMilitary($_POST["time"], 'H:MI T', 'HH:MI'):$_POST["time"]),
								"UF_TM_REPORT_DATE"=>$_POST["date"],
								"UF_TM_DAY"=>$_POST["day"],
								"ID"=>$OBJID
							);

							if ($_POST["object"] == "user")
							{
								$bCanEdit = ((in_array($OBJID,$arAccessUsers["WRITE"]) && $OBJID!=$USER->GetID())
									|| $bCanEditAll || CTimeMan::IsAdmin()
								);
								if($bCanEdit)
								{
									$arReportUser = new CUserReportFull($OBJID);
									$res= $arReportUser->SetPeriod($arFields);

									$CACHE_MANAGER->Clean(CReportSettings::getSettingsCacheId($OBJID), 'timeman_report_settings');
									$CACHE_MANAGER->Clean(CUserReportFull::getInfoCacheId($OBJID), 'timeman_report_info');

								}
							}
							elseif($_POST["object"] == "dep")
							{
								$arSubordination = CIntranetUtils::GetSubordinateDepartments($USER->GetID(),true);
								$bCanEdit = (in_array($OBJID,$arSubordination) || CTimeMan::IsAdmin());
								if($bCanEdit)
								{
									$res = CTimeManReportFull::SetPeriodSection($arFields);

									$CACHE_MANAGER->CleanDir('timeman_report_settings');
									$CACHE_MANAGER->CleanDir('timeman_report_info');
								}
							}
						}

						if ($bCanReadUser && !$_POST["mode"])
						{
							$dbRes = CUser::GetList(
								$by = 'ID', $order = 'ASC',
								array('ID' => $USER_ID),
								array('SELECT' => array('UF_*'))
							);
							$arUser = $dbRes->GetNext();
							$arUser['PHOTO'] =
								$arUser['PERSONAL_PHOTO'] > 0
								? CIntranetUtils::InitImage($arUser['PERSONAL_PHOTO'], 100, 100, BX_RESIZE_IMAGE_EXACT)
								: array();

							$arManagers = CTimeMan::GetUserManagers($USER_ID);
							$arManagers[] = $USER_ID;
							if (!is_array($arManagers) || count($arManagers) <= 0)
								$arManagers = array($USER_ID);

							$user_url = COption::GetOptionString('intranet', 'path_user', '/company/personal/user/#USER_ID#/', $_REQUEST['site_id']);
							$dbManagers = CUser::GetList($by='ID', $order='ASC', array('ID' => implode('|', $arManagers)));

							$res["TO"] = array();
							$res["FROM"] = array();
							while ($manager = $dbManagers->Fetch())
							{
								$manager['PHOTO'] =
									$manager['PERSONAL_PHOTO'] > 0
									? CIntranetUtils::InitImage($manager['PERSONAL_PHOTO'], 100, 100, BX_RESIZE_IMAGE_EXACT)
									: array();
								$arUserData = array(
									'ID' => $manager['ID'],
									'LOGIN' => $manager['LOGIN'],
									'NAME' => CUser::FormatName(CSite::GetNameFormat(false), $manager, true, true),
									'URL' => str_replace(array('#ID#', '#USER_ID#'), $manager['ID'], $user_url),
									'WORK_POSITION' => $manager['WORK_POSITION'],
									'PHOTO' => $manager['PHOTO']['CACHE']['src'],
								);

								if($USER_ID == $arUserData["ID"])
									$res["FROM"] = $arUserData;
								else
									$res["TO"][] = $arUserData;
							}

							if (count($res["TO"]) == 0)
								$res["TO"][] = $res["FROM"];

							$arFilter = array("ID"=>$ID,"USER_ID"=>$USER_ID);
							$arReportID = array();

							if ($_POST["empty_slider"])
								$arFilter = array("ACTIVE"=>"Y","USER_ID"=>$USER_ID);

							$dbres = CTimeManReportFull::GetList(array("USER_ID"=>"asc","ID"=>"asc"), $arFilter);
							$report = null;
							while($rep = $dbres->Fetch())
							{
								$arReportID[] = $rep["ID"];
								if($rep["ID"] == $ID)
								{
									$report = $rep;
								}
							}

							$res["REPORT_LIST"]=$arReportID;
							if(is_array($report))
							{
								$res["INFO"] = $report;

								if ($res["INFO"]['TASKS_ENABLED'] = (CBXFeatures::IsFeatureEnabled('Tasks') && CModule::IncludeModule('tasks')))
									$res["INFO"]['TASKS'] = unserialize($res["INFO"]['TASKS']);
								else
									unset($res["INFO"]['TASKS']);

								if ($res["INFO"]['CALENDAR_ENABLED'] = CBXFeatures::IsFeatureEnabled('Calendar'))
									$res["INFO"]['EVENTS'] = unserialize($res["INFO"]['EVENTS']);
								else
									unset($res["INFO"]['EVENTS']);

								if ($res["INFO"]['FILES'])
									$res["INFO"]['FILES'] = unserialize($res["INFO"]['FILES']);

								$res["INFO"]['CAN_EDIT'] = ($arUser['ID']!= $USER->GetID())&&($bCanEditAll || in_array($arUser['ID'], $arAccessUsers['WRITE']));
								$res["INFO"]['CAN_EDIT_TEXT'] = ($report["APPROVE"] == "Y")?"N":"Y";

								if ($report["DATE_FROM"]!=$report["DATE_TO"])
									$res["INFO"]["TEXT_TITLE"]= FormatDate('j F', MakeTimeStamp($report["DATE_FROM"]))." - ".FormatDate('j F', MakeTimeStamp($report["DATE_TO"]));
								else
									$res["INFO"]["TEXT_TITLE"]= FormatDate('j F', MakeTimeStamp($report["DATE_TO"]));

								$res["INFO"]["REPORT_STRIP_TAGS"] = strip_tags(nl2br($res["INFO"]["REPORT"]));
								$res["INFO"]["PLAN_STRIP_TAGS"] = strip_tags(nl2br($res["INFO"]["PLANS"]));
								$res["INFO"]["APPROVER_INFO"] = Array();

								if (intval($res["INFO"]["APPROVER"])>0)
								{
									$res["INFO"]["APPROVE_DATE"] = FormatDate($DB->DateFormatToPHP(FORMAT_DATETIME), MakeTimeStamp($res["INFO"]["APPROVE_DATE"]));

									foreach($res["TO"] as $manager)
									{
										if ($manager["ID"] == intval($res["INFO"]["APPROVER"]))
										{
											$res["INFO"]["APPROVER_INFO"] = $manager;
											break;
										}
									}

									if(!$res["INFO"]["APPROVER_INFO"])
									{
										$dbaprrove = CUser::GetList($by='ID', $order='ASC', array('ID' => intval($res["INFO"]["APPROVER"])));

										if($approver = $dbaprrove->Fetch())
										{
											$approver['PHOTO'] =
												$approver['PERSONAL_PHOTO'] > 0
												? CIntranetUtils::InitImage($approver['PERSONAL_PHOTO'], 100, 100, BX_RESIZE_IMAGE_EXACT)
												: array();

											$res["INFO"]["APPROVER_INFO"] = array(
												'ID' => $approver['ID'],
												'LOGIN' => $approver['LOGIN'],
												'NAME' => CUser::FormatName(CSite::GetNameFormat(false), $approver, true, true),
												'URL' => str_replace(array('#ID#', '#USER_ID#'), $approver['ID'], $user_url),
												'WORK_POSITION' => $approver['WORK_POSITION'],
												'PHOTO' => $approver['PHOTO']['CACHE']['src'],
											);
										}
									}
								}

								ob_start();
									$APPLICATION->IncludeComponent(
										"bitrix:timeman.topic.reviews",
										"",
										Array(
											"REPORT_ID"=>$ID
										),
									false
									);
									$res["COMMENTS"] = ob_get_contents();
								ob_end_clean();
							}
						}

					break;
					//carter
					case 'check_report':
						$bReturnRes = true;
						$obReportUser = new CUserReportFull;
						$force = false;
						if ($_REQUEST["force"] == "Y")
							$force = true;
						$res = $obReportUser->GetReportData($force);
					break;
					case 'get_task':
						$bReturnRes = true;
						$task_id = intval($_POST["task_id"]);
						$dbTasks = CTasks::GetList(array(), array(
									'ID' => $task_id,
									'RESPONSIBLE_ID' => $USER->GetID(),
								));
						if ($arTask = $dbTasks->Fetch())
						{
							$res = array(
								'ID' => $arTask['ID'],
								'PRIORITY' => $arTask['PRIORITY'],
								'STATUS' => $arTask['STATUS'],
								'TITLE' => $arTask['TITLE'],
								'TASK_CONTROL' => $arTask['TASK_CONTROL'],
								'TIME'=>$arTask['TIME'],
								'URL' => str_replace(
									array('#USER_ID#', '#TASK_ID#'),
									array($USER->GetID(), $arTask['ID']),
									COption::GetOptionString('intranet', 'path_task_user_entry', '/company/personal/user/#USER_ID#/tasks/task/view/#TASK_ID#/')
								)
							);
						}
					break;
					case 'save_full_report':
						$bReturnRes = true;
						$tm_user = new CUserReportFull;
						$curUser = $USER->GetID();
						$toUser = intval($_POST["TO_USER"]);
						$bSameUser = ($toUser == $curUser);
						if ($_POST['ACTIVE'])
						{
							if($_POST['DELAY'] == "Y")
								$res = $tm_user->Delay();

							$sanitizer = new CBXSanitizer();
							$sanitizer->ApplyDoubleEncode(false);
							$sanitizer->SetLevel(CBXSanitizer::SECURE_LEVEL_LOW);

							$arFields = Array(
								"DATE_TO"=>ConvertTimeStamp($_POST["DATE_TO"],"SHORT"),
								"DATE_FROM"=>ConvertTimeStamp($_POST["DATE_FROM"],"SHORT"),
								"MARK"=>"X",
								"TASKS"=>$_POST["TASKS"],
								"EVENTS"=>$_POST["EVENTS"],
								"ACTIVE"=>$_POST["ACTIVE"],
								"REPORT"=>$sanitizer->SanitizeHtml($_POST["REPORT"]),
								"PLANS"=>$sanitizer->SanitizeHtml($_POST["PLANS"])
							);

							if ($arFields["ACTIVE"]!="Y")
							{
								$arFields["EVENTS"] = Array();
								$arFields["TASKS"] = Array();
							}

							if ($_POST["TASKS_TIME"] && is_array($arFields["TASKS"]))
							{
								foreach($arFields["TASKS"] as $key=>$arTask)
									$arFields["TASKS"][$key]["TIME"] = $_POST["TASKS_TIME"][$key];
							}

							$ID = (intval($_POST["REPORT_ID"])>0)?$_POST["REPORT_ID"]:false;
							if ($ID == false)
							{
								//we have saved report?
								$dbres = CTimeManReportFull::GetList(Array("ID"=>"desc"),Array("ACTIVE"=>"N","USER_ID"=>$curUser),Array("ID"),Array("nTopCount"=>1));
								if($arCurrentReport = $dbres->Fetch())
									$ID = $arCurrentReport["ID"];
							}

							if ($bSameUser && $arFields["ACTIVE"] == "Y")
							{
								$arFields["APPROVE"] = "Y";
								$arFields["APPROVER"] = $curUser;
								$arFields["APPROVE_DATE"] = ConvertTimeStamp(time(),"FULL");
								$arFields["MARK"] = "N";
							}

							if($ID)
							{
								$dbReport = CTimeManReportFull::GetByID($ID);
								$arReport = $dbReport->Fetch();

								if ($USER->GetID() == $arReport["USER_ID"])
									$res = CTimeManReportFull::Update($ID, $arFields);
								if($arFields["ACTIVE"] == "Y" && $arReport["ACTIVE"] == "N")
								{
									$last_date = MakeTimeStamp($arFields["DATE_TO"]);
									$last_date = ConvertTimeStampForReport($last_date, "SHORT");
									$tm_user->SetLastDate($arReport["USER_ID"],$last_date);
									$tm_user->CancelDelay();
									if (!$bSameUser)
									{
										CReportNotifications::SendMessage($ID);
									}
								}
							}
							else
							{
								if(is_array($_SESSION['report_files']))
								{
									$arFields['FILES'] = $_SESSION['report_files'];
								}

								$arManagers = CTimeMan::GetUserManagers($curUser);

								$res = CTimeManReportFull::Add($arFields);
								if ($res && $arFields["ACTIVE"]!="N" && !$bSameUser)
									CReportNotifications::SendMessage($res);
							}

							$_SESSION['report_files'] = array();
							$CACHE_MANAGER->Clean(CUserReportFull::getInfoCacheId($curUser), 'timeman_report_info');
							$CACHE_MANAGER->Clean(CReportSettings::getSettingsCacheId($curUser), 'timeman_report_settings');
						}

					break;

					case "add_comment_full_report":
						$bReturnRes = true;
						$ID = intval($_POST["report_id"]);

						$dbReport = CTimeManReportFull::GetByID($ID);
						$report = $dbReport->Fetch();

						if ($report && CModule::IncludeModule("forum"))
						{
							$OWNER = intval($report["USER_ID"]);
							$CURRENT_USER = $USER->GetID();

							$arAccessUsers = CTimeMan::GetAccess();

							$bCanEditAll = in_array('*', $arAccessUsers['WRITE']);
							$bCanReadAll = in_array('*', $arAccessUsers['READ']);

							$bCanAddComment = (in_array($OWNER,$arAccessUsers['WRITE']) || CTimeMan::IsAdmin() || $bCanEditAll || $OWNER == $CURRENT_USER);

							if ($_POST["add_comment"] == "Y" && $bCanAddComment)
							{
								$arFields = array(
									"REPORT_ID"=>$ID,
									"COMMENT_TEXT"=>$_POST["comment_text"],
									"USER_ID"=>$CURRENT_USER,
									"REPORT_OWNER"=>$OWNER
								);
								$comment_id = CReportNotifications::AddCommentToLog($arFields);

								ob_start();
								$APPLICATION->IncludeComponent(
									"bitrix:timeman.topic.reviews",
									"",
									array(
										"REPORT_ID"=>$ID,
									),
								false
								);
								$res["COMMENTS"] = ob_get_contents();
								ob_end_clean();

								$count = CForumMessage::GetList(array("ID"=>"ASC"), array("TOPIC_ID"=>$report['FORUM_TOPIC_ID']),true);
								$res["COMMENTS_COUNT"] = $count;
							}
							else
							{
								$res = array("ERROR"=>"ADD COMMENT ERROR");
							}
						}
						else
						{
							$res = array("ERROR"=>"ADD COMMENT ERROR");
						}

					break;

					case "add_comment_entry":
						$bReturnRes = true;
						$ID = intval($_REQUEST["entry_id"]);

						$dbEntry = CTimeManEntry::GetByID($ID);
						$entry = $dbEntry->Fetch();

						if ($entry && CModule::IncludeModule("forum"))
						{
							$OWNER = intval($entry["USER_ID"]);
							$CURRENT_USER = $USER->GetID();

							$arAccessUsers = CTimeMan::GetAccess();
							$bCanEditAll = in_array('*', $arAccessUsers['WRITE']);
							$bCanReadAll = in_array('*', $arAccessUsers['READ']);

							$bCanAddComment = (in_array($OWNER, $arAccessUsers['WRITE']) || CTimeMan::IsAdmin() || $bCanEditAll || $OWNER == $CURRENT_USER);

							if ($bCanAddComment)
							{
								$arFields = array(
									"ENTRY_ID" => $ID,
									"COMMENT_TEXT" => $_REQUEST["comment_text"],
									"USER_ID" => $USER->GetID()
								);
								$comment_id = CTimeManNotify::AddCommentToLog($arFields);

								ob_start();
								$APPLICATION->IncludeComponent('bitrix:timeman.topic.reviews', '', array('ENTRY_ID' => $ID), null, array('HIDE_ICONS' => 'Y'));
								$res['COMMENTS'] = trim(ob_get_contents());
								ob_end_clean();

								$count = CForumMessage::GetList(
									array("ID"=>"ASC"),
									array("TOPIC_ID" => $report['FORUM_TOPIC_ID']),
									true
								);
								$res["COMMENTS_COUNT"] = $count;
							}
							else
							{
								$res = array("ERROR" => "ADD COMMENT ERROR");
							}
						}
						else
						{
							$res = array("ERROR" => "ADD COMMENT ERROR");
						}

					break;

					case 'save':

						$arSettings = $obUser->GetSettings(array('UF_TM_REPORT_REQ'));
						$bClose = false;
						if ($arSettings['UF_TM_REPORT_REQ'] != 'A' && isset($_REQUEST['timeman_edit_to']))
						{
							$bClose = true;
							$timestamp = $_REQUEST['timeman_edit_to'];
							unset($_REQUEST['timeman_edit_to']);
						}

						$res = $obUser->EditDay(array(
							'REPORT' => trim($_REQUEST['report']),
							'TIME_START' => isset($_REQUEST['timeman_edit_from'])
								? intval($_REQUEST['timeman_edit_from']) % 86400 : null,
							'TIME_FINISH' => isset($_REQUEST['timeman_edit_to'])
								? intval($_REQUEST['timeman_edit_to']) % 86400 : null,
							'TIME_LEAKS' => isset($_REQUEST['TIME_LEAKS'])
								? intval($_REQUEST['TIME_LEAKS']) % 86400 : null,
							'LAT_CLOSE' => isset($_REQUEST['lat']) ? doubleval($_REQUEST['lat']) : '',
							'LON_CLOSE' => isset($_REQUEST['lon']) ? doubleval($_REQUEST['lon']) : '',
						));

						if ($res !== false && $bClose)
						{
							$bReturnRes = true;
							$res = CTimeMan::GetRuntimeInfo(true);
							$res['CLOSE_TIMESTAMP'] = $timestamp;
							$res['CLOSE_TIMESTAMP_REPORT'] = trim($_REQUEST['report']);
						}

					break;

					case 'close':
						$bReturnRes = true;

						$TMUSER = CTimeManUser::instance();

						$dbRes = CUser::GetList(
							$by = 'ID', $order = 'ASC',
							array('ID' => $USER->GetID()),
							array('SELECT' => array('UF_*'))
						);

						$arCurrentUser = $dbRes->GetNext();
						$arCurrentUser['PHOTO'] =
							$arCurrentUser['PERSONAL_PHOTO'] > 0
							? CIntranetUtils::InitImage($arCurrentUser['PERSONAL_PHOTO'], 100, 100, BX_RESIZE_IMAGE_EXACT)
							: array();

						$arManagers = CTimeMan::GetUserManagers($USER->GetID());

						if (!is_array($arManagers) || count($arManagers) <= 0)
							$arManagers = array($USER->GetID());

						$arCurrentUserManagers = array();
						$user_url = COption::GetOptionString('intranet', 'path_user', '/company/personal/user/#USER_ID#/', $_REQUEST['site_id']);

						foreach ($arManagers as $managerId)
						{
							$dbManager = CUser::GetByID($managerId);
							if ($manager = $dbManager->Fetch())
							{
								$manager['PHOTO'] =
									$manager['PERSONAL_PHOTO'] > 0
									? CIntranetUtils::InitImage($manager['PERSONAL_PHOTO'], 100, 100, BX_RESIZE_IMAGE_EXACT)
									: array();

								$arCurrentUserManagers[] = array(
									'ID' => $manager['ID'],
									'LOGIN' => $manager['LOGIN'],
									'NAME' => CUser::FormatName(CSite::GetNameFormat(false), $manager, true, false),
									'URL' => str_replace(array('#ID#', '#USER_ID#'), $manager['ID'], $user_url),
									'WORK_POSITION' => $manager['WORK_POSITION'],
									'PHOTO' => $manager['PHOTO']['CACHE']['src'],
								);
							}
						}

						$arInfo = CTimeMan::GetRuntimeInfo(true);
						$arInfo['DATE_TEXT'] = FormatDate('j F Y', $arInfo['INFO']['DATE_START']);
						$arInfo['INFO']['TIME_OFFSET'] = CTimeManUser::getDayStartOffset($arInfo['INFO'], true);


						if($arInfo['PLANNER'])
						{
							$arPlannerInfo = CIntranetPlanner::callAction('timeman_close', $_REQUEST['site_id']);

							// TODO: migrate this to calendar module ASAP
							if (is_array($arInfo['PLANNER']['DATA']['EVENTS']))
							{
								foreach ($arInfo['PLANNER']['DATA']['EVENTS'] as $key => $arEvent)
								{
									if ($arEvent['STATUS'] && $arEvent['STATUS'] != 'Y')
										unset($arInfo['PLANNER']['DATA']['EVENTS'][$key]);
								}
								$arInfo['PLANNER']['DATA']['EVENTS'] = array_values($arInfo['PLANNER']['DATA']['EVENTS']);
							}
							// \TODO

							$arInfo['PLANNER']['DATA'] = array_merge($arInfo['PLANNER']['DATA'], $arPlannerInfo);
							$arInfo = array_merge($arInfo, $arInfo['PLANNER']['DATA']);
							unset($arInfo['PLANNER']);
						}

						$arUserSettings = $TMUSER->GetSettings();

						$res = array(
							'FROM' => array(
								'ID' => $arCurrentUser['ID'],
								'LOGIN' => $arCurrentUser['LOGIN'],
								'NAME' => CUser::FormatName(CSite::GetNameFormat(false), $arCurrentUser, true, false),
								'URL' => str_replace(array('#ID#', '#USER_ID#'), $arCurrentUser['ID'], $user_url),
								'WORK_POSITION' => $arCurrentUser['WORK_POSITION'],
								'PHOTO' => $arCurrentUser['PHOTO']['CACHE']['src']
							),
							'TO' => array_values($arCurrentUserManagers),
							'INFO' => $arInfo,

							'REPORT' => '',
							'REPORTS' => array(),
							'REPORT_REQ' => $arUserSettings['UF_TM_REPORT_REQ'],
							'REPORT_TPL' => $arUserSettings['UF_TM_REPORT_TPL'],
						);

						if (count($res['TO']) <= 0)
							$res['TO'] = array($res['FROM']);

						$arUserIDs = array();
						$dbReports = CTimeManReport::GetList(array('ID' => 'ASC'), array('ENTRY_ID' => $arInfo['ID']));
						while ($arReport = $dbReports->Fetch())
						{
							switch($arReport['REPORT_TYPE'])
							{
								case 'ERR_OPEN':
								case 'ERR_CLOSE':
								case 'ERR_DURATION':
									$arUserIDs[] = $arReport['USER_ID'];

									$key = $arReport['REPORT_TYPE'] == 'ERR_OPEN'
										? 'TIME_START'
										: (
											$arReport['REPORT_TYPE'] == 'ERR_CLOSE'
											? 'TIME_FINISH'
											: 'DURATION'
										);

									$arReportData = explode(';', $arReport['REPORT']);

									if (!$res['REPORTS'][$key])
										$res['REPORTS'][$key] = array();

									$report_ts = strtotime($arReportData[1])+CTimeZone::GetOffset();
									$res['REPORTS'][$key][0] = array(
										'TYPE' => $arReportData[0],
										'TYPE_TEXT' => $arReportData[0],
										'TIME' => $report_ts+date('Z'),
										'DATE_TIME' => FormatDate(str_replace(':s', '', $DB->DateFormatToPHP(FORMAT_DATETIME)), MakeTimeStamp($arReport['TIMESTAMP_X'])),
										'ACTIVE' => $arReport['ACTIVE'] == 'Y',
										'USER_ID' => $arReport['USER_ID']
									);

								break;

								case 'REPORT_OPEN':
								case 'REPORT_CLOSE':
								case 'REPORT_DURATION':
									$key = $arReport['REPORT_TYPE'] == 'REPORT_OPEN'
										? 'TIME_START'
										: (
											$arReport['REPORT_TYPE'] == 'REPORT_CLOSE'
											? 'TIME_FINISH'
											: 'DURATION'
										);

									if (count($res['REPORTS'][$key]) > 0)
									{
										if (strlen($arReport['REPORT']) > 150)
										{
											$arReport['REPORT_FULL'] = $arReport['REPORT'];
											$arReport['REPORT'] = substr($arReport['REPORT'], 0, 150).'...';
										}

										$res['REPORTS'][$key][0]['REPORT'] = htmlspecialcharsbx($arReport['REPORT']);

										if ($arReport['REPORT_FULL'])
										$res['REPORTS'][$key][0]['REPORT_FULL'] = htmlspecialcharsbx($arReport['REPORT_FULL']);
									}
								break;

								case 'REPORT':
									$res['REPORT'] = $arReport['REPORT'];
							}
						}

						if (count($arUserIDs) > 0)
						{
							$arUserIDs = array_unique($arUserIDs);
							$dbUsers = CUser::GetList(
								$by='ID', $order='ASC',
								array('ID' => implode('|', $arUserIDs), 'ACTIVE' => 'Y')
							);
							while ($arUser = $dbUsers->Fetch())
							{
								$name = CUser::FormatName(CSite::GetNameFormat(false), $arUser);

								foreach ($res['REPORTS'] as &$rep)
								{
									foreach ($rep as &$arReport)
									{
										if ($arReport['USER_ID'] == $arUser['ID'])
											$arReport['USER_NAME'] = $name;
									}
								}
							}
						}

						// all data ready, show form
						if (!$_REQUEST['ready'] && $arUserSettings['UF_TM_REPORT_REQ'] !== 'A' && !$arUserSettings['UF_TM_FREE'])
						{
							ob_start();
							$APPLICATION->IncludeComponent('bitrix:timeman.topic.reviews', '', array('ENTRY_ID' => $arInfo['ID']), null, array('HIDE_ICONS' => 'Y'));
							$res['COMMENTS'] = trim(ob_get_contents());
							ob_end_clean();

							break;
						}

						// we shouldn't show the form or form is already sent

						$report_text = $res['REPORT'];

						$res = true;
						$bReturnRes = false;

						// check required report text
						if ($arUserSettings['UF_TM_REPORT_REQ'] == 'Y' && !$arUserSettings['UF_TM_FREE'])
						{
							$report = preg_replace('/\s/', '', $_REQUEST['REPORT']);

							if (strlen($report) <= 0)
								$res = false;
							elseif (is_array($arUserSettings['UF_TM_REPORT_TPL']))
							{
								foreach ($arUserSettings['UF_TM_REPORT_TPL'] as $tpl)
								{

									if ($report == preg_replace('/\s/', '', $tpl))
									{
										$res = false; break;
									}
								}
							}

							if (!$res) break;
						}

						$arFields = array(
							'ACTIVE' => 'N',
							'USER_ID' => $USER->GetID(),
							'ENTRY_ID' => $arInfo['ID'],
							'REPORT_DATE' => ConvertTimeStamp($arInfo['INFO']['DATE_START']),
							'REPORT' => $_REQUEST['REPORT'],
							'EVENTS' => $arInfo['EVENTS']
						);

						// auto generated report
						if ($arUserSettings['UF_TM_REPORT_REQ'] == 'A')
						{
							$arFields['REPORT'] = $report_text;

							if ($arInfo['TASKS_ENABLED'])
							{
								$arFields['TASKS'] = $arInfo['TASKS'];

								foreach ($arFields['TASKS'] as $key => $arTask)
								{
									if (!isset($arTask['TIME']) || $arTask['TIME'] <= 0)
									{
										unset($arFields['TASKS'][$key]);
									}
								}

								$arFields['TASKS'] = array_values($arFields['TASKS']);
							}
						}

						// tasks added from form
						elseif (is_array($_REQUEST['TASKS']) && count($_REQUEST['TASKS']) > 0)
						{
							$arTaskTime = array();
							foreach ($_REQUEST['TASKS'] as $i => $task_id)
								$arTaskTime[$task_id] = $_REQUEST['TASKS_TIME'][$i];

							$arFields['TASKS'] = $TMUSER->GetTasks($_REQUEST['TASKS']);
							foreach ($arFields['TASKS'] as $key => $arTask)
							{
								$arFields['TASKS'][$key]['TIME'] = $arTaskTime[$arTask['ID']];
							}
						}

						if (isset($_REQUEST['timeman_edit_from']) || isset($_REQUEST['timeman_edit_to']) || isset($_REQUEST['TIME_LEAKS']))
						{
							$res = $obUser->EditDay(array(
								'REPORT' => trim($_REQUEST['report']),
								'TIME_START' => isset($_REQUEST['timeman_edit_from'])
									? intval($_REQUEST['timeman_edit_from']) % 86400 : null,
								'TIME_FINISH' => isset($_REQUEST['timeman_edit_to'])
									? intval($_REQUEST['timeman_edit_to']) % 86400 : null,
								'TIME_LEAKS' => isset($_REQUEST['TIME_LEAKS'])
									? intval($_REQUEST['TIME_LEAKS']) % 86400 : null,
								'LAT_CLOSE' => isset($_REQUEST['lat']) ? doubleval($_REQUEST['lat']) : '',
								'LON_CLOSE' => isset($_REQUEST['lon']) ? doubleval($_REQUEST['lon']) : '',
							));
						}
						else
						{
							$res = $obUser->CloseDay(intval($_REQUEST['timestamp'])%86400, $_REQUEST['report']);
							if($res !== false)
							{
								$updateFields = array(
									'LAT_CLOSE' => isset($_REQUEST['lat']) ? doubleval($_REQUEST['lat']) : '',
									'LON_CLOSE' => isset($_REQUEST['lon']) ? doubleval($_REQUEST['lon']) : '',
								);

								CTimeManEntry::Update($res['ID'], $updateFields);
							}
						}

						if ($res)
						{
							$res = CTimeManReportDaily::Add($arFields);
						}

						break;

					case 'open':
						$timestamp = 0;
						$report = '';

						if(\Bitrix\Main\Loader::includeModule('bitrix24'))
						{
							if(!\CBitrix24BusinessTools::isUserUnlimited($USER->GetID()))
							{
								$res = false;

								ob_start();

								$assetCollector = new \Bitrix\Main\UserField\AssetCollector();

								$assetCollector->startAssetCollection();
								$APPLICATION->IncludeComponent("bitrix:bitrix24.business.tools.info", "", array('SHOW_TITLE' => 'N'));

								$asset = $assetCollector->getCollectedAssets();

								$errorData = ob_get_clean().implode('', $asset);

								$error = \Bitrix\Main\Web\Json::encode(array(
									'error_id' => 'USER_RESTRICTION',
									'error' => array(
										'data' => $errorData
									),
								));


								break;

							}
						}

						if ($_REQUEST['timestamp'] > 0)
						{
							$timestamp = intval($_REQUEST['timestamp']) % 86400;
						}

						if ($_REQUEST['report'])
						{
							$report = trim($_REQUEST['report']);
						}

						$res = $obUser->OpenDay($timestamp, $report);
						if($res !== false)
						{
							$updateFields = array(
								'LAT_OPEN' => isset($_REQUEST['lat']) ? doubleval($_REQUEST['lat']) : '',
								'LON_OPEN' => isset($_REQUEST['lon']) ? doubleval($_REQUEST['lon']) : '',
							);

							CTimeManEntry::Update($res['ID'], $updateFields);
						}
					break;

					case 'reopen':
						$res = $obUser->ReopenDay(true, $_REQUEST['site_id']);
					break;

					case 'pause':
						$res = $obUser->PauseDay();

						if($res !== false)
						{
							$updateFields = array(
								'LAT_CLOSE' => isset($_REQUEST['lat']) ? doubleval($_REQUEST['lat']) : '',
								'LON_CLOSE' => isset($_REQUEST['lon']) ? doubleval($_REQUEST['lon']) : '',
							);

							CTimeManEntry::Update($res['ID'], $updateFields);
						}

					break;

					case 'report':
						$arReport = $obUser->SetReport($_REQUEST['report'], $_REQUEST['report_ts'], $_REQUEST['entry_id']);
						if (is_array($arReport))
						{
							if($obUser->State() == 'CLOSED')
							{
								$arSettings = $obUser->GetSettings(array('UF_TM_REPORT_REQ'));
								if($arSettings['UF_TM_REPORT_REQ'] == 'A')
								{
									$dbRes = CTimeManReportDaily::GetList(array(), array('ENTRY_ID' => $arReport['ENTRY_ID']), false, false, array('ID'));
									$reportDaily = $dbRes->Fetch();
									if($reportDaily)
									{
										CTimeManReportDaily::Update($reportDaily['ID'], array('REPORT' => $arReport['REPORT']));
									}
								}
							}

							$bReturnRes = true;
							$res = array(
								'REPORT' => $arReport['REPORT'],
								'REPORT_TS' => MakeTimeStamp($arReport['TIMESTAMP_X'])
							);
						}
						else
						{
							$res = false;
						}
					break;

					case 'task':
						if (!CBXFeatures::IsFeatureEnabled('Tasks'))
							break;

						$obUser->TaskActions(array(
							'name' => $_REQUEST['name'],
							'add' => $_REQUEST['add'],
							'remove' => $_REQUEST['remove'],
						), $_REQUEST['site_id']);
					break;

					case 'calendar_show':
						if (!CBXFeatures::IsFeatureEnabled('Calendar'))
							break;

						$ID = intval($_REQUEST['id']);
						$bReturnRes = true;

						if ($event = CTimeManCalendar::Get(array(
							'ID' => $ID, 'site_id' => $_REQUEST['site_id']
						)))
						{
							$now = time();
							$today = CTimeMan::RemoveHoursTS($now);

							$res = array(
								'ID' => $event['ID'],
								'NAME' => $event['NAME'],
								'DESCRIPTION' => $event['DETAIL_TEXT'],
								'URL' => '/company/personal/user/'.$USER->GetID().'/calendar/?EVENT_ID=' .$event['ID'],
								'DATE_FROM' => MakeTimeStamp($event['DATE_FROM']),
								'DATE_TO' => MakeTimeStamp($event['DATE_TO']),
								'STATUS' => $event['STATUS'],
							);

							$res['DATE_FROM_TODAY'] = CTimeMan::RemoveHoursTS($res['DATE_FROM']) == $today;
							$res['DATE_TO_TODAY'] = CTimeMan::RemoveHoursTS($res['DATE_TO']) == $today;

							$res['DATE_FROM_TODAY'] = CTimeMan::RemoveHoursTS($res['DATE_FROM']) == $today;
							$res['DATE_TO_TODAY'] = CTimeMan::RemoveHoursTS($res['DATE_TO']) == $today;

							if ($res['DATE_FROM_TODAY'])
							{
								if (IsAmPmMode())
								{
									$res['DATE_F'] = FormatDate("today g:i a", $res['DATE_FROM']);
									$res['DATE_T'] = FormatDate("g:i a", $res['DATE_TO']);
								}
								else
								{
									$res['DATE_F'] = FormatDate("today H:i", $res['DATE_FROM']);
									$res['DATE_T'] = FormatDate("H:i", $res['DATE_TO']);
								}

								if ($res['DATE_TO_TODAY'])
									$res['DATE_F'] .= ' - '.$res['DATE_T'];

								if ($res['DATE_FROM'] > $now)
								{

									$res['DATE_F_TO'] = GetMessage('TM_IN').' '.FormatDate('Hdiff', time()*2-($res['DATE_FROM'] - CTimeZone::GetOffset()));
								}
							}
							else if ($res['DATE_TO_TODAY'])
							{
								$res['DATE_F'] = FormatDate(str_replace(
									array('#today#', '#time#'),
									array('today', 'H:i'),
									GetMessage('TM_TILL')
								), $res['DATE_TO']);
							}
							else
							{
								$fmt = preg_replace('/:s$/', '', $DB->DateFormatToPHP(CSite::GetDateFormat("FULL")));
								$res['DATE_F'] = FormatDate($fmt, $res['DATE_FROM']);
								$res['DATE_F_TO'] = FormatDate($fmt, $res['DATE_TO']);
							}

							if ($event['IS_MEETING'] == 'Y')
							{
								$arGuests = array('Y' => array(), 'N' => array(), 'Q' => array());
								foreach ($event['GUESTS'] as $key => $guest)
								{
									$guest['url'] = str_replace(
										array('#ID#', '#USER_ID#'),
										$guest['id'],
										COption::GetOptionString('intranet', 'path_user', '/company/personal/user/#USER_ID#/', $_REQUEST['site_id'])
									);

									if ($guest['bHost'])
									{
										$res['HOST'] = $guest;
									}
									else
									{
										$arGuests[$guest['status']][] = $guest;
									}
								}

								$res['GUESTS'] = array_merge($arGuests['Y'], $arGuests['N'], $arGuests['Q']);
							}

							$res['DESCRIPTION'] = HTMLToTxt($res['DESCRIPTION']);
							if (strlen($res['DESCRIPTION']) > 150)
							{
								$res['DESCRIPTION'] = CUtil::closetags(substr($res['DESCRIPTION'], 0, 150)).'...';
							}
						}
						else
						{
							$APPLICATION->ThrowException('event not found', 'event_not_found');
						}

					break;

					case 'calendar_add':
						if (!CBXFeatures::IsFeatureEnabled('Calendar'))
							break;

						$arParams = array(
							'calendar_id' => $_REQUEST['cal'],
							'site_id' => $_REQUEST['site_id'],
							'from' => $_REQUEST['from'],
							'to' => $_REQUEST['to'],
							'name' => $_REQUEST['name'],
							'absence' => $_REQUEST['absence'],
							'cal_set_default' => $_REQUEST['cal_set_default'],
						);
						$res = CTimeManCalendar::Add($arParams);
						$bReturnRes = is_array($res);

					break;
					case 'admin_data_report_full':
						$bReturnRes = true;
						$amount = 30;
						$res = array('DEPARTMENTS' => array(), 'USERS' => array(), 'NAV' => array());

						$bShowAll = $_REQUEST['show_all'] == 'Y';
						CUserOptions::SetOption("timeman.report.weekly","show_all",$_REQUEST['show_all'], false,$USER->GetID());
						CUserOptions::SetOption("timeman.report.weekly","department_id",intval($_REQUEST['department']), false,$USER->GetID());
						$page = intval($_REQUEST['page']);
						if ($page <= 0) $page = 1;
						$arAccessUsers = CTimeMan::GetAccess();
						if (count($arAccessUsers['READ']) > 0)
						{
							$bCanEditAll = in_array('*', $arAccessUsers['WRITE']);
							$date_to = ConvertTimeStamp($_POST["tf"]);
							$date_from = ConvertTimeStamp($_POST["ts"]);

							$datefomat = CSite::GetDateFormat("SHORT",SITE_ID);
							$bCanReadAll = in_array('*', $arAccessUsers['READ']);

							$section_id = 0;
							if ($_REQUEST['department'])
							{
								$section_id = intval($_REQUEST['department']);
								$arFilter['UF_DEPARTMENT'] = CIntranetUtils::GetIBlockSectionChildren(intval($_REQUEST['department']));
							}

							if (!$bShowAll)
							{
								$arDirectUsers = CTimeMan::GetDirectAccess();

								if (!$bCanReadAll)
								{
									$arAccessUsers['READ'] = array_intersect($arAccessUsers['READ'], $arDirectUsers);
								}
								else
								{
									$arAccessUsers['READ'] = $arDirectUsers;
								}
								//$arAccessUsers['READ'] = $arDirectUsers;
								$bCanReadAll = false;
								if (count($arAccessUsers['READ']) <= 0)
									break;
							}

							$arFilter[] = Array(
								"LOGIC" => "OR",
								Array(
									"LOGIC" =>"AND",
									"<DATE_TO"=>$date_to,
									">=DATE_TO"=>$date_from
								),
								Array(
									"LOGIC" =>"AND",
									"<DATE_FROM"=>$date_to,
									">=DATE_FROM"=>$date_from
								),

							);
							$arFilter[] = Array(
										"LOGIC" =>"AND",
										Array("ACTIVE"=>"Y")
									);
							if ($arAccessUsers["READ"][0]!="*")
								$arFilter[] = Array(
										"LOGIC" =>"AND",
										Array("USER_ID"=>$arAccessUsers["READ"])
									);

							$arUserIDs = CIntranetUtils::GetEmployeesForSorting($page, $amount, $section_id, $bCanReadAll ? false : $arAccessUsers['READ']);
							$arSections = array_keys($arUserIDs);

							$arUsers = array();
							foreach ($arUserIDs as $ar)
								$arUsers = array_merge($arUsers, $ar);
							$arFilterUser = Array();
							$arFilterUser['USER_ID'] = $arUsers;
							$dbRes = CUser::GetList($by = 'ID', $order = 'ASC', array('ID' => implode('|', $arUsers), 'ACTIVE' => 'Y'), array('SELECT' => array('*', 'UF_DEPARTMENT')));
							while ($arRes = $dbRes->GetNext())
							{
								$res['USERS'][$arRes['ID']] = array(
									'ID' => $arRes['ID'],
									'NAME' => CUser::FormatName(
										CSite::GetNameFormat(false), array(
											'USER_ID' => $arRes['ID'],
											'NAME' => $arRes['NAME'],
											'LAST_NAME' => $arRes['LAST_NAME'],
											'SECOND_NAME' => $arRes['SECOND_NAME']
										),
										true, false
									),
									'DEPARTMENT' => $arRes['UF_DEPARTMENT'][0],
									'URL' => str_replace(array('#ID#', '#USER_ID#'), $arRes['ID'], COption::GetOptionString('intranet', 'path_user', '/company/personal/user/#USER_ID#/', $_REQUEST['site_id'])),
									'SETTINGS' => array(),
								);


								$arFilterReport = Array();

									$arFilterReport[] = Array(
										"LOGIC" => "OR",
										Array(
											"LOGIC" =>"AND",
											"<DATE_TO"=>$date_to,
											">=DATE_TO"=>$date_from
										),
										Array(
											"LOGIC" =>"AND",
											"<DATE_FROM"=>$date_to,
											">=DATE_FROM"=>$date_from
										),

									);
									//fix this in future
										$arFilterReport[] = Array(
												"LOGIC" =>"AND",
												Array("USER_ID"=>$arRes["ID"])
											);
										$arFilterReport[] = Array(
										"LOGIC" =>"AND",
										Array("ACTIVE"=>"Y")
										);
									$res["USERS"][$arRes['ID']]["FULL_REPORT"] = Array();
									$datefomat = CSite::GetDateFormat("SHORT",SITE_ID);
									$res["USERS"][$arRes['ID']]["FULL_REPORT_INFO"]["BAD"] = 0;
									$res["USERS"][$arRes['ID']]["FULL_REPORT_INFO"]["COUNT"] = 0;
									$arSelect = Array(
											"ID",
											"USER_ID",
											"DATE_FROM",
											"DATE_TO",
											"MARK",
											"FORUM_TOPIC_ID"
										);
									//$arSelect = Array();
									$dbres = CTimeManReportFull::GetList(Array("ID"=>"desc"),$arFilterReport,$arSelect);
									$res["USERS"][$arRes['ID']]["FULL_REPORT_INFO"]["GOOD"] = 0;
									$res["USERS"][$arRes['ID']]["FULL_REPORT_INFO"]["MARKED"] = 0;


									while($report = $dbres->Fetch())
									{

										if ($report["MARK"] != "X")
											$res["USERS"][$arRes['ID']]["FULL_REPORT_INFO"]["MARKED"]++;
										if ($report["MARK"] == "G")
											$res["USERS"][$arRes['ID']]["FULL_REPORT_INFO"]["GOOD"]++;
										$res["USERS"][$arRes['ID']]["FULL_REPORT_INFO"]["COUNT"]++;
										$report["DATE_TO"] = MakeTimeStamp($report["DATE_TO"],$datefomat);
											$report["DATE_FROM"] = MakeTimeStamp($report["DATE_FROM"],$datefomat);
											$report["FOR_JS"] = CTimeManReportFull::__getReportJSDraw(
													Array(
														"PERIOD_DATE_FROM" => $_POST["ts"],
														"REPORT_DATE_FROM"=>$report['DATE_FROM'],
														"REPORT_DATE_TO"=>$report['DATE_TO']
													)
											);

										$report["COMMENTS_COUNT"] = 0;

										if ($report['FORUM_TOPIC_ID'] && CModule::IncludeModule("forum"))
										{
											$count = CForumMessage::GetList(array("ID"=>"ASC"), array("TOPIC_ID"=>$report['FORUM_TOPIC_ID']),true);
											$report["COMMENTS_COUNT"] = $count;
										}
										$res["USERS"][$arRes['ID']]["FULL_REPORT"][] = $report;
									}
									if($arRes['ID'] == $USER->GetID()&&!CTimeMan::IsAdmin())
										$res["USERS"][$arRes['ID']]["CAN_EDIT_TIME"] = "N";
									else
										$res["USERS"][$arRes['ID']]["CAN_EDIT_TIME"] = "Y";
									$tm_user = new CUserReportFull($arRes["ID"]);
									$res["USERS"][$arRes['ID']]["SETTINGS"] = $tm_user->GetSettings(true);
								}


							if (count($arSections) > 0)
							{
								$arSubordination = CIntranetUtils::GetSubordinateDepartments($USER->GetID(),true);
								$arChains = array();
								$section_url = "/company/structure.php?set_filter_structure=Y&structure_UF_DEPARTMENT=#ID#";
								$iblockId = COption::GetOptionInt('intranet', 'iblock_structure', 0);

								$arSectionFilter = array(
									'IBLOCK_ID' => $iblockId,
									'ID' => array_unique($arSections)
								);

								$dbRes = CIBlockSection::GetList(
									array('LEFT_MARGIN' => 'DESC'),
									$arSectionFilter,
									false,
									array('ID', 'NAME', 'IBLOCK_SECTION_ID', 'UF_HEAD')
								);

								$chain_root = null;
								while ($arRes = $dbRes->Fetch())
								{
									$arRes['CHAIN'] = array();
									if (isset($arChains[$arRes['ID']]))
									{
										$arRes['CHAIN'] = $arChains[$arRes['ID']];
									}
									elseif ($arRes['IBLOCK_SECTION_ID'] > 0
											&& isset($arChains[$arRes['IBLOCK_SECTION_ID']]))
									{
										$arRes['CHAIN'] = $arChains[$arRes['IBLOCK_SECTION_ID']];
										$arRes['CHAIN'][] = array(
											'ID' => $arRes['ID'],
											'NAME' => $arRes['NAME'],
											'URL' => str_replace('#ID#', $arRes['ID'], $section_url)
										);
									}
									else
									{
										$db1 = CIBlockSection::GetNavChain($iblockId, $arRes['ID']);
										while ($sect = $db1->Fetch())
										{
											$arRes['CHAIN'][] = array(
												'ID' => $sect['ID'],
												'NAME' => $sect['NAME'],
												'URL' => str_replace('#ID#', $sect['ID'], $section_url)
											);
										}
									}

									if (!isset($arChains[$sect['ID']]))
									{
										$arChains[$sect['ID']] = $arRes['CHAIN'];
									}

									if (null === $chain_root)
										$chain_root = $arRes['CHAIN'][0]['ID'];
									elseif (
										false !== $chain_root
										&& $chain_root != $arRes['CHAIN'][0]['ID']
									)
										$chain_root = false;
									$arRes["SETTINGS"] = CReportSettings::GetSectionSettings($arRes["ID"], true);
									$arRes["CAN_EDIT_TIME"] = "N";

									$arRes["HAS_SETTINGS"] = "N";
									if($arRes["SETTINGS"]["UF_REPORT_PERIOD"] && !$arRes["SETTINGS"]["PARENT"])
										$arRes["HAS_SETTINGS"] = "Y";
									if (in_array($arRes['ID'], $arSubordination) || CTimeMan::IsAdmin())
										$arRes["CAN_EDIT_TIME"] ="Y";
									$res['DEPARTMENTS'][$arRes['ID']] = $arRes;
								}

								if ($chain_root)
								{
									foreach ($res['DEPARTMENTS'] as &$dpt)
									{
										if (count($dpt['CHAIN']) > 1)
											array_shift($dpt['CHAIN']);
									}
								}
							}
						}

						$old_res = $res;
						$res = array('DEPARTMENTS' => array(), 'USERS' => array());

						foreach ($arUserIDs as $dpt_id => $arDptUsers)
						{
							$res['DEPARTMENTS'][] = $old_res['DEPARTMENTS'][$dpt_id];
							foreach ($arDptUsers as $user_id)
							{
								if ($old_res['USERS'][$user_id])
								{
									$old_res['USERS'][$user_id]['DEPARTMENT'] = $dpt_id;
									$old_res['USERS'][$user_id]['HEAD'] =
										$old_res['DEPARTMENTS'][$dpt_id]['UF_HEAD'] == $user_id;

									$res['USERS'][] = $old_res['USERS'][$user_id];
								}
							}
						}

						\Bitrix\Main\Type\Collection::sortByColumn(
							$res['USERS'],
							array('HEAD' => SORT_DESC, 'NAME' => SORT_ASC)
						);

						$tm_user = new CUserReportFull($USER->GetID());
						$res["OVERDUE"] = $tm_user->GetReportData(true);
						$res['NAV'] = '';
						$item_count = CIntranetUtils::GetEmployeesCountForSorting($section_id, 0, $bCanReadAll ? false : $arAccessUsers['READ']);
						$page_count = intval($item_count/$amount)+($item_count%$amount>0?1:0);

						$navResult = new CDBResult();
						$navResult->NavNum = 'STRUCTURE';
						$navResult->NavPageSize = $amount;
						$navResult->NavRecordCount = $item_count;
						$navResult->NavPageCount = $page_count;
						$navResult->NavPageNomer = $page;

						ob_start();
						$APPLICATION->IncludeComponent(
								'bitrix:system.pagenavigation',
								'js',
								array(
									'NAV_RESULT' => $navResult,
									'HANDLER' => 'window.BXTMREPORT.Page'
								)
						);
						$res['NAV'] = ob_get_contents();
						ob_end_clean();
					break;

					case 'admin_data_settings':
						$bReturnRes = true;
						$arNeededSettings = array('UF_TIMEMAN', 'UF_TM_MAX_START', 'UF_TM_MIN_FINISH', 'UF_TM_MIN_DURATION', 'UF_TM_REPORT_REQ', 'UF_TM_FREE', 'UF_TM_ALLOWED_DELTA');

						if (isset($_REQUEST['ID']))
						{
							$ID = $_REQUEST['ID'];
							$source = $_REQUEST['source'];


							if ($source == 'department')
							{
								if (!CTimeMan::IsAdmin())
								{
									$arSubordination = CIntranetUtils::GetSubordinateDepartments($USER->GetID(), true);
									if (!in_array($ID, $arSubordination))
									{
										echo "{error: 'access denied', type: 'fatal'}";
										die();
									}
								}
							}
							else
							{
								$arAccessUsers = CTimeMan::GetAccess();
								$bCanEditAll = in_array('*', $arAccessUsers['WRITE']);
								$bCanReadAll = in_array('*', $arAccessUsers['READ']);
								if(!$bCanReadAll && !in_array($ID, $arAccessUsers["READ"]))
								{
										echo "{error: 'access denied', type: 'fatal'}";
										die();
								}
							}

							$arFields = array();
							foreach($arNeededSettings as $key)
							{
								if (IsAmPmMode() && ($key == 'UF_TM_MAX_START' || $key == 'UF_TM_MIN_FINISH'))
								{
									$v = trim($_REQUEST[$key]);
									if (strlen($v) > 0)
									{
										if (preg_match_all('/^(\d+):(\d+)\s*(am|pm)$/i', $v, $matches))
										{
											$v = (intval($matches[1][0]) + (strtolower($matches[3][0]) == 'pm' ? 12 : 0)).':'.$matches[2][0];
										}
									}
									$arFields[$key] = $v;
								}
								else
								{
									$arFields[$key] = $_REQUEST[$key];
								}
							}

							if ($arFields['UF_TM_ALLOWED_DELTA'])
								$arFields['UF_TM_ALLOWED_DELTA'] = CTimeMan::FormatTime($arFields['UF_TM_ALLOWED_DELTA'], true);

							$arAllFields = $USER_FIELD_MANAGER->GetUserFields($source == 'department' ? 'IBLOCK_'.COption::GetOptionInt('intranet', 'iblock_structure').'_SECTION' : 'USER');

							$arEnumFields = array('UF_TIMEMAN', 'UF_TM_REPORT_REQ', 'UF_TM_FREE');
							foreach ($arEnumFields as $fld)
							{
								if ($arFields[$fld])
								{
									$dbRes = CUserFieldEnum::GetList(array(), array(
										'USER_FIELD_ID' => $arAllFields[$fld]['ID'],
										'XML_ID' => $arFields[$fld]
									));
									if ($arRes = $dbRes->Fetch())
									{
										$arFields[$fld] = $arRes['ID'];
									}
								}
							}

							if ($source == 'department')
							{
								$obSection = new CIBlockSection();
								$obSection->Update($ID, $arFields);

								$CACHE_MANAGER->CleanDir("timeman_structure_".COption::GetOptionInt('intranet', 'iblock_structure'));

								$res = array(
									'ID' => $ID,
									'SETTINGS' => CTimeMan::GetSectionPersonalSettings($ID, true, $arNeededSettings),
									'SETTINGS_ALL' => CTimeMan::GetSectionSettings($ID, $arNeededSettings)
								);
							}
							else
							{
								$obUser = new CUser();
								$obUser->Update($ID, $arFields);

								$CACHE_MANAGER->CleanDir("timeman_structure_".COption::GetOptionInt('intranet', 'iblock_structure'));

								$TMUSER = new CTimeManUser($ID);
								$res = array(
									'ID' => $ID,
									'SETTINGS' => $TMUSER->GetPersonalSettings($arNeededSettings),
									'SETTINGS_ALL' => $TMUSER->GetSettings($arNeededSettings)
								);
							}
						}
						else
						{
							$res = array(
								'DEFAULTS' => CTimeMan::GetModuleSettings($arNeededSettings),
								'DEPARTMENTS' => array(),
								'USERS' => array(),
							);

							foreach ($_REQUEST['DEPARTMENTS'] as $dpt)
							{
								$res['DEPARTMENTS'][] = array(
									'ID' => $dpt,
									'SETTINGS' => CTimeMan::GetSectionPersonalSettings($dpt, true, $arNeededSettings),
									'SETTINGS_ALL' => CTimeMan::GetSectionSettings($dpt, $arNeededSettings)
								);
							}

							foreach ($_REQUEST['USERS'] as $user)
							{
								$TMUSER = new CTimeManUser($user);
								$res['USERS'][] = array(
									'ID' => $user,
									'SETTINGS' => $TMUSER->GetPersonalSettings($arNeededSettings),
									'SETTINGS_ALL' => $TMUSER->GetSettings($arNeededSettings),
								);
							}
						}

					break;

					case 'admin_data':
						$obReport = new CTimeManAdminReport(array(
							'show_all' => $_REQUEST['show_all'] == 'Y',
							'ts' => $_REQUEST['ts'],
							'page' => $_REQUEST['page'],
							'amount' => 30,
							'department' => $_REQUEST['department'],
							'path_user' => COption::GetOptionString('intranet', 'path_user', '/company/personal/user/#USER_ID#/', $site_id),
							'nav_handler' => 'window.BXTMREPORT.Page'
						));

						$bReturnRes = true;
						$res = $obReport->GetData();

						\Bitrix\Main\Type\Collection::sortByColumn(
							$res['USERS'],
							array('HEAD' => SORT_DESC, 'NAME' => SORT_ASC)
						);
						$res['DEPARTMENTS'] = array_values($res['DEPARTMENTS']);

					break;

					case 'admin_save':

						$ID = intval($_REQUEST['ID']);
						$arEntry = null;

						if (CTimeManEntry::Approve($ID, true)) // rights check inside
						{
							if ($_REQUEST['INFO'])
							{
								$arFields = array();

								if (isset($_REQUEST['INFO']['TIME_START']))
									$arFields['TIME_START'] = intval($_REQUEST['INFO']['TIME_START']);
								if (isset($_REQUEST['INFO']['TIME_FINISH']))
									$arFields['TIME_FINISH'] = intval($_REQUEST['INFO']['TIME_FINISH']);
								if (isset($_REQUEST['INFO']['TIME_LEAKS']))
									$arFields['TIME_LEAKS'] = intval($_REQUEST['INFO']['TIME_LEAKS']);

								$dbRes = CTimeManEntry::GetList(
									array(),
									array('ID' => $ID),
									false, false, array('*', 'ACTIVATED')
								);

								if ($arEntry = $dbRes->Fetch())
								{
									if ($arFields['TIME_FINISH'] && $arEntry['PAUSED'] == 'Y')
									{
										$arFields['PAUSED'] = 'N';
									}

									$arFields['TIME_LEAKS'] = isset($arFields['TIME_LEAKS']) ? $arFields['TIME_LEAKS'] : $arEntry['TIME_LEAKS'];
									$arFields['DURATION'] = $arFields['TIME_FINISH']-$arFields['TIME_START']-$arFields['TIME_LEAKS'];
								}

								CTimeManEntry::Update($ID, $arFields);

								$TMUSER = new CTimeManUser($arEntry['USER_ID']);
								$TMUSER->ClearCache();
							}
						}

					case 'admin_entry':

						$ID = $_REQUEST['ID'];

						$arAccessUsers = CTimeMan::GetAccess();
						if (count($arAccessUsers['READ']) > 0)
						{
							$bCanEditAll = in_array('*', $arAccessUsers['WRITE']);
							$bCanReadAll = in_array('*', $arAccessUsers['READ']);

							$dbRes = CTimeManEntry::GetList(
								array(),
								array('ID' => $ID),
								false, false, array('*', 'ACTIVATED')
							);
							if ($arRes = $dbRes->Fetch())
							{
								if (
									$arRes['USER_ID'] == $USER->GetID()
									|| $bCanReadAll
									|| in_array($arRes['USER_ID'], $arAccessUsers['READ'])
								)
								{
									$arRes['TIME_OFFSET'] = CTimeManUser::getDayStartOffset($arRes);

									$bCanEdit = ($bCanEditAll || in_array($arRes['USER_ID'], $arAccessUsers['WRITE']));

									$user_url = COption::GetOptionString('intranet', 'path_user', '/company/personal/user/#USER_ID#/', $_REQUEST['site_id']);

									$obUser = new CTimeManUser($arRes['USER_ID']);
									$obUser->SITE_ID = $_REQUEST['site_id'];

									$bReturnRes = true;

									$dbRes = CUser::GetList(
										$by = 'ID', $order = 'ASC',
										array('ID' => $arRes['USER_ID']),
										array('SELECT' => array('UF_*'))
									);

									$arCurrentUser = $dbRes->GetNext();
									$arCurrentUser['PHOTO'] =
										$arCurrentUser['PERSONAL_PHOTO'] > 0
										? CIntranetUtils::InitImage($arCurrentUser['PERSONAL_PHOTO'], 100, 100, BX_RESIZE_IMAGE_EXACT)
										: array();

									$arManagers = CTimeMan::GetUserManagers($arRes['USER_ID']);

									if (!is_array($arManagers) || count($arManagers) <= 0)
										$arManagers = array($arRes['USER_ID']);

									$user_url = COption::GetOptionString('intranet', 'path_user', '/company/personal/user/#USER_ID#/', $_REQUEST['site_id']);

									$dbManagers = CUser::GetList($by='ID', $order='ASC', array('ID' => implode('|', $arManagers)));

									$arCurrentUserManagers = array();

									while ($manager = $dbManagers->Fetch())
									{
										$manager['PHOTO'] =
											$manager['PERSONAL_PHOTO'] > 0
											? CIntranetUtils::InitImage($manager['PERSONAL_PHOTO'], 100, 100, BX_RESIZE_IMAGE_EXACT)
											: array();

										$arCurrentUserManagers[] = array(
											'ID' => $manager['ID'],
											'LOGIN' => $manager['LOGIN'],
											'NAME' => CUser::FormatName(CSite::GetNameFormat(false), $manager, true, false),
											'URL' => str_replace(array('#ID#', '#USER_ID#'), $manager['ID'], $user_url),
											'WORK_POSITION' => $manager['WORK_POSITION'],
											'PHOTO' => $manager['PHOTO']['CACHE']['src'],
										);
									}

									$arRes['DATE_START'] = MakeTimeStamp($arRes['DATE_START']) - CTimeZone::GetOffset();
									$arRes['DATE_FINISH'] = MakeTimeStamp($arRes['DATE_FINISH']) - CTimeZone::GetOffset();

									$arInfo = array(
										'INFO' => $arRes,
										'DATE_TEXT' => FormatDate('j F Y', $arRes['DATE_START']),
										'CALENDAR_ENABLED' => CBXFeatures::IsFeatureEnabled('Calendar'),
										'TASKS_ENABLED' => CBXFeatures::IsFeatureEnabled('Tasks') && IsModuleInstalled('tasks'),
									);

									$cur_info = $obUser->GetCurrentInfo();
									if ($cur_info['ID'] == $ID)
									{
										$arInfo['STATE'] = $obUser->State();
										$arInfo['EXPIRED_DATE'] = $obUser->GetExpiredRecommendedDate();
									}
									else
									{
										$arInfo['STATE'] = 'CLOSED';
									}

									$res = array(
										'FROM' => array(
											'ID' => $arCurrentUser['ID'],
											'LOGIN' => $arCurrentUser['LOGIN'],
											'NAME' => CUser::FormatName(CSite::GetNameFormat(false), $arCurrentUser, true, false),
											'URL' => str_replace(array('#ID#', '#USER_ID#'), $arCurrentUser['ID'], $user_url),
											'WORK_POSITION' => $arCurrentUser['WORK_POSITION'],
											'PHOTO' => $arCurrentUser['PHOTO']['CACHE']['src']
										),
										'TO' => array_values($arCurrentUserManagers),
										'INFO' => $arInfo,
										'REPORT' => '',
										'CAN_EDIT' => $bCanEdit ? 'Y' : 'N',
										'REPORTS' => array(),
									);

									if (count($res['TO']) <= 0)
										$res['TO'] = array($res['FROM']);

									$arUserIDs = array();

									$dbReports = CTimeManReport::GetList(
										array('ID' => 'ASC'),
										array('ENTRY_ID' => $arInfo['INFO']['ID'])
									);

									while ($arReport = $dbReports->Fetch())
									{
										switch($arReport['REPORT_TYPE'])
										{
											case 'ERR_OPEN':
											case 'ERR_CLOSE':
											case 'ERR_DURATION':
												$arUserIDs[] = $arReport['USER_ID'];

												$key = $arReport['REPORT_TYPE'] == 'ERR_OPEN'
													? 'TIME_START'
													: (
														$arReport['REPORT_TYPE'] == 'ERR_CLOSE'
														? 'TIME_FINISH'
														: 'DURATION'
													);

												$arReportData = explode(';', $arReport['REPORT']);

												if (!$res['REPORTS'][$key])
													$res['REPORTS'][$key] = array();

												$report_ts = strtotime($arReportData[1])+CTimeZone::GetOffset();
												$res['REPORTS'][$key][0] = array(
													'TYPE' => $arReportData[0],
													'TYPE_TEXT' => $arReportData[0],//GetMessage('TM_REPORT_TYPE_'.$arReportData[0]),
													'TIME' => $report_ts + date('Z'),
													'DATE_TIME' => FormatDate(str_replace(':s', '', $DB->DateFormatToPHP(FORMAT_DATETIME)), MakeTimeStamp($arReport['TIMESTAMP_X'])),
													'ACTIVE' => $arReport['ACTIVE'] == 'Y',
													'USER_ID' => $arReport['USER_ID']
												);

											break;

											case 'REPORT_OPEN':
											case 'REPORT_CLOSE':
											case 'REPORT_DURATION':
												$key = $arReport['REPORT_TYPE'] == 'REPORT_OPEN'
													? 'TIME_START'
													: (
														$arReport['REPORT_TYPE'] == 'REPORT_CLOSE'
														? 'TIME_FINISH'
														: 'DURATION'
													);

												if (count($res['REPORTS'][$key]) > 0)
												{
													if (strlen($arReport['REPORT']) > 150)
													{
														$arReport['REPORT_FULL'] = $arReport['REPORT'];
														$arReport['REPORT'] = substr($arReport['REPORT'], 0, 150).'...';
													}

													$res['REPORTS'][$key][0]['REPORT'] = htmlspecialcharsbx($arReport['REPORT']);

													if ($arReport['REPORT_FULL'])
													$res['REPORTS'][$key][count($res['REPORTS'][$key])-1]['REPORT_FULL'] = htmlspecialcharsbx($arReport['REPORT_FULL']);
												}
											break;

											case 'REPORT':
												$res['REPORT'] = nl2br(htmlspecialcharsbx($arReport['REPORT']));
										}
									}

									if (count($arUserIDs) > 0)
									{
										$arUserIDs = array_unique($arUserIDs);
										$dbUsers = CUser::GetList(
											$by='ID', $order='ASC',
											array('ID' => implode('|', $arUserIDs), 'ACTIVE' => 'Y')
										);
										while ($arUser = $dbUsers->Fetch())
										{
											$name = CUser::FormatName(CSite::GetNameFormat(false), $arUser);

											foreach ($res['REPORTS'] as &$rep)
											{
												foreach ($rep as &$arReport)
												{
													if ($arReport['USER_ID'] == $arUser['ID'])
														$arReport['USER_NAME'] = $name;
												}
											}
										}
									}

									$dbRes = CTimeManReportDaily::GetList(array('ID' => 'DESC'), array('ENTRY_ID' => $arInfo['INFO']['ID']));
									if ($arRes = $dbRes->Fetch())
									{
										// $arRes['ACTIVE'], $arRes['MARK'];

										$res['REPORT'] = nl2br(htmlspecialcharsEx($arRes['REPORT']));

										if ($res['INFO']['TASKS_ENABLED'])
											$res['INFO']['TASKS'] = unserialize($arRes['TASKS']);
										else
											unset($res['INFO']['TASKS']);

										if ($res['INFO']['CALENDAR_ENABLED'])
											$res['INFO']['EVENTS'] = unserialize($arRes['EVENTS']);
										else
											unset($res['INFO']['EVENTS']);
									}

									$res['NEIGHBOURS'] = CTimeManEntry::GetNeighbours($arInfo['INFO']['ID'], $arInfo['INFO']['USER_ID'], !!$_REQUEST['slider_type']);

									ob_start();
									$APPLICATION->IncludeComponent('bitrix:timeman.topic.reviews', '', array('ENTRY_ID' => $arInfo['INFO']['ID']), null, array('HIDE_ICONS' => 'Y'));
									$res['COMMENTS'] = trim(ob_get_contents());
									ob_end_clean();
								}
							}
						}


						/**************************************************************************/


					break;
				}

				if (!$res)
				{
					if ($ex = $APPLICATION->GetException())
					{
						$error = "{error: '".CUtil::JSEscape($ex->GetString())."', error_id:'".CUtil::JSEscape($ex->GetId())."'}";
					}
				}

				$APPLICATION->RestartBuffer();

				if ($error)
					echo $error;
				elseif ($bReturnRes)
				{
					echo CUtil::PhpToJsObject($res);
				}
				else
				{
					$info = CTimeMan::GetRuntimeInfo(true);
					$info['PLANNER'] = $info['PLANNER']['DATA'];

					$arReport = $obUser->SetReport('', 0, $info['ID']);
					if (is_array($arReport))
					{
						$info['REPORT'] = $arReport['REPORT'];
						$info['REPORT_TS'] = MakeTimeStamp($arReport['TIMESTAMP_X']);
					}
					echo CUtil::PhpToJsObject($info);
					$info["request_id"] = $_REQUEST["request_id"];
					if (CModule::IncludeModule("pull"))
					{
						CPullWatch::AddToStack('TIMEMANWORKINGDAY_'.$USER->GetID(),
							Array(
								'module_id' => 'timeman',
								'command' => $action,
								'params' => $info
							)
						);
					}
				}
			}
		}
	}
}
else
{
	echo GetMessage('main_include_decode_pass_sess');
}

//require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_after.php");
?>
