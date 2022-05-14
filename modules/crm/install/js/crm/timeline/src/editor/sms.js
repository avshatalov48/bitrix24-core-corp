import Editor from "../editor";

/** @memberof BX.Crm.Timeline.Editors */
export default class Sms extends Editor
{
	constructor()
	{
		super();
		this._history = null;
		this._serviceUrl = "";

		this._isRequestRunning = false;
		this._isLocked = false;

		this._senderId = null;
		this._from = null;
		this._commEntityTypeId = null;
		this._commEntityId = null;
		this._to = null;

		this._canUse = null;
		this._canSendMessage = null;
		this._manageUrl = '';
		this._senders = [];
		this._fromList = [];
		this._toList = [];
		this._defaults = {};
		this._communications = [];

		this._menu = null;
		this._isMenuShown = false;
		this._shownMenuId = null;
		this._documentSelector = null;
		this._source = null;

		this._paymentId = null;
		this._shipmentId = null;

		this._templateId = null;
		this._templatesContainer = null;
		this._templateFieldHintNode = null;
		this._templateSelectorNode = null;
		this._templateTemplateTitleNode = null;
		this._templatePreviewNode = null;
		this._templateSelectorMenuId = 'CrmTimelineSmsEditorTemplateSelector';
		this._templateFieldHintHandler = BX.delegate(this.onTemplateHintIconClick, this);
		this._templateSeletorClickHandler = BX.delegate(this.onTemplateSelectClick, this);
		this._selectTemplateHandler = BX.delegate(this.onSelectTemplate, this);
	}

	doInitialize()
	{
		this._serviceUrl = BX.util.remove_url_param(
			this.getSetting("serviceUrl", ""),
			['sessid', 'site']
		);

		const config = BX.prop.getObject(this._settings, "config", {});

		this._canUse = BX.prop.getBoolean(config, "canUse", false);
		this._canSendMessage = BX.prop.getBoolean(config, "canSendMessage", false);
		this._manageUrl = BX.prop.getString(config, "manageUrl", '');
		this._senders = BX.prop.getArray(config, "senders", []);
		this._defaults = BX.prop.getObject(config, "defaults", {senderId:null,from:null});
		this._communications = BX.prop.getArray(config, "communications", []);
		this._isSalescenterEnabled = BX.prop.getBoolean(config, "isSalescenterEnabled", false);
		this._isDocumentsEnabled = BX.prop.getBoolean(config, "isDocumentsEnabled", false);
		if(this._isDocumentsEnabled)
		{
			this._documentsProvider = BX.prop.getString(config, "documentsProvider", '');
			this._documentsValue = BX.prop.getString(config, "documentsValue", '');
		}
		this._isFilesEnabled = BX.prop.getBoolean(config, "isFilesEnabled", false);
		if(this._isFilesEnabled)
		{
			this._diskUrls = BX.prop.getObject(config, "diskUrls");
			this._isFilesExternalLinkEnabled = BX.prop.getBoolean(config, "isFilesExternalLinkEnabled", true);
		}

		this._senderSelectorNode = this._container.querySelector('[data-role="sender-selector"]');
		this._fromContainerNode = this._container.querySelector('[data-role="from-container"]');
		this._fromSelectorNode = this._container.querySelector('[data-role="from-selector"]');
		this._clientContainerNode = this._container.querySelector('[data-role="client-container"]');
		this._clientSelectorNode = this._container.querySelector('[data-role="client-selector"]');
		this._toSelectorNode = this._container.querySelector('[data-role="to-selector"]');
		this._messageLengthCounterWrapperNode = this._container.querySelector('[data-role="message-length-counter-wrap"]');
		this._messageLengthCounterNode = this._container.querySelector('[data-role="message-length-counter"]');
		this._salescenterStarter = this._container.querySelector('[data-role="salescenter-starter"]');
		this._smsDetailSwitcher = this._container.querySelector('[data-role="sms-detail-switcher"]');
		this._smsDetail = this._container.querySelector('[data-role="sms-detail"]');
		this._documentSelectorButton = this._container.querySelector('[data-role="sms-document-selector"]');
		this._fileSelectorButton = this._container.querySelector('[data-role="sms-file-selector"]');
		this._fileUploadZone = this._container.querySelector('[data-role="sms-file-upload-zone"]');
		this._fileUploadLabel = this._container.querySelector('[data-role="sms-file-upload-label"]');
		this._fileSelectorBitrix = this._container.querySelector('[data-role="sms-file-selector-bitrix"]');
		this._fileExternalLinkDisabledContent = this._container.querySelector('[data-role="sms-file-external-link-disabled"]');

		this._templatesContainer = BX(this.getSetting("templatesContainer"));
		if (this._templatesContainer)
		{
			this._templateFieldHintNode = this._templatesContainer.querySelector('[data-role="hint"]');
			this._templateSelectorNode = this._templatesContainer.querySelector('[data-role="template-selector"]');
			this._templateTemplateTitleNode = this._templatesContainer.querySelector('[data-role="template-title"]');
			this._templatePreviewNode = this._templatesContainer.querySelector('[data-role="preview"]');
		}
		if (this._templateFieldHintNode)
		{
			BX.bind(this._templateFieldHintNode, "click", this._templateFieldHintHandler);
		}
		if (this._templateSelectorNode)
		{
			BX.bind(this._templateSelectorNode, "click", this._templateSeletorClickHandler);
		}

		if (this._canUse && this._senders.length > 0)
		{
			this.initSenderSelector();
		}
		if (this._canUse && this._canSendMessage)
		{
			this.initDetailSwitcher();
			this.initFromSelector();
			this.initClientContainer();
			this.initClientSelector();
			this.initToSelector();
			this.initMessageLengthCounter();
			this.setMessageLengthCounter();
			if(this._isDocumentsEnabled)
			{
				this.initDocumentSelector();
			}
			if(this._isFilesEnabled)
			{
				this.initFileSelector();
			}
		}

		if(this._isSalescenterEnabled)
		{
			this.initSalescenterApplication();
		}
	}

