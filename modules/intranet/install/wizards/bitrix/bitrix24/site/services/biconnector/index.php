<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

if (!Bitrix\Main\Loader::includeModule('bitrix24'))
{
	return;
}

$taskId = CTask::getIdByLetter('W', 'biconnector');
if ($taskId)
{
	$adminGroups = [12, 1];
	$rights = CGroup::getTasksForModule('biconnector');
	foreach ($adminGroups as $groupId)
	{
		$rights[$groupId] = ['ID' => $taskId];
	}
	CGroup::setTasksForModule('biconnector', $rights);
}
