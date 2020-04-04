BX.namespace("BX.Bitrix24.Configs.Vm");

BX.Bitrix24.Configs.Vm = {
	init : function (params)
	{
		params = typeof params === "object" ? params : {};

		this.ajaxPath = params.ajaxPath || null;
		this.siteNameConf = params.siteNameConf || "";

		if (BX.type.isDomNode((BX("smtp_use_auth"))))
		{
			BX.bind(BX("smtp_use_auth"), "change", BX.proxy(function ()
			{
				this.showHideSmtpAuth();
			}, this));
		}

		BX.bind(BX("certificate_lets_encrypt"), "click", BX.proxy(function ()
		{
			this.showCertificatePopup("le");
		}, this));

		BX.bind(BX("certificate_self"), "click", BX.proxy(function ()
		{
			this.showCertificatePopup("self");
		}, this));

		BX.bind(BX("certificate_self_key_path_file"), "change", BX.proxy(function ()
		{
			this.uploadFile("certificate_self_key_path_file", "certificate_self_key_path");
		}, this));

		BX.bind(BX("certificate_self_path_file"), "change", BX.proxy(function ()
		{
			this.uploadFile("certificate_self_path_file", "certificate_self_path");
		}, this));

		BX.bind(BX("certificate_self_path_chain_file"), "change", BX.proxy(function ()
		{
			this.uploadFile("certificate_self_path_chain_file", "certificate_self_path_chain");
		}, this));
	},

	showHideSmtpAuth: function ()
	{
		var blocks = BX("configPostForm").querySelectorAll("[data-role='smtp-auth']");
		if (blocks)
		{
			for(var i=0, l=blocks.length; i<l; i++)
			{
				blocks[i].style.display = BX("smtp_use_auth").checked ? "" : "none";
			}
		}
	},

	submitForm : function (button)
	{
		if (BX.type.isDomNode(BX('smtp-pass-block')))
		{
			if (BX('smtp-pass-block').style.display == 'none')
			{
				BX.remove(BX('smtp-pass-block'));
			}
		}

		BX.addClass(button, 'webform-button-wait webform-button-active');
		BX.submit(BX('configPostForm'));
	},

	showCertificatePopup: function (type)
	{
		BX.PopupWindowManager.create("certificate-popup-" + type, null, {
			closeIcon: true,
			contentNoPaddings : false,
			content: type == "self" ? BX("self_certificate_popup_content") : BX("lets_encrypt_popup_content"),
			titleBar: BX.message("CONFIG_VM_CERTIFICATE_TITLE"),
			width: 470,
			buttons: [
				(button = new BX.PopupWindowButton({
					text: BX.message("CONFIG_VM_START"),
					className: "popup-window-button-create",
					events: {
						click: BX.proxy(function ()
						{
							BX.addClass(button.buttonNode, "popup-window-button-wait");
							if (type == "self")
							{
								var certificateData = {
									keyPath: BX("certificate_self_key_path").value,
									path: BX("certificate_self_path").value,
									chainPath: BX("certificate_self_path_chain").value,
									siteNameConf: this.siteNameConf
								};
								BX("self_certificate_popup_content").innerHTML = BX.message("CONFIG_VM_CERTIFICATE_PROCESS");
							}
							else
							{
								certificateData = {
									email: BX("certificate_lets_email").value,
									dns: BX("certificate_lets_dns").value,
									siteNameConf: this.siteNameConf
								};
								BX("lets_encrypt_popup_content").innerHTML = BX.message("CONFIG_VM_CERTIFICATE_PROCESS");
							}

							BX.ajax({
								method: 'POST',
								dataType: 'json',
								url: this.ajaxPath,
								data: {
									sessid: BX.bitrix_sessid(),
									action: "set_certificate",
									type: type,
									certificateData: certificateData
								},
								onsuccess: BX.proxy(function (json)
								{
									if (json.hasOwnProperty("error"))
									{
										alert(json.error);
									}
									else
									{
										this.interval = setInterval(BX.proxy(function() {
											this.checkCertitficateState();
										}, this), 15000);
									}
								}, this),
								onfailure: function ()
								{
								}
							});
						}, this)
					}
				})),
				new BX.PopupWindowButton({
					text: BX.message('MENU_CANCEL'),
					className: "popup-window-button-link popup-window-button-link-cancel",
					events: {
						click: function ()
						{
							this.popupWindow.close();
						}
					}
				})
			]
		}).show();
	},

	checkCertitficateState: function ()
	{
		BX.ajax({
			method: 'POST',
			dataType: 'json',
			url: this.ajaxPath,
			data: {
				sessid: BX.bitrix_sessid(),
				action: "check_state"
			},
			onsuccess: BX.proxy(function (json)
			{
				if (json.hasOwnProperty("success"))
				{
					document.location.reload();
				}
				else if (json.hasOwnProperty("error"))
				{
					alert(json.error);
				}
			}, this),
			onfailure: function ()
			{
			}
		});
	},

	uploadFile: function (inputFileId, inputPathId)
	{
		var form = BX("self_certificate_form");
		if (BX.type.isDomNode(BX(inputFileId)))
		{
			BX.addClass(BX.previousSibling(BX(inputFileId)), "webform-button-wait");
		}

		var obj = new BX.ajax.FormData();
		if (obj.isSupported())
		{
			obj.append("sessid", BX.bitrix_sessid());
			obj.append("action", "upload_files");

			for(var i = 0; i< form.elements.length; i++)
			{
				if (form[i].name == inputFileId)
					obj.append(form[i].name, form[i].files[0]);
			}
			obj.send(this.ajaxPath, function(result){
				if (result)
				{
					BX(inputPathId).value = decodeURIComponent(result);
					BX.removeClass(BX.previousSibling(BX(inputFileId)), "webform-button-wait");
				}
			});
		}
	}
};