	initDetailSwitcher()
	{
		BX.bind(this._smsDetailSwitcher, 'click', function()
		{
			if(this._smsDetail.classList.contains('hidden'))
			{
				this._smsDetail.classList.remove('hidden');
				this._smsDetailSwitcher.innerText = BX.message('CRM_TIMELINE_COLLAPSE');
			}
			else
			{
				this._smsDetail.classList.add('hidden');
				this._smsDetailSwitcher.innerText = BX.message('CRM_TIMELINE_DETAILS');
			}
		}.bind(this));
	}

	initSenderSelector()
	{
		const defaultSenderId = this._defaults.senderId;
		let defaultSender = this._senders[0].canUse ? this._senders[0] : null;
		let restSender = null;
		const menuItems = [];
		const handler = this.onSenderSelectorClick.bind(this);

		for (let i = 0; i < this._senders.length; ++i)
		{
			if (this._senders[i].canUse && this._senders[i].fromList.length && (this._senders[i].id === defaultSenderId || !defaultSender))
			{
				defaultSender = this._senders[i];
			}

			if (this._senders[i].id === 'rest')
			{
				restSender = this._senders[i];
				continue;
			}

			menuItems.push({
				text: this._senders[i].name,
				sender: this._senders[i],
				onclick: handler,
				className: (!this._senders[i].canUse || !this._senders[i].fromList.length)
					? 'crm-timeline-popup-menu-item-disabled menu-popup-no-icon' : ''
			});
		}

		if (restSender)
		{
			if (restSender.fromList.length > 0)
			{
				menuItems.push({delimiter: true});
				for (let i = 0; i < restSender.fromList.length; ++i)
				{
					menuItems.push({
						text: restSender.fromList[i].name,
						sender: restSender,
						from: restSender.fromList[i],
						onclick: handler
					});
				}
			}
			menuItems.push({delimiter: true}, {
				text: BX.message('CRM_TIMELINE_SMS_REST_MARKETPLACE'),
				href: '/marketplace/category/crm_robot_sms/',
				target: '_blank'
			});
		}

		if (defaultSender)
		{
			this.setSender(defaultSender);
		}

		BX.bind(this._senderSelectorNode, 'click', this.openMenu.bind(this, 'sender', this._senderSelectorNode, menuItems));
	}

