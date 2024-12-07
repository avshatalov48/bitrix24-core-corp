<?php
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('PUBLIC_AJAX_MODE', true);
define('DisableEventsCheck', true);

use Bitrix\Crm\Activity\Analytics\Dictionary;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Analytics\AnalyticsEvent;
use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

if (!\Bitrix\Main\Loader::includeModule('crm'))
{
	die();
}

Container::getInstance()->getLocalization()->loadMessages();

$context = Application::getInstance()->getContext();
$request = $context->getRequest();
$action = $request->get('action');
$id = $request->get('id');
$ownerId = $request->get('ownerid');
$ownerTypeId = $request->get('ownertypeid');
$providerId = $request->get('providerId');
$result = array();

if (!\Bitrix\Main\Loader::includeModule('crm'))
{
	$result = array('ERROR' => 'Unknown type');
}
//complete
elseif ($action == 'complete' && !empty($id) && !empty($ownerId))
{
	//waiter
	if (mb_strtolower(mb_substr($id, 0, 1)) == 'w')
	{
		$id = mb_substr($id, 1);
		if (
			($ownerTypeId == \CCrmOwnerType::Lead && \CCrmLead::getById($ownerId))
			||
			($ownerTypeId == \CCrmOwnerType::Deal && \CCrmDeal::getById($ownerId))
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
			$result = array('ERROR' => Loc::getMessage('CRM_COMMON_ERROR_ACCESS_DENIED'));
		}
		else
		{
			$completed = $request->get('completed') > 0;
			if (\CCrmActivity::Complete($id, $completed, array('REGISTER_SONET_EVENT' => true)))
			{
				$result = array('SUCCESS' => 1);
				sendAnalyticsEvent($providerId, $ownerTypeId);
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

function sendAnalyticsEvent(string $providerId, int $ownerTypeId): void
{
	if ($providerId !== \Bitrix\Crm\Activity\Provider\ToDo\ToDo::PROVIDER_ID)
	{
		return;
	}

	$entityType = \Bitrix\Crm\Integration\Analytics\Dictionary::getAnalyticsEntityType($ownerTypeId);
	if ($entityType === null)
	{
		return;
	}

	$section = $entityType . '_section';

	$event = new AnalyticsEvent(
		Dictionary::COMPLETE_EVENT,
		Dictionary::TOOL,
		Dictionary::OPERATIONS_CATEGORY
	);
	$event
		->setType(Dictionary::TODO_TYPE)
		->setSection($section)
		->setSubSection(Dictionary::KANBAN_SUB_SECTION)
		->setElement(Dictionary::CHECKBOX_ELEMENT)
		->setP1(\Bitrix\Crm\Integration\Analytics\Dictionary::getCrmMode())
		->send()
	;
}

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');
