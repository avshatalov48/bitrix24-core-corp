<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
/** @global CMain $APPLICATION */
/** @global CDatabase $DB */
/** @global CUser $USER */

if (!$USER->CanDoOperation("controller_counter_view") || !CModule::IncludeModule("controller"))
{
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
}
require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/controller/prolog.php");

IncludeModuleLangFile(__FILE__);

$sTableID = "t_controller_counter_history";
$lAdmin = new CAdminList($sTableID);

$arFilterRows = array(
	GetMessage("CTRL_MEMB_HIST_FIELD"),
);

$filter = new CAdminFilter(
	$sTableID."_filter_id",
	$arFilterRows
);

$arFilterFields = Array(
	"find_id",
	"find_field",
);

$adminFilter = $lAdmin->InitFilter($arFilterFields);

$arFilter = array(
	"=COUNTER_ID" => $find_id,
);
foreach ($arFilter as $k => $v)
	if (!strlen($v))
		unset($arFilter[$k]);

$arHeaders = array(
	array(
		"id" => "TIMESTAMP_X",
		"content" => GetMessage("CTRL_CONTER_HIST_TIMESTAMP_X"),
		"default" => true,
	),
	array(
		"id" => "USER_ID",
		"content" => GetMessage("CTRL_CONTER_HIST_USER_ID"),
		"default" => true,
	),
	array(
		"id" => "COMMAND",
		"content" => GetMessage("CTRL_CONTER_HIST_COMMAND"),
		"default" => true,
	),
	array(
		"id" => "COMMAND_FROM",
		"content" => GetMessage("CTRL_CONTER_HIST_COMMAND_FROM"),
		"default" => false,
	),
	array(
		"id" => "COMMAND_TO",
		"content" => GetMessage("CTRL_CONTER_HIST_COMMAND_TO"),
		"default" => false,
	),
);

$lAdmin->AddHeaders($arHeaders);

$rsData = CControllerCounter::GetHistory($arFilter);
$rsData = new CAdminResult($rsData, $sTableID);
$rsData->NavStart();

$lAdmin->NavText($rsData->GetNavPrint(GetMessage("CTRL_CONTER_HIST_NAVSTRING")));

while ($arRes = $rsData->Fetch())
{
	$row =& $lAdmin->AddRow($arRes['ID'], $arRes);

	$row->AddViewField("TIMESTAMP_X", htmlspecialcharsEx($arRes['TIMESTAMP_X']));
	adminListAddUserLink($row, "USER_ID", $arRes['USER_ID'], $arRes['USER_ID_USER']);

	$cmd_from = htmlspecialcharsEx($arRes['COMMAND_FROM']);
	$cmd_to = htmlspecialcharsEx($arRes['COMMAND_TO']);
	$cmd_diff = getCounterCommandDiff($cmd_from, $cmd_to);
	$cmd_html = str_replace("\n", "<br>", $cmd_diff);
	$cmd_html = str_replace("\t", "&nbsp;&nbsp;&nbsp;&nbsp;", $cmd_html);
	$row->AddViewField("COMMAND", '<span class="command-code">'.$cmd_html.'</span>');

	$row->AddViewField("COMMAND_FROM", '<pre>'.htmlspecialcharsEx($arRes['COMMAND_FROM']).'</pre>');
	$row->AddViewField("COMMAND_TO", '<pre>'.htmlspecialcharsEx($arRes['COMMAND_TO']).'</pre>');
}

$lAdmin->AddFooter(
	array(
		array("title" => GetMessage("MAIN_ADMIN_LIST_SELECTED"), "value" => $rsData->SelectedRowsCount()),
	)
);

$aContext = array(
	array(
		"TEXT" => GetMessage("CTRL_CONTER_HIST_BACK"),
		"LINK" => "controller_counter_edit.php?ID=".intval($adminFilter['find_id'])."&lang=".LANGUAGE_ID,
		"TITLE" => GetMessage("CTRL_CONTER_HIST_BACK_TITLE"),
		"ICON" => "btn_edit",
	),
);

$lAdmin->AddAdminContextMenu($aContext);

$lAdmin->CheckListMode();

$APPLICATION->SetTitle(GetMessage("CTRL_CONTER_HIST_TITLE"));
require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/prolog_admin_after.php");
?>
<form name="form1" method="GET" action="<? echo $APPLICATION->GetCurPage() ?>?">
	<? $filter->Begin(); ?>
	<tr>
		<td nowrap><?=GetMessage("CTRL_CONTER_HIST_COUNTER_ID")?>:</td>
		<td nowrap>
			<input type="text" name="find_id" value="<? echo htmlspecialcharsbx($adminFilter['find_id']) ?>" size="47">
		</td>
	</tr>
	<? $filter->Buttons(array("table_id" => $sTableID, "url" => $APPLICATION->GetCurPage(), "form" => "form1"));
	$filter->End(); ?>
</form>

<?
$lAdmin->DisplayList();