	onSenderSelectorClick(e, item)
	{
		if (item.sender)
		{
			if (!item.sender.canUse || !item.sender.fromList.length)
			{
				const url = BX.Uri.addParam(item.sender.manageUrl, {'IFRAME': 'Y'});
				const slider = BX.SidePanel.Instance.getTopSlider();
				const options = {
					events: {
						onClose: function () {
							if (slider)
							{
								slider.reload();
							}
						},
						onCloseComplete: function () {
							if (!slider)
							{
								document.location.reload();
							}
						}
					}
				};
				if (item.sender.id === 'ednaru')
				{
					options.width = 700;
				}
				BX.SidePanel.Instance.open(url, options);
				return;
			}

			this.setSender(item.sender, true);
			const from = item.from ? item.from : item.sender.fromList[0];
			this.setFrom(from, true);
		}
		this._menu.close();
	}

	setSender(sender, setAsDefault)
	{
		this._senderId = sender.id;
		this._fromList = sender.fromList;
		this._senderSelectorNode.textContent = sender.shortName ? sender.shortName : sender.name;

		this._templateId = null;
		if (sender.isTemplatesBased)
		{
			this.showNode(this._templatesContainer);
			this.hideNode(this._messageLengthCounterWrapperNode);
			this.hideNode(this._fileSelectorButton);
			this.hideNode(this._documentSelectorButton);
			this.hideNode(this._input);
			this.toggleTemplateSelectAvailability();
			this.toggleSaveButton();
			this._hideButtonsOnBlur = false;
			this.onFocus();
		}
		else
		{
			this.hideNode(this._templatesContainer);
			this.showNode(this._messageLengthCounterWrapperNode);
			this.showNode(this._fileSelectorButton);
			this.showNode(this._documentSelectorButton);
			this.showNode(this._input);
			this.setMessageLengthCounter();
			this._hideButtonsOnBlur = true;
		}

		const visualFn = sender.id === 'rest' ? 'hide' : 'show';
		BX[visualFn](this._fromContainerNode);

		if (setAsDefault)
		{
			BX.userOptions.save("crm", "sms_manager_editor", "senderId", this._senderId);
		}
	}

	showNode(node)
	{
		if (node)
		{
			node.style.display = "";
		}
	}

	hideNode(node)
	{
		if (node)
		{
			node.style.display = "none";
		}
	}

	initFromSelector()
	{
		if (this._fromList.length > 0)
		{
			const defaultFromId = this._defaults.from || this._fromList[0].id;
			let defaultFrom = null;
			for (let i = 0; i < this._fromList.length; ++i)
			{
				if (this._fromList[i].id === defaultFromId || !defaultFrom)
				{
					defaultFrom = this._fromList[i];
				}
			}
			if (defaultFrom)
			{
				this.setFrom(defaultFrom);
			}
		}

		BX.bind(this._fromSelectorNode, 'click', this.onFromSelectorClick.bind(this));
	}

	onFromSelectorClick(e)
	{
		const menuItems = [];
		const handler = this.onFromSelectorItemClick.bind(this);

		for (let i = 0; i < this._fromList.length; ++i)
		{
			menuItems.push({
				text: this._fromList[i].name,
				from: this._fromList[i],
				onclick: handler
			});
		}

		this.openMenu('from_'+this._senderId, this._fromSelectorNode, menuItems, e);
	}

	onFromSelectorItemClick(e, item)
	{
		if (item.from)
		{
			this.setFrom(item.from, true);
		}
		this._menu.close();
	}

