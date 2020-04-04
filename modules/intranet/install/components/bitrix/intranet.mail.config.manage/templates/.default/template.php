<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

$customDomains = array();
foreach ($arParams['SERVICES'] as $service)
{
	if (in_array($service['type'], array('domain', 'crdomain')))
		$customDomains[] = $service['server'];
}

$hasOptions1 = \CIntranetMailConfigComponent::isFeatureAvailable('domain_service') > 0;
$hasOptions2 = isModuleInstalled('crm');

$bodyClass = $APPLICATION->getPageProperty('BodyClass', false);
$APPLICATION->setPageProperty('BodyClass', trim(sprintf('%s %s', $bodyClass, 'pagetitle-toolbar-field-view')));

\CJSCore::init(array('socnetlogdest', 'popup', 'fx'));
$APPLICATION->setAdditionalCSS('/bitrix/components/bitrix/main.post.form/templates/.default/style.css');

$currentUser = $arParams['CURRENT_USER'];
$currentUser['__id'] = sprintf('U%u', $currentUser['ID']);
$currentUser['__photo'] = '';

if ($currentUser['PERSONAL_PHOTO'])
{
	$currentUserPhoto = \CFile::resizeImageGet(
		$currentUser['PERSONAL_PHOTO'],
		array('width' => 100, 'height' => 100),
		BX_RESIZE_IMAGE_EXACT, false
	);

	if (!empty($currentUserPhoto['src']))
		$currentUser['__photo'] = $currentUserPhoto['src'];
}

$usersForSelector = array(
	$currentUser['__id'] => array(
		'id'       => $currentUser['__id'],
		'entityId' => $currentUser['ID'],
		'name'     => \CUser::formatName(\CSite::getNameFormat(), $currentUser, true),
		'avatar'   => $currentUser['__photo'],
		'desc'     => $currentUser['WORK_POSITION'] ?: $currentUser['PERSONAL_PROFESSION'] ?: '&nbsp;'
	),
);
$lastForSelector  = array(
	$currentUser['__id'] => $currentUser['__id'],
);

if (SITE_TEMPLATE_ID == 'bitrix24')
{
	$this->setViewTarget('inside_pagetitle'); ?>

	<div class="pagetitle-container pagetitle-flexible-space">
		<? $APPLICATION->includeComponent(
			'bitrix:main.ui.filter', '',
			array(
				'FILTER_ID'    => $arResult['FILTER_ID'],
				'GRID_ID'      => $arResult['GRID_ID'],
				'ENABLE_LABEL' => true,
				'FILTER'       => $arResult['FILTER'],
			)
		); ?>
		<span class="webform-small-button webform-small-button-transparent  webform-small-button-dropdown" onclick="mb.modeMenu(this); ">
			<span class="webform-small-button-text">
				<?=getMessage('user' == $arResult['MODE'] ? 'INTR_MAIL_MANAGE_MODE_USER' : 'INTR_MAIL_MANAGE_MODE_MAILBOX') ?>
			</span>
			<span class="webform-small-button-icon"></span>
		</span>
	</div>

	<span class="pagetitle-container pagetitle-align-right-container">
		<? if ($hasOptions1 || $hasOptions2): ?>
			<span class="webform-small-button webform-small-button-transparent webform-cogwheel" onclick="mb.optionsMenu(this); ">
				<span class="webform-button-icon"></span>
			</span>
		<? endif ?>
		<? if (!empty($arParams['SERVICES'])): ?>
			<span class="webform-small-button webform-small-button-blue webform-small-button-add" onclick="mb.create(); ">
				<span class="webform-small-button-icon"></span>
				<span class="webform-small-button-text">
					<?=getMessage('INTR_MAIL_MANAGE_ADD_MAILBOX2') ?>
				</span>
			</span>
		<? endif ?>
	</span>

	<? $this->endViewTarget();
}
else
{
	?>

	<table style="border: none; border-spacing: 0; width: 100%; ">
		<tr>
			<td style="vertical-align: middle; ">
				<table style="border: none; border-spacing: 0; ">
					<tr>
						<td style="vertical-align: middle; ">
							<? $APPLICATION->includeComponent(
								'bitrix:main.ui.filter', '',
								array(
									'FILTER_ID'    => $arResult['FILTER_ID'],
									'GRID_ID'      => $arResult['GRID_ID'],
									'ENABLE_LABEL' => true,
									'FILTER'       => $arResult['FILTER'],
								)
							); ?>
						</td>
						<td style="vertical-align: middle; ">&nbsp;
							<span class="webform-small-button-separate-wrap" onclick="mb.modeMenu(this); ">
								<span class="webform-small-button">
									<?=getMessage('user' == $arResult['MODE'] ? 'INTR_MAIL_MANAGE_MODE_USER' : 'INTR_MAIL_MANAGE_MODE_MAILBOX') ?>
								</span>
								<span class="webform-small-button-right-part"></span>
							</span>
						</td>
					</tr>
				</table>
			</td>
			<td style="vertical-align: middle; text-align: right; ">
				<? if ($hasOptions1 || $hasOptions2): ?>
					<span class="webform-small-button" onclick="mb.optionsMenu(this); ">
						<?=getMessage('INTR_MAIL_MANAGE_SETUP') ?>
					</span>
				<? endif ?>
				<? if (!empty($arParams['SERVICES'])): ?>
					<span class="webform-small-button webform-small-button-blue" onclick="mb.create(); ">
						<?=getMessage('INTR_MAIL_MANAGE_ADD_MAILBOX2') ?>
					</span>
				<? endif ?>
			</td>
		</tr>
	</table>

	<?
}

