<?php
$langs = CLanguage::GetList();
while ($lang = $langs->Fetch())
{
	$lid = $lang['LID'];
	IncludeModuleLangFile(__FILE__, $lid);

	CEventType::Add([
		'LID' => $lid,
		'EVENT_NAME' => 'CONTROLLER_MEMBER_REGISTER',
		'NAME' => GetMessage('CONTROLLER_MEMBER_REGISTER_NAME'),
		'DESCRIPTION' => GetMessage('CONTROLLER_MEMBER_REGISTER_DESC'),
	]);

	CEventType::Add([
		'LID' => $lid,
		'EVENT_NAME' => 'CONTROLLER_MEMBER_CLOSED',
		'NAME' => GetMessage('CONTROLLER_MEMBER_CLOSED_NAME'),
		'DESCRIPTION' => GetMessage('CONTROLLER_MEMBER_CLOSED_DESC'),
	]);

	CEventType::Add([
		'LID' => $lid,
		'EVENT_NAME' => 'CONTROLLER_MEMBER_OPENED',
		'NAME' => GetMessage('CONTROLLER_MEMBER_OPENED_NAME'),
		'DESCRIPTION' => GetMessage('CONTROLLER_MEMBER_OPENED_DESC'),
	]);
}