	setFrom(from, setAsDefault)
	{
		this._from = from.id;

		if (this._senderId === 'rest')
		{
			this._senderSelectorNode.textContent = from.name;
		}
		else
		{
			this._fromSelectorNode.textContent = from.name;
		}

		if (setAsDefault)
		{
			BX.userOptions.save("crm", "sms_manager_editor", "from", this._from);
		}
	}

	initClientContainer()
	{
		if (this._communications.length === 0)
		{
			BX.hide(this._clientContainerNode);
		}
	}

	initClientSelector()
	{
		const menuItems = [];
		const handler = this.onClientSelectorClick.bind(this);

		for (let i = 0; i < this._communications.length; ++i)
		{
			menuItems.push({
				text: this._communications[i].caption,
				client: this._communications[i],
				onclick: handler
			});
			if (i === 0)
			{
				this.setClient(this._communications[i]);
			}
		}

		BX.bind(this._clientSelectorNode, 'click', this.openMenu.bind(this, 'comm', this._clientSelectorNode, menuItems));
	}

	onClientSelectorClick(e, item)
	{
		if (item.client)
		{
			this.setClient(item.client);
		}
		this._menu.close();
	}

	setClient(client)
	{
		this._commEntityTypeId = client.entityTypeId;
		this._commEntityId = client.entityId;
		this._clientSelectorNode.textContent = client.caption;
		this._toList = client.phones;
		this.setTo(client.phones[0]);
	}

	initToSelector()
	{
		BX.bind(this._toSelectorNode, 'click', this.onToSelectorClick.bind(this));
	}

	onToSelectorClick(e)
	{
		const menuItems = [];
		const handler = this.onToSelectorItemClick.bind(this);

		for (let i = 0; i < this._toList.length; ++i)
		{
			menuItems.push({
				text: this._toList[i].valueFormatted || this._toList[i].value,
				to: this._toList[i],
				onclick: handler
			});
		}

		this.openMenu('to_'+this._commEntityTypeId+'_'+this._commEntityId, this._toSelectorNode, menuItems, e);
	}

	onToSelectorItemClick(e, item)
	{
		if (item.to)
		{
			this.setTo(item.to);
		}
		this._menu.close();
	}

	setTo(to)
	{
		this._to = to.value;
		this._toSelectorNode.textContent = to.valueFormatted || to.value;
	}

	openMenu(menuId, bindElement, menuItems, e)
	{
		if (this._shownMenuId === menuId)
		{
			return;
		}

		if(this._shownMenuId !== null && this._menu)
		{
			this._menu.close();
			this._shownMenuId = null;
		}

		BX.PopupMenu.show(
			this._id + menuId,
			bindElement,
			menuItems,
			{
				offsetTop: 0,
				offsetLeft: 36,
				angle: { position: "top", offset: 0 },
				events:
					{
						onPopupClose: BX.delegate(this.onMenuClose, this)
					}
			}
		);

		this._menu = BX.PopupMenu.currentItem;
		e.preventDefault();
	}

	onMenuClose()
	{
		this._shownMenuId = null;
		this._menu = null;
	}

	initMessageLengthCounter()
	{
		this._messageLengthMax = parseInt(this._messageLengthCounterNode.getAttribute('data-length-max'));
		BX.bind(this._input, 'keyup', this.setMessageLengthCounter.bind(this));
		BX.bind(this._input, 'cut', this.setMessageLengthCounterDelayed.bind(this));
		BX.bind(this._input, 'paste', this.setMessageLengthCounterDelayed.bind(this));
	}

	setMessageLengthCounterDelayed()
	{
		setTimeout(this.setMessageLengthCounter.bind(this), 0);
	}

	setMessageLengthCounter()
	{
		const length = this._input.value.length;
		this._messageLengthCounterNode.textContent = length;

		const classFn = length >= this._messageLengthMax ? 'addClass' : 'removeClass';
		BX[classFn](this._messageLengthCounterNode, 'crm-entity-stream-content-sms-symbol-counter-number-overhead');

		this.toggleSaveButton();
	}

