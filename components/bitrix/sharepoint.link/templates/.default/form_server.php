<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die(); 

__IncludeLang($_SERVER['DOCUMENT_ROOT'].$this->GetFolder().'/lang/'.LANGUAGE_ID.'/template.php');
?>

<script type="text/javascript">
var oberror = null;
var step = 1;
function SLshowError(msg)
{
	if (null == oberror) oberror = BX('error_message');
	oberror.innerHTML = msg;
	oberror.style.display = 'block';
	step--;
}

function SLhideError()
{
	if (oberror)
		oberror.style.display = 'none';
}

function SLshowPass(bShow)
{
	var form = wnd.GetForm();
	
	if (form.sp_pass)
	{
		form.sp_pass.type = bShow ? 'text' : 'password';
	}
}

var bParamsChanged = false, paramsTimer = null;

function SLtestParams()
{
	if (null != paramsTimer)
		clearTimeout(paramsTimer);
	
	if (bParamsChanged)
		paramsTimer = setTimeout(_SLtestParams, 300);
}

function _SLtestParams()
{
	var form = wnd.GetForm();
	
	var arParams = {
		mode: 'test',
		sp_server: form.sp_server.value,
		sp_user: form.sp_user.value,
		sp_pass: form.sp_pass.value,
		sessid: BX.bitrix_sessid()
	};

	BX.showWait('bx_admin_form', '<?=CUtil::JSEscape(GetMessage('SL_FORM_SERVER_WAIT_SERVER_CHECK'))?>');
	SLnextButton.disable();
	
	var url = '<?=CUtil::JSEscape($arResult['SELF'])?>';
	BX.ajax.get(url, arParams, function() {
		SLnextButton.enable();
		BX.closeWait();
		bParamsChanged = false;
	});
	
	return false;
}

function SLtestParamsSetValue(val)
{
	BX('sp_server_result_' + val[0]).style.display = 'block';
	BX('sp_server_result_' + (1-val[0])).style.display = 'none';
	BX('sp_auth_result_' + val[1]).style.display = 'block';
	BX('sp_auth_result_' + (1-val[1])).style.display = 'none';
}

var SLnextButton = new BX.CWindowButton({
	title: '<?=CUtil::JSEscape(GetMessage('SL_FORM_SERVER_BUTTON_CAPTION_NEXT'))?>',
	action: function() 
	{
		var form = this.parentWindow.GetForm();

		var url = '<?=CUtil::JSEscape($arResult['SELF'])?>?mode=edit&ID=<?=$arParams['IBLOCK_ID']?>';
		
		var steps = 5;
		
		if (form.sp_server.value.length > 0 && form.sp_server.value != 'http://')
		{
			url += '&server=' + BX.util.urlencode(form.sp_server.value) + '&user=' + BX.util.urlencode(form.sp_user.value) + '&pass=' + BX.util.urlencode(form.sp_pass.value);
			step = 2;
		}
		
		if (window.sp_list_id)
		{
			url += '&list_id=' + window.sp_list_id;
			step = 3;
		}
		
		if (form.step)
		{
			step = form.step;
			
			var arRows, t, len;
			if ((t = BX('sp_fields')) && (arRows = t.tBodies[0].rows))
			{
				if ((len = arRows.length) > 0)
				{
					step = 4;
					
					for(var i=0; i<form.sp_interval.length; ++i)
					{
						if (form.sp_interval[i].checked)
						{
							url += '&period=' + form.sp_interval[i].value;
							break;
						}
					}
					
					for (i=0; i<len; ++i)
					{
						var val = arRows[i].getAttribute('name');
						if (!val) continue;
						
						val = val.split('|');
						
						if (val.length > 0 && val[0].length > 0 && val[1].length > 0)
							url += '&'+BX.util.urlencode('FIELDS[' + val[0] + ']') + '=' + BX.util.urlencode(val[1]);
					}

				}
			}
		}
		
		url += '&sessid=' + BX.bitrix_sessid();
		
		for (var i=1; i<=steps;i++)
		{
			var q = BX('step' + i);
			if (q) q.style.display = 'none';
		}
		
		SLhideError();
		
		BX.showWait('bx_admin_form', step == 2 ? '<?=CUtil::JSEscape(GetMessage('SL_FORM_SERVER_WAIT_STEP_2'))?>' :
				(step == 3 ? '<?=CUtil::JSEscape(GetMessage('SL_FORM_SERVER_WAIT_STEP_3'))?>' : 
				(step == 4 ? '<?=CUtil::JSEscape(GetMessage('SL_FORM_SERVER_WAIT_STEP_4'))?>' : '')));
		
		var url = url.split('?')
		BX.ajax.post(url[0], url[1], function(data) {
			data = BX.util.trim(data);
			
			var cont = BX('step' + step);
			if (cont)
			{
				cont.style.display = 'block';

				if (data.length > 0)
					cont.innerHTML = data;
			}
			
			BX.closeWait();
		});
	}
});

