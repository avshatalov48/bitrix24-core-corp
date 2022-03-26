<?require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

if($_SERVER["REQUEST_METHOD"]=="POST" && $_POST["action"] <> '' && check_bitrix_sessid())
{
	CUtil::JSPostUnescape();
	$ajaxResult = array();
	switch ($_POST["action"])
	{
		case "setView":
		{
			if (isset($_POST["currentStepId"]))
			{
				$currentStepId = $_POST["currentStepId"];

				$arViewedSteps = CUserOptions::GetOption("bitrix24", "new_helper_views", array());
				if (!in_array($currentStepId, $arViewedSteps))
				{
					$arViewedSteps[] = $currentStepId;
					CUserOptions::SetOption("bitrix24", "new_helper_views", $arViewedSteps);
				}
			}
			break;
		}
		case "setNotify":
		{
			$notify = CUserOptions::GetOption("bitrix24", "new_helper_notify", array());
			if (isset($_POST["time"]) && $_POST["time"] == "hour")
			{
				$notify["time"] = time() + 60*60;
			}
			elseif (isset($_POST["num"]))
			{
				$notify["num"] = intval($_POST["num"]);
				$notify["time"] = time() + 24*60*60;

				if ($notify["num"] == 0)//user has read all notifies
				{
					$notify["counter_update_date"] = time(); // time when user read all current notifications
				}
			}

			CUserOptions::SetOption("bitrix24", "new_helper_notify", $notify);
			break;
		}
		case 'saveNotifications':
		{
			$notify = CUserOptions::GetOption("bitrix24", "new_helper_notify", array());

			$needUpdate = false;
			$userOptionNotificationsParamName = 'notifications';
			if (array_key_exists($userOptionNotificationsParamName, $_POST))
			{
				$notifications = trim($_POST[$userOptionNotificationsParamName]);
				if($notifications <> '')
				{
					$notify[$userOptionNotificationsParamName] = $notifications;
					$needUpdate = true;
					$ajaxResult['isNotificationsUpdate'] = true;
				}
			}
			$userOptionLastCheckNotificationsParamName = 'lastCheckNotificationsTime';
			if ($_POST[$userOptionLastCheckNotificationsParamName] === 'Y')
			{
				$notify[$userOptionLastCheckNotificationsParamName] = time();
				$needUpdate = true;
				$ajaxResult['isTimeUpdate'] = true;
			}

			if($needUpdate)
			{
				CUserOptions::SetOption("bitrix24", "new_helper_notify", $notify);
			}
			break;
		}
	}
	if(is_array($ajaxResult) && count($ajaxResult) > 0)
	{
		try
		{
			echo \Bitrix\Main\Web\Json::encode($ajaxResult);
		}
		catch (\Exception $jsonParseException)
		{
			echo 'saveError';
		}
	}
	CMain::FinalActions();
	die();
}
?>