	toggleSaveButton()
	{
		const sender = this.getSelectedSender();
		let enabled;
		if (!sender || !sender.isTemplatesBased)
		{
			enabled = this._input.value.length > 0;
		}
		else
		{
			enabled = !!this._templateId;
		}
		if (enabled)
		{
			BX.removeClass(this._saveButton, 'ui-btn-disabled');
		}
		else
		{
			BX.addClass(this._saveButton, 'ui-btn-disabled');
		}
	}

	save()
	{
		const sender = this.getSelectedSender();

		let text = '';
		let templateId = '';

		if (!sender || !sender.isTemplatesBased)
		{
			text = this._input.value;
			if (text === '')
			{
				return;
			}
		}
		else
		{
			const template = this.getSelectedTemplate();
			if (!template)
			{
				return;
			}
			text = template.PREVIEW;
			templateId = template.ID;
		}

		if (!this._communications.length)
		{
			alert(BX.message('CRM_TIMELINE_SMS_ERROR_NO_COMMUNICATIONS'));
			return;
		}

		if(this._isRequestRunning || this._isLocked)
		{
			return;
		}

		this._isRequestRunning = this._isLocked = true;
		BX.ajax(
			{
				url: BX.util.add_url_param(this._serviceUrl, {
					"action": "save_sms_message",
					"sender": this._senderId
				}),
				method: "POST",
				dataType: "json",
				data:
					{
						'site': BX.message('SITE_ID'),
						'sessid': BX.bitrix_sessid(),
						'source': this._source,
						"ACTION": "SAVE_SMS_MESSAGE",
						"SENDER_ID": this._senderId,
						"MESSAGE_FROM": this._from,
						"MESSAGE_TO": this._to,
						"MESSAGE_BODY": text,
						"MESSAGE_TEMPLATE": templateId,
						"OWNER_TYPE_ID": this._ownerTypeId,
						"OWNER_ID": this._ownerId,
						"TO_ENTITY_TYPE_ID": this._commEntityTypeId,
						"TO_ENTITY_ID": this._commEntityId,
						"PAYMENT_ID": this._paymentId,
						"SHIPMENT_ID": this._shipmentId,
					},
				onsuccess: BX.delegate(this.onSaveSuccess, this),
				onfailure: BX.delegate(this.onSaveFailure, this)
			}
		);
	}

	cancel()
	{
		this._input.value = "";
		this.setMessageLengthCounter();
		this._input.style.minHeight = "";
		this.release();
	}

	onSaveSuccess(data)
	{
		this._isRequestRunning = this._isLocked = false;

		const error = BX.prop.getString(data, "ERROR", "");
		if(error !== "")
		{
			alert(error);
			return;
		}

		this._input.value = "";
		this.setMessageLengthCounter();
		this._input.style.minHeight = "";
		this._manager.processEditingCompletion(this);
		this.release();
	}

	onSaveFailure()
	{
		this._isRequestRunning = this._isLocked = false;
	}

	initSalescenterApplication()
	{
		BX.bind(this._salescenterStarter, 'click', this.startSalescenterApplication.bind(this));
	}

	startSalescenterApplication()
	{
		BX.loadExt('salescenter.manager').then(function()
		{
			BX.Salescenter.Manager.openApplication({
				disableSendButton: this._canSendMessage ? '' : 'y',
				context: 'sms',
				ownerTypeId: this._ownerTypeId,
				ownerId: this._ownerId,
				mode: this._ownerTypeId === BX.CrmEntityType.enumeration.deal ? 'payment_delivery' : 'payment',
			}).then(function(result)
			{
				if(result && result.get('action'))
				{
					if(result.get('action') === 'sendPage' && result.get('page') && result.get('page').url)
					{
						this._input.focus();
						this._input.value = this._input.value + result.get('page').name + ' ' + result.get('page').url;
						this.setMessageLengthCounter();
					}
					else if (result.get('action') === 'sendPayment' && result.get('order'))
					{
						this._input.focus();
						this._input.value = this._input.value + result.get('order').title;
						this.setMessageLengthCounter();
						this._source = 'order';
						this._paymentId = result.get('order').paymentId;
						this._shipmentId = result.get('order').shipmentId;
					}
				}
			}.bind(this));
		}.bind(this));
	}

