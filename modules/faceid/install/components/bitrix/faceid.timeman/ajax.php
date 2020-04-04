<?php

/** @var $USER \CUser */
/** @var $APPLICATION \CMain */

define("NOT_CHECK_FILE_PERMISSIONS", true);
define("PUBLIC_AJAX_MODE", true);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

\Bitrix\Main\Loader::includeModule('faceid');
\Bitrix\Main\Loader::includeModule('timeman');

use Bitrix\Main\Localization\Loc;

// check permissions
$accessAllowed = false;
$acceptedAgreement = \Bitrix\Faceid\AgreementTable::checkUser($USER->getId());

if (empty($USER) || !$USER->GetID())
{
	echo \Bitrix\Main\Web\Json::encode(array('error' => array(
		'code' => 'FACEID_LOCAL_ERR_NO_AUTH',
		'msg' => Loc::getMessage("FACEID_TRACKERWD_CMP_ERROR_ANON")
	)));
	die;
}

if (!empty($_POST['id']) && $_POST['action'] != 'identify')
{
	if ((int) $USER->GetID() === (int) $_POST['id'])
	{
		// request from current user for current user
		$accessAllowed = true;
	}
}

if (!$accessAllowed)
{
	if (!\Bitrix\FaceId\TrackingWorkdayApplication::checkPermission())
	{
		echo \Bitrix\Main\Web\Json::encode(array('error' => array(
			'msg' => Loc::getMessage("FACEID_TRACKERWD_CMP_ERROR_TIMEMAN_ACCESS").' ('.$USER->GetID().')'
		)));
		die;
	}

	// request from admin for any user
	if (!$acceptedAgreement)
	{
		if (!in_array($_POST['action'], array('portal', 'getAgreement', 'acceptAgreement'), true))
		{
			echo \Bitrix\Main\Web\Json::encode(array(
				'error' => array('msg' => Loc::getMessage("FACEID_TRACKERWD_CMP_ERROR_AGREEMENT"))
			));
			die;
		}
	}
}

// check if instrument is enabled
if (!\Bitrix\Main\Config\Option::get('faceid', 'user_index_enabled', 0))
{
	echo \Bitrix\Main\Web\Json::encode(array('error' => array(
		'msg' => Loc::getMessage("FACEID_TRACKERWD_CMP_ERROR_INDEX_DISABLED")
	)));
	die;
}

// remember user before logout
$loggedUser = array(
	'ID' => $USER->GetID(),
	'FULL_NAME' => $USER->GetFullName(),
	'EMAIL' => $USER->GetEmail()
);

$USER->Logout();

$expReasons = \Bitrix\Faceid\UsersTable::getExpiredReasonList();

