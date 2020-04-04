<?

/** @var $USER \CALLUser */
/** @var $APPLICATION \CALLUser */

define("NOT_CHECK_FILE_PERMISSIONS", true);
define("PUBLIC_AJAX_MODE", true);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

\Bitrix\Main\Loader::includeModule('timeman');



use Bitrix\Main\Localization\Loc;
header("Content-Type: application/x-javascript");

// check permissions
$accessAllowed = false;

if (empty($USER) || !$USER->GetID())
{
	echo \Bitrix\Main\Web\Json::encode(array('error' => array(
		'code' => 'FACEID_LOCAL_ERR_NO_AUTH',
		'msg' => Loc::getMessage("FACEID_TRACKERWD_CMP_ERROR_ANON")
	)));
	die;
}

$userId = $USER->getId();

if (!empty($userId) && $_REQUEST['action'] != 'identify')
{
	if ((int) $USER->GetID() === (int) $userId)
	{
		// request from current user for current user
		$accessAllowed = true;
	}
}

// remember user before logout
$loggedUser = array(
	'ID' => $USER->GetID(),
	'FULL_NAME' => $USER->GetFullName(),
	'EMAIL' => $USER->GetEmail()
);


if (!empty($_REQUEST['action']))
{
	$outputVisitor = array();
	$errorResponse = array();
	$actionStatus = '';

	$portalInfo = array();

	// adapt timezone
	if (!empty($userId))
	{
		faceidAdjustTimezone($userId);
	}

	if ($_REQUEST['action'] == 'open')
	{
		$outputVisitor = array('id' => (int) $userId);
		$tmUser = new CTimeManUser($outputVisitor['id']);

		if ($tmUser->OpenDay() || $tmUser->ReopenDay(true))
		{
			// ok
			$actionStatus = 'OPENED';
		}
		else
		{
			$errorResponse = array('msg' => Loc::getMessage("FACEID_TRACKERWD_CMP_ERROR_OPEN_DAY"));
		}
	}
	elseif ($_REQUEST['action'] == 'pause')
	{
		$outputVisitor = array('id' => (int) $userId);
		$tmUser = new CTimeManUser($outputVisitor['id']);

		if ($tmUser->PauseDay())
		{
			// ok
			$actionStatus = 'PAUSED';
		}
		else
		{
			$errorResponse = array('msg' => Loc::getMessage("FACEID_TRACKERWD_CMP_ERROR_PAUSE_DAY"));
		}
	}
	elseif ($_REQUEST['action'] == 'reopen')
	{
		$outputVisitor = array('id' => (int) $userId);
		$tmUser = new CTimeManUser($outputVisitor['id']);

		if ($tmUser->ReopenDay(true))
		{
			$actionStatus = 'REOPENED';
		}
		else
		{
			$errorResponse = array('msg' => Loc::getMessage("FACEID_TRACKERWD_CMP_ERROR_REOPEN_DAY"));
		}
	}
	elseif ($_REQUEST['action'] == 'close')
	{
		$outputVisitor = array('id' => (int) $userId);
		$tmUser = new CTimeManUser($outputVisitor['id']);

		$timestamp = false;
		$report = '';

		if (!empty($_REQUEST['ts']) && !empty($_REQUEST['reason']))
		{
			$timestamp = (int) $_REQUEST['ts'];
			$report = $expReasons[$_REQUEST['reason']];
		}

		if ($tmUser->CloseDay($timestamp, $report))
		{
			// ok
			$actionStatus = 'CLOSED';
			// save daily report
			$currentInfo = $tmUser->GetCurrentInfo();
			$reportData = $tmUser->SetReport('', 0, $currentInfo['ID']);

			$dailyReportFields = array(
				'USER_ID' => $outputVisitor['id'],
				'ENTRY_ID' => $currentInfo['ID'],
				'REPORT_DATE' => $currentInfo['DATE_START'],
				'ACTIVE' => $currentInfo['ACTIVE'],
				'REPORT' => $reportData['REPORT'],
			);

			\CTimeManReportDaily::Add($dailyReportFields);
		}
		else
		{
			$errorResponse = array('msg' => Loc::getMessage("FACEID_TRACKERWD_CMP_ERROR_CLOSE_DAY"));
		}
	}
	elseif ($_REQUEST['action'] == 'info')
	{
		$user = \Bitrix\Main\UserTable::getByPrimary($USER->getId(), array(
			'select' => array('ID', 'LOGIN', 'NAME', 'LAST_NAME', 'PERSONAL_PHOTO', 'WORK_POSITION')
		))->fetch();

		if (!empty($user))
		{
			if (isTimemanEnabled($user['ID']))
			{
				$outputVisitor = faceidFormatUserInfo($user);

				$actionStatus = 'INFO';

				// is he working now?
				$tmUser = new CTimeManUser($user['ID']);
				$currentStatus = $tmUser->State();

				$openAction = $tmUser->OpenAction();

				// get working time for today
				if ($currentStatus == 'OPENED' || $currentStatus == 'PAUSED'
					|| ($currentStatus == 'CLOSED' && $openAction == 'REOPEN')
				)
				{
					$userOffset = \CTimeZone::GetOffset($user['ID']);
					$workdayData = $tmUser->GetCurrentInfo(true);

					if ($currentStatus == 'OPENED')
					{
						$lastUserWorkTime = time() + $userOffset;
					}
					else
					{
						$lastUserWorkTime = MakeTimeStamp($workdayData['DATE_FINISH']);
					}

					$wDuration = $lastUserWorkTime - MakeTimeStamp($workdayData['DATE_START']) - $workdayData['TIME_LEAKS'];

					$outputVisitor['workday_duration'] = $wDuration;
				}

				if ($currentStatus == 'CLOSED')
				{
					$outputVisitor['workday_status'] = 'CLOSED';
					$outputVisitor['workday_open_action'] = $openAction;
				}
				elseif ($currentStatus == 'OPENED')
				{
					$workdayData = $tmUser->GetCurrentInfo();

					$outputVisitor['workday_status'] = 'OPENED';
				}
				elseif ($currentStatus == 'PAUSED')
				{
					$workdayData = $tmUser->GetCurrentInfo();
					$userOffset = \CTimeZone::GetOffset($user['ID']);

					$pDuration = time() + $userOffset - strtotime('today') - $workdayData['TIME_FINISH'];

					$outputVisitor['workday_status'] = 'PAUSED';
					$outputVisitor['workday_pause_duration'] = $pDuration;
				}
				elseif ($currentStatus == 'EXPIRED')
				{
					$outputVisitor['workday_status'] = 'EXPIRED';

					// get time of start
					$workdayData = $tmUser->GetCurrentInfo();
					$outputVisitor['workday_expired_start'] =  $workdayData['TIME_START'];
					$outputVisitor['workday_expired_predicted_close_time'] = $tmUser->getExpiredRecommendedDate();
				}
			}
			else
			{
				$errorResponse = array('msg' => Loc::getMessage("FACEID_TRACKERWD_CMP_ERROR_TIMEMAN_DISABLED").' ('.$user['ID'].')');
			}
		}
		else
		{
			// user not found in local db (old cloud index)
			$errorResponse = array('msg' => Loc::getMessage("FACEID_TRACKERWD_CMP_ERROR_USER_NOT_FOUND_LOCAL").' ('.$user['ID'].')');
		}
	}
	else
	{
		$errorResponse = array('msg' => 'unknown action');
	}

	if (!empty($errorResponse))
	{
		/*$errorResponse = array(
			//'code' => $result['code'],
			//'msg' => \Bitrix\FaceId\FaceId::getErrorMessage($result['code'])
			'msg' => $errorResponse['msg']
		);*/

		$actionStatus = 'ERROR';
	}

	// output

	echo \Bitrix\Main\Web\Json::encode(array(
		'visitor' => $outputVisitor,
		'action' => $actionStatus,
		'error' => $errorResponse
	), JSON_FORCE_OBJECT);
}

function faceidFormatUserInfo($user)
{
	// format json response
	$user['FULL_NAME'] = $user['NAME']." ".$user['LAST_NAME'];

	if (!strlen(trim($user['FULL_NAME'])))
	{
		$user['FULL_NAME'] = $user['LOGIN'];
	}

	$outputVisitor = array(
		'id' => $user['ID'],
		'name' => $user['NAME'],
		'last_name' => $user['LAST_NAME'],
		'full_name' => $user['FULL_NAME'],
		'work_position' => $user['WORK_POSITION'],
		'workday_status' => ''
	);

	return $outputVisitor;
}

function faceidAdjustTimezone($userId)
{
	if (\CTimeZone::OptionEnabled())
	{
		$localTime = new DateTime();
		$userOffset = $localTime->getOffset() + \CTimeZone::GetOffset($userId);

		$cookieValue = $userOffset/-60;
		CTimeZone::SetCookieValue($cookieValue);
	}
}

function isTimemanEnabled($userId)
{
	if (\Bitrix\Main\Loader::includeModule('timeman'))
	{
		$tmUser = new \CTimeManUser($userId);
		$settings = $tmUser->GetSettings();
		return (bool)$settings['UF_TIMEMAN'];
	}

	return false;
}

CMain::FinalActions();