	initDocumentSelector()
	{
		BX.bind(this._documentSelectorButton, 'click', this.onDocumentSelectorClick.bind(this));
	}

	onDocumentSelectorClick()
	{
		if(!this._documentSelector)
		{
			BX.loadExt('documentgenerator.selector').then(function()
			{
				this._documentSelector = new BX.DocumentGenerator.Selector.Menu({
					node: this._documentSelectorButton,
					moduleId: 'crm',
					provider: this._documentsProvider,
					value: this._documentsValue,
					analyticsLabelPrefix: 'crmTimelineSmsEditor'
				});
				this.selectPublicUrl();
			}.bind(this));
		}
		else
		{
			this.selectPublicUrl();
		}
	}

	selectPublicUrl()
	{
		if(!this._documentSelector)
		{
			return;
		}
		this._documentSelector.show().then(function(object)
		{
			if(object instanceof BX.DocumentGenerator.Selector.Template)
			{
				this._documentSelector.createDocument(object).then(function(document)
				{
					this.pasteDocumentUrl(document);
				}.bind(this)).catch(function(error)
				{
					console.error(error);
				}.bind(this));
			}
			else if(object instanceof BX.DocumentGenerator.Selector.Document)
			{
				this.pasteDocumentUrl(object);
			}
		}.bind(this)).catch(function(error)
		{
			console.error(error);
		}.bind(this));
	}

	pasteDocumentUrl(document)
	{
		this._documentSelector.getDocumentPublicUrl(document).then(function(publicUrl)
		{
			this._input.focus();
			this._input.value = this._input.value + ' ' + document.getTitle() + ' ' + publicUrl;
			this.setMessageLengthCounter();
			this._source = 'document';
		}.bind(this)).catch(function(error)
		{
			console.error(error);
		}.bind(this));
	}

	initFileSelector()
	{
		BX.bind(this._fileSelectorButton, 'click', this.onFileSelectorClick.bind(this));
	}

	closeFileSelector()
	{
		BX.PopupMenu.destroy('sms-file-selector');
	}

	onFileSelectorClick()
	{
		BX.PopupMenu.show('sms-file-selector', this._fileSelectorButton, [
			{
				text: BX.message('CRM_TIMELINE_SMS_UPLOAD_FILE'),
				onclick: this.uploadFile.bind(this),
				className: this._isFilesExternalLinkEnabled ? '' : 'crm-entity-stream-content-sms-menu-item-with-lock'
			},
			{
				text: BX.message('CRM_TIMELINE_SMS_FIND_FILE'),
				onclick: this.findFile.bind(this),
				className: this._isFilesExternalLinkEnabled ? '' : 'crm-entity-stream-content-sms-menu-item-with-lock'
			}
		])
	}

	getFileUploadInput()
	{
		return document.getElementById(this._fileUploadLabel.getAttribute('for'));
	}

	uploadFile()
	{
		this.closeFileSelector();
		if(this._isFilesExternalLinkEnabled)
		{
			this.initDiskUF();
			BX.fireEvent(this.getFileUploadInput(), 'click');
		}
		else
		{
			this.showFilesExternalLinkFeaturePopup();
		}
	}

	findFile()
	{
		this.closeFileSelector();
		if(this._isFilesExternalLinkEnabled)
		{
			this.initDiskUF();
			BX.fireEvent(this._fileSelectorBitrix, 'click');
		}
		else
		{
			this.showFilesExternalLinkFeaturePopup();
		}
	}

	getLoader()
	{
		if(!this.loader)
		{
			this.loader = new BX.Loader(
				{
					size: 50
				});
		}

		return this.loader;
	}

	showLoader(node)
	{
		if(node && !this.getLoader().isShown())
		{
			this.getLoader().show(node);
		}
	}

	hideLoader()
	{
		if(this.getLoader().isShown())
		{
			this.getLoader().hide();
		}
	}

