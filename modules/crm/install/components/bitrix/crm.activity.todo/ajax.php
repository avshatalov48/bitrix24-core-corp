<?php
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('PUBLIC_AJAX_MODE', true);
define('DisableEventsCheck', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

if (!\Bitrix\Main\Loader::includeModule('crm'))
{
	die();
}

$context = \Bitrix\Main\Application::getInstance()->getContext();
$request = $context->getRequest();
$action = $request->get('action');
$id = $request->get('id');
$ownerId = $request->get('ownerid');
$ownerTypeId = $request->get('ownertypeid');
$result = array();

if (!\Bitrix\Main\Loader::includeModule('crm'))
{
	$result = array('ERROR' => 'Unknown type');
}
//complete
elseif ($action == 'complete' && !empty($id) && !empty($ownerId))
{
	//waiter
	if (strtolower(substr($id, 0, 1)) == 'w')
	{
		$id = substr($id, 1);
		if (
			($ownerTypeId == \CCrmOwnerType::Lead && \CCrmLead::getById($id))
			||
			($ownerTypeId == \CCrmOwnerType::Deal && \CCrmDeal::getById($id))
		)
		{
			\Bitrix\Crm\Pseudoactivity\WaitEntry::complete($id, true);
		}
		else
		{
			$result = array('ERROR' => 'Not found');
		}
	}
	elseif (!($activity = \CCrmActivity::GetByID($id)))
	{
		$result = array('ERROR' => 'Not found');
	}
	elseif (!\CCrmOwnerType::IsDefined($ownerTypeId))
	{
		$result = array('ERROR' => 'Owner type is not defined');
	}
	else
	{
		$userPermissions = \CCrmPerms::GetCurrentUserPermissions();
		if (!\CCrmActivity::CheckCompletePermission($ownerTypeId, $ownerId, $userPermissions, array('FIELDS' => $activity)))
		{
			$result = array('ERROR' => 'Access denied');
		}
		else
		{
			$completed = $request->get('completed') > 0;
			if (\CCrmActivity::Complete($id, $completed, array('REGISTER_SONET_EVENT' => true)))
			{
				$result = array('SUCCESS' => 1);
			}
			else
			{
				$error = \CCrmActivity::GetLastErrorMessage();
				if (!is_array($error) || !isset($error[0]))
				{
					$result = array('ERROR' => 'Could not complete activity');
				}
				else
				{
					$result = array('ERROR' => $error[0]);
				}
			}
		}
	}
}
else
{
	$result = array('ERROR' => 'Unknown action or params');
}

//output
if (is_array($result) && (isset($result['SUCCESS']) || isset($result['ERROR'])))
{
	$GLOBALS['APPLICATION']->RestartBuffer();
	if (SITE_CHARSET != 'UTF-8')
	{
		$result = $GLOBALS['APPLICATION']->ConvertCharsetArray($result, SITE_CHARSET, 'UTF-8');
	}

	header('Content-Type: application/json');

	if (isset($result['ERROR']))
	{
		echo json_encode(array('error' => $result['ERROR']));
	}
	else
	{
		echo json_encode(array('success' => 1));
	}
}


require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');