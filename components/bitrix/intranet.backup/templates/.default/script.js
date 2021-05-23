BX.namespace("BX.Intranet.Backup");
BX.Intranet.Backup = {
	start: "Y",
	ajaxPath: "",
	successUrl: "",
	currentUrl: "",

	init: function (params)
	{
		if (typeof params === "object")
		{
			this.ajaxPath = params.ajaxPath || "";
			this.successUrl = params.successUrl || "";
			this.currentUrl = params.currentUrl || "";
		}
	},

	makeBackup: function()
	{
		BX("backupProgressBlock").style.display = "block";
		BX("cancelButton").style.display = "inline-block";
		BX("backupButton").style.display = "none";

		BX.ajax({
			method: 'POST',
			url: '/bitrix/tools/backup.php',
			data: {
				sessid: BX.bitrix_sessid(),
				start: this.start,
				public: "Y"
			},
			onsuccess: BX.proxy(function (response) {
				if (response.indexOf("NEXT") === 0)
				{
					percent = response.replace("NEXT", "");
					percent = parseInt(percent);

					if (percent >= 100)
						percent = 100;
					else if (percent <= 0)
						percent = 1;

					BX("backupProgressBar").style.width = percent + "%";
					BX("backupPercent").innerHTML = percent;
					this.start = "N";
					this.makeBackup();
				}
				else if (response == "FINISH")
				{
					document.location.href = this.successUrl;
				}
				else if (response.indexOf("ERROR") >= 0)
				{
					BX("backupErrorBlock").style.display = "block";

					error = response.replace(/ERROR_\d*\s/, "");
					BX("backupErrorText").innerHTML = BX.message("BACKUP_SYSTEM_ERROR") + error;
					BX("backupProgressBlock").style.display = "none";
				}
				else
				{
					BX("backupErrorBlock").style.display = "block";
					BX("backupErrorText").innerHTML = BX.message("BACKUP_ERROR");
					BX("backupProgressBlock").style.display = "none";
				}
			}, this)
		});
	},

	stopBackup: function (button)
	{
		BX.addClass(button, "webform-button-wait");
		document.location.href = this.currentUrl;
	},

	partDownload: function  (links)
	{
		if (!links || links.length == 0)
			return;

		var link = links.pop();
		var iframe = document.createElement('iframe');
		iframe.style.display = "none";
		iframe.src = link;
		document.body.appendChild(iframe);

		window.setTimeout(BX.proxy(function(){this.partDownload(links)}, this), 10000);
	},

	downloadFiles: function (fileId)
	{
		BX.ajax({
			method: 'POST',
			url: this.ajaxPath,
			data: {
				sessid: BX.bitrix_sessid(),
				f_id: fileId,
				action: "download"
			},
			onsuccess: BX.proxy(function (result) {
				eval(result);
				this.partDownload(links);
			}, this)
		});
	},

	deleteFiles: function (fileId)
	{
		if (!confirm(BX.message("BACKUP_DELETE_CONFIRM")))
			return;

		BX.showWait(BX("backup_grid"));

		BX.ajax({
			method: 'POST',
			url: this.ajaxPath,
			dataType: 'json',
			data: {
				sessid: BX.bitrix_sessid(),
				f_id: fileId,
				action: "delete"
			},
			onsuccess: BX.proxy(function (result) {
				BX.closeWait();
				if (typeof result === "object" && result.hasOwnProperty("error"))
				{
					alert(result.error);
				}
				else
				{
					BX.Main.gridManager.getById("backup_grid").instance.reload();
				}
			}, this)
		});
	},

	getLink: function (fileId)
	{
		BX.showWait(BX("backup_grid"));
		BX.ajax({
			method: 'POST',
			url: this.ajaxPath,
			data: {
				sessid: BX.bitrix_sessid(),
				f_id: fileId,
				action: "link"
			},
			onsuccess: function (result) {
				BX.closeWait();
			}
		});
	},

	restoreFiles: function (fileId)
	{
		if (!confirm(BX.message("BACKUP_RESTORE_CONFIRM")))
			return;

		BX.showWait(BX("backup_grid"));
		BX.ajax({
			method: 'POST',
			url: this.ajaxPath,
			data: {
				sessid: BX.bitrix_sessid(),
				f_id: fileId,
				action: "restore"
			},
			onsuccess: BX.proxy(function (result) {
				if (result.error)
				{
					alert(result.error);
					BX.closeWait();
				}
			}, this)
		});
	},

	renameFiles: function (fileId, fileName, element)
	{
		var inputObj = BX.create("input", {
			attrs: {value: fileName, class: "content-edit-form-field-input-text"}
		});
		var popupContent = BX.create("div", {
			children: [inputObj]
		})

		BX.PopupWindowManager.create("BXBackupRename", null, {
			autoHide: false,
			zIndex: 0,
			offsetLeft: 0,
			offsetTop: 0,
			overlay : true,
			draggable: {restrict:true},
			closeByEsc: true,
			titleBar: BX.message('BACKUP_RENAME_TITLE'),
			closeIcon: { right : "12px", top : "10px"},
			buttons: [
				new BX.PopupWindowButton({
					text: BX.message('BACKUP_SAVE_BUTTON'),
					className: "popup-window-button-create",
					events: {
						click : BX.proxy(function()
						{
							var popup = BX.proxy_context;
							BX.addClass(BX.proxy_context.buttonNode, 'popup-window-button-wait');
							BX.ajax({
								method: 'POST',
								url: this.ajaxPath,
								dataType: 'json',
								data: {
									sessid: BX.bitrix_sessid(),
									ID: fileId,
									name: inputObj.value,
									action: "rename"
								},
								onsuccess: BX.proxy(function (result) {
									BX.removeClass(popup.buttonNode, 'popup-window-button-wait');
									if (typeof result === "object" && result.hasOwnProperty("error"))
									{
										var errorObj = BX.create("div", {
											attrs:{
												style: "background-color: #ffebeb; border: 1px solid #ffa7a7; color: #d10000;padding: 10px;margin-bottom: 17px;",
												id: "backupRenameError"
											},
											html: result.error
										});
										popupContent.insertBefore(errorObj, inputObj)
									}
									else
									{
										if (BX.type.isDomNode(BX("backupRenameError")))
										{
											BX.remove(BX("backupRenameError"));
										}
										popup.popupWindow.destroy();

										BX.Main.gridManager.getById("backup_grid").instance.reload();
										BX.Main.gridManager.getById("backup_grid").instance.updateRow();
									}
								}, this)
							});
						}, this)
					}
				}),
				new BX.PopupWindowButtonLink({
					text: BX.message('BACKUP_CANCEL_BUTTON'),
					className: "popup-window-button-link-cancel",
					events: { click : function()
					{
						this.popupWindow.close();
					}}
				})
			],
			content: popupContent
		}).show();
	}
};