	initDiskUF()
	{
		if(this.isDiskFileUploaderInited || !this._isFilesEnabled)
		{
			return;
		}
		this.isDiskFileUploaderInited = true;
		BX.addCustomEvent(this._fileUploadZone, 'OnFileUploadSuccess', this.OnFileUploadSuccess.bind(this));
		BX.addCustomEvent(this._fileUploadZone, 'DiskDLoadFormControllerInit', function(uf)
		{
			uf._onUploadProgress = function()
			{
				this.showLoader(this._fileSelectorButton.parentNode.parentNode);
			}.bind(this);
		}.bind(this));

		BX.Disk.UF.add({
			UID: this._fileUploadZone.getAttribute('data-node-id'),
			controlName: this._fileUploadLabel.getAttribute('for'),
			hideSelectDialog: false,
			urlSelect: this._diskUrls.urlSelect,
			urlRenameFile: this._diskUrls.urlRenameFile,
			urlDeleteFile: this._diskUrls.urlDeleteFile,
			urlUpload: this._diskUrls.urlUpload
		});

		BX.onCustomEvent(
			this._fileUploadZone,
			'DiskLoadFormController',
			['show']
		);
	}

	OnFileUploadSuccess(fileResult, uf, file, uploaderFile)
	{
		this.hideLoader();
		const diskFileId = parseInt(fileResult.element_id.replace('n', ''));
		const fileName = fileResult.element_name;
		this.pasteFileUrl(diskFileId, fileName);
	}

	pasteFileUrl(diskFileId, fileName)
	{
		this.showLoader(this._fileSelectorButton.parentNode.parentNode);
		BX.ajax.runAction('disk.file.generateExternalLink', {
			analyticsLabel: 'crmTimelineSmsEditorGetFilePublicUrl',
			data: {
				fileId: diskFileId
			}
		}).then(function(response)
		{
			this.hideLoader();
			if(response.data.externalLink && response.data.externalLink.link)
			{
				this._input.focus();
				this._input.value = this._input.value + ' ' + fileName + ' ' + response.data.externalLink.link;
				this.setMessageLengthCounter();
				this._source = 'file';
			}
		}.bind(this)).catch(function(response)
		{
			console.error(response.errors.pop().message);
		});
	}

	getFeaturePopup(content)
	{
		if(this.featurePopup != null)
		{
			return this.featurePopup;
		}
		this.featurePopup = new BX.PopupWindow('bx-popup-crm-sms-editor-feature-popup', null, {
			zIndex: 200,
			autoHide: true,
			closeByEsc: true,
			closeIcon: true,
			overlay : true,
			events : {
				onPopupDestroy : function()
				{
					this.featurePopup = null;
				}.bind(this)
			},
			content : content,
			contentColor: 'white'
		});

		return this.featurePopup;
	}

	showFilesExternalLinkFeaturePopup()
	{
		this.getFeaturePopup(this._fileExternalLinkDisabledContent).show();
	}

	onTemplateHintIconClick()
	{
		if (this._senderId === 'ednaru')
		{
			top.BX.Helper.show("redirect=detail&code=14214014");
		}
	}

