BX.namespace("BX.Bitrix24.Configs");

BX.Bitrix24.Configs.LogoClass = (function()
{
	var LogoClass = function(ajax_path)
	{
		this.ajaxPath = ajax_path;
	};

	LogoClass.prototype.LogoChange = function(mode)
	{
		mode = (mode == "retina" ? "retina" : "");

		BX('configWaitLogo' + mode).style.display='inline-block';
		BX.ajax.submit(
			BX(mode == "retina" ? 'configLogoRetinaPostForm' : 'configLogoPostForm'),
			function(reply)
			{
				try {
					var json = JSON.parse(reply);

					if (json.error)
					{
						BX('config_logo_error_block').style.display = 'block';
						var error_block = BX.findChild(BX('config_logo_error_block'), {class: 'content-edit-form-notice-text'}, true, false);
						error_block.innerHTML = '<span class=\'content-edit-form-notice-icon\'></span>'+json.error;
					}
					else if (json.path)
					{
						BX('config_logo_error_block').style.display = 'none';
						BX('configImgLogo' + mode).src = json.path;
						BX('configBlockLogo' + mode).style.display = 'inline-block';
						BX('configDeleteLogo' + mode).style.display = 'inline-block';
					}
					BX('configWaitLogo' + mode).style.display='none';
				} catch (e) {
					BX('configWaitLogo' + mode).style.display='none';
					return false;
				}
			}
		);
	};

	LogoClass.prototype.LogoDelete = function(curLink, mode)
	{
		mode = (mode == "retina" ? "retina" : "");

		if (confirm(BX.message("LogoDeleteConfirm")))
		{
			BX('configWaitLogo' + mode).style.display='inline-block';

			BX.ajax.post(
				this.ajaxPath,
				{
					client_delete_logo: 'Y',
					sessid : BX.bitrix_sessid(),
					mode: mode
				},
				function(){

					BX('configBlockLogo' + mode).style.display = 'none';
					curLink.style.display = 'none';
					BX('config_error_block').style.display = 'none';
					BX('configWaitLogo' + mode).style.display='none';
				}
			);
		}
	};

	return LogoClass;
})();