$APPLICATION->includeComponent(
	'bitrix:main.ui.grid', '',
	array(

		'GRID_ID' => $arResult['GRID_ID'],
		'AJAX_MODE' => 'Y',
		'AJAX_OPTION_HISTORY' => 'N',

		'HEADERS' => $arResult['MODE'] == 'user' ? array(
			array('id' => 'NAME', 'name' => GetMessage('INTR_MAIL_MANAGE_GRID_NAME'), 'sort' => 'name', 'default' => true, 'editable' => false),
			array('id' => 'EMAIL', 'name' => GetMessage('INTR_MAIL_MANAGE_GRID_EMAIL'), 'default' => true, 'editable' => false),
			array('id' => 'CRM', 'name' => 'CRM', 'showname' => false, 'default' => true, 'editable' => false),
		) : array(
			array('id' => 'EMAIL', 'name' => GetMessage('INTR_MAIL_MANAGE_GRID_EMAIL'), 'sort' => 'email', 'default' => true, 'editable' => false),
			array('id' => 'NAME', 'name' => GetMessage('INTR_MAIL_MANAGE_GRID_NAME'), 'default' => true, 'editable' => false),
			array('id' => 'CRM', 'name' => 'CRM', 'showname' => false, 'default' => true, 'editable' => false),
		),

		'ROWS' => $arResult['ROWS'],

		'SHOW_GRID_SETTINGS_MENU' => false,
		'ALLOW_COLUMNS_SORT' => false,
		'ALLOW_ROWS_SORT' => false,

		'SHOW_ROW_CHECKBOXES' => false,
		'SHOW_SELECTED_COUNTER' => false,

		'NAV_OBJECT' => $arResult['NAV_OBJECT'],

		'TOTAL_ROWS_COUNT' => $arResult['NAV_OBJECT']->getRecordCount(),

	)
);

?>