	showTemplateSelectDropdown(items)
	{
		const menuItems = [];
		if (BX.Type.isArray(items))
		{
			if (items.length)
			{
				items.forEach(function(item)
				{
					menuItems.push({
						value: item.ID,
						text: item.TITLE,
						onclick: this._selectTemplateHandler
					})
				}.bind(this));

				BX.PopupMenu.show({
					id: this._templateSelectorMenuId,
					bindElement: this._templateSelectorNode,
					items: menuItems,
					angle: false,
					width: this._templateSelectorNode.offsetWidth,
				});
			}
		}
		else if (this._senderId)
		{
			const loaderMenuId = this._templateSelectorMenuId + 'loader';
			const loaderMenuLoaderId = this._templateSelectorMenuId + 'loader';

			BX.PopupMenu.show({
				id: loaderMenuId,
				bindElement: this._templateSelectorNode,
				items: [{
					html: '<div id="' + loaderMenuLoaderId + '"></div>',
				}],
				angle: false,
				width: this._templateSelectorNode.offsetWidth,
				height: 60,
				events: {
					onDestroy: function() {
						this.hideLoader();
					}.bind(this)
				}
			});
			this.showLoader(BX(loaderMenuLoaderId));

			if (!this._isRequestRunning)
			{
				this._isRequestRunning = true;
				const senderId = this._senderId;
				BX.ajax.runAction(
					'messageservice.Sender.getTemplates',
					{
						data: {
							id: senderId,
							context: {
								module: 'crm',
								entityTypeId: this._manager._ownerTypeId,
								entityId: this._manager._ownerId,
							}
						}
					}
				)
					.then(function(response)
					{
						this._isRequestRunning = false;
						const sender = this._senders.find(function (sender) {
							return (sender.id === senderId);
						}.bind(this));

						if (sender)
						{
							sender.templates = response.data.templates;
							this.toggleTemplateSelectAvailability();
							if (BX.PopupMenu.getMenuById(loaderMenuId))
							{
								BX.PopupMenu.getMenuById(loaderMenuId).close();
								this.showTemplateSelectDropdown(sender.templates);
							}
						}
					}.bind(this))
					.catch(function(response)
					{
						this._isRequestRunning = false;
						if (BX.PopupMenu.getMenuById(loaderMenuId))
						{
							if (response && response.errors && response.errors[0] && response.errors[0].message)
							{
								alert(response.errors[0].message);
							}
							BX.PopupMenu.getMenuById(loaderMenuId).close();
						}
					}.bind(this))
				;
			}
		}

	}

	getSelectedSender()
	{
		return this._senders.find(function(sender) {
			return (sender.id === this._senderId);
		}.bind(this));
	}

	getSelectedTemplate()
	{
		const sender = this.getSelectedSender();
		if (!this._templateId || !sender || !sender.templates)
		{
			return null;
		}

		const template = sender.templates.find(function (template) {
			return template.ID == this._templateId;
		}.bind(this));

		return template ? template : null;
	}

	onTemplateSelectClick()
	{
		const sender = this.getSelectedSender();

		if (sender)
		{
			this.showTemplateSelectDropdown(sender.templates);
		}
	}

	onSelectTemplate(e, item)
	{
		this._templateId = item.value;
		this.applySelectedTemplate();
		this.toggleSaveButton();
		const menu = BX.PopupMenu.getMenuById(this._templateSelectorMenuId);
		if (menu)
		{
			menu.close();
		}
	}

	toggleTemplateSelectAvailability()
	{
		const sender = this.getSelectedSender();
		if (sender && BX.Type.isArray(sender.templates) && !sender.templates.length)
		{
			BX.addClass(this._templateSelectorNode, 'ui-ctl-disabled');
			this._templateTemplateTitleNode.textContent = BX.message('CRM_TIMELINE_SMS_TEMPLATES_NOT_FOUND');
		}
		else
		{
			BX.removeClass(this._templateSelectorNode, 'ui-ctl-disabled');
			this.applySelectedTemplate();
		}
	}

	applySelectedTemplate()
	{
		const sender = this.getSelectedSender();
		if (!this._templateId || !sender || !sender.templates)
		{
			this.hideNode(this._templatePreviewNode);
			this._templateTemplateTitleNode.textContent = '';
		}
		else
		{
			const template = this.getSelectedTemplate();
			if (template)
			{
				const preview = BX.Text.encode(template.PREVIEW).replace(/\n/g, '<br>');

				this.showNode(this._templatePreviewNode);
				this._templatePreviewNode.innerHTML = preview;
				this._templateTemplateTitleNode.textContent = template.TITLE;
			}
			else
			{
				this.hideNode(this._templatePreviewNode);
				this._templateTemplateTitleNode.textContent = '';
			}
		}
	}

	static create(id, settings)
	{
		const self = new Sms();
		self.initialize(id, settings);
		Sms.items[self.getId()] = self;
		return self;
	}

	static items = {};
}
