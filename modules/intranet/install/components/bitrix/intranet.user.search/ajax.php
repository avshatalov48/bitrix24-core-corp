<?
define("STOP_STATISTICS", true);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

// we shouldn't check any access rights here
// if(!($USER->CanDoOperation('view_subordinate_users') || $USER->CanDoOperation('view_all_users')))
	// $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

CModule::IncludeModule('intranet');

$SITE_ID = trim($_REQUEST['SITE_ID']);
$SITE_ID = mb_substr(preg_replace("/[^a-z0-9_]/i", "", $SITE_ID), 0, 2);

if (isset($_REQUEST["nt"]))
{
	preg_match_all("/(#NAME#)|(#NOBR#)|(#\/NOBR#)|(#LAST_NAME#)|(#SECOND_NAME#)|(#NAME_SHORT#)|(#SECOND_NAME_SHORT#)|\s|\,/", urldecode($_REQUEST["nt"]), $matches);
	$nameTemplate = implode("", $matches[0]);
}
else
	$nameTemplate = CSite::GetNameFormat(false);

if (!$USER->IsAuthorized() || CModule::IncludeModule('extranet') && !CExtranet::IsIntranetUser($SITE_ID) && !$USER->IsAdmin() || !check_bitrix_sessid())
	die();

__IncludeLang(__DIR__.'/lang/'.LANGUAGE_ID.'/'.basename(__FILE__));

$bExtranet = (CModule::IncludeModule('extranet') && CExtranet::IsExtranetSite($SITE_ID) && $_REQUEST['IS_EXTRANET'] == "Y") ? true : false;

