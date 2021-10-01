;(function(window)
{
	BX.namespace("BX.Voximplant");

	if (window.BX.Voximplant.ConfigEditor)
	{
		return;
	}

	var defaults = {
		isPaid: false,
		isDemo: false,
		ivrEnabled: false,
		canSelectLine: false,
		isTimemanInstalled: false,
		isBitrix24: false,
		maximumGroups: -1
	};

	var ajaxUrl = '/bitrix/components/bitrix/voximplant.config.edit/ajax.php';

	BX.Voximplant.ConfigEditor = function(params)
	{
		this.node = params.node;
		this.melodies = params.melodies;
		this.accessCodes = params.accessCodes || {};
		this.portalMode = params.portalMode;
		this.sipConfig = params.sipConfig;

		this.popupTooltip = {};


		this.groupSliderOpen = false;
		this.currentGroupId = null;
		this.previousGroupId = null;

		this.ivrSliderOpen = false;
		this.currentIvrId = null;
		this.previousIvrId = null;

		this._onTooltipMouseOverHandler = this._onTooltipMouseOver.bind(this);
		this._onTooltipMouseOutHandler = this._onTooltipMouseOut.bind(this);

		this.pages = {};
		this.getNodes("page", document).forEach(function(node) {this.pages[node.dataset.page] = node}, this);

		this.map = {
			'welcome-melody': 'welcome-melody-settings',
			'enable-ivr': 'ivr-settings',
			'enable-crm-forward': 'crm-forward-settings',
			'enable-recording': 'recording-settings',
			'enable-worktime': 'worktime-settings',
			'number-selection': 'number-selection-settings',
			'callback-redial': 'callback-redial-settings',
			'backup-number': 'backup-number-settings',
			'enable-sip-detect-line-number': 'sip-detect-line-number-settings',
		};

		this.sipNumberSelector = BX.UI.TileSelector.getById('sip-numbers');
		this.numberInputPopup = null;

		Object.keys(this.map).forEach(function(checkboxRole)
		{
			var node = this.getNode(checkboxRole);
			var slaveNode = this.getNode(this.map[checkboxRole]);
			if(node && slaveNode)
			{
				BX.bind(node, 'change', this._onCheckBoxChange.bind(this));
			}
		}, this);

		this.elements = {
			sipNeedUpdate: BX('sip-need-update'),
			sipStatus: BX('sip-status'),
			sipStatusText: BX('sip-status-text'),
		};

		this.init();
		this.bindEvents();
	};

	BX.Voximplant.ConfigEditor.setDefaults = function (params)
	{
		for (var key in params)
		{
			if (params.hasOwnProperty(key) && defaults.hasOwnProperty(key))
			{
				defaults[key] = params[key];
			}
		}
	};

	BX.Voximplant.ConfigEditor.prototype.getNode = function(name, scope)
	{
		if (!scope)
			scope = this.node;

		return scope ? scope.querySelector('[data-role="'+name+'"]') : null;
	};

	BX.Voximplant.ConfigEditor.prototype.getNodes = function(name, scope)
	{
		if (!scope)
			scope = this.node;

		return scope ? scope.querySelectorAll('[data-role="'+name+'"]') : null;
	};

	BX.Voximplant.ConfigEditor.prototype.init = function ()
	{
		this.currentGroupId = this.getNode('select-group').value;
		for (var melodyId in this.melodies)
		{
			if(this.melodies.hasOwnProperty(melodyId))
			{
				this.loadMelody(melodyId, this.melodies[melodyId]);
			}
		}

		if(this.getNode('crm-create'))
		{
			this.setCrmCreate(this.getNode('crm-create').value);
		}

		if(this.portalMode === 'SIP' && this.sipConfig.TYPE === 'cloud')
		{
			this.checkSipState();
		}

		this.sipNumberSelector.getTiles().forEach(function (tile) {tile.changeRemoving(tile.data.canRemove);}, this);
	};

	BX.Voximplant.ConfigEditor.prototype.bindEvents = function ()
	{
		var self = this;

		var lineAccessContainer = this.getNode("line-access");
		if (lineAccessContainer)
		{
			this.lineAccessEditor = new AccessEdit({
				node: lineAccessContainer,
				accessCodes: this.accessCodes
			});
		}

		var tooltipNodes = BX.findChildrenByClassName(BX('tel-set-main-wrap'), "tel-context-help");
		/*for (var i = 0; i < tooltipNodes.length; i++)
		{
			tooltipNodes[i].setAttribute('data-id', i);
			BX.bind(tooltipNodes[i], 'mouseover', this._onTooltipMouseOverHandler);
			BX.bind(tooltipNodes[i], 'mouseout', this._onTooltipMouseOutHandler);
		}*/

		BX.bind(this.getNode('show-group-config'), 'click', this.onShowGroupClick.bind(this));
		BX.bind(this.getNode('show-crm-exception-list'), 'click', this.onShowCrmExceptionListClick.bind(this));
		BX.bind(this.getNode('show-ivr-config'), 'click', this.onShowIvrClick.bind(this));
		BX.bind(this.getNode('select-group'), 'change', this._onGroupIdChanged.bind(this));
		BX.bind(this.getNode('select-ivr'), 'change', this._onIvrIdChanged.bind(this));
		BX.bind(this.getNode('transcribe-language-select'), 'change', this._onTranscribeLanguageChanged.bind(this));
		BX.bind(this.getNode('input-line-prefix'), 'input', this._onInputLinePrefixInput.bind(this));
		BX.bind(this.getNode('more-tunes'), 'click', this._onMoreTunesClick.bind(this));
		BX.bind(this.getNode('delete-caller-id'), 'click', this._onDeleteCallerIdClick.bind(this));
		BX.bind(this.getNode('delete-number'), 'click', this._onDeleteNumberClick.bind(this));
		BX.bind(this.getNode('cancel-number-delete'), 'click', this._onCancelNumberDeleteClick.bind(this));

		BX.bind(BX("config_edit_form").elements["MELODY_LANG"], "change", this._onMelodyLangChange.bind(this));

		this.getNodes('menu-item', document).forEach(function(node)
		{
			BX.bind(node, 'click', this.onMenuItemClick.bind(this))
		}, this);

		BX.addCustomEvent(window, 'SidePanel.Slider:onClose', this._onSliderClosed.bind(this));
		BX.addCustomEvent(window, 'SidePanel.Slider:onMessage', this._onSliderMessageReceived.bind(this));

		BX.bind(this.getNode('crm-create'), 'change', this._onCrmCreateChange.bind(this));

		if(!defaults.isTimemanInstalled)
		{
			BX.bind(BX('vi_timeman'), 'change', function(e){
				BX('vi_timeman').checked = false;
				BX.Voximplant.alert(
					BX.message("VI_CONFIG_EDIT_ERROR"),
					defaults.isBitrix24 ? BX.message("VI_CONFIG_EDIT_TIMEMAN_SUPPORT_B24_2") : BX.message("VI_CONFIG_EDIT_TIMEMAN_SUPPORT_CP")
				);
			});
		}

		BX.addCustomEvent(this.sipNumberSelector, this.sipNumberSelector.events.buttonAdd, this.onAddSipNumberButtonClick.bind(this));
		BX.addCustomEvent(this.sipNumberSelector, this.sipNumberSelector.events.tileRemove, this.onNumberSelectorRemoveTile.bind(this));
	};


	BX.Voximplant.ConfigEditor.prototype.onMenuItemClick = function(e)
	{
		var page = e.currentTarget.dataset.page;
		this.setActivePage(page);
	};

	BX.Voximplant.ConfigEditor.prototype.setActivePage = function(page)
	{
		for (var key in this.pages)
		{
			if(!this.pages.hasOwnProperty(key))
			{
				continue;
			}
			if(key == page)
			{
				this.pages[key].classList.add("active");
			}
			else
			{
				this.pages[key].classList.remove("active");
			}
		}

		if(page === "unlink")
		{
			this.getNode("button-panel").style.display = "none";
		}
		else
		{
			this.getNode("button-panel").style.display = "block";
		}
	};

	BX.Voximplant.ConfigEditor.prototype.checkSipState = function()
	{
		var registrationId = this.sipConfig.REG_ID;

		BX.ajax.runComponentAction("bitrix:voximplant.config.edit", "checkConnection", {
			data: {
				registrationId: registrationId
			}
		}).then(function(response)
		{
			var data = response.data;
			var statusClassName;

			var descriptionNodes = [
				BX.create("div", {text: BX.message('VI_CONFIG_SIP_C_STATUS_' + data.statusResult.toUpperCase() + '_DESC')})
			];

			if(data.statusResult === "success")
			{
				statusClassName = "ui-alert ui-alert-success ui-alert-icon-info";
			}
			else if(data.statusResult === "in_progress")
			{
				statusClassName = "ui-alert ui-alert-warning ui-alert-icon-warning";
				setTimeout(this.checkSipState.bind(this), 30000);
			}
			else if(data.statusResult === "error")
			{
				statusClassName = "ui-alert ui-alert-danger ui-alert-icon-danger";
				this.elements.sipNeedUpdate.value = 'Y';
				descriptionNodes = [
					BX.create("div", {text: BX.message("VI_CONFIG_SIP_ERROR_1").replace('#DATE#', data.lastUpdated)}),
					BX.create("div", {text: BX.message("VI_CONFIG_SIP_ERROR_2").replace('#CODE#', data.statusCode)}),
					BX.create("div", {text: BX.message("VI_CONFIG_SIP_ERROR_3").replace('#MESSAGE#', data.errorMessage)})
				];
			}

			BX.cleanNode(this.elements.sipStatus);
			BX.cleanNode(this.elements.sipStatusText);
			BX.adjust(this.elements.sipStatus, {
				props: {className: statusClassName},
				children: [
					BX.create("span", {
						props: {className: "ui-alert-message"},
						text: BX.message('VI_CONFIG_SIP_C_STATUS_'+data.statusResult.toUpperCase())
					})
				]
			});
			BX.adjust(this.elements.sipStatusText, {
				props: {className: "ui-alert ui-alert-warning"},
				children: [
					BX.create("span", {
						props: {className: "ui-alert-message"},
						children: descriptionNodes
					})
				]
			});
		}.bind(this)).catch(function(response)
		{
			var error = response.errors[0];

			BX.cleanNode(this.elements.sipStatus);
			BX.cleanNode(this.elements.sipStatusText);
			BX.adjust(this.elements.sipStatus, {
				props: {className: "ui-alert ui-alert-danger ui-alert-icon-danger"},
				children: [
					BX.create("span", {
						props: {className: "ui-alert-message"},
						text: BX.message('VI_CONFIG_SIP_C_STATUS_ERROR')
					})
				]
			});
			BX.adjust(this.elements.sipStatusText, {
				props: {className: "ui-alert ui-alert-warning"},
				children: [
					BX.create("span", {
						props: {className: "ui-alert-message"},
						children: [
							BX.create("div", {text: BX.message("VI_CONFIG_SIP_ERROR_1").replace('#DATE#', 'n/a')}),
							BX.create("div", {text: BX.message("VI_CONFIG_SIP_ERROR_2").replace('#CODE#', 'ACCOUNT_ERROR')}),
							BX.create("div", {text: BX.message("VI_CONFIG_SIP_ERROR_3").replace('#MESSAGE#', error.message)})
						]
					})
				]
			});
		}.bind(this));
	};

	BX.Voximplant.ConfigEditor.prototype.loadMelody = function (curId, params)
	{
		if (typeof params !== "object")
			return;

		var inputName = params.INPUT_NAME || "";
		var defaultMelody = params.DEFAULT_MELODY || "";
		var mfi = BX["MFInput"] ? BX.MFInput.get(curId) : null;
		var node = this.getNode("melody-container-"+curId);

		if(node && !params.HIDDEN)
		{
			node.classList.add("active");
		}

		BX(curId + 'span').appendChild(BX('file_input_' + curId));

		if (mfi)
		{
			BX.bind(BX(curId + 'default'), "click", function ()
			{
				mfi.clear();
			});
			BX.addCustomEvent(mfi, "onDeleteFile", function ()
			{
				BX.hide(BX(curId + 'default'));
				BX.show(BX(curId + 'notice'));
				var player = BX.Fileman.PlayerManager.getPlayerById(curId + "player");
				player.setSource(defaultMelody.replace("#LANG_ID#", BX("config_edit_form").elements["MELODY_LANG"].value));
			});
			BX.addCustomEvent(mfi, "onUploadDone", function (file, item)
			{
				BX.show(BX(curId + 'default'));
				BX.hide(BX(curId + 'notice'));
				var player = BX.Fileman.PlayerManager.getPlayerById(curId + "player");
				player.setSource(file["url"] + (file["url"].indexOf(".mp3") > 0 ? "" : "&/melody.mp3" ));
			});
		}
		else
		{
			BX.bind(BX(curId + 'default'), "click", function ()
			{
				window["FILE_INPUT_" + curId]._deleteFile(BX('config_edit_form').elements[inputName]);
			});
			BX.addCustomEvent(window["FILE_INPUT_" + curId], 'onSubmit', function ()
			{
				BX(curId + 'span').appendChild(
					BX.create('SPAN', {
						attrs: {id: curId + 'waiter'},
						props: {className: "webform-field-upload-list"},
						html: '<i></i>'
					})
				);
			});
			BX.addCustomEvent(window["FILE_INPUT_" + curId], 'onFileUploaderChange', function ()
			{
				window["FILE_INPUT_" + curId].INPUT.disabled = false;
			});
			BX.addCustomEvent(window["FILE_INPUT_" + curId], 'onDeleteFile', function (id)
			{
				BX.hide(BX(curId + 'default'));
				BX(curId + 'notice').innerHTML = BX.message("VI_CONFIG_EDIT_DOWNLOAD_TUNE_TIP");
				var player = BX.Fileman.PlayerManager.getPlayerById(curId + "player");
				player.setSource(defaultMelody.replace("#LANG_ID#", BX("config_edit_form").elements["MELODY_LANG"].value));
				window["FILE_INPUT_" + curId].INPUT.disabled = false;
			});

			BX.addCustomEvent(window["FILE_INPUT_" + curId], 'onDone', function (files, id, err)
			{
				BX.remove(BX(curId + 'waiter'));
				if (!!files && files.length > 0)
				{
					var n = BX(curId + 'notice');
					if (err === false && !!files[0])
					{
						if (id !== 'init')
						{
							n.innerHTML = BX.message('VI_CONFIG_EDIT_UPLOAD_SUCCESS');
							var player = BX.Fileman.PlayerManager.getPlayerById(curId + "player");
							player.setSource(files[0]["fileURL"]);
							BX(curId + 'default').style.display = '';
						}
					}
					else if (!!files[0] && files[0]["error"])
					{
						n.innerHTML = files[0]["error"];
					}
				}
			});
		}
	};

	BX.Voximplant.ConfigEditor.prototype._onTooltipMouseOver = function (e)
	{
		this.showTooltip(e.target.dataset.id, e.target, e.target.dataset.text);
	};

	BX.Voximplant.ConfigEditor.prototype._onTooltipMouseOut = function (e)
	{
		this.hideTooltip(e.target.dataset.id);
	};

	BX.Voximplant.ConfigEditor.prototype.showTooltip = function (id, bind, text)
	{
		if (this.popupTooltip[id])
			this.popupTooltip[id].close();

		this.popupTooltip[id] = new BX.PopupWindow('bx-voximplant-tooltip', bind, {
			lightShadow: true,
			autoHide: false,
			darkMode: true,
			offsetLeft: 0,
			offsetTop: 2,
			bindOptions: {position: "top"},
			zIndex: 200,
			events: {
				onPopupClose: function ()
				{
					this.destroy()
				}
			},
			content: BX.create("div", {attrs: {style: "padding-right: 5px; width: 250px;"}, html: text})
		});
		this.popupTooltip[id].setAngle({offset: 13, position: 'bottom'});
		this.popupTooltip[id].show();

		return true;
	};

	BX.Voximplant.ConfigEditor.prototype.hideTooltip = function (id)
	{
		this.popupTooltip[id].close();
		this.popupTooltip[id] = null;
	};

	BX.Voximplant.ConfigEditor.prototype.onShowGroupClick = function (e)
	{
		this.showGroupSettings({
			groupId: this.getNode('select-group').value
		});
	};

	BX.Voximplant.ConfigEditor.prototype.onShowCrmExceptionListClick = function(e)
	{
		BX.SidePanel.Instance.open('/crm/configs/exclusion/', {cacheable: false});
	};

	BX.Voximplant.ConfigEditor.prototype.onShowIvrClick = function (e)
	{
		this.showIvrSettings({
			ivrId: this.getNode('select-ivr').value
		});
	};

	BX.Voximplant.ConfigEditor.prototype.onAddSipNumberButtonClick = function (e)
	{
		this.numberInputPopup = new AddNumberPopup({
			bindElement: this.sipNumberSelector.buttonAdd,
			onAdd: this.addSipNumber.bind(this),
			onCancel: function()
			{
				this.numberInputPopup.close()
			}.bind(this),
			onDestroy: function()
			{
				this.numberInputPopup = null;
			}.bind(this)
		});
		this.numberInputPopup.show();
	};

	BX.Voximplant.ConfigEditor.prototype.addSipNumber = function(params)
	{
		BX.ajax.runComponentAction("bitrix:voximplant.config.edit", "addSipNumber", {
			data: {
				sipId: this.sipConfig.ID,
				number: params.number
			}
		}).then(function(response)
		{
			this.numberInputPopup.close();
			var numberFields = response.data;
			this.sipNumberSelector.addTile(
				numberFields["FORMATTED_NUMBER"],
				{
					canRemove: true
				},
				numberFields["NUMBER"]
			)

		}.bind(this)).catch(function(response)
		{
			this.numberInputPopup.close();
			BX.Voximplant.alert(" ", response.errors[0].message);
		}.bind(this));
	};

	BX.Voximplant.ConfigEditor.prototype.onNumberSelectorRemoveTile = function(tile)
	{
		BX.ajax.runComponentAction("bitrix:voximplant.config.edit", "removeSipNumber", {
			data: {
				sipId: this.sipConfig.ID,
				number: tile.id
			}
		}).catch(function(response)
		{
			console.error(response.errors[0].message);
		})
	};

	BX.Voximplant.ConfigEditor.prototype.showGroupSettings = function (params)
	{
		var groupId = parseInt(params.groupId);
		if (BX.SidePanel.Instance.open("/telephony/editgroup.php?ID=" + groupId, {cacheable: false}))
		{
			this.groupSliderOpen = true;
		}
	};

	BX.Voximplant.ConfigEditor.prototype.showIvrSettings = function (params)
	{
		var ivrId = parseInt(params.ivrId);
		if (BX.SidePanel.Instance.open("/telephony/editivr.php?ID=" + ivrId, {cacheable: false}))
		{
			this.ivrSliderOpen = true;
		}
	};

	BX.Voximplant.ConfigEditor.prototype.deleteCallerId = function(number)
	{
		BX.Voximplant.confirm(
			BX.message("VOX_CONFIG_CONFIRM_ACTION"),
			BX.message("VOX_CONFIG_CALLERID_DELETE_CONFIRM").replace("#NUMBER#", number)
		).then(function(result)
		{
			if(!result)
			{
				return;
			}

			BX.Voximplant.showLoader();
			BX.ajax.runAction("voximplant.callerId.delete", {
				data: {
					phoneNumber: number
				}
			}).then(function()
			{
				BX.Voximplant.hideLoader();
				BX.SidePanel.Instance.close();
			}).catch(function(response)
			{
				BX.Voximplant.hideLoader();
				var error = response.errors[0];
				BX.Voximplant.alert(BX.message("VOX_LINES_ERROR"), error.message);
			})
		});
	};

	BX.Voximplant.ConfigEditor.prototype.deleteNumber = function(number)
	{
		BX.Voximplant.NumberRent.create().deleteNumber(number).then(function()
		{
			BX.SidePanel.Instance.close();
		});
	};

	BX.Voximplant.ConfigEditor.prototype.cancelDeleteNumber = function(number)
	{
		BX.Voximplant.NumberRent.create().cancelNumberDeletion(number).then(function()
		{
			BX.SidePanel.Instance.close();
		})
	};

	BX.Voximplant.ConfigEditor.prototype._onGroupIdChanged = function (e)
	{
		var groupId = e.target.value;
		var groupCount = e.target.options.length - 2;
		if (groupId === 'new')
		{
			if (defaults.maximumGroups > -1 && groupCount >= defaults.maximumGroups)
			{
				e.target.value = e.target.options.item(2).value;
				if (defaults.maximumGroups == 0)
				{
					BX.UI.InfoHelper.show('limit_contact_center_telephony_groups_zero');
				}
				else
				{
					BX.UI.InfoHelper.show('limit_contact_center_telephony_groups');
				}
			}
			else
			{
				this.showGroupSettings({
					groupId: 0
				})
			}
		}
		this.previousGroupId = this.currentGroupId;
		this.currentGroupId = groupId;
		BX.PreventDefault(e);
	};

	BX.Voximplant.ConfigEditor.prototype._onIvrIdChanged = function (e)
	{
		var ivrId = e.target.value;
		if (ivrId === 'new')
		{
			this.showIvrSettings({
				ivrId: 0
			})
		}
		this.previousIvrId = this.currentIvrId;
		this.currentIvrId = ivrId;
		BX.PreventDefault(e);
	};

	BX.Voximplant.ConfigEditor.prototype._onTranscribeLanguageChanged = function (e)
	{
		var languageId = e.target.value;
		var engineWrap = this.getNode("transcribe-provider-wrap");
		if (languageId == "RUSSIAN_RU") // @see \Bitrix\Voximplant\Asr\Language::RUSSIAN_RU
		{
			engineWrap.style.maxHeight = engineWrap.dataset.height;
		}
		else
		{
			engineWrap.style.maxHeight = 0;
		}
	};

	BX.Voximplant.ConfigEditor.prototype._onSliderClosed = function(event)
	{
		if(this.groupSliderOpen)
		{
			this.groupSliderOpen = false;
			if (this.currentGroupId === 'new')
			{
				this.getNode('select-group').value = this.previousGroupId;
				this.currentGroupId = this.previousGroupId;
			}
		}
		else if(this.ivrSliderOpen)
		{
			if (this.currentIvrId === 'new')
			{
				this.getNode('select-ivr').value = this.previousIvrId;
				this.currentIvrId = this.previousIvrId;
			}
		}
	};

	BX.Voximplant.ConfigEditor.prototype._onSliderMessageReceived = function(event)
	{
		var eventId = event.getEventId();

		if(eventId === "QueueEditor::onSave")
		{
			var groupFields = event.getData()['DATA']['GROUP'];
			if(!groupFields['ID'])
			{
				return;
			}
			this.afterGroupSaved(groupFields);
		}
		else if(eventId === "IvrEditor::onSave")
		{
			var ivrFields = event.getData()['ivr'];
			if(!ivrFields['ID'])
			{
				return;
			}
			this.afterIvrSaved(ivrFields);
		}
	};

	BX.Voximplant.ConfigEditor.prototype.setCrmCreate = function(crmCreateFlag)
	{
		if(crmCreateFlag === 'lead')
		{
			this.getNode('crm-source-select').disabled = false;
			this.getNode('crm-create-call-type').disabled = false;
		}
		else
		{
			this.getNode('crm-source-select').disabled = true;
			this.getNode('crm-create-call-type').disabled = true;
		}
	};

	BX.Voximplant.ConfigEditor.prototype._onCrmCreateChange = function(e)
	{
		this.setCrmCreate(e.currentTarget.value);
	};

	BX.Voximplant.ConfigEditor.prototype._onCheckBoxChange = function(e)
	{
		var target = e.currentTarget;
		var targetRole = target.dataset.role;

		var locked = target.dataset.locked === "Y";
		var licensePopupId = target.dataset.licensePopup;

		if(locked && licensePopupId)
		{
			BX.UI.InfoHelper.show(licensePopupId);
			target.checked = false;
			e.preventDefault();
		}
		else if(this.map.hasOwnProperty(targetRole))
		{
			var slaveBlock = this.getNode(this.map[targetRole]);

			if(slaveBlock)
			{
				if(target.checked)
				{
					slaveBlock.style.maxHeight = slaveBlock.dataset.height;
				}
				else
				{
					slaveBlock.style.maxHeight = 0;
				}
			}
		}
	};

	/**
	 * @param {Event} e
	 */
	BX.Voximplant.ConfigEditor.prototype._onInputLinePrefixInput = function(e)
	{
		var node = e.target;
		node.value = node.value.replace(/[^\d#*]/g,'');
		e.preventDefault();
	};

	BX.Voximplant.ConfigEditor.prototype._onMelodyLangChange = function(e)
	{
		var lang = e.currentTarget.value;
		for(var melodyId in this.melodies)
		{
			if(!this.melodies.hasOwnProperty(melodyId))
			{
				continue;
			}
			var inputName = this.melodies[melodyId]["INPUT_NAME"];
			var player = BX.Fileman.PlayerManager.getPlayerById(melodyId + "player");
			var defaultMelody = this.melodies[melodyId]["DEFAULT_MELODY"];

			if (!BX("config_edit_form").elements[inputName])
			{
				player.setSource(defaultMelody.replace("#LANG_ID#", lang));
			}
		}
	};

	BX.Voximplant.ConfigEditor.prototype._onMoreTunesClick = function(e)
	{
		for(var melodyId in this.melodies)
		{
			var node = this.getNode("melody-container-" + melodyId);
			if(node)
			{
				node.classList.add("active");
			}
		}

		BX.remove(e.currentTarget);
	};

	BX.Voximplant.ConfigEditor.prototype._onDeleteCallerIdClick = function(e)
	{
		var number = e.currentTarget.dataset.number;
		if(BX.type.isNotEmptyString(number))
		{
			this.deleteCallerId(number);
		}
	};

	BX.Voximplant.ConfigEditor.prototype._onDeleteNumberClick = function(e)
	{
		var number = e.currentTarget.dataset.number;
		if(BX.type.isNotEmptyString(number))
		{
			this.deleteNumber(number);
		}
	};

	BX.Voximplant.ConfigEditor.prototype._onCancelNumberDeleteClick = function(e)
	{
		var number = e.currentTarget.dataset.number;
		if(BX.type.isNotEmptyString(number))
		{
			this.cancelDeleteNumber(number);
		}
	};

	BX.Voximplant.ConfigEditor.prototype.afterGroupSaved = function(groupFields)
	{
		var groupSelect = this.getNode('select-group');
		var optionFound = false;
		var optionNode;
		for (var i = 0; i < groupSelect.options.length; i++)
		{
			optionNode = groupSelect.options.item(i);
			if (optionNode.value == groupFields.ID)
			{
				optionNode.innerText = BX.util.htmlspecialchars(groupFields.NAME);
				optionFound = true;
				break;
			}
		}
		if (!optionFound)
		{
			groupSelect.add(BX.create('option', {
				attrs: {value: groupFields.ID},
				text: BX.util.htmlspecialchars(groupFields.NAME)
			}));
		}
		groupSelect.value = groupFields.ID;
		this.currentGroupId = groupFields.ID;
	};

	BX.Voximplant.ConfigEditor.prototype.afterIvrSaved = function(ivrFields)
	{
		var ivrSelect = this.getNode('select-ivr');
		var optionFound = false;
		var optionNode;
		for (var i = 0; i < ivrSelect.options.length; i++)
		{
			optionNode = ivrSelect.options.item(i);
			if (optionNode.value == ivrFields.ID)
			{
				optionNode.innerText = BX.util.htmlspecialchars(ivrFields.NAME);
				optionFound = true;
				break;
			}
		}
		if (!optionFound)
		{
			ivrSelect.add(BX.create('option', {
				attrs: {value: ivrFields.ID},
				text: BX.util.htmlspecialchars(ivrFields.NAME)
			}));
		}
		ivrSelect.value = ivrFields.ID;
		this.currentIvrId = ivrFields.ID;
	};

	var AddNumberPopup = function(config)
	{
		this.popup = null;
		this.bindElement = config.bindElement;

		this.input = null;
		this.value = "";

		this.callbacks = {
			onAdd: BX.type.isFunction(config.onAdd) ? config.onAdd : BX.DoNothing,
			onCancel: BX.type.isFunction(config.onCancel) ? config.onCancel : BX.DoNothing,
			onDestroy: BX.type.isFunction(config.onDestroy) ? config.onDestroy : BX.DoNothing,
		}
	};

	AddNumberPopup.prototype = {
		show: function()
		{
			if(this.popup)
			{
				this.popup.show();
				return;
			}

			this.popup = new BX.PopupWindow("vox-config-add-number", this.bindElement, {
				autoHide: true,
				closeByEsc: true,
				closeIcon: false,
				contentNoPaddings: true,
				contentColor: "white",
				events: {
					onPopupClose: function ()
					{
						this.destroy()
					}.bind(this),
					onPopupDestroy: function ()
					{
						this.popup = null;
					}.bind(this)
				},
				content: BX.create("div", {
					children: [
						this.input = BX.create("input", {
							props: {className: "voximplant-control-input"},
							events: {
								keyup: this.onInputKeyUp.bind(this)
							}
						})
					]
				}),
				buttons: [
					new BX.PopupWindowCustomButton({
						id: "button-add",
						text: BX.message("VI_CONFIG_SIP_ADD"),
						className: "ui-btn ui-btn-sm ui-btn-primary",
						events: {
							click: this.onAddButtonClick.bind(this)
						}
					}),
					new BX.PopupWindowCustomButton({
						id: "button-cancel",
						text: BX.message("VI_CONFIG_SIP_CANCEL"),
						className: "ui-btn ui-btn-sm ui-btn-link",
						events: {
							click: this.onCancelButtonClick.bind(this)
						}
					})
				]
			});

			this.inputFormatted = new BX.PhoneNumber.Input({
				node: this.input,
				onChange: this.onInputChange.bind(this)
			});

			this.popup.show();
			this.input.focus();
		},

		close: function()
		{
			if(this.popup)
			{
				this.popup.close();
			}
		},

		onInputChange: function(e)
		{
			this.value = e.value;
		},

		onInputKeyUp: function(e)
		{
			if(e.key === "Enter")
			{
				this.onAddButtonClick();
				e.preventDefault();
				e.stopPropagation();
			}
		},

		onAddButtonClick: function()
		{
			this.popup.getButton("button-add").addClassName("ui-btn-wait");
			this.callbacks.onAdd({
				number: this.value
			});
		},

		onCancelButtonClick: function()
		{
			this.close();
		},

		destroy: function()
		{
			this.popup.destroy();
			this.callbacks.onDestroy();
		}
	}

})(window);