BX.Bitrix24.Configs.IpSettingsClass = (function()
{
	var IpSettingsClass = function(arCurIpRights)
	{
		this.arCurIpRights = arCurIpRights;

		var deleteButtons = document.querySelectorAll("[data-role='ip-right-delete']");
		deleteButtons.forEach(function (button) {
			BX.bind(button, "click", function () {
				this.DeleteIpAccessRow(button);
			}.bind(this));
		}.bind(this));
	};

	IpSettingsClass.prototype.DeleteIpAccessRow = function(ob)
	{
		var tdObj = ob.parentNode.parentNode;
		BX.remove(ob.parentNode);
		var allInputBlocks = BX.findChildren(tdObj, {tagName:'div'}, true);
		if (allInputBlocks.length <= 0)
		{
			var deleteRight = tdObj.parentNode.getAttribute("data-bx-right");
			var arCurIpRightsNew = [];
			for(var i = 0; i < this.arCurIpRights.length; i++)
				if (this.arCurIpRights[i] != deleteRight)
					arCurIpRightsNew.push(this.arCurIpRights[i]);
			this.arCurIpRights = arCurIpRightsNew;

			BX.remove(tdObj.parentNode);
		}
	};

	IpSettingsClass.prototype.ShowIpAccessPopup = function(val)
	{
		var curObj = this;

		val = val || [];

		BX.Access.Init({
			other: {
				disabled: false,
				disabled_g2: true,
				disabled_cr: true
			},
			groups: { disabled: true },
			socnetgroups: { disabled: true }
		});

		var startValue = {};
		for(var i = 0; i < val.length; i++)
			startValue[val[i]] = true;

		BX.Access.SetSelected(startValue);

		BX.Access.ShowForm({
			callback: function(arRights)
			{
				var pr = false;

				for(var provider in arRights)
				{
					pr = BX.Access.GetProviderName(provider);
					for(var right in arRights[provider])
					{
						var insertBlock = BX.create('tr', {
							attrs: {
								"data-bx-right" : right
							},
							children: [
								BX.create('td', {
									html: (pr.length > 0 ? pr + ': ' : '') + BX.util.htmlspecialchars(arRights[provider][right].name) + '&nbsp;',
									props: {
										'className': 'content-edit-form-field-name'
									}
								}),
								BX.create('td', {
									props: {
										'className': 'content-edit-form-field-input',
										'colspan': 2
									},
									children: [
										BX.create('div', {
											children: [
												BX.create('input', {
													attrs: {
														type: 'text',
														name: 'ip_access_rights_' + right+'[]',
														size: '30'
													},
													props: {
													},
													events: {
														click: function() {
															curObj.addInputForIp(this);
														}
													}
												}),
												BX.create('a', {
													attrs: {
														href: 'javascript:void(0);',
														title: BX.message('SLToAllDel')
													},
													props: {
														'className': 'access-delete'
													},
													events: {
														click: function() { curObj.DeleteIpAccessRow(this); }
													}
												})
											]
										})
									]
								})
							]
						});

						BX('ip_add_right_button').parentNode.insertBefore(insertBlock, BX('ip_add_right_button'));

						curObj.arCurIpRights.push(right);
					}
				}
			}
		});
	};

	IpSettingsClass.prototype.addInputForIp = function(input)
	{
		var curObj = this;

		var inputParent = input.parentNode;
		if (BX.nextSibling(inputParent))
			return;

		var newInputBlock = BX.clone(inputParent);
		var newInput = BX.firstChild(newInputBlock);
		newInput.value = "";
		newInput.onclick = function(){curObj.addInputForIp(this)};
		BX.nextSibling(newInput).onclick = function(){curObj.DeleteIpAccessRow(this)};
		inputParent.parentNode.appendChild(newInputBlock);
	};

	return IpSettingsClass;
})();