//http://en.wikipedia.org/wiki/Longest_common_subsequence_problem
//function  LCS(X[1..m], Y[1..n])
function ch_LongestCommonSubsequence($X, $Y)
{
//	m_start := 1
	$m_start = 0;
//	m_end := m
	$m_end = count($X)-1;
//	n_start := 1
	$n_start = 0;
//	n_end := n
	$n_end = count($Y)-1;
//	C = array(m_start-1..m_end, n_start-1..n_end)
	$C = array();
//	for($i = $m_start-1; $i <= $m_end; $i++)
//	{
//		$C[$i] = array();
//		for($j = $n_start-1; $j <= $n_end; $j++)
//		{
//			$C[$i][$j] = 0;
//		}
//	}
//	for i := m_start..m_end
	for($i = $m_start; $i <= $m_end; $i++)
	{
//		for j := n_start..n_end
		for($j = $n_start; $j <= $n_end; $j++)
		{
//			if X[i] = Y[j]
			if($X[$i] == $Y[$j])
			{
//				C[i,j] := C[i-1,j-1] + 1
				$C[$i][$j] = $C[($i-1)][($j-1)] + 1;
			}
//			else:
			else
			{
				$k = max($C[$i][($j-1)], $C[($i-1)][$j]);
//				C[i,j] := max(C[i,j-1], C[i-1,j])
				if($k != 0)
				{
					$C[$i][$j] = $k;
					//Clean up to the left
					if($C[$i][$j-1] < $k)
						for($jj = $j-1;$jj >= $n_start;$jj--)
							if(is_array($C[$i]) && array_key_exists($jj, $C[$i]))
								unset($C[$i][$jj]);
							else
								break;
				}
			}
		}
		//Clean up to the up
		if($i > $m_start)
		{
			$ii = $i - 1;
			if(is_array($C[$ii]))
			{
				for($j = $n_end; $j > $n_start && array_key_exists($j, $C[$ii]); $j--)
				{
					if($C[$i][$j] > $C[$ii][$j])
						unset($C[$ii][$j]);
				}
			}
		}
	}
//	return C[m,n]
	return $C;
}

//function printDiff(C[0..m,0..n], X[1..m], Y[1..n], i, j)
//	if i > 0 and j > 0 and X[i] = Y[j]
//		printDiff(C, X, Y, i-1, j-1)
//		print "  " + X[i]
//	else
//		if j > 0 and (i = 0 or C[i,j-1] >= C[i-1,j])
//			printDiff(C, X, Y, i, j-1)
//			print "+ " + Y[j]
//		else if i > 0 and (j = 0 or C[i,j-1] < C[i-1,j])
//			printDiff(C, X, Y, i-1, j)
//			print "- " + X[i]

function computeCounterCommandDiff($C, $X, $Y, $Xt, $Yt, $i, $j)
{
	$a = array();
	while($i >= 0 || $j >= 0)
	{
		if( ($i >= 0) && ($j >= 0) && ($Xt[$i] == $Yt[$j]) )
		{
			array_unshift($a, $X[$i]);
			$i--; $j--;
		}
		elseif( ($j >= 0) && ($i <= 0 || ($C[$i][($j-1)] >= $C[($i-1)][$j])) )
		{
			array_unshift($a, '<span class="command-code-add">'.$Y[$j]."</span>");
			$j--;
		}
		elseif( ($i >= 0) && ($j <= 0 || ($C[$i][($j-1)] < $C[($i-1)][$j])) )
		{
			array_unshift($a, '<span class="command-code-del">'.$X[$i]."</span>");
			$i--;
		}
	}
	return $a;
}

function getCounterCommandDiff($X, $Y)
{
	$Xmatch = explode("\n", $X);
	$Ymatch = explode("\n", $Y);

	//Determine common beginning
	$sCodeStart = "";
	while( count($Xmatch) && count($Ymatch) && (trim($Xmatch[0], " \t\n\r") == trim($Ymatch[0], " \t\n\r")) )
	{
		$sCodeStart .= "\n".$Xmatch[0];
		array_shift($Xmatch);
		array_shift($Ymatch);
	}

	//Find common ending
	$X_end = count($Xmatch)-1;
	$Y_end = count($Ymatch)-1;
	$sCodeEnd = "";
	while( ($X_end >= 0) && ($Y_end >= 0) && (trim($Xmatch[$X_end], " \t\n\r") == trim($Ymatch[$Y_end], " \t\n\r")) )
	{
		$sCodeEnd = $Xmatch[$X_end]."\n".$sCodeEnd;
		unset($Xmatch[$X_end]);
		unset($Ymatch[$Y_end]);
		$X_end--;
		$Y_end--;
	}

	//What will actually diff
	$Xmatch_trimmed = array();
	foreach($Xmatch as $i => $match)
	{
		$Xmatch_trimmed[] = trim($match, " \t\n\r");
	}

	$Ymatch_trimmed = array();
	foreach($Ymatch as $i => $match)
	{
		$Ymatch_trimmed[] = trim($match, " \t\n\r");
	}

	$diff = computeCounterCommandDiff(
		ch_LongestCommonSubsequence($Xmatch_trimmed, $Ymatch_trimmed),
		$Xmatch,
		$Ymatch,
		$Xmatch_trimmed,
		$Ymatch_trimmed,
		count($Xmatch_trimmed)-1,
		count($Ymatch_trimmed)-1
	);
	$sCode = implode("\n", $diff);

	return trim($sCodeStart."\n".$sCode."\n".$sCodeEnd, "\n");
}

require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_admin.php");
