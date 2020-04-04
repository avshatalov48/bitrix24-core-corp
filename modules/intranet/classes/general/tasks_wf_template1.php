<?
IncludeModuleLangFile(__FILE__);

$ar111 = array(
	"INPROGRESS" => 'tasks_inprogress.php',
	"WAITING" => 'tasks_waiting.php',
	"DEFERRED" => 'tasks_deferred.php',
	"NOTSTARTED" => 'tasks_notstarted.php',
	"NOTACCEPTED" => 'tasks_notapproved.php',
);
$ar111_en = array(
	"INPROGRESS" => 'tasks_inprogress_en.php',
	"WAITING" => 'tasks_waiting_en.php',
	"DEFERRED" => 'tasks_deferred_en.php',
	"NOTSTARTED" => 'tasks_notstarted_en.php',
	"NOTACCEPTED" => 'tasks_notapproved_en.php',
);
$ar111_utf = array(
	"INPROGRESS" => 'tasks_inprogress_utf.php',
	"WAITING" => 'tasks_waiting_utf.php',
	"DEFERRED" => 'tasks_deferred_utf.php',
	"NOTSTARTED" => 'tasks_notstarted_utf.php',
	"NOTACCEPTED" => 'tasks_notapproved_utf.php',
);

$arOldTasksWFs = array();
foreach ($ar111 as $k111 => $v111)
{
	if (LANGUAGE_ID != "ru")
		$v111 = $ar111_en[$k111];
	if (defined("BX_UTF") && BX_UTF && LANGUAGE_ID == "ru")
		$v111 = $ar111_utf[$k111];

	$f = fopen($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/tools/".$v111, "rb");
	$s = fread($f, filesize($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/tools/".$v111));
	fclose($f);

	$arOldTasksWFs[$k111] = $s;
}

$a = array(
	"SRE" => array(GetMessage("INTASK_WF_TMPL_HACK_SRE"), strlen(GetMessage("INTASK_WF_TMPL_HACK_SRE"))),
	"IPE" => array(GetMessage("INTASK_WF_TMPL_HACK_IPE"), strlen(GetMessage("INTASK_WF_TMPL_HACK_IPE"))),
	"CE" => array(GetMessage("INTASK_WF_TMPL_HACK_CE"), strlen(GetMessage("INTASK_WF_TMPL_HACK_CE"))),
	"CLE" => array(GetMessage("INTASK_WF_TMPL_HACK_CLE"), strlen(GetMessage("INTASK_WF_TMPL_HACK_CLE"))),
	"WE" => array(GetMessage("INTASK_WF_TMPL_HACK_WE"), strlen(GetMessage("INTASK_WF_TMPL_HACK_WE"))),
	"DE" => array(GetMessage("INTASK_WF_TMPL_HACK_DE"), strlen(GetMessage("INTASK_WF_TMPL_HACK_DE"))),
	"NS" => array(GetMessage("INTASK_WF_TMPL_HACK_NS"), strlen(GetMessage("INTASK_WF_TMPL_HACK_NS"))),
	"AE" => array(GetMessage("INTASK_WF_TMPL_HACK_AE"), strlen(GetMessage("INTASK_WF_TMPL_HACK_AE"))),
);
if (defined("BX_UTF") && BX_UTF && LANGUAGE_ID == "ru")
{
	$a = array(
		"SRE" => array(GetMessage("INTASK_WF_TMPL_HACK_SRE"), mb_strlen(GetMessage("INTASK_WF_TMPL_HACK_SRE"), "latin1")),
		"IPE" => array(GetMessage("INTASK_WF_TMPL_HACK_IPE"), mb_strlen(GetMessage("INTASK_WF_TMPL_HACK_IPE"), "latin1")),
		"CE" => array(GetMessage("INTASK_WF_TMPL_HACK_CE"), mb_strlen(GetMessage("INTASK_WF_TMPL_HACK_CE"), "latin1")),
		"CLE" => array(GetMessage("INTASK_WF_TMPL_HACK_CLE"), mb_strlen(GetMessage("INTASK_WF_TMPL_HACK_CLE"), "latin1")),
		"WE" => array(GetMessage("INTASK_WF_TMPL_HACK_WE"), mb_strlen(GetMessage("INTASK_WF_TMPL_HACK_WE"), "latin1")),
		"DE" => array(GetMessage("INTASK_WF_TMPL_HACK_DE"), mb_strlen(GetMessage("INTASK_WF_TMPL_HACK_DE"), "latin1")),
		"NS" => array(GetMessage("INTASK_WF_TMPL_HACK_NS"), mb_strlen(GetMessage("INTASK_WF_TMPL_HACK_NS"), "latin1")),
		"AE" => array(GetMessage("INTASK_WF_TMPL_HACK_AE"), mb_strlen(GetMessage("INTASK_WF_TMPL_HACK_AE"), "latin1")),
	);
}

$arOldTasksSTs = array(
"NOTSTARTED" => array(
	"STATE" => "NotStarted",
	"STATE_TITLE" => GetMessage("INTASK_WF_TMPL_HACK_NOTSTARTED"),
	"PARAMS" => 'a:6:{i:0;a:3:{s:4:"NAME";s:35:"HEEA_NotStarted_SetResponsibleEvent";s:5:"TITLE";s:'.$a["SRE"][1].':"'.$a["SRE"][0].'";s:10:"PERMISSION";a:3:{i:0;s:11:"responsible";i:1;s:6:"author";i:2;s:1:"A";}}i:1;a:3:{s:4:"NAME";s:31:"HEEA_NotStarted_InProgressEvent";s:5:"TITLE";s:'.$a["IPE"][1].':"'.$a["IPE"][0].'";s:10:"PERMISSION";a:2:{i:0;s:11:"responsible";i:1;s:1:"A";}}i:2;a:3:{s:4:"NAME";s:29:"HEEA_NotStarted_CompleteEvent";s:5:"TITLE";s:'.$a["CE"][1].':"'.$a["CE"][0].'";s:10:"PERMISSION";a:2:{i:0;s:11:"responsible";i:1;s:1:"A";}}i:3;a:3:{s:4:"NAME";s:26:"HEEA_NotStarted_CloseEvent";s:5:"TITLE";s:'.$a["CLE"][1].':"'.$a["CLE"][0].'";s:10:"PERMISSION";a:2:{i:0;s:6:"author";i:1;s:1:"A";}}i:4;a:3:{s:4:"NAME";s:28:"HEEA_NotStarted_WaitingEvent";s:5:"TITLE";s:'.$a["WE"][1].':"'.$a["WE"][0].'";s:10:"PERMISSION";a:2:{i:0;s:11:"responsible";i:1;s:1:"A";}}i:5;a:3:{s:4:"NAME";s:29:"HEEA_NotStarted_DeferredEvent";s:5:"TITLE";s:'.$a["DE"][1].':"'.$a["DE"][0].'";s:10:"PERMISSION";a:2:{i:0;s:11:"responsible";i:1;s:1:"A";}}}',
	"PERMS" => array(
		"read" => array("responsible", "author", "trackers", "A"),
		"comment" => array("responsible", "author", "trackers", "A"),
		"write" => array("responsible", "author", "A"),
		"delete" => array("A"),
	)
),
"INPROGRESS" => array(
	"STATE" => "InProgress",
	"STATE_TITLE" => GetMessage("INTASK_WF_TMPL_HACK_INPROGRESS"),
	"PARAMS" => 'a:5:{i:0;a:3:{s:4:"NAME";s:35:"HEEA_InProgress_SetResponsibleEvent";s:5:"TITLE";s:'.$a["SRE"][1].':"'.$a["SRE"][0].'";s:10:"PERMISSION";a:3:{i:0;s:11:"responsible";i:1;s:6:"author";i:2;s:1:"A";}}i:1;a:3:{s:4:"NAME";s:29:"HEEA_InProgress_CompleteEvent";s:5:"TITLE";s:'.$a["CE"][1].':"'.$a["CE"][0].'";s:10:"PERMISSION";a:2:{i:0;s:11:"responsible";i:1;s:1:"A";}}i:2;a:3:{s:4:"NAME";s:26:"HEEA_InProgress_CloseEvent";s:5:"TITLE";s:'.$a["CLE"][1].':"'.$a["CLE"][0].'";s:10:"PERMISSION";a:2:{i:0;s:6:"author";i:1;s:1:"A";}}i:3;a:3:{s:4:"NAME";s:28:"HEEA_InProgress_WaitingEvent";s:5:"TITLE";s:'.$a["WE"][1].':"'.$a["WE"][0].'";s:10:"PERMISSION";a:2:{i:0;s:11:"responsible";i:1;s:1:"A";}}i:4;a:3:{s:4:"NAME";s:29:"HEEA_InProgress_DeferredEvent";s:5:"TITLE";s:'.$a["DE"][1].':"'.$a["DE"][0].'";s:10:"PERMISSION";a:2:{i:0;s:11:"responsible";i:1;s:1:"A";}}}',
	"PERMS" => array(
		"read" => array("responsible", "author", "trackers", "A"),
		"comment" => array("responsible", "author", "trackers", "A"),
		"write" => array("responsible", "A"),
		"delete" => array("A"),
	)
),
"COMPLETED" => array(
	"STATE" => "Closed",
	"STATE_TITLE" => GetMessage("INTASK_WF_TMPL_HACK_CLOSED"),
	"PARAMS" => '',
	"PERMS" => array(
		"read" => array("responsible", "author", "trackers", "A"),
		"comment" => array("responsible", "author", "trackers", "A"),
		"write" => array("A"),
		"delete" => array("A"),
	)
),
"WAITING" => array(
	"STATE" => "Waiting",
	"STATE_TITLE" => GetMessage("INTASK_WF_TMPL_HACK_WAITING"),
	"PARAMS" => 'a:6:{i:0;a:3:{s:4:"NAME";s:32:"HEEA_Waiting_SetResponsibleEvent";s:5:"TITLE";s:'.$a["SRE"][1].':"'.$a["SRE"][0].'";s:10:"PERMISSION";a:3:{i:0;s:11:"responsible";i:1;s:6:"author";i:2;s:1:"A";}}i:1;a:3:{s:4:"NAME";s:28:"HEEA_Waiting_NotStartedEvent";s:5:"TITLE";s:'.$a["NS"][1].':"'.$a["NS"][0].'";s:10:"PERMISSION";a:2:{i:0;s:11:"responsible";i:1;s:1:"A";}}i:2;a:3:{s:4:"NAME";s:28:"HEEA_Waiting_InProgressEvent";s:5:"TITLE";s:'.$a["IPE"][1].':"'.$a["IPE"][0].'";s:10:"PERMISSION";a:2:{i:0;s:11:"responsible";i:1;s:1:"A";}}i:3;a:3:{s:4:"NAME";s:26:"HEEA_Waiting_CompleteEvent";s:5:"TITLE";s:'.$a["CE"][1].':"'.$a["CE"][0].'";s:10:"PERMISSION";a:2:{i:0;s:11:"responsible";i:1;s:1:"A";}}i:4;a:3:{s:4:"NAME";s:23:"HEEA_Waiting_CloseEvent";s:5:"TITLE";s:'.$a["CLE"][1].':"'.$a["CLE"][0].'";s:10:"PERMISSION";a:2:{i:0;s:6:"author";i:1;s:1:"A";}}i:5;a:3:{s:4:"NAME";s:26:"HEEA_Waiting_DeferredEvent";s:5:"TITLE";s:'.$a["DE"][1].':"'.$a["DE"][0].'";s:10:"PERMISSION";a:2:{i:0;s:11:"responsible";i:1;s:1:"A";}}}',
	"PERMS" => array(
		"read" => array("responsible", "author", "trackers", "A"),
		"comment" => array("responsible", "author", "trackers", "A"),
		"write" => array("responsible", "A"),
		"delete" => array("A"),
	)
),
"DEFERRED" => array(
	"STATE" => "Deferred",
	"STATE_TITLE" => GetMessage("INTASK_WF_TMPL_HACK_DEFERRED"),
	"PARAMS" => 'a:6:{i:0;a:3:{s:4:"NAME";s:33:"HEEA_Deferred_SetResponsibleEvent";s:5:"TITLE";s:'.$a["SRE"][1].':"'.$a["SRE"][0].'";s:10:"PERMISSION";a:3:{i:0;s:11:"responsible";i:1;s:6:"author";i:2;s:1:"A";}}i:1;a:3:{s:4:"NAME";s:29:"HEEA_Deferred_NotStartedEvent";s:5:"TITLE";s:'.$a["NS"][1].':"'.$a["NS"][0].'";s:10:"PERMISSION";a:2:{i:0;s:11:"responsible";i:1;s:1:"A";}}i:2;a:3:{s:4:"NAME";s:29:"HEEA_Deferred_InProgressEvent";s:5:"TITLE";s:'.$a["IPE"][1].':"'.$a["IPE"][0].'";s:10:"PERMISSION";a:2:{i:0;s:11:"responsible";i:1;s:1:"A";}}i:3;a:3:{s:4:"NAME";s:27:"HEEA_Deferred_CompleteEvent";s:5:"TITLE";s:'.$a["CE"][1].':"'.$a["CE"][0].'";s:10:"PERMISSION";a:2:{i:0;s:11:"responsible";i:1;s:1:"A";}}i:4;a:3:{s:4:"NAME";s:24:"HEEA_Deferred_CloseEvent";s:5:"TITLE";s:'.$a["CLE"][1].':"'.$a["CLE"][0].'";s:10:"PERMISSION";a:2:{i:0;s:6:"author";i:1;s:1:"A";}}i:5;a:3:{s:4:"NAME";s:26:"HEEA_Deferred_WaitingEvent";s:5:"TITLE";s:'.$a["WE"][1].':"'.$a["WE"][0].'";s:10:"PERMISSION";a:2:{i:0;s:11:"responsible";i:1;s:1:"A";}}}',
	"PERMS" => array(
		"read" => array("responsible", "author", "trackers", "A"),
		"comment" => array("responsible", "author", "trackers", "A"),
		"write" => array("responsible", "A"),
		"delete" => array("A"),
	)
),
"NOTACCEPTED" => array(
	"STATE" => "NotAccepted",
	"STATE_TITLE" => GetMessage("INTASK_WF_TMPL_HACK_NOTACCEPTED"),
	"PARAMS" => 'a:5:{i:0;a:3:{s:4:"NAME";s:36:"HEEA_NotAccepted_SetResponsibleEvent";s:5:"TITLE";s:'.$a["SRE"][1].':"'.$a["SRE"][0].'";s:10:"PERMISSION";a:3:{i:0;s:11:"responsible";i:1;s:6:"author";i:2;s:1:"A";}}i:1;a:3:{s:4:"NAME";s:29:"HEEA_NotAccepted_ApproveEvent";s:5:"TITLE";s:'.$a["AE"][1].':"'.$a["AE"][0].'";s:10:"PERMISSION";a:2:{i:0;s:11:"responsible";i:1;s:1:"A";}}i:2;a:3:{s:4:"NAME";s:32:"HEEA_NotAccepted_InProgressEvent";s:5:"TITLE";s:'.$a["IPE"][1].':"'.$a["IPE"][0].'";s:10:"PERMISSION";a:2:{i:0;s:11:"responsible";i:1;s:1:"A";}}i:3;a:3:{s:4:"NAME";s:30:"HEEA_NotAccepted_CompleteEvent";s:5:"TITLE";s:'.$a["CE"][1].':"'.$a["CE"][0].'";s:10:"PERMISSION";a:2:{i:0;s:11:"responsible";i:1;s:1:"A";}}i:4;a:3:{s:4:"NAME";s:27:"HEEA_NotAccepted_CloseEvent";s:5:"TITLE";s:'.$a["CLE"][1].':"'.$a["CLE"][0].'";s:10:"PERMISSION";a:2:{i:0;s:6:"author";i:1;s:1:"A";}}}',
	"PERMS" => array(
		"read" => array("responsible", "author", "trackers", "A"),
		"comment" => array("responsible", "author", "trackers", "A"),
		"write" => array("responsible", "author", "A"),
		"delete" => array("A", "author"),
	)
),
);
?>