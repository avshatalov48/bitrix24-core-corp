<?
$langs = CLanguage::GetList(($b=""), ($o=""));
while($lang = $langs->Fetch())
{
	$lid = $lang["LID"];
	IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/tasks/install/events.php", $lid);

	$et = new CEventType;
	$et->Add(array(
		"LID" => $lid,
		"EVENT_NAME" => "TASK_REMINDER",
		"NAME" => GetMessage("TASK_REMINDER_NAME"),
		"DESCRIPTION" => GetMessage("TASK_REMINDER_DESC"),
	));

	$et->Add(array(
		"LID" => $lid,
		"EVENT_NAME" => "TASKS_ADD_TASK",
		"NAME" => GetMessage("TASKS_ADD_TASK_NAME"),
		"DESCRIPTION" => GetMessage("TASKS_ADD_TASK_DESC"),
	));

	$et->Add(array(
		"LID" => $lid,
		"EVENT_NAME" => "TASKS_UPDATE_TASK",
		"NAME" => GetMessage("TASKS_UPDATE_TASK_NAME"),
		"DESCRIPTION" => GetMessage("TASKS_UPDATE_TASK_DESC"),
	));

	$et->Add(array(
		"LID" => $lid,
		"EVENT_NAME" => "TASKS_STATUS_TASK",
		"NAME" => GetMessage("TASKS_STATUS_TASK_NAME"),
		"DESCRIPTION" => GetMessage("TASKS_STATUS_TASK_DESC"),
	));

	$et->Add(array(
		"LID" => $lid,
		"EVENT_NAME" => "TASKS_ADD_COMMENT",
		"NAME" => GetMessage("TASKS_ADD_COMMENT_NAME"),
		"DESCRIPTION" => GetMessage("TASKS_ADD_COMMENT_DESC"),
	));

	// mail user events
	$et->Add(array(
		"LID" => $lid,
		"EVENT_NAME" => "TASKS_TASK_ADD_EMAIL",
		"NAME" => GetMessage("TASKS_TASK_ADD_EMAIL_NAME"),
		"DESCRIPTION" => GetMessage("TASKS_TASK_ADD_EMAIL_DESC"),
	));
	$et->Add(array(
		"LID" => $lid,
		"EVENT_NAME" => "TASKS_TASK_UPDATE_EMAIL",
		"NAME" => GetMessage("TASKS_TASK_UPDATE_EMAIL_NAME"),
		"DESCRIPTION" => GetMessage("TASKS_TASK_UPDATE_EMAIL_DESC"),
	));
	$et->Add(array(
		"LID" => $lid,
		"EVENT_NAME" => "TASKS_TASK_COMMENT_ADD_EMAIL",
		"NAME" => GetMessage("TASKS_TASK_COMMENT_ADD_EMAIL_NAME"),
		"DESCRIPTION" => GetMessage("TASKS_TASK_COMMENT_ADD_EMAIL_DESC"),
	));

	$arSites = array();
	$sites = CSite::GetList(($b=""), ($o=""), Array("LANGUAGE_ID"=>$lid));
	while ($site = $sites->Fetch())
		$arSites[] = $site["LID"];

	if(count($arSites) > 0)
	{
		$emess = new CEventMessage;

		$emess->Add(array(
			"ACTIVE" => "Y",
			"EVENT_NAME" => "TASK_REMINDER",
			"LID" => $arSites,
			"EMAIL_FROM" => "#DEFAULT_EMAIL_FROM#",
			"EMAIL_TO" => "#EMAIL_TO#",
			"SUBJECT" => GetMessage("TASK_REMINDER_SUBJECT"),
			"MESSAGE" => GetMessage("TASK_REMINDER_MESSAGE"),
			"BODY_TYPE" => "text",
		));

		$emess->Add(array(
			"ACTIVE" => "Y",
			"EVENT_NAME" => "TASKS_ADD_TASK",
			"LID" => $arSites,
			"EMAIL_FROM" => "#EMAIL_FROM#",
			"EMAIL_TO" => "#EMAIL_TO#",
			"SUBJECT" => "#TASK_TITLE#",
			"MESSAGE" => "<?EventMessageThemeCompiler::includeComponent(\"bitrix:tasks.mail.task\",\"\",Array(\"EMAIL_TO\" => \"{#EMAIL_TO#}\",\"USER_RECEIVER\" => \"{#RECIPIENT_ID#}\",\"ID\" => \"{#TASK_ID#}\"));?>",
			"BODY_TYPE" => "html",
			"SITE_TEMPLATE_ID" => "mail_user"
		));

		$emess->Add(array(
			"ACTIVE" => "Y",
			"EVENT_NAME" => "TASKS_UPDATE_TASK",
			"LID" => $arSites,
			"EMAIL_FROM" => "#EMAIL_FROM#",
			"EMAIL_TO" => "#EMAIL_TO#",
			"SUBJECT" => "#TASK_TITLE#",
			"MESSAGE" => "<?EventMessageThemeCompiler::includeComponent(\"bitrix:tasks.mail.task\",\"\",Array(\"EMAIL_TO\" => \"{#EMAIL_TO#}\",\"USER_RECEIVER\" => \"{#RECIPIENT_ID#}\",\"ID\" => \"{#TASK_ID#}\"));?>",
			"BODY_TYPE" => "html",
			"SITE_TEMPLATE_ID" => "mail_user"
		));

		$emess->Add(array(
			"ACTIVE" => "Y",
			"EVENT_NAME" => "TASKS_STATUS_TASK",
			"LID" => $arSites,
			"EMAIL_FROM" => "#EMAIL_FROM#",
			"EMAIL_TO" => "#EMAIL_TO#",
			"SUBJECT" => "#TASK_TITLE#",
			"MESSAGE" => "<?EventMessageThemeCompiler::includeComponent(\"bitrix:tasks.mail.task\",\"\",Array(\"EMAIL_TO\" => \"{#EMAIL_TO#}\",\"USER_RECEIVER\" => \"{#RECIPIENT_ID#}\",\"ID\" => \"{#TASK_ID#}\"));?>",
			"BODY_TYPE" => "html",
			"SITE_TEMPLATE_ID" => "mail_user"
		));

		$emess->Add(array(
			"ACTIVE" => "Y",
			"EVENT_NAME" => "TASKS_ADD_COMMENT",
			"LID" => $arSites,
			"EMAIL_FROM" => "#EMAIL_FROM#",
			"EMAIL_TO" => "#EMAIL_TO#",
			"SUBJECT" => "#TASK_TITLE#",
			"MESSAGE" => "<?EventMessageThemeCompiler::includeComponent(\"bitrix:tasks.mail.task\",\"\",Array(\"ENTITY\" => \"COMMENT\", \"EMAIL_TO\" => \"{#EMAIL_TO#}\",\"USER_RECEIVER\" => \"{#RECIPIENT_ID#}\",\"ID\" => \"{#TASK_ID#}\"));?>",
			"BODY_TYPE" => "html",
			"SITE_TEMPLATE_ID" => "mail_user"
		));

		// mail user templates
		$emess->Add(array(
			"ACTIVE" => "Y",
			"EVENT_NAME" => "TASKS_TASK_ADD_EMAIL",
			"LID" => $arSites,
			"EMAIL_FROM" => "#EMAIL_FROM#",
			"EMAIL_TO" => "#EMAIL_TO#",
			"SUBJECT" => '#SUBJECT#',
			"MESSAGE" => '<?EventMessageThemeCompiler::includeComponent(
	"bitrix:tasks.task.mail",
	"",
	array(
		"ID" => "{#TASK_ID#}",
		"RECIPIENT_ID" => "{#RECIPIENT_ID#}",
		"USER_ID" => "{#USER_ID#}",

		"ENTITY" => "TASK",
		"ENTITY_ACTION" => "ADD",

		"URL" => "{#URL#}",
		"EMAIL_TO" => "{#EMAIL_TO#}"
	)
);?>',
			"BODY_TYPE" => "html",
			"SITE_TEMPLATE_ID" => "mail_user",
		));

		$emess->Add(array(
			"ACTIVE" => "Y",
			"EVENT_NAME" => "TASKS_TASK_UPDATE_EMAIL",
			"LID" => $arSites,
			"EMAIL_FROM" => "#EMAIL_FROM#",
			"EMAIL_TO" => "#EMAIL_TO#",
			"SUBJECT" => '#SUBJECT#',
			"MESSAGE" => '<?EventMessageThemeCompiler::includeComponent(
	"bitrix:tasks.task.mail",
	"",
	array(
		"ID" => "{#TASK_ID#}",
		"PREVIOUS_FIELDS" => "{#TASK_PREVIOUS_FIELDS#}",

		"RECIPIENT_ID" => "{#RECIPIENT_ID#}",
		"USER_ID" => "{#USER_ID#}",

		"ENTITY" => "TASK",
		"ENTITY_ACTION" => "UPDATE",

		"URL" => "{#URL#}",
		"EMAIL_TO" => "{#EMAIL_TO#}"
	)
);?>',
			"BODY_TYPE" => "html",
			"SITE_TEMPLATE_ID" => "mail_user",
		));

		$emess->Add(array(
			"ACTIVE" => "Y",
			"EVENT_NAME" => "TASKS_TASK_COMMENT_ADD_EMAIL",
			"LID" => $arSites,
			"EMAIL_FROM" => "#EMAIL_FROM#",
			"EMAIL_TO" => "#EMAIL_TO#",
			"SUBJECT" => '#SUBJECT#',
			"MESSAGE" => '<?EventMessageThemeCompiler::includeComponent(
	"bitrix:tasks.task.mail",
	"",
	array(
		"ID" => "{#TASK_ID#}",
		"RECIPIENT_ID" => "{#RECIPIENT_ID#}",
		"USER_ID" => "{#USER_ID#}",

		"ENTITY" => "COMMENT",
		"ENTITY_ACTION" => "ADD",

		"URL" => "{#URL#}",
		"EMAIL_TO" => "{#EMAIL_TO#}"
	)
);?>',
			"BODY_TYPE" => "html",
			"SITE_TEMPLATE_ID" => "mail_user",
		));
	}
}
?>