if (!empty($_POST['action']))
{
	$outputVisitor = array();
	$errorResponse = array();
	$actionStatus = '';

	$portalInfo = array();

	$snapshotBinaryContent = null;

	if (isset($_POST['snapshot']))
	{
		$imageContent = str_replace('data:image/jpeg', 'data://image/jpeg', $_POST['snapshot']);
		$snapshotBinaryContent = base64_decode(str_replace('data://image/jpeg;base64,', '', $imageContent));
	}

	// adapt timezone
	if (!empty($_POST['id']))
	{
		faceidAdjustTimezone($_POST['id']);
	}

	if ($_POST['action'] == 'open')
	{
		$outputVisitor = array('id' => (int) $_POST['id']);
		$tmUser = new CTimeManUser($outputVisitor['id']);

		if ($tmUser->OpenDay() || $tmUser->ReopenDay(true))
		{
			// ok
			$actionStatus = 'OPENED';
			faceidSaveWorkdayActionLog($outputVisitor['id'], 'START', $snapshotBinaryContent);
		}
		else
		{
			$errorResponse = array('msg' => Loc::getMessage("FACEID_TRACKERWD_CMP_ERROR_OPEN_DAY"));
		}
	}
	elseif ($_POST['action'] == 'pause')
	{
		$outputVisitor = array('id' => (int) $_POST['id']);
		$tmUser = new CTimeManUser($outputVisitor['id']);

		if ($tmUser->PauseDay())
		{
			// ok
			$actionStatus = 'PAUSED';
			faceidSaveWorkdayActionLog($outputVisitor['id'], 'PAUSE', $snapshotBinaryContent);
		}
		else
		{
			$errorResponse = array('msg' => Loc::getMessage("FACEID_TRACKERWD_CMP_ERROR_PAUSE_DAY"));
		}
	}
	elseif ($_POST['action'] == 'reopen')
	{
		$outputVisitor = array('id' => (int) $_POST['id']);
		$tmUser = new CTimeManUser($outputVisitor['id']);

		if ($tmUser->ReopenDay(true))
		{
			$actionStatus = 'REOPENED';
			faceidSaveWorkdayActionLog($outputVisitor['id'], 'START', $snapshotBinaryContent);
		}
		else
		{
			$errorResponse = array('msg' => Loc::getMessage("FACEID_TRACKERWD_CMP_ERROR_REOPEN_DAY"));
		}
	}
	elseif ($_POST['action'] == 'close')
	{
		$outputVisitor = array('id' => (int) $_POST['id']);
		$tmUser = new CTimeManUser($outputVisitor['id']);

		$timestamp = false;
		$report = '';

		if (!empty($_POST['ts']) && !empty($_POST['reason']))
		{
			$timestamp = (int) $_POST['ts'];
			$report = $expReasons[$_POST['reason']];
		}

		if ($tmUser->CloseDay($timestamp, $report))
		{
			// ok
			$actionStatus = 'CLOSED';
			faceidSaveWorkdayActionLog($outputVisitor['id'], 'STOP', $snapshotBinaryContent);

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
	elseif ($_POST['action'] == 'identify')
	{
		$imageContent = str_replace('data:image/jpeg', 'data://image/jpeg', $_POST['image']);
		$fileContent = base64_decode(str_replace('data://image/jpeg;base64,', '', $imageContent));

		$visitor = null;

		$confidence = 0;

		$response = \Bitrix\FaceId\FaceId::identifyUser($fileContent);
		$result = $response['result'];

		if (!empty($response['success']) && !empty($response['result']['found']))
		{
			$faceId = $result['items'][0]['face_id'];
			$confidence = round($result['items'][0]['confidence']);

			$userFace = \Bitrix\Faceid\UsersTable::getById($faceId)->fetch();
			if (!empty($userFace))
			{
				$user = \Bitrix\Main\UserTable::getByPrimary($userFace['USER_ID'], array(
					'select' => array('ID', 'LOGIN', 'NAME', 'LAST_NAME', 'PERSONAL_PHOTO', 'WORK_POSITION')
				))->fetch();

				if (!empty($user))
				{
					faceidAdjustTimezone($userFace['USER_ID']);

					if (\Bitrix\Faceid\UsersTable::checkTimemanEnabled($user['ID']))
					{
						$outputVisitor = faceidFormatUserInfo($user);
						$outputVisitor['confidence'] = $confidence;

						$actionStatus = 'INFO';

						// is he working now?
						$tmUser = new CTimeManUser($userFace['USER_ID']);
						$currentStatus = $tmUser->State();

						$openAction = $tmUser->OpenAction();

						// get working time for today
						if ($currentStatus == 'OPENED' || $currentStatus == 'PAUSED'
							|| ($currentStatus == 'CLOSED' && $openAction == 'REOPEN')
						)
						{
							$userOffset = \CTimeZone::GetOffset($userFace['USER_ID']);
							$workdayData = $tmUser->GetCurrentInfo();

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
							if (!empty($_POST['autoOpen']))
							{
								// auto open
								if ($tmUser->OpenDay() || $tmUser->ReopenDay())
								{
									$outputVisitor['workday_status'] = 'OPENED';
									$actionStatus = 'OPENED';
									faceidSaveWorkdayActionLog($outputVisitor['id'], 'START', $snapshotBinaryContent);
								}
								else
								{
									$errorResponse = array('msg' => Loc::getMessage('FACEID_TRACKERWD_CMP_ERROR_OPEN_DAY'));
								}
							}
							else
							{
								// open by input
								$outputVisitor['workday_status'] = 'CLOSED';
								$outputVisitor['workday_open_action'] = $openAction;
							}
						}
						elseif ($currentStatus == 'OPENED')
						{
							$outputVisitor['workday_status'] = 'OPENED';
						}
						elseif ($currentStatus == 'PAUSED')
						{
							$workdayData = $tmUser->GetCurrentInfo();
							$userOffset = \CTimeZone::GetOffset($user['ID']);

							$pDuration = time() + $userOffset - strtotime('today') - $workdayData['TIME_FINISH'] + $workdayData['TIME_LEAKS'];

							$outputVisitor['workday_pause_duration'] = $pDuration;
							$outputVisitor['workday_status'] = 'PAUSED';
						}
						elseif ($currentStatus == 'EXPIRED')
						{
							$outputVisitor['workday_status'] = 'EXPIRED';
							$outputVisitor['workday_expired_reasons'] = $expReasons;
							$outputVisitor['workday_expired_reasons_string'] = \Bitrix\Main\Web\Json::encode($expReasons);

							// get day of expiration
							$timemanInfo = $tmUser->GetCurrentInfo();
							$startDate = new \Bitrix\Main\Type\DateTime($timemanInfo['DATE_START']);
							$outputVisitor['workday_expired_day'] = $startDate->format('Y-m-d');

							// get time of start
							$workdayData = $tmUser->GetCurrentInfo();
							$outputVisitor['workday_expired_start'] =  $workdayData['TIME_START'];
							$outputVisitor['workday_expired_predicted_close_time'] = $tmUser->getExpiredRecommendedDate();
						}
					}
					else
					{
						$errorResponse = array(
							'msg' => Loc::getMessage("FACEID_TRACKERWD_CMP_ERROR_TIMEMAN_DISABLED").' ('.$user['ID'].')'
						);
					}
				}
				else
				{
					// user not found in local db (old cloud index)
					$errorResponse = array(
						'msg' => Loc::getMessage("FACEID_TRACKERWD_CMP_ERROR_USER_NOT_FOUND_LOCAL").' (u'.$user['ID'].', f'.$faceId.')'
					);
				}
			}
			else
			{
				// no face in local db
				$errorResponse = array('msg' => Loc::getMessage("FACEID_TRACKERWD_CMP_ERROR_NO_FACE_LOCAL").' ('.$faceId.')');
			}
		}
		else
		{
			$errorResponse = array(
				'code' => $response['result']['code'],
				'msg' => \Bitrix\FaceId\FaceId::getErrorMessage($response['result']['code'])
			);
		}
	}
	elseif ($_POST['action'] == 'info')
	{
		$user = \Bitrix\Main\UserTable::getByPrimary($_POST['id'], array(
			'select' => array('ID', 'LOGIN', 'NAME', 'LAST_NAME', 'PERSONAL_PHOTO', 'WORK_POSITION')
		))->fetch();

		if (!empty($user))
		{
			if (\Bitrix\Faceid\UsersTable::checkTimemanEnabled($user['ID']))
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
	elseif ($_POST['action'] == 'portal')
	{
		// empty query, portal info will be places in response automatically
		$actionStatus = 'INFO';

		$portalInfo['agreement'] = $acceptedAgreement;
	}
	elseif ($_POST['action'] == 'getAgreement')
	{
		$actionStatus = 'INFO';
		$portalInfo['agreementText'] = \Bitrix\Faceid\AgreementTable::getAgreementText();
	}
	elseif ($_POST['action'] == 'acceptAgreement')
	{
		\Bitrix\Faceid\AgreementTable::add(array(
			'USER_ID' => $loggedUser['ID'],
			'NAME' => $loggedUser['FULL_NAME'],
			'EMAIL' => $loggedUser['EMAIL'],
			'DATE' => new \Bitrix\Main\Type\DateTime,
			'IP_ADDRESS' => \Bitrix\Main\Context::getCurrent()->getRequest()->getRemoteAddress()
		));

		$actionStatus = 'ACCEPTED';
	}
	else
	{
		$errorResponse = array('msg' => 'unknown action');
	}

	// portal info
	$portalInfo['title'] = faceidGetPortalTitle();
	$portalInfo['logo24'] = \COption::GetOptionString("bitrix24", "logo24show", "Y") !== "N";

	// error info
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
		'status' => array(
			'balance' => \Bitrix\Main\Config\Option::get('faceid', 'balance', '1000'),
		),
		'portal' => $portalInfo,
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

function faceidSaveWorkdayActionLog($userId, $action, $imageBinaryContent)
{
	$snapshotId = 0;

	if (!empty($imageBinaryContent))
	{
		$snapshotId = \CFile::SaveFile(array(
			'MODULE_ID' => 'faceid',
			'name' => 'face_'.microtime(true).'.jpg',
			'type' => 'image/jpeg',
			'content' => $imageBinaryContent,
			'description' => 'faceid_wds'
		), 'faceid_wds'); // work day snapshots
	}

	return \Bitrix\Faceid\TrackingWorkdayTable::add(array(
		'USER_ID' => $userId,
		'ACTION' => $action,
		'SNAPSHOT_ID' => $snapshotId
	));
}

function faceidGetPortalTitle()
{
	$title = \COption::GetOptionString("bitrix24", "site_title", "");
	if (empty($title))
	{
		$title = \COption::GetOptionString("main", "site_name", "");
	}

	return $title;
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

CMain::FinalActions();