if ($_REQUEST['MODE'] == 'EMPLOYEES')
{
	if ($SECTION_ID != 'last' && $SECTION_ID != 'extranet')
		$SECTION_ID = intval($_REQUEST['SECTION_ID']);

	$arFilter = array(
		'ACTIVE' => 'Y',
		'CONFIRM_CODE' => false
	);

	if ($SECTION_ID == 'last')
	{
		$arLastSelected = CUserOptions::GetOption("intranet", "user_search", array());
		if (is_array($arLastSelected) && $arLastSelected['last_selected'] <> '')
			$arLastSelected = array_unique(explode(',', $arLastSelected['last_selected']));
		else
			$arLastSelected = false;

		$arFilter['!UF_DEPARTMENT'] = false;
		$arFilter['ID'] = is_array($arLastSelected) ? implode('|', array_slice($arLastSelected, 0, 5)) : '-1';

	}
	elseif($SECTION_ID == "extranet")
	{
		if ($bExtranet)
		{
			$arFilter['GROUPS_ID'] = Array(COption::GetOptionInt("extranet", "extranet_group", ""));
			$arFilter['UF_DEPARTMENT'] = false;
		}
	}
	else
	{
		$arFilter['UF_DEPARTMENT'] = $SECTION_ID;
	}

	/*
	if(!$USER->CanDoOperation('view_all_users'))
	{
		$arUserSubordinateGroups = array();
		$arUserGroups = CUser::GetUserGroup($USER->GetID());
		foreach($arUserGroups as $grp)
			$arUserSubordinateGroups = array_merge($arUserSubordinateGroups, CGroup::GetSubordinateGroups($grp));

		$arFilter["CHECK_SUBORDINATE"] = array_unique($arUserSubordinateGroups);
	}
	*/
	$arUsers = array();

	if ($SECTION_ID != 'last' && $SECTION_ID != "extranet")
	{
		$dbRes = CIBlockSection::GetList(
			array('ID' => 'ASC'),
			array('ID' => $SECTION_ID, 'IBLOCK_ID' => COption::GetOptionInt('intranet', 'iblock_structure')),
			false,
			array('UF_HEAD')
		);
		if (($arSection = $dbRes->Fetch()) && $arSection['UF_HEAD'] > 0)
		{
			$dbUsers = CUser::GetList(
				'last_name', 'asc',
				array(
					'ID' => $arSection['UF_HEAD'],
					'ACTIVE' => 'Y'
				),
				array('SELECT' => array('UF_*'))
			);

			if ($arRes = $dbUsers->GetNext())
			{
				$arFilter['!ID'] = $arRes['ID'];

				$arPhoto = array('IMG' => '');

				if (!$arRes['PERSONAL_PHOTO'])
				{
					switch ($arRes['PERSONAL_GENDER'])
					{
						case "M":
							$suffix = "male";
							break;
						case "F":
							$suffix = "female";
							break;
						default:
							$suffix = "unknown";
					}
					$arRes['PERSONAL_PHOTO'] = COption::GetOptionInt("socialnetwork", "default_user_picture_".$suffix, false, $SITE_ID);
				}

				if ($arRes['PERSONAL_PHOTO'] > 0)
					$arPhoto = CIntranetUtils::InitImage($arRes['PERSONAL_PHOTO'], 30);

				$arUsers[] = array(
					'ID' => $arRes['ID'],
					'NAME' => CUser::FormatName($nameTemplate, $arRes, true, false),
					'LOGIN' => $arRes['LOGIN'],
					'EMAIL' => $arRes['EMAIL'],
					'WORK_POSITION' => $arRes['WORK_POSITION'] ? $arRes['WORK_POSITION'] : $arRes['PERSONAL_PROFESSION'],
					'PHOTO' => $arPhoto['IMG'],
					'HEAD' => true,
				);
			}
		}
	}

	$dbRes = CUser::GetList('last_name', 'asc', $arFilter);
	while ($arRes = $dbRes->GetNext())
	{
		$arPhoto = array('IMG' => '');

		if (!$arRes['PERSONAL_PHOTO'])
		{
			switch ($arRes['PERSONAL_GENDER'])
			{
				case "M":
					$suffix = "male";
					break;
				case "F":
					$suffix = "female";
					break;
				default:
					$suffix = "unknown";
			}
			$arRes['PERSONAL_PHOTO'] = COption::GetOptionInt("socialnetwork", "default_user_picture_".$suffix, false, $SITE_ID);
		}

		if ($arRes['PERSONAL_PHOTO'] > 0)
			$arPhoto = CIntranetUtils::InitImage($arRes['PERSONAL_PHOTO'], 30);

		$arUsers[] = array(
			'ID' => $arRes['ID'],
			'NAME' => CUser::FormatName($nameTemplate, $arRes, true, false),
			'LOGIN' => $arRes['LOGIN'],
			'EMAIL' => $arRes['EMAIL'],
			'WORK_POSITION' => $arRes['WORK_POSITION'] ? $arRes['WORK_POSITION'] : $arRes['PERSONAL_PROFESSION'],
			'PHOTO' => $arPhoto['IMG'],
			'HEAD' => false,
		);
	}

	$APPLICATION->RestartBuffer();
	Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
?>
BXShowEmployees('<?echo $SECTION_ID?>', <?echo CUtil::PhpToJsObject($arUsers)?>);
<?
	if ($SECTION_ID == 'last'):
?>
window.arLastSelected = <?echo CUtil::PhpToJsObject($arLastSelected)?>;
<?
	endif;
	die();
}
elseif ($_REQUEST['MODE'] == 'SEARCH')
{
	CUtil::JSPostUnescape();
	$APPLICATION->RestartBuffer();

	$search = $_REQUEST['search'];
	$arUsers = array();

	if (GetFilterQuery("TEST", $search))
	{
		$arFilter = array(
			"ACTIVE" => "Y",
			"CONFIRM_CODE" => false,
			"NAME_SEARCH" => $search,
			"!UF_DEPARTMENT" => false
		);
		if ($bExtranet)
			unset($arFilter["!UF_DEPARTMENT"]);

		$dbRes = CUser::GetList(
			"last_name", "asc",
			$arFilter,
			array(
				"SELECT" => array("UF_DEPARTMENT"),
				"FIELDS" => array("ID", "NAME", "LAST_NAME", "SECOND_NAME", "EMAIL", "LOGIN"),
				"NAV_PARAMS" => array("nTopCount" => 10)
			)
		);
		while ($arRes = $dbRes->Fetch())
			$arUsers[] = array(
				"ID" => $arRes["ID"],
				"NAME" => CUser::FormatName($nameTemplate, $arRes),
				"UF_DEPARTMENT" => $arRes["UF_DEPARTMENT"]
			);
	}

	Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
?>
jsEmpSearch.SetResult(<?echo CUtil::PhpToJsObject($arUsers)?>);
<?
	die();
}