BX.Bitrix24.Configs.Functions = {
	addressFormatList: [],
	init : function ()
	{
		var toAllCheckBox = BX('allow_livefeed_toall');
		var defaultCont = BX('DEFAULT_all');

		if (toAllCheckBox && defaultCont)
		{
			BX.bind(toAllCheckBox, 'click', BX.delegate(function(e) {
				defaultCont.style.display = (this.checked ? "" : "none");
			}, toAllCheckBox));
		}

		var rightsCont = BX('RIGHTS_all');
		if (toAllCheckBox && rightsCont)
		{
			BX.bind(toAllCheckBox, 'click', BX.delegate(function(e) {
				rightsCont.style.display = (this.checked ? "" : "none");
			}, toAllCheckBox));
		}

		if (BX("configLogoPostForm") && BX("configLogoPostForm").client_logo)
		{
			BX.bind(BX("configLogoPostForm").client_logo, "change", function(){
				B24ConfigsLogo.LogoChange();
			});
		}

		if (BX("configDeleteLogo"))
		{
			BX.bind(BX("configDeleteLogo"), "click", function(){
				B24ConfigsLogo.LogoDelete(this);
			});
		}

		if (BX("configLogoRetinaPostForm") && BX("configLogoRetinaPostForm").client_logo_retina)
		{
			BX.bind(BX("configLogoRetinaPostForm").client_logo_retina, "change", function(){
				B24ConfigsLogo.LogoChange("retina");
			});
		}

		if (BX("configDeleteLogoretina"))
		{
			BX.bind(BX("configDeleteLogoretina"), "click", function(){
				B24ConfigsLogo.LogoDelete(this, "retina");
			});
		}

		//im chat
		var toChatAllCheckBox = BX('allow_general_chat_toall');
		var chatRightsCont = BX('chat_rights_all');
		if (toChatAllCheckBox && chatRightsCont)
		{
			BX.bind(toChatAllCheckBox, 'click', function() {
				chatRightsCont.style.display = (this.checked ? "" : "none");
			});
		}

		var mpUserInstallChechBox= BX('mp_allow_user_install');
		var mpUserInstallCont = BX('mp_user_install');
		if (mpUserInstallChechBox && mpUserInstallCont)
		{
			BX.bind(mpUserInstallChechBox, 'click', function() {
				mpUserInstallCont.style.display = (this.checked ? "" : "none");
			});
		}

		var addressFormatSelect = BX('location_address_format_select');

		if(addressFormatSelect)
		{
			BX.bind(addressFormatSelect, 'change', function ()
			{
				var addressFormatDescription = BX('location_address_format_description');
				addressFormatDescription.innerHTML = BX.Bitrix24.Configs.Functions.addressFormatList[addressFormatSelect.value];
			});
		}

		if (BX.type.isDomNode((BX("smtp_use_auth"))))
		{
			BX.bind(BX("smtp_use_auth"), "change", BX.proxy(function ()
			{
				this.showHideSmtpAuth();
			}, this));
		}
	},

	submitForm : function (button)
	{
		BX.addClass(button, 'webform-button-wait webform-button-active');
		BX.submit(BX('configPostForm'));
	},

	otpSwitchOffInfo : function(elem)
	{
		if (!elem.checked)
		{
			BX.PopupWindowManager.create("otpSwitchOffInfo", elem, {
				autoHide: true,
				offsetLeft: -100,
				offsetTop: 15,
				overlay : false,
				draggable: {restrict:true},
				closeByEsc: true,
				closeIcon: { right : "12px", top : "10px"},
				content: '<div style="padding: 15px; width: 300px; font-size: 13px">' + BX.message("CONFIG_OTP_SECURITY_SWITCH_OFF_INFO") + '</div>'
			}).show();
		}
	},

	onGdprChange : function(element)
	{
		var items = document.querySelectorAll("[data-role='gdpr-data']");
		for (var i=0; i<items.length; i++)
		{
			items[i].style.visibility = element.checked ? "visible" : "collapse";
		}
	},

	adminOtpIsRequiredInfo : function(elem)
	{
		BX.PopupWindowManager.create("adminOtpIsRequiredInfo", elem, {
			autoHide: true,
			offsetLeft: -100,
			offsetTop: 15,
			overlay : false,
			draggable: {restrict:true},
			closeByEsc: true,
			closeIcon: { right : "12px", top : "10px"},
			content: '<div style="padding: 15px; width: 300px; font-size: 13px">' + BX.message("CONFIG_OTP_ADMIN_IS_REQUIRED_INFO") + '</div>'
		}).show();
	},

	showDiskExtendedFullTextInfo: function (event, elem)
	{
		event.stopPropagation;
		event.preventDefault();
		BX.PopupWindowManager.create("diskExtendedFullTextInfo", elem, {
			autoHide: true,
			offsetLeft: -100,
			offsetTop: 15,
			overlay : false,
			draggable: {restrict:true},
			closeByEsc: true,
			closeIcon: { right : "12px", top : "10px"},
			content: '<div style="padding: 15px; width: 300px; font-size: 13px">' + BX.message("CONFIG_DISK_EXTENDED_FULLTEXT_INFO") + '</div>'
		}).show();
	},

	geoDataSwitch: function (element)
	{
		if (element.checked)
		{
			element.checked = false;

			BX.UI.Dialogs.MessageBox.show({
				'modal': true,
				'minWidth': BX.message('CONFIG_COLLECT_GEO_DATA_CONFIRM').length > 400 ? 640 : 480,
				'title': BX.message('CONFIG_COLLECT_GEO_DATA'),
				'message': BX.message('CONFIG_COLLECT_GEO_DATA_CONFIRM'),
				'buttons': BX.UI.Dialogs.MessageBoxButtons.OK_CANCEL,
				'okCaption': BX.message('CONFIG_COLLECT_GEO_DATA_OK'),
				'onOk': function ()
				{
					element.checked = true;
					return true;
				}
			});
		}
	},
};