<script type="text/javascript">

	var domains = {};
	var services = {};
	var domainUsers = {};

	<? foreach ($arParams['SERVICES'] as $service)
	{
		if ($service['type'] == 'controller')
		{
			?>services['<?=$service['id']; ?>'] = <?=CUtil::phpToJSObject(array_values($service['domains'])); ?>;<?
			?>domainUsers['<?=$service['id']; ?>'] = <?=CUtil::phpToJSObject($service['users']); ?>;<?
		}
		if (in_array($service['type'], array('domain', 'crdomain')))
		{
			?>domains['<?=$service['id']; ?>'] = ['<?=$service['server']; ?>'];<?
			?>services['<?=$service['id']; ?>'] = ['<?=$service['server']; ?>'];<?
			?>domainUsers['<?=$service['id']; ?>'] = <?=CUtil::phpToJSObject($service['users']); ?>;<?
		}
	} ?>

	var blacklist = '<?=CUtil::jsEscape(join(', ', $arParams['BLACKLIST'])) ?>';
	var allowCrm = <?=($arParams['ALLOW_CRM'] ? 'true' : 'false') ?>;

	var mb = {
		dialog: (function()
		{
			var dlg = new BX.PopupWindow('qweqwe', null, {
				width: 580,
				titleBar: ' ',
				closeIcon: true,
				overlay: true,
				lightShadow: true,
				contentColor: 'white',
				contentNoPaddings: true
			});

			dlg.hideNotify = function()
			{
				var error = BX.findChild(dlg.contentContainer, {attr: {'name': 'form_error'}}, true);
				BX.hide(error, 'block');
			};

			dlg.showNotify = function(text)
			{
				var error = BX.findChild(dlg.contentContainer, {attr: {'name': 'form_error'}}, true);

				error.innerHTML = text;
				BX.show(error, 'block');
			};

			dlg.getFormData = function()
			{
				var form = BX.findChild(dlg.contentContainer, {'tag': 'form'}, true);

				var data = {};
				for (var i = 0; i < form.elements.length; i++)
				{
					if (form.elements[i].name)
					{
						var inputType = form.elements[i].type.toLowerCase();
						if (inputType == 'checkbox' || inputType == 'radio')
						{
							if (!form.elements[i].checked)
								continue;
						}

						if (form.elements[i].name.match(/\[\]$/))
						{
							var pname = form.elements[i].name.substr(0, form.elements[i].name.length-2);
							if (typeof data[pname] == 'undefined')
								data[pname] = [];
							data[pname].push(form.elements[i].value);
						}
						else
						{
							data[form.elements[i].name] = form.elements[i].value;
						}
					}
				}

				return data;
			}

			return dlg;
		})(),
		toggleSubform: function(el)
		{
			var form = BX.findParent(el, {'tag': 'form'});
			var tabs = BX.findChild(form, {'class': 'popup-window-tab'}, true, true);

			mb.dialog.hideNotify();

			if (el.getAttribute('data-name') != 'select')
			{
				var show = BX.findChild(form, {attr: {'name': 'create_subform'}}, true);
				var hide = BX.findChild(form, {attr: {'name': 'select_subform'}}, true);

				form.elements['create'].value = 1;
			}
			else
			{
				var show = BX.findChild(form, {attr: {'name': 'select_subform'}}, true);
				var hide = BX.findChild(form, {attr: {'name': 'create_subform'}}, true);

				form.elements['create'].value = 0;
			}

			for (var i = 0; i < tabs.length; i++)
				BX.removeClass(tabs[i], 'popup-window-tab-selected');
			BX.addClass(el, 'popup-window-tab-selected');

			BX.removeClass(hide, 'popup-window-tab-content-selected');
			BX.addClass(show, 'popup-window-tab-content-selected');
		},
		toggleService: function(el, iname)
		{
			var sid = el.options[el.selectedIndex].getAttribute('data-sid');
			BX.findChild(el.parentNode, {attr: {'name': iname}}, true, false).value = sid;

			if (iname == 'sservice')
			{
				var domain = el.value;
				var select = BX.findChild(BX.findParent(el, {attr: {'name': 'select_subform'}}), {attr: {'name': 'suser'}}, true, false);

				while (select.options.length > 1)
					select.remove(1);

				for (var i in domainUsers[sid][domain])
				{
					var option = document.createElement('option');

					option.value = domainUsers[sid][domain][i];
					option.text  = domainUsers[sid][domain][i];

					select.add(option);
				}
			}
		},
		getConnectForm: function(rid)
		{
			var connectForm = '<form class="manage-dialog-form">';
			connectForm += '<?=bitrix_sessid_post() ?>';
			connectForm += '<input type="hidden" name="create" value="0">';

			var dummyNode = BX.create('DIV', {
				children: [
					BX.create('INPUT', {
						attrs: { 
							type: 'hidden', 
							name: 'MAILBOX', 
							value: rid
						}
					})
				]
			});

			connectForm += '<div class="manage-dialog-form-error" name="form_error" style="display: none; "></div>'
				+ '<div class="popup-window-tab-content popup-window-tab-content-selected">'
				+ '<span class="manage-dialog-form-label" style="padding-top: 0px; "><?=CUtil::jsEscape(getMessage('INTR_MAIL_MANAGE_INP_USER')) ?>:</span>'
				+ '<div class="feed-add-post-destination-wrap mail-set-crm-resp-wrap" id="mailbox_user_selector_container" style="background-color: #ffffff; ">'
				+ '<span id="mailbox_user_selector_item"></span>'
				+ '<span class="feed-add-destination-input-box" id="mailbox_user_selector_input_box" style="display: none; ">'
				+ '<input type="text" value="" class="feed-add-destination-inp" id="mailbox_user_selector_input"></span>'
				+ '<a href="javascript:void(0)" class="feed-add-destination-link" id="mailbox_user_selector_tag" style="display: inline-block; ">'
				+ '<?=CUtil::jsEscape(getMessage('INTR_MAIL_MANAGE_INP_USER_SELECT')) ?></a></div></div>'
				+ '<div class="popup-window-tabs-box"><div class="popup-window-tab-content popup-window-tab-content-selected">'
				+ '<span class="manage-dialog-form-label" style="padding-top: 0px; "><?=CUtil::jsEscape(getMessage('INTR_MAIL_MANAGE_FINP_EMAIL')) ?>:</span>'
				+ dummyNode.innerHTML
				+ BX('email_'+rid).parentNode.innerHTML
				+ '</div>';

			connectForm += '</form>';

			return connectForm;
		},
		getCreateForm: function(uid)
		{
			var createForm = '<form class="manage-dialog-form">';
			createForm += '<?=bitrix_sessid_post() ?>';
			createForm += '<input type="hidden" name="create" value="1">';

			if (uid > 0)
			{
				createForm += '<input type="hidden" name="USER_ID" value="'+uid+'">';
				createForm += '<div class="popup-window-tab-content popup-window-tab-content-selected">'+BX('user_'+uid).parentNode.innerHTML+'</div>';

				var selectDomains = [];
				var selectUsers   = [];
				for (var sid in domainUsers)
				{
					for (var domain in domainUsers[sid])
					{
						if (domainUsers[sid][domain].length > 0)
						{
							selectDomains.push([sid, domain]);
							if (selectUsers.length == 0)
							{
								for (var i in domainUsers[sid][domain])
									selectUsers.push(domainUsers[sid][domain][i]);
							}
						}
					}
				}

				if (selectDomains.length > 0)
				{
					var selectSubform = '<div class="popup-window-tab-content" name="select_subform">';

					var selectUser = '<select class="manage-dialog-form-select" name="suser"><option></option>';
					for (var i in selectUsers)
						selectUser += '<option value="'+selectUsers[i]+'">'+selectUsers[i]+'</option>';
					selectUser += '</select>';

					var selectDomain = '<input type="hidden" name="sservice" value="'+selectDomains[0][0]+'">';
					if (selectDomains.length > 1)
					{
						selectDomain += '<select class="manage-dialog-form-select" name="sdomain" onchange="mb.toggleService(this, \'sservice\'); ">';

						for (var i in selectDomains)
							selectDomain += '<option value="'+selectDomains[i][1]+'" data-sid="'+selectDomains[i][0]+'">'+selectDomains[i][1]+'</option>';

						selectDomain += '</select>';
					}
					else
					{
						selectDomain += '<input type="hidden" name="sdomain" value="'+selectDomains[0][1]+'">';
						selectDomain += '@'+selectDomains[0][1];
					}

					selectSubform += '<table class="manage-dialog-form-table" style="margin-top: -12px; ">';
					selectSubform += '<tr><td style="width: 50%; "><span class="manage-dialog-form-label"><?=CUtil::jsEscape(getMessage('INTR_MAIL_MANAGE_INP_EXIST_MB')) ?>:</span>'+selectUser+'</td>';

					selectSubform += selectDomains.length > 1
						? '<td><span class="manage-dialog-form-label">&nbsp;</span><span class="manage-dialog-form-fake" style="font-weight: bold; padding: 7px; ">@</span></td>'
						: '<td></td>';

					selectSubform += '<td style="width: 50%; "><span class="manage-dialog-form-label">&nbsp;</span>';
					selectSubform += selectDomains.length > 1
						? selectDomain+'</td></tr>'
						: '<span class="manage-dialog-form-fake" style="font-weight: bold; padding: 7px; ">'+selectDomain+'</span></td></tr>';

					selectSubform += '</table>';
					selectSubform += '</div>';
				}
			}

			if (typeof selectSubform == 'undefined')
				createForm += '<div class="manage-dialog-form-error" name="form_error" style="display: none; "></div>';

			createForm += '<div class="popup-window-tabs-box">';

			if (typeof selectSubform != 'undefined')
			{
				createForm += '<div class="popup-window-tabs">';
				createForm += '<span class="popup-window-tab popup-window-tab-selected" onclick="mb.toggleSubform(this); " data-name="create"><?=CUtil::jsEscape(getMessage('INTR_MAIL_MANAGE_CREATE_SUBFORM')) ?></span>';
				createForm += '<span class="popup-window-tab" onclick="mb.toggleSubform(this); " data-name="select"><?=CUtil::jsEscape(getMessage('INTR_MAIL_MANAGE_SELECT_SUBFORM')) ?></span>';
				createForm += '</div>';

				createForm += '<div class="popup-window-tabs-content">';
				createForm += '<div class="manage-dialog-form-error" name="form_error" style="display: none; "></div>';
			}

			var createSubform = '<div class="popup-window-tab-content popup-window-tab-content-selected" name="create_subform">';

			var selectDomains = [];
			for (var sid in services)
			{
				for (i in services[sid])
					selectDomains.push([sid, services[sid][i]]);
			}

			var selectDomain = '<input type="hidden" name="cservice" value="'+selectDomains[0][0]+'">';
			if (selectDomains.length > 1)
			{
				selectDomain += '<select class="manage-dialog-form-select" name="cdomain" onchange="mb.toggleService(this, \'cservice\'); ">';

				for (var i in selectDomains)
					selectDomain += '<option value="'+selectDomains[i][1]+'" data-sid="'+selectDomains[i][0]+'">'+selectDomains[i][1]+'</option>';

				selectDomain += '</select>';
			}
			else
			{
				selectDomain += '<input type="hidden" name="cdomain" value="'+selectDomains[0][1]+'">';
				selectDomain += '@'+selectDomains[0][1];
			}

			createSubform += '<table class="manage-dialog-form-table" style="margin-top: -12px; ">';
			createSubform += '<tr><td style="width: 50%; "><span class="manage-dialog-form-label"><?=CUtil::jsEscape(getMessage('INTR_MAIL_MANAGE_INP_LOGIN')) ?>:</span>';
			createSubform += '<input class="manage-dialog-form-inp" type="text" name="cuser"></td>';

			createSubform += selectDomains.length > 1
				? '<td><span class="manage-dialog-form-label">&nbsp;</span><span class="manage-dialog-form-fake" style="font-weight: bold; padding: 7px; ">@</span></td>'
				: '<td></td>';

			createSubform += '<td style="width: 50%; "><span class="manage-dialog-form-label">';
			createSubform += selectDomains.length > 1
				? '<?=CUtil::jsEscape(getMessage('INTR_MAIL_MANAGE_INP_DOMAIN')) ?>:</span>'+selectDomain+'</td></tr>'
				: '&nbsp;</span><span class="manage-dialog-form-fake" style="font-weight: bold; padding: 7px; ">'+selectDomain+'</span></td></tr>';

			createSubform += '<tr><td colspan="3"><span class="manage-dialog-form-label"><?=CUtil::jsEscape(getMessage('INTR_MAIL_MANAGE_INP_PASSWORD')) ?>:</span>';
			createSubform += '<input class="manage-dialog-form-inp" type="password" name="password"></td></tr>';
			createSubform += '<tr><td colspan="3"><span class="manage-dialog-form-label"><?=CUtil::jsEscape(getMessage('INTR_MAIL_MANAGE_INP_PASSWORD2')) ?>:</span>';
			createSubform += '<input class="manage-dialog-form-inp" type="password" name="password2"></td></tr>';

			createSubform += '</table></div>';

			createForm += createSubform;
			if (typeof selectSubform != 'undefined')
				createForm += selectSubform + '</div>';

			createForm += '</div></div></form>';

			return createForm;
		},
		create: function(rid, uid)
		{
			mb.dialog.setTitleBar(
				typeof rid == 'undefined'
					? '<?=CUtil::jsEscape(getMessage('INTR_MAIL_MANAGE_CREATE_TITLE')) ?>'
					: '<?=CUtil::jsEscape(getMessage('INTR_MAIL_MANAGE_CONNECT_TITLE')) ?>'
			);
			mb.dialog.setContent(uid < 0 ? mb.getConnectForm(rid) : mb.getCreateForm(uid));
			mb.dialog.setButtons([
				new BX.PopupWindowButton({
					text: typeof rid == 'undefined'
						? '<?=CUtil::jsEscape(getMessage('INTR_MAIL_MANAGE_CREATE_BTN')) ?>'
						: '<?=CUtil::jsEscape(getMessage('INTR_MAIL_MANAGE_CONNECT_BTN')) ?>',
					className: 'popup-window-button-accept',
					events: {
						click: function ()
						{
							var btn = this;

							if (BX.hasClass(btn.buttonNode, 'popup-window-button-wait'))
								return;

							mb.dialog.hideNotify();
							BX.addClass(btn.buttonNode, 'popup-window-button-wait');

							BX.ajax({
								method: 'POST',
								url: '<?=$this->__component->getPath() ?>/ajax.php?siteid=<?=urlencode(SITE_ID) ?>&act=create',
								data: mb.dialog.getFormData(),
								dataType: 'json',
								onsuccess: function(json)
								{
									BX.removeClass(btn.buttonNode, 'popup-window-button-wait');

									if (json.users)
									{
										for (var sid in json.users.vacant)
										{
											if (typeof domainUsers[sid] == 'undefined')
												continue;

											for (var domain in json.users.vacant[sid])
											{
												if (typeof domainUsers[sid][domain] == 'undefined')
													continue;

												for (var i in json.users.vacant[sid][domain])
												{
													var key = BX.util.array_search(json.users.vacant[sid][domain][i], domainUsers[sid][domain]);
													if (key < 0)
														domainUsers[sid][domain].unshift(json.users.vacant[sid][domain][i]);
												}
											}
										}

										for (var sid in json.users.occupied)
										{
											if (typeof domainUsers[sid] == 'undefined')
												continue;

											for (var domain in json.users.occupied[sid])
											{
												if (typeof domainUsers[sid][domain] == 'undefined')
													continue;

												for (var i in json.users.occupied[sid][domain])
												{
													var key = BX.util.array_search(json.users.occupied[sid][domain][i], domainUsers[sid][domain]);
													if (key >= 0)
														domainUsers[sid][domain].splice(key, 1);
												}
											}
										}
									}

									if (json.result == 'error')
									{
										mb.dialog.showNotify(json.error);
									}
									else
									{
										if (<? if ($arResult['MODE'] == 'user'): ?>uid<? else: ?>true<? endif ?>)
											BX.Main.gridManager.getInstanceById('<?=CUtil::jsEscape($arResult['GRID_ID']) ?>').reload();

										mb.dialog.close();
									}
								},
								onfailure: function()
								{
									BX.removeClass(btn.buttonNode, 'popup-window-button-wait');
									mb.dialog.showNotify('<?=CUtil::jsEscape(getMessage('INTR_MAIL_MANAGE_ERR_AJAX')) ?>');
								}
							});
						}
					}
				}),
				new BX.PopupWindowButton({
					text: '<?=CUtil::jsEscape(getMessage('INTR_MAIL_MANAGE_CANCEL_BTN')) ?>',
					className: 'popup-window-button',
					events: {
						click: function()
						{
							this.popupWindow.close();
						}
					}
				})
			]);

			mb.dialog.show();

			if (uid < 0)
			{
				BX.SocNetLogDestination.init({
					name: 'mailbox_user_selector',
					searchInput: BX('mailbox_user_selector_input'),
					departmentSelectDisable: true,
					extranetUser:  false,
					allowAddSocNetGroup: false,
					bindMainPopup: {
						node: BX('mailbox_user_selector_container'),
						offsetTop: '5px',
						offsetLeft: '15px'
					},
					bindSearchPopup: {
						node: BX('mailbox_user_selector_container'),
						offsetTop: '5px',
						offsetLeft: '15px'
					},
					callback: {
						select: function(item, type)
						{
							var selected = BX.SocNetLogDestination.getSelected('mailbox_user_selector');
							for (var i in selected)
							{
								if (i != item.id || selected[i] != type)
									BX.SocNetLogDestination.deleteItem(i, selected[i], 'mailbox_user_selector');
							}

							BX.SocNetLogDestination.BXfpSelectCallback({
								item: item,
								type: type,
								varName: 'MAILBOX_OWNER',
								bUndeleted: false,
								containerInput: BX('mailbox_user_selector_item'),
								valueInput: BX('mailbox_user_selector_input'),
								formName: 'mailbox_user_selector',
								tagInputName: 'mailbox_user_selector_tag',
								tagLink1: '<?=CUtil::jsEscape(getMessage('INTR_MAIL_MANAGE_INP_USER_SELECT')) ?>',
								tagLink2: '<?=CUtil::jsEscape(getMessage('INTR_MAIL_MANAGE_INP_USER_REPLACE')) ?>'
							});
							BX.SocNetLogDestination.closeDialog('mailbox_user_selector');
						},
						unSelect: BX.delegate(BX.SocNetLogDestination.BXfpUnSelectCallback, {
							formName: 'mailbox_user_selector',
							inputContainerName: 'mailbox_user_selector_item',
							inputName: 'mailbox_user_selector_input',
							tagInputName: 'mailbox_user_selector_tag',
							tagLink1: '<?=CUtil::jsEscape(getMessage('INTR_MAIL_MANAGE_INP_USER_SELECT')) ?>',
							tagLink2: '<?=CUtil::jsEscape(getMessage('INTR_MAIL_MANAGE_INP_USER_REPLACE')) ?>'
						}),
						openDialog: BX.delegate(BX.SocNetLogDestination.BXfpOpenDialogCallback, {
							inputBoxName: 'mailbox_user_selector_input_box',
							inputName: 'mailbox_user_selector_input',
							tagInputName: 'mailbox_user_selector_tag'
						}),
						closeDialog: BX.delegate(BX.SocNetLogDestination.BXfpCloseDialogCallback, {
							inputBoxName: 'mailbox_user_selector_input_box',
							inputName: 'mailbox_user_selector_input',
							tagInputName: 'mailbox_user_selector_tag'
						}),
						openSearch: BX.delegate(BX.SocNetLogDestination.BXfpOpenDialogCallback, {
							inputBoxName: 'mailbox_user_selector_input_box',
							inputName: 'mailbox_user_selector_input',
							tagInputName: 'mailbox_user_selector_tag'
						})
					},
					items: {
						users: <?=CUtil::phpToJSObject($usersForSelector) ?>,
						groups: {},
						sonetgroups: {},
						department: <?=CUtil::phpToJSObject($arParams['COMPANY_STRUCTURE']['department']) ?>,
						departmentRelation: <?=CUtil::phpToJSObject($arParams['COMPANY_STRUCTURE']['department_relation']) ?>
					},
					itemsLast: {
						users: <?=CUtil::phpToJSObject($lastForSelector) ?>,
						sonetgroups: {},
						department: {},
						groups: {}
					},
					itemsSelected: {},
					destSort: {}
				});

				BX.bind(BX('mailbox_user_selector_input'), 'keyup', BX.delegate(BX.SocNetLogDestination.BXfpSearch, {
					formName: 'mailbox_user_selector',
					inputName: 'mailbox_user_selector_input',
					tagInputName: 'mailbox_user_selector_tag'
				}));
				BX.bind(BX('mailbox_user_selector_input'), 'keydown', BX.delegate(BX.SocNetLogDestination.BXfpSearchBefore, {
					formName: 'mailbox_user_selector',
					inputName: 'mailbox_user_selector_input'
				}));

				BX.bind(BX('mailbox_user_selector_tag'), 'click', function (e) {
					BX.SocNetLogDestination.openDialog('mailbox_user_selector');
					BX.PreventDefault(e);
				});
				BX.bind(BX('mailbox_user_selector_container'), 'click', function (e) {
					BX.SocNetLogDestination.openDialog('mailbox_user_selector');
					BX.PreventDefault(e);
				});

				BX.addCustomEvent(mb.dialog, 'onPopupClose', function()
				{
					BX.SocNetLogDestination.closeDialog('mailbox_user_selector');
				});
			}
		},
		changePassword: function(rid, mid)
		{
			var content = '<form class="manage-dialog-form">'
				+ '<?=bitrix_sessid_post() ?>'
				+ '<div class="manage-dialog-form-error" name="form_error" style="display: none; "></div>'
				+ (rid.match(/^\d+$/) ? '<div class="popup-window-tab-content popup-window-tab-content-selected">'+BX('user_'+rid).parentNode.innerHTML+'</div>' : '')
				+ '<div class="popup-window-tabs-box"><div class="popup-window-tab-content popup-window-tab-content-selected">'
				+ '<span class="manage-dialog-form-label" style="padding-top: 0px; "><?=CUtil::jsEscape(getMessage('INTR_MAIL_MANAGE_FINP_EMAIL')) ?>:</span>'
				+ BX('email_'+rid).parentNode.innerHTML
				+ '<br><br><table class="manage-dialog-form-table">'
				+ '<tr><td style="width: 50%; "><span class="manage-dialog-form-label"><?=CUtil::jsEscape(getMessage('INTR_MAIL_MANAGE_INP_NEW_PASSWORD')) ?>:</span>'
				+ '<input class="manage-dialog-form-inp" type="password" name="password"></td></tr>'
				+ '<tr><td><span class="manage-dialog-form-label"><?=CUtil::jsEscape(getMessage('INTR_MAIL_MANAGE_INP_PASSWORD2')) ?>:</span>'
				+ '<input class="manage-dialog-form-inp" type="password" name="password2"></td></tr>'
				+ '</table></div>'
				+ '</form>';

			mb.dialog.setTitleBar('<?=CUtil::jsEscape(getMessage('INTR_MAIL_MANAGE_PASSWORD_TITLE')) ?>');
			mb.dialog.setContent(content);
			mb.dialog.setButtons([
				new BX.PopupWindowButton({
					text: '<?=CUtil::jsEscape(getMessage('INTR_MAIL_MANAGE_SAVE_BTN')) ?>',
					className: 'popup-window-button-accept',
					events: {
						click: function ()
						{
							var btn = this;

							if (BX.hasClass(btn.buttonNode, 'popup-window-button-wait'))
								return;

							mb.dialog.hideNotify();
							BX.addClass(btn.buttonNode, 'popup-window-button-wait');

							BX.ajax({
								method: 'POST',
								url: '<?=$this->__component->getPath() ?>/ajax.php?siteid=<?=urlencode(SITE_ID) ?>&act=password&MAILBOX='+encodeURIComponent(mid),
								data: mb.dialog.getFormData(),
								dataType: 'json',
								onsuccess: function(json)
								{
									BX.removeClass(btn.buttonNode, 'popup-window-button-wait');

									if (json.result == 'error')
										mb.dialog.showNotify(json.error);
									else
										mb.dialog.close();
								},
								onfailure: function()
								{
									BX.removeClass(btn.buttonNode, 'popup-window-button-wait');
									mb.dialog.showNotify('<?=CUtil::jsEscape(getMessage('INTR_MAIL_MANAGE_ERR_AJAX')) ?>');
								}
							});
						}
					}
				}),
				new BX.PopupWindowButton({
					text: '<?=CUtil::jsEscape(getMessage('INTR_MAIL_MANAGE_CANCEL_BTN')) ?>',
					className: 'popup-window-button',
					events: {
						click: function()
						{
							this.popupWindow.close();
						}
					}
				})
			]);

			mb.dialog.show();
		},
		release: function(rid, mid)
		{
			var content = '<form class="manage-dialog-form">'
				+ '<?=bitrix_sessid_post() ?>'
				+ '<div class="manage-dialog-form-error" name="form_error" style="display: none; "></div>'
				+ '<div class="popup-window-tab-content popup-window-tab-content-selected">'+BX('user_'+rid).parentNode.innerHTML+'</div>'
				+ '<div class="popup-window-tabs-box"><div class="popup-window-tab-content popup-window-tab-content-selected">'
				+ '<span class="manage-dialog-form-label" style="padding-top: 0px; "><?=CUtil::jsEscape(getMessage('INTR_MAIL_MANAGE_FINP_EMAIL')) ?>:</span>'
				+ BX('email_'+rid).parentNode.innerHTML
				+ '<br><br><br><?=CUtil::jsEscape(getMessage('INTR_MAIL_MANAGE_RELEASE_CONFIRM')) ?>'
				+ '</div></div>';
				+ '</form>';

			mb.dialog.setTitleBar('<?=CUtil::jsEscape(getMessage('INTR_MAIL_MANAGE_RELEASE_TITLE')) ?>');
			mb.dialog.setContent(content);
			mb.dialog.setButtons([
				new BX.PopupWindowButton({
					text: '<?=CUtil::jsEscape(getMessage('INTR_MAIL_MANAGE_RELEASE_BTN')) ?>',
					className: 'popup-window-button-decline',
					events: {
						click: function ()
						{
							var btn = this;

							if (BX.hasClass(btn.buttonNode, 'popup-window-button-wait'))
								return;

							mb.dialog.hideNotify();
							BX.addClass(btn.buttonNode, 'popup-window-button-wait');

							BX.ajax({
								method: 'POST',
								url: '<?=$this->__component->getPath() ?>/ajax.php?siteid=<?=urlencode(SITE_ID) ?>&act=release&MAILBOX_ID='+mid,
								data: mb.dialog.getFormData(),
								dataType: 'json',
								onsuccess: function(json)
								{
									BX.removeClass(btn.buttonNode, 'popup-window-button-wait');

									if (json.users)
									{
										for (var sid in json.users.vacant)
										{
											if (typeof domainUsers[sid] == 'undefined')
												continue;

											for (var domain in json.users.vacant[sid])
											{
												if (typeof domainUsers[sid][domain] == 'undefined')
													continue;

												for (var i in json.users.vacant[sid][domain])
												{
													var key = BX.util.array_search(json.users.vacant[sid][domain][i], domainUsers[sid][domain]);
													if (key < 0)
														domainUsers[sid][domain].unshift(json.users.vacant[sid][domain][i]);
												}
											}
										}
									}

									if (json.result == 'error')
									{
										mb.dialog.showNotify(json.error);
									}
									else
									{
										BX.Main.gridManager.getInstanceById('<?=CUtil::jsEscape($arResult['GRID_ID']) ?>').reload();
										mb.dialog.close();
									}
								},
								onfailure: function()
								{
									BX.removeClass(btn.buttonNode, 'popup-window-button-wait');
									mb.dialog.showNotify('<?=CUtil::jsEscape(getMessage('INTR_MAIL_MANAGE_ERR_AJAX')) ?>');
								}
							});
						}
					}
				}),
				new BX.PopupWindowButton({
					text: '<?=CUtil::jsEscape(getMessage('INTR_MAIL_MANAGE_CANCEL_BTN')) ?>',
					className: 'popup-window-button',
					events: {
						click: function()
						{
							this.popupWindow.close();
						}
					}
				})
			]);

			mb.dialog.show();
		},
		remove: function(rid)
		{
			var content = '<form class="manage-dialog-form">'
				+ '<?=bitrix_sessid_post() ?>'
				+ '<div class="manage-dialog-form-error" name="form_error" style="display: none; "></div>'
				+ '<div class="popup-window-tabs-box"><div class="popup-window-tab-content popup-window-tab-content-selected">'
				+ '<span class="manage-dialog-form-label" style="padding-top: 0px; "><?=CUtil::jsEscape(getMessage('INTR_MAIL_MANAGE_FINP_EMAIL')) ?>:</span>'
				+ BX('email_'+rid).parentNode.innerHTML+'<br><br><br>'
				+ '<span style="color: #c91d24; font-weight: bold; "><?=CUtil::jsEscape(getMessage('INTR_MAIL_MANAGE_DELETE_WT')) ?></span><br><br>'
				+ '<?=CUtil::jsEscape(getMessage('INTR_MAIL_MANAGE_DELETE_WARNING')) ?><br><br>'
				+ '<label style="color: #808080; ">'
				+ '<input type="checkbox" name="confirm" value="1" onclick="'
				+ "BX[this.checked?'removeClass':'addClass'](mb.dialog.buttons[0].buttonNode, 'popup-window-button-disable');"
				+ '" style="margin: 0px; vertical-align: middle; ">&nbsp;'
				+ '<?=CUtil::jsEscape(getMessage('INTR_MAIL_MANAGE_DELETE_CONFIRM')) ?>'
				+ '</label></div></div>'
				+ '</form>';

			mb.dialog.setTitleBar('<?=CUtil::jsEscape(getMessage('INTR_MAIL_MANAGE_DELETE_TITLE')) ?>');
			mb.dialog.setContent(content);
			mb.dialog.setButtons([
				new BX.PopupWindowButton({
					text: '<?=CUtil::jsEscape(getMessage('INTR_MAIL_MANAGE_DELETE')) ?>',
					className: 'popup-window-button-decline popup-window-button-disable',
					events: {
						click: function ()
						{
							var btn = this;

							if (BX.hasClass(btn.buttonNode, 'popup-window-button-wait') || BX.hasClass(btn.buttonNode, 'popup-window-button-disable'))
								return;

							mb.dialog.hideNotify();
							BX.addClass(btn.buttonNode, 'popup-window-button-wait');

							BX.ajax({
								method: 'POST',
								url: '<?=$this->__component->getPath() ?>/ajax.php?siteid=<?=urlencode(SITE_ID) ?>&act=delete&MAILBOX='+encodeURIComponent(rid),
								data: mb.dialog.getFormData(),
								dataType: 'json',
								onsuccess: function(json)
								{
									BX.removeClass(btn.buttonNode, 'popup-window-button-wait');

									if (json.users)
									{
										for (var sid in json.users.occupied)
										{
											if (typeof domainUsers[sid] == 'undefined')
												continue;

											for (var domain in json.users.occupied[sid])
											{
												if (typeof domainUsers[sid][domain] == 'undefined')
													continue;

												for (var i in json.users.occupied[sid][domain])
												{
													var key = BX.util.array_search(json.users.occupied[sid][domain][i], domainUsers[sid][domain]);
													if (key >= 0)
														domainUsers[sid][domain].splice(key, 1);
												}
											}
										}
									}

									if (json.result == 'error')
									{
										mb.dialog.showNotify(json.error);
									}
									else
									{
										BX.Main.gridManager.getInstanceById('<?=CUtil::jsEscape($arResult['GRID_ID']) ?>').reload();
										mb.dialog.close();
									}
								},
								onfailure: function()
								{
									BX.removeClass(btn.buttonNode, 'popup-window-button-wait');
									mb.dialog.showNotify('<?=CUtil::jsEscape(getMessage('INTR_MAIL_MANAGE_ERR_AJAX')) ?>');
								}
							});
						}
					}
				}),
				new BX.PopupWindowButton({
					text: '<?=CUtil::jsEscape(getMessage('INTR_MAIL_MANAGE_CANCEL_BTN')) ?>',
					className: 'popup-window-button',
					events: {
						click: function()
						{
							this.popupWindow.close();
						}
					}
				})
			]);

			mb.dialog.show();
		},
		settings: function()
		{
			var dummyNode = BX.create('DIV', {text: blacklist});
			var content = '<form class="manage-dialog-form">'
				+ '<?=bitrix_sessid_post() ?>'
				+ '<div class="manage-dialog-form-error" name="form_error" style="display: none; "></div>'
				+ '<div class="popup-window-tabs-box"><div class="popup-window-tab-content popup-window-tab-content-selected">'
				+ '<span class="manage-dialog-form-label" style="padding-top: 0px; "><?=CUtil::jsEscape(getMessage('INTR_MAIL_MANAGE_SETUP_BLACKLIST')) ?>'
				+ '<span class="post-dialog-stat-info" style="margin-top: -3px; margin-left: 7px; " title="<?=CUtil::jsEscape(getMessage('INTR_MAIL_MANAGE_SETUP_BLACKLIST_HINT')) ?>"></span></span>'
				+ '<textarea class="manage-dialog-form-textarea" name="blacklist" placeholder="<?=getMessage('INTR_MAIL_MANAGE_SETUP_BLACKLIST_PLACEHOLDER') ?>">'
				+ dummyNode.innerHTML+'</textarea>'
				+ '<span class="manage-dialog-form-label"><label>'
				+ '<input name="allow_crm" type="checkbox" value="Y" '+(allowCrm ? 'checked' : '')+' style="margin: 0px; vertical-align: middle; ">&nbsp;'
				+ '<?=CUtil::jsEscape(getMessage('INTR_MAIL_MANAGE_SETUP_ALLOW_CRM')) ?></label></span>'
				+ '</div></form>';

			mb.dialog.setTitleBar('<?=CUtil::jsEscape(getMessage('INTR_MAIL_MANAGE_SETUP')) ?>');
			mb.dialog.setContent(content);
			mb.dialog.setButtons([
				new BX.PopupWindowButton({
					text: '<?=CUtil::jsEscape(getMessage('INTR_MAIL_MANAGE_SAVE_BTN')) ?>',
					className: 'popup-window-button-accept',
					events: {
						click: function ()
						{
							var btn = this;

							if (BX.hasClass(btn.buttonNode, 'popup-window-button-wait'))
								return;

							mb.dialog.hideNotify();
							BX.addClass(btn.buttonNode, 'popup-window-button-wait');

							var data = mb.dialog.getFormData();
							BX.ajax({
								method: 'POST',
								url: '<?=$this->__component->getPath() ?>/ajax.php?siteid=<?=urlencode(SITE_ID) ?>&act=settings',
								data: data,
								dataType: 'json',
								onsuccess: function(json)
								{
									BX.removeClass(btn.buttonNode, 'popup-window-button-wait');

									if (json.result == 'error')
									{
										mb.dialog.showNotify(json.error);
									}
									else
									{
										blacklist = data['blacklist'];
										allowCrm  = data['allow_crm'] == 'Y';

										mb.dialog.close();
									}
								},
								onfailure: function()
								{
									BX.removeClass(btn.buttonNode, 'popup-window-button-wait');
									mb.dialog.showNotify('<?=CUtil::jsEscape(getMessage('INTR_MAIL_MANAGE_ERR_AJAX')) ?>');
								}
							});
						}
					}
				}),
				new BX.PopupWindowButton({
					text: '<?=CUtil::jsEscape(getMessage('INTR_MAIL_MANAGE_CANCEL_BTN')) ?>',
					className: 'popup-window-button',
					events: {
						click: function()
						{
							this.popupWindow.close();
						}
					}
				})
			]);

			mb.dialog.show();
		},
		modeMenu: function(bind)
		{
			BX.PopupMenu.show(
				'mail-manage-mode-menu', bind,
				[
					{
						text: '<?=CUtil::jsEscape(getMessage('INTR_MAIL_MANAGE_MODE_USER')) ?>',
						className: '<? if ($arResult['MODE'] == 'user'): ?>menu-popup-item-take<? else: ?>dummy<? endif ?>',
						href: '?mode=user'
					},
					{
						text: '<?=CUtil::jsEscape(getMessage('INTR_MAIL_MANAGE_MODE_MAILBOX')) ?>',
						className: '<? if ($arResult['MODE'] == 'mailbox'): ?>menu-popup-item-take<? else: ?>dummy<? endif ?>',
						href: '?mode=mailbox'
					}
				],
				{
					offsetLeft: 41,
					angle: true
				}
			);
		},
		optionsMenu: function(bind)
		{
			BX.PopupMenu.show(
				'mail-manage-options-menu', bind,
				[
					<? if ($hasOptions1): ?>
						{
							text: '<?=CUtil::jsEscape(empty($customDomains)
								? getMessage('INTR_MAIL_MANAGE_DOMAIN_ADD')
								: sprintf('%s <b>%s</b>', getMessage('INTR_MAIL_MANAGE_DOMAIN_EDIT2'), htmlspecialcharsbx(end($customDomains)))
							) ?>',
							href: '<?=CUtil::jsEscape($arParams['PATH_TO_MAIL_CFG_DOMAIN']) ?>'
						}
					<? endif ?>
					<? if ($hasOptions1 && $hasOptions2): ?>,<? endif ?>
					<? if ($hasOptions2): ?>
						{
							text: '<?=CUtil::jsEscape(getMessage('INTR_MAIL_MANAGE_SETTINGS')) ?>',
							onclick: mb.settings
						}
					<? endif ?>
				],
				{
					offsetLeft: 20,
					angle: true
				}
			);
		}
	};

</script>