$bMultiple = $_GET['multiple'] == 'Y';
$win_id = \CUtil::jsEscape($win_id);

$current_user = $bMultiple ? array() : 0;
$arOpenedSections = array();
if (isset($_GET['value']))
{
	if ($bMultiple)
	{
		$current_user = explode(',', $_GET['value']);
		foreach ($current_user as $key => $value)
			$current_user[$key] = intval(trim($value));

		$arLoadUsers = $current_user;
	}
	else
	{
		$current_user = intval($_GET['value']);
		$arLoadUsers = array($current_user);
	}
	if (count($arLoadUsers) > 0)
	{
		$dbRes = CUser::GetList('ID', 'ASC', array('ID' => implode('|', $arLoadUsers), '!UF_DEPARTMENT' => false), array('SELECT' => array('UF_*')));
		while ($arUser = $dbRes->Fetch())
		{
			$arOpenedSections[] = $arUser['UF_DEPARTMENT'][0];
		}
	}
}
?>
<div class="title">
<table cellspacing="0" width="100%">
	<tr>
		<td width="100%" class="title-text" onmousedown="jsFloatDiv.StartDrag(arguments[0], document.getElementById('<?=htmlspecialcharsbx($win_id) ?>'));"><?echo GetMessage('INTR_EMP_WINDOW_TITLE')?></td>
		<td width="0%"><a class="close" href="javascript:document.getElementById('<?=htmlspecialcharsbx($win_id) ?>').__object.CloseDialog();" title="<?=GetMessage("INTR_EMP_WINDOW_CLOSE")?>"></a></td>
	</tr>
</table>
</div>
<script>
var current_selected = <?echo CUtil::PhpToJsObject($current_user)?>;
var arLastSelected = [];
function BXEmployeeSelect()
{
<?
if ($bMultiple):
?>
	var bFound = false;
	for (var i = 0; i < current_selected.length; i++)
	{
		if (current_selected[i] == this.BX_ID)
		{
			bFound = true;
			break;
		}
	}

	if (bFound)
	{
		this.className = 'bx-employee-row';
		current_selected = current_selected.slice(0, i).concat(current_selected.slice(i + 1));
		this.firstChild.checked = false;
	}
	else
	{
		current_selected[current_selected.length] = this.BX_ID;
		this.className = 'bx-employee-row bx-emp-selected';
		this.firstChild.checked = true;
	}

<?
else:
?>
	if (current_selected > 0 && document.getElementById('bx_employee_' + current_selected))
	{
		document.getElementById('bx_employee_' + current_selected).className = 'bx-employee-row';
	}

	current_selected = this.BX_ID;
	this.className = 'bx-employee-row bx-emp-selected';
<?
endif;
?>
}

function BXEmployeeSet()
{
	if (current_selected<?if ($bMultiple):?>.length<?endif;?> > 0)
	{

		document.getElementById('<?echo $win_id?>').__object.SetValue(current_selected);
		document.getElementById('<?echo $win_id?>').__object.OnSelect();

		var arSelected = <?if ($bMultiple):?>current_selected<?else:?>[current_selected]<?endif;?>.concat(arLastSelected).slice(0, 15);


		jsUserOptions.SaveOption('intranet', 'user_search', 'last_selected', arSelected);

		document.getElementById('<?echo $win_id?>').__object.CloseDialog();
	}
}