var wnd = BX.WindowManager.Get();
wnd.SetTitle('<?=CUtil::JSEscape(GetMessage('SL_FORM_SERVER_STEP_TITLE'))?>');
wnd.SetHead('<?=CUtil::JSEscape(GetMessage('SL_FORM_SERVER_STEP_HEAD'))?>');
wnd.ClearButtons(SLnextButton);
wnd.SetButtons(SLnextButton);
</script>
<form>
<div id="bx_admin_form">
<div id="error_message"></div>

<div id="step1">
	<table class="bx-width100">
		<tbody>
			<tr class="section">
				<td colspan="2"><?=GetMessage('SL_SETTINGS_SECTION_CONN')?></td>
			</tr>
			<tr>
				<td class="bx-popup-label bx-width30" valign="top"><?=GetMessage('SL_SETTINGS_CONN_SERVER')?>: </td>
				<td><input type="text" name="sp_server" value="<?=$arResult['SERVICE']['SP_URL'] ? htmlspecialcharsbx($arResult['SERVICE']['SP_URL']) : 'http://'?>" size="50" onchange="bParamsChanged = true;" onblur="SLtestParams();" />
				<div id="sp_server_result_0" style="display: none;"><?=ShowError(GetMessage('SL_FORM_SERVER_DOWN'))?></div>
				<div id="sp_server_result_1" style="display: none;"><?=ShowNote(GetMessage('SL_FORM_SERVER_OK'))?></div>
				</td>
			</tr>
			<tr class="section">
				<td colspan="2"><?=GetMessage('SL_SETTINGS_SECTION_AUTH')?></td>
			</tr>
			<tr>
				<td class="bx-popup-label" valign="top"><?=GetMessage('SL_SETTINGS_CONN_USER')?>: </td>
				<td><input type="text" name="sp_user" value="<?=htmlspecialcharsbx($arResult['SERVICE']['SP_AUTH_USER'])?>" size="30" onchange="bParamsChanged = true;" onblur="SLtestParams();" /></td>
			</tr>
			<tr>
				<td class="bx-popup-label" valign="top"><?=GetMessage('SL_SETTINGS_CONN_PASS')?>: </td>
				<td>
					<input type="password" name="sp_pass"  value="<?=htmlspecialcharsbx($arResult['SERVICE']['SP_AUTH_PASS'])?>" size="30" onchange="bParamsChanged = true" onblur="SLtestParams();" />
					<input type="checkbox" id="show_pass" onclick="SLshowPass(this.checked)" /> <label for="show_pass"><?=GetMessage('SL_SETTINGS_CONN_PASS_SHOW_SYMBOLS')?></label>
					<div id="sp_auth_result_0" style="display: none;"><?=ShowError(GetMessage('SL_FORM_SERVER_AUTH_FAILED'))?></div>
					<div id="sp_auth_result_1" style="display: none;"><?=ShowNote(GetMessage('SL_FORM_SERVER_AUTH_OK'))?></div>

				</td>
			</tr>
		</tbody>
	</table>
<a href="javascript: void(0)" onclick="_SLtestParams(); return false;"><?echo GetMessage('SL_SETTINGS_SERVER_TEST');?></a>
<?echo BeginNote(),GetMessage('SL_FORM_SERVER_NOTE_AUTH_BASIC'),EndNote();?>
</div>

<div id="step2" style="display: none;"></div>
<div id="step3" style="display: none;"></div>

<div id="message"></div>
</form>