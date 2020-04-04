<?php

use \Bitrix\Main\Localization\Loc;

define("STOP_STATISTICS", true);
define("BX_SECURITY_SHOW_MESSAGE", true);
define('NO_AGENT_CHECK',     true);
define('DisableEventsCheck', true);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

CUtil::JSPostUnescape();
CModule::IncludeModule('tasks');

Loc::loadMessages(__FILE__);

function tasksTaskEditAjaxHandler()
{
	global $DB;

	if (isset($_POST['NAME_TEMPLATE']))
		$nameTemplate = $_POST['NAME_TEMPLATE'];
	else
		$nameTemplate = CSite::getNameFormat(false);

	if ( ! check_bitrix_sessid() )
		die();

	switch ($_POST['action'])
	{
		case 'tasks_isUserMemberOfGroup':
			if (!CModule::IncludeModule('socialnetwork'))
			{
				throw new Exception($_POST['action'] 
					. ': socialnetwork module failed to load.');
			}

			if (
				( ! isset($_POST['groupId']) )
				|| ( ! isset($_POST['userId']) )
				|| ($_POST['groupId'] < 0)
				|| ($_POST['userId'] < 0)
			)
			{
				throw new Exception($_POST['action'] 
					. ': invalid userId or groupId');
			}

			$rc = CSocNetUserToGroup::GetUserRole(
				(int) $_POST['userId'],
				(int) $_POST['groupId']
			);

			if (($rc === false) || ($rc == SONET_ROLES_REQUEST))
				echo 'N';
			else
				echo 'Y';
		break;

		case 'getWarnings':
			if (isset($_POST['TASK']['RESPONSIBLE_ID']))
			{
				$responsibleId = (int) $_POST['TASK']['RESPONSIBLE_ID'];
				$responsibleName = '#unknown user#';

				$rsUser = CUser::GetList(
					$by = 'ID', $order = 'ASC', 
					array('ID' => $responsibleId),
					array('FIELDS' => array('NAME', 'LAST_NAME', 'SECOND_NAME', 'LOGIN'))
				);

				if ($arUser = $rsUser->fetch())
				{
					$responsibleName = CUser::FormatName(
						$nameTemplate,
						array(
							"NAME"        => $arUser["NAME"],
							"LAST_NAME"   => $arUser["LAST_NAME"],
							"SECOND_NAME" => $arUser["SECOND_NAME"],
							"LOGIN"       => $arUser["LOGIN"]
						), true, false
					);
				}

				if (isset($_POST['TASK']['GROUP_ID']) && $_POST['TASK']['GROUP_ID'])
				{
					if (CModule::IncludeModule('socialnetwork'))
					{
						$rc = CSocNetUserToGroup::GetUserRole(
							$responsibleId,
							(int) $_POST['TASK']['GROUP_ID']
						);

						if (($rc === false) || ($rc == SONET_ROLES_REQUEST))
						{
							$arGroup = CSocNetGroup::GetByID($_POST['TASK']['GROUP_ID']);

							echo '<div>' . htmlspecialcharsbx(str_replace(
								array('#FORMATTED_USER_NAME#', '#GROUP_NAME#'),
								array($responsibleName, $arGroup["NAME"]),
								GetMessage('TASKS_WARNING_RESPONSIBLE_NOT_IN_TASK_GROUP')
							)) . "</div>\n";
						}
					}
				}

				if (CModule::IncludeModule('intranet'))
				{
					$dt = ConvertTimeStamp(false, 'SHORT');

					$arAbsenceData = CIntranetUtils::GetAbsenceData(
						array(
							'USERS'       => array($responsibleId),
							'DATE_START'  => $dt,
							'DATE_FINISH' => $dt,
							'PER_USER'    => false
						),
						$MODE = BX_INTRANET_ABSENCE_ALL
					);

					$curTs = MakeTimeStamp(ConvertTimeStamp(false, 'FULL'));

					if (isset($arAbsenceData[0]))
					{
						if (
							array_key_exists('DATE_ACTIVE_FROM', $arAbsenceData[0])
							&& array_key_exists('DATE_ACTIVE_TO', $arAbsenceData[0])
						)
						{
							$fromTs = MakeTimeStamp($arAbsenceData[0]['DATE_ACTIVE_FROM']);
							$toTs   = MakeTimeStamp($arAbsenceData[0]['DATE_ACTIVE_TO']);
						}
						else
						{
							$fromTs = MakeTimeStamp($arAbsenceData[0]['DATE_FROM']);
							$toTs   = MakeTimeStamp($arAbsenceData[0]['DATE_TO']);
						}

						if ($toTs > $curTs)
						{
							$from = FormatDate(
								$DB->DateFormatToPhp(CSite::GetDateFormat(
									CIntranetUtils::IsDateTime($fromTs) ? 'FULL' : 'SHORT'
								)),
								$fromTs
							);

							$to = FormatDate(
								$DB->DateFormatToPhp(CSite::GetDateFormat(
									CIntranetUtils::IsDateTime($toTs) ? 'FULL' : 'SHORT'
								)),
								$toTs							
							);

							echo '<div>' . htmlspecialcharsbx(str_replace(
								array(
									'#FORMATTED_USER_NAME#', 
									'#DATE_FROM#',
									'#DATE_TO#',
									'#ABSCENCE_REASON#'
								),
								array(
									$responsibleName,
									$from,
									$to,
									$arAbsenceData[0]['NAME']
								),
								GetMessage('TASKS_WARNING_RESPONSIBLE_IS_ABSENCE')
							)) . '</div>';
						}
					}
				}
			}
		break;

		default:
			throw new Exception('Requested action is unknown!');
		break;
	}
}

try
{
	ob_start();
	tasksTaskEditAjaxHandler();
	ob_end_flush();
}
catch (Exception $e)
{
	ob_end_clean();
	$strErrorMessage = $e->GetMessage();

	if (!strlen($strErrorMessage))
		$strErrorMessage = 'Request cannot be processed!';

	echo 'FATAL ERROR: ' . htmlspecialcharsbx($strErrorMessage);
}

CMain::FinalActions(); // to make events work on bitrix24