function BXShowEmployees(SECTION_ID, arEmployees)
{
	if (null == document.getElementById('<?echo $win_id?>'))
		return false;

	var obSection = document.getElementById('bx_employee_section_' + SECTION_ID);
	var obMain = document.getElementById('<?echo $win_id?>').__object;

	if (!obSection.BX_LOADED)
	{
		obSection.BX_LOADED = true;

		var obSectionDiv = document.getElementById('bx_employees_' + SECTION_ID);
		if (obSectionDiv)
		{
			obSectionDiv.innerHTML = '';

			for (var i = 0; i < arEmployees.length; i++)
			{
				obMain.arEmployeesData[arEmployees[i].ID] = {
					ID: arEmployees[i].ID,
					NAME: arEmployees[i].NAME,
					LOGIN: arEmployees[i].LOGIN,
					EMAIL: arEmployees[i].EMAIL,
					PHOTO: arEmployees[i].PHOTO,
					HEAD: arEmployees[i].HEAD
				};

				var obUserRow = document.createElement('DIV');
				obUserRow.id = 'bx_employee_' + arEmployees[i].ID;
				obUserRow.className = 'bx-employee-row';

				obUserRow.BX_ID = arEmployees[i].ID;

<?
if ($bMultiple):
?>
				var obCheckbox = BX.create('INPUT', {
					props: {
						type: 'checkbox',
						id: 'bx_employee_check_' + arEmployees[i].ID,
						defaultChecked: false
					}
				})

				for (var j = 0; j < current_selected.length; j++)
				{
					if (obUserRow.BX_ID == current_selected[j])
					{
						obCheckbox.defaultChecked = true;
						obUserRow.className += ' bx-emp-selected';
						break;
					}
				}
<?
else:
?>
				if (obUserRow.BX_ID == current_selected)
					obUserRow.className += ' bx-emp-selected';

				obUserRow.ondblclick = BXEmployeeSet;
<?
endif;
?>
				obUserRow.onclick = BXEmployeeSelect;

				obUserRow.innerHTML = '<div class="bx-employee-photo' + (arEmployees[i].PHOTO ? '' : ' bx-no-photo') + '"' + (arEmployees[i].HEAD ? ' style="border-color: #1952BE"' : '') + '>' + arEmployees[i].PHOTO + '</div><div class="bx-employee-info"><div class="bx-employee-name">' + arEmployees[i].NAME + '</div><div class="bx-employee-position"' + (arEmployees[i].HEAD ? ' style="color: #1952BE; font-weight: bold;"' : '') + '>' + arEmployees[i].WORK_POSITION + (arEmployees[i].HEAD ? ', <?echo CUtil::JSEscape(GetMessage('INTR_EMP_HEAD'))?>' : '') + '</div></div>';

<?
if ($bMultiple):
?>
				obUserRow.insertBefore(obCheckbox, obUserRow.firstChild);
<?
endif;
?>

				obSectionDiv.appendChild(obUserRow);
			}

			var obClearer = obSectionDiv.appendChild(document.createElement('DIV'));
			obClearer.style.clear = 'both';
		}
	}
}

function BXLoadEmployees(SECTION_ID, bShowOnly, bScrollToSection)
{
	if (null == bShowOnly) bShowOnly = false;
	if (null == bScrollToSection) bScrollToSection = false;

	if (SECTION_ID != 'last' && SECTION_ID != 'extranet') SECTION_ID = parseInt(SECTION_ID);

	var obSection = document.getElementById('bx_employee_section_' + SECTION_ID);

	if (null == obSection.BX_LOADED)
	{
		var url = '/bitrix/components/bitrix/intranet.user.search/ajax.php?lang=<?echo LANGUAGE_ID?>&MODE=EMPLOYEES&SECTION_ID=' + SECTION_ID + '&SITE_ID=<?=$SITE_ID?>' + '&IS_EXTRANET=<?=(($bExtranet) ? "Y" : "N")?>&sessid=<?=bitrix_sessid();?>&nt=<?=urlencode($nameTemplate);?>';

		if (bScrollToSection)
		{
			jsUtils.loadJSFile(url,	function(){document.getElementById('bx_employee_search_layout').scrollTop = document.getElementById('bx_employee_section_' + SECTION_ID).offsetTop - 40;});
		}
		else
		{
			jsUtils.loadJSFile(url);
		}
	}
	else if (bScrollToSection)
	{
		document.getElementById('bx_employee_search_layout').scrollTop = document.getElementById('bx_employee_section_' + SECTION_ID).offsetTop - 40;
	}

	var obChildren = document.getElementById('bx_children_' + SECTION_ID);
	if (bShowOnly || obChildren.style.display == 'none')
	{
		obSection.firstChild.className = obSection.firstChild.className.replace('bx-emp-closed', 'bx-emp-opened');

		obChildren.style.display = 'block';
	}
	else
	{
		obSection.firstChild.className = obSection.firstChild.className.replace('bx-emp-opened', 'bx-emp-closed');
		obChildren.style.display = 'none';
	}
}
</script>
<script>
var jsEmpSearch = {
	_control: null,
	_timerId: null,

	_delay: 500,

	_value: '',
	_result: [],

	_div: null,

	_search_focus: -1,

	InitControl: function(control_id)
	{
		this._control = document.getElementById(control_id);
		if (this._control)
		{
			this._control.value = '<?echo CUtil::JSEscape(GetMessage('INTR_EMP_SEARCH'))?>';
			this._control.value_tmp = this._control.value;

			this._control.className = 'bx-search-control-empty';
			this._control.onfocus = this.__control_focus;
			this._control.onblur = this.__control_blur;

			this._control.onkeydown = this.__control_keypress;
		}
	},

	Run: function()
	{
		if (null != jsEmpSearch._timerId)
			clearTimeout(jsEmpSearch._timerId);

		jsEmpSearch._search_focus = -1;

		if (jsEmpSearch._control.value && jsEmpSearch._control.value != jsEmpSearch._control.value_tmp)
		{
			jsEmpSearch._value = jsEmpSearch._control.value;
			jsUtils.loadJSFile('/bitrix/components/bitrix/intranet.user.search/ajax.php?lang=<?echo LANGUAGE_ID?>&MODE=SEARCH&search=' + encodeURIComponent(jsEmpSearch._value) + '&SITE_ID=<?=$SITE_ID?>' + '&IS_EXTRANET=<?=(($bExtranet) ? "Y" : "N")?>&sessid=<?=bitrix_sessid();?>&nt=<?=urlencode($nameTemplate);?>');
		}
	},

	SetResult: function(data)
	{
		jsEmpSearch._result = data;
		jsEmpSearch.Show();
	},

	Show: function()
	{
		if (null == jsEmpSearch._div)
		{
			var pos = jsUtils.GetRealPos(jsEmpSearch._control);

			//jsEmpSearch._div = document.body.appendChild(document.createElement('DIV'));
			jsEmpSearch._div = document.getElementById('_f_popup_content').insertBefore(document.createElement('DIV'), document.getElementById('_f_popup_content').firstChild);
			jsEmpSearch._div.className = 'bx-emp-search-result';

			//jsEmpSearch._div.style.top = (pos.bottom + 2) + 'px';
			//jsEmpSearch._div.style.left = (pos.left + 3) + 'px';
			jsEmpSearch._div.style.top = (22 + pos.bottom-pos.top) + 'px';
			jsEmpSearch._div.style.left = '0px';

			jsEmpSearch._div.style.zIndex = 1110;

			jsUtils.addCustomEvent('onEmployeeSearchClose', jsEmpSearch.__onclose, [], jsEmpSearch);
		}
		else
		{
			jsEmpSearch._div.innerHTML = '';
		}

		if (jsEmpSearch._result.length > 0)
		{
			for (var i = 0; i < jsEmpSearch._result.length; i++)
			{
				jsEmpSearch._result[i]._row = jsEmpSearch._div.appendChild(document.createElement('DIV'));
				jsEmpSearch._result[i]._row.className = 'bx-emp-search-result-row';
				jsEmpSearch._result[i]._row.innerHTML = jsEmpSearch._result[i].NAME;

				jsEmpSearch._result[i]._row.onclick = jsEmpSearch.__result_row_click;

				jsEmpSearch._result[i]._row.__bx_data = jsEmpSearch._result[i];
			}
		}
		else
		{
			jsEmpSearch._div.innerHTML = '<i><?echo CUtil::JSEscape(GetMessage('INTR_EMP_NOTHING_FOUND'));?></i>';
		}
	},

	_openSection: function(SECTION_ID, bScrollToSection)
	{
		if (null == bScrollToSection)
			bScrollToSection = false;

		var obSectionDiv = document.getElementById('bx_employee_section_' + SECTION_ID);
		if (null != obSectionDiv)
		{
			var obParentSection = obSectionDiv.parentNode;
			if (null != obParentSection)
			{
				obParentSection = obParentSection.previousSibling;

				if (null != obParentSection && obParentSection.id && obParentSection.id.substr(0, 20) == 'bx_employee_section_')
				{
					jsEmpSearch._openSection(parseInt(obParentSection.id.substr(20)));
				}
			}

			BXLoadEmployees(SECTION_ID, true, bScrollToSection);
		}
	},

	__result_row_click: function()
	{
		if(this.__bx_data.UF_DEPARTMENT[0])
			jsEmpSearch._openSection(this.__bx_data.UF_DEPARTMENT[0], true);
		else
			jsEmpSearch._openSection('extranet', true);

		var obUserRow = document.getElementById('bx_employee_' + this.__bx_data.ID);
		if (null != obUserRow)
		{

			if (obUserRow.className != 'bx-employee-row bx-emp-selected')
			{
				obUserRow.onclick();
			}
		}
		else
		{
<?
if (!$bMultiple):
?>

			if (current_selected > 0 && document.getElementById('bx_employee_' + current_selected))
			{
				document.getElementById('bx_employee_' + current_selected).className = 'bx-employee-row';
			}

<?
endif;
?>

			current_selected<?echo $bMultiple ? '[current_selected.length]' : ''?> = parseInt(this.__bx_data.ID);
		}
	},

	__onclose: function()
	{
		if (null != this._div)
			this._div.parentNode.removeChild(this._div);

		if (null != this._timerId)
			clearTimeout(this._timerId);

		jsUtils.removeCustomEvent('onEmployeeSearchClose', this.__onclose);
	},

	__control_keypress: function(e)
	{
		if (null == e)
			e = window.event;

		// 40 - down, 38 - up, 13 - enter
		switch (e.keyCode)
		{
			case 13: //enter
				if (jsEmpSearch._search_focus < 0)
					jsEmpSearch.Run();
				else
				{
					jsEmpSearch._control.onblur();
					jsEmpSearch._control.blur();
					jsEmpSearch._result[jsEmpSearch._search_focus]._row.onclick();
				}

			break;

			case 40: //down
				if (jsEmpSearch._result.length > 0 && jsEmpSearch._search_focus < jsEmpSearch._result.length-1)
				{
					if (jsEmpSearch._search_focus >= 0)
						jsEmpSearch._result[jsEmpSearch._search_focus]._row.className = 'bx-emp-search-result-row';

					jsEmpSearch._search_focus++;
					jsEmpSearch._result[jsEmpSearch._search_focus]._row.className = 'bx-emp-search-result-row bx-emp-search-result-row-selected';
				}
			break;

			case 38: //up
				if (jsEmpSearch._result.length > 0 && jsEmpSearch._search_focus > -1)
				{
					jsEmpSearch._result[jsEmpSearch._search_focus]._row.className = 'bx-emp-search-result-row';
					jsEmpSearch._search_focus--;

					if (jsEmpSearch._search_focus >= 0)
						jsEmpSearch._result[jsEmpSearch._search_focus]._row.className = 'bx-emp-search-result-row bx-emp-search-result-row-selected';
				}

			break;
			default:
				if (null != jsEmpSearch._timerId)
					clearTimeout(jsEmpSearch._timerId);

				jsEmpSearch._timerId = setTimeout(jsEmpSearch.Run, jsEmpSearch._delay);
			break;
		}
	},

	__control_focus: function()
	{
		if (this.value == this.value_tmp)
		{
			this.value = '';
			this.className = '';
		}

		if (null != jsEmpSearch._div)
			jsEmpSearch._div.style.display = 'block';
	},

	__control_blur: function()
	{
		if (this.value == '')
		{
			this.value = this.value_tmp;
			this.className = 'bx-search-control-empty';
		}

		if (null != jsEmpSearch._div)
		{
			setTimeout(function() {
				jsEmpSearch._div.style.display = 'none';
			}, 300);
		}
	}
};
</script>
<div class="content" id="_f_popup_content" style="height: 400px; overflow-x: hidden; oveflow-y: auto; padding: 0px;"><input id="bx_emp_search_control" type="text" style="width: 99%" value="" autocomplete="off" />
<script>
jsEmpSearch.InitControl('bx_emp_search_control');
</script>
<?
	if (true || is_array($arLastSelected) && count($arLastSelected) > 0):
?>
<div class="bx-employee-section-list" id="bx_employee_search_layout">
	<div class="bx-employee-section-first" onclick="BXLoadEmployees('last')" id="bx_employee_section_last"><div class="bx-employee-section-name bx-emp-opened"><?echo GetMessage('INTR_EMP_LAST')?></div></div>
	<div style="display: none;" id="bx_children_last"><div class="bx-employees-list" id="bx_employees_last" style="margin-left: 15px"><i><?echo GetMessage('INTR_EMP_WAIT')?></i></div></div>
<script>
BXLoadEmployees('last', true);
</script>
<?
	endif;

	function EmployeeDrawStructure($arStructure, $arSections, $key)
	{
		foreach ($arStructure[$key] as $ID)
		{
			$arRes = $arSections[$ID];

			echo '<div class="bx-employee-section'.($key == 0 ? '-first' : '').'" style="padding-left: '.(($arRes['DEPTH_LEVEL']-1)*15).'px" onclick="BXLoadEmployees(\''.$ID.'\')" id="bx_employee_section_'.$ID.'">';
			echo '<div class="bx-employee-section-name bx-emp-closed">'.htmlspecialcharsbx($arRes['NAME']).'</div>';
			echo '</div>';

			echo '<div style="display: none" id="bx_children_'.$arRes['ID'].'">';
			if (is_array($arStructure[$ID]))
			{
				EmployeeDrawStructure($arStructure, $arSections, $ID);
			}
			echo '<div class="bx-employees-list" id="bx_employees_'.$ID.'" style="margin-left: '.($arRes['DEPTH_LEVEL']*15).'px"><i>'.GetMessage('INTR_EMP_WAIT').'</i></div>';
			echo '</div>';

		}
	}

	$dbRes = CIBlockSection::GetTreeList(array('IBLOCK_ID' => COption::GetOptionInt('intranet', 'iblock_structure')));
	$arStructure = array(0 => array());
	$arSections = array();
	while ($arRes = $dbRes->Fetch())
	{
		if (!$arRes['IBLOCK_SECTION_ID'])
			$arStructure[0][] = $arRes['ID'];
		elseif (!is_array($arStructure[$arRes['IBLOCK_SECTION_ID']]))
			$arStructure[$arRes['IBLOCK_SECTION_ID']] = array($arRes['ID']);
		else
			$arStructure[$arRes['IBLOCK_SECTION_ID']][] = $arRes['ID'];

		$arSections[$arRes['ID']] = $arRes;
	}

	if ($bExtranet)
	{
		$arStructure[0][] = "extranet";
		$arSections["extranet"] = Array("ID" => "extranet", "NAME" => GetMessage("INTR_EMP_EXTRANET"));
	}

	EmployeeDrawStructure($arStructure, $arSections, 0);
	if (count($arStructure[0]) <= 1 && count($arOpenedSections) <= 0)
	{
		$arOpenedSections[] = $arStructure[0][0];
	}

	if (count($arOpenedSections) > 0)
	{
?>
<script>
<?
		$arSectionList = array();
		foreach ($arOpenedSections as $opened_section)
		{
			while ($opened_section > 0)
			{
				if (in_array($opened_section, $arSectionList))
					break;
				$arSectionList[] = $opened_section;

?>
section_id = '<?echo $opened_section?>';
BXLoadEmployees(section_id, true);
<?
				$opened_section = $arSections[$opened_section]['IBLOCK_SECTION_ID'];
			}
		}
?>
</script>
<?
	}
?>
	</div>
</div>
<div class="buttons">
	<input type="button" id="submitbtn" value="<?echo GetMessage('INTR_EMP_SUBMIT')?>" onclick="BXEmployeeSet();" title="<?echo GetMessage('INTR_EMP_SUBMIT_TITLE')?>" />
	<input type="button" value="<?echo GetMessage('INTR_EMP_CANCEL')?>" onclick="document.getElementById('<?=htmlspecialcharsbx($win_id) ?>').__object.CloseDialog();" title="<?echo GetMessage('INTR_EMP_CANCEL_TITLE')?>" />
</div>