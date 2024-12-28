;(function(){

	BX.namespace('BX.Crm.DocumentViewComponent');

	BX.Crm.DocumentView = function()
	{
		this.pdfUrl = '';
		this.printUrl = '';
		this.downloadUrl = '';
		this.editTemplateUrl = '';
		this.editDocumentUrl = '';
		this.title = '';
		this.values = {};
		this.documentId = null;
		this.progress = false;
		this.loader = null;
		this.changeStampsEnabled = false;
		this.changeStampsDisabledReason = '';
		this.changeQrCodeEnabled = false;
		this.changeQrCodeEnabledDisabledReason = '';
		this.myCompanyEditUrl = '';
		this.isTransformationError = false;
		this.transformationErrorMessage = '';
		this.transformationErrorCode = 0;
		this.isDisplayTransformationErrors = true;
		this.viewer = null;
		this.publicUrl = null;
		this.sendedToSign = false;
		this.signingInfoHelperSliderCode = null;
	};

	BX.Crm.DocumentView.init = function(options)
	{
		this.transformationErrorNode = document.getElementById('crm__document-view--transform-error');
		this.previewNode = document.getElementById('crm__document-view--node');
		this.documentId = options.id;
		this.publicUrl = options.publicUrl;

		// set this prop only on init, dont allow override from options later
		if (BX.type.isBoolean(options.isDisplayTransformationErrors))
		{
			this.isDisplayTransformationErrors = options.isDisplayTransformationErrors;
		}
		// eslint-disable-next-line no-param-reassign
		delete options.isDisplayTransformationErrors;

		options.previewNode = this.previewNode;
		options.transformationErrorNode = this.transformationErrorNode;
		options.onReady = BX.proxy(function(options)
		{
			this.showError(false);
			this.applyOptions(options);
			// if transformation errors display enabled, will display error message if there is no pdfUrl
			this.showPdf();
		}, this);
		this.preview = new BX.DocumentGenerator.DocumentPreview(options);
		this.applyOptions(options);
		this.initButtons();
		this.initEvents();
		if(!options.pdfUrl && !this.isTransformationError)
		{
			this.initPreviewMessage(2);
		}
		if (options.pdfUrl)
		{
			var viewer = this.getViewer();
			if (viewer)
			{
				viewer.setPdfSource(options.pdfUrl);
			}
			this.showPdf();
		}
		if(options.documentUrl)
		{
			window.history.replaceState({}, "", options.documentUrl);
		}
		BX.Crm.DocumentView.saveDocumentToSliderData();
	};

	BX.Crm.DocumentView.applyOptions = function(options)
	{
		if(options.pdfUrl)
		{
			this.pdfUrl = options.pdfUrl;
		}
		if(options.printUrl)
		{
			this.printUrl = options.printUrl;
		}
		if(options.downloadUrl)
		{
			this.downloadUrl = options.downloadUrl;
		}
		if(options.editTemplateUrl)
		{
			this.editTemplateUrl = options.editTemplateUrl;
		}
		if(options.editDocumentUrl)
		{
			this.editDocumentUrl = options.editDocumentUrl;
		}
		if(options.values)
		{
			this.values = options.values;
		}
		if(options.emailDiskFile)
		{
			var selector = this.getChannelSelector();
			if (selector)
			{
				selector.setFiles([options.emailDiskFile]);
			}
		}
		if(options.storageTypeID)
		{
			this.storageTypeID = options.storageTypeID;
		}
		if(options.title)
		{
			this.title = options.title;
		}
		if(BX.type.isBoolean(options.isTransformationError))
		{
			this.isTransformationError = options.isTransformationError;
		}
		if(BX.type.isBoolean(options.changeStampsEnabled))
		{
			this.changeStampsEnabled = options.changeStampsEnabled;
		}
		if(options.changeStampsDisabledReason)
		{
			this.changeStampsDisabledReason = options.changeStampsDisabledReason;
		}
		if(BX.type.isBoolean(options.changeQrCodeEnabled))
		{
			this.changeQrCodeEnabled = options.changeQrCodeEnabled;
		}
		if(options.changeQrCodeDisabledReason)
		{
			this.changeQrCodeDisabledReason = options.changeQrCodeDisabledReason;
		}
		if(options.myCompanyEditUrl)
		{
			this.myCompanyEditUrl = options.myCompanyEditUrl;
		}
		if(BX.type.isString(options.transformationErrorMessage))
		{
			this.transformationErrorMessage = options.transformationErrorMessage;
		}
		else
		{
			this.transformationErrorMessage = '';
		}
		if(BX.type.isNumber(options.transformationErrorCode))
		{
			this.transformationErrorCode = options.transformationErrorCode;
		}
		if (BX.type.isBoolean(options.isSigningEnabledInCurrentTariff))
		{
			this.isSigningEnabledInCurrentTariff = options.isSigningEnabledInCurrentTariff;
		}

		if (BX.type.isString(options.signingInfoHelperSliderCode))
		{
			this.signingInfoHelperSliderCode =
				options.signingInfoHelperSliderCode.length > 0
				? options.signingInfoHelperSliderCode
				: null
			;
		}

		this.preview.applyOptions(options);
	};
	BX.Crm.DocumentView.closeSlider = function()
	{
		var slider = BX.SidePanel.Instance.getTopSlider();
		if(slider)
		{
			slider.close();
		}
		if(BX.PopupMenu.getCurrentMenu())
		{
			BX.PopupMenu.getCurrentMenu().popupWindow.close();
		}
	};

	BX.Crm.DocumentView.saveDocumentToSliderData = function()
	{
		var slider = BX.SidePanel.Instance.getTopSlider();
		if(slider)
		{
			slider.getData().set('document', {
				id: Number(this.documentId),
				title: document.getElementById('pagetitle') ? document.getElementById('pagetitle').innerText : '',
				detailUrl: BX.Uri.removeParam(this.editDocumentUrl || '', ['mode']),
				isWithStamps: document.getElementById('crm-document-stamp').checked,
			})
		}
	}

	BX.Crm.DocumentView.initButtons = function()
	{
		const stampInput = document.getElementById('crm-document-stamp');
		if (stampInput)
		{
			if (stampInput.parentNode)
			{
				BX.bind(stampInput.parentNode, 'click', BX.proxy(this.showChangeStampsDisabledMessage, this));
			}
			BX.bind(document.getElementById('crm-document-stamp'), 'change', BX.proxy(this.onChangeStamps, this));
		}

		var qrCodeInput = document.getElementById('crm-document-qr');
		if (qrCodeInput)
		{
			if (qrCodeInput.parentNode)
			{
				BX.Event.bind(qrCodeInput.parentNode, 'click', this.handleQrCodeInputClick.bind(this));
			}
			BX.Event.bind(qrCodeInput, 'change', this.handleQrCodeInputChange.bind(this));
		}
		BX.bind(document.getElementById('crm-document-edit-template'), 'click', BX.proxy(function(event)
		{
			if(this.editTemplateUrl)
			{
				if(BX.SidePanel)
				{
					BX.SidePanel.Instance.open(this.editTemplateUrl, {width: 845});
				}
				else
				{
					top.location.href = this.editTemplateUrl;
				}
			}
			event.preventDefault();
		}, this));
		BX.bind(document.getElementById('crm-document-print'), 'click', BX.proxy(function()
		{
			if(this.printUrl)
			{
				window.open(this.printUrl, '_blank');
			}
			else
			{
				this.showError(BX.message('CRM_DOCUMENT_VIEW_TRANSFORMATION_PROGRESS'));
			}
		}, this));

		var downloadButton = BX.UI.ButtonManager.createFromNode(document.getElementById('crm-document-download'));
		if (downloadButton)
		{
			// fix popup menu position
			downloadButton.getMenuButton().bindEvent('click', function () {
				var popup = downloadButton.menuWindow.popupWindow;
				if (popup) {
					popup.setWidth(BX.pos(downloadButton.getContainer()).width);
					popup.setOffset({ offsetLeft: BX.pos(popup.bindElement).width - 20 });
				}
			});
			// set action to main button
			BX.Crm.DocumentView.rebindDownloadButtonClick(downloadButton);
			// set menu
			downloadButton.setMenu({
				closeByEsc: true,
				angle: true,
				autoHide: true,
				items: [{
					text: 'PDF',
					onclick: function() {
						BX.Crm.DocumentView.downloadPdf(downloadButton);
						BX.userOptions.save('crm.document.view', 'download_button', 'format', 'pdf', false);
					}
				}, {
					text: 'DOCX',
					onclick: function() {
						BX.Crm.DocumentView.downloadDoc(downloadButton);
						BX.userOptions.save('crm.document.view', 'download_button', 'format', 'doc', false);
					}
				}]
			});
		}

		BX.bind(document.getElementById('crm-document-edit-document'), 'click', BX.proxy(function()
		{
			if(BX.SidePanel)
			{
				var sliderUrl = '';
				var curSlider = BX.SidePanel.Instance.getSliderByWindow(window);
				if(curSlider)
				{
					sliderUrl = curSlider.getUrl();
				}
				BX.SidePanel.Instance.open(this.editDocumentUrl, {width: 500, mode: 'edit', sliderUrl: sliderUrl});
			}
			else
			{
				top.location.href = this.editDocumentUrl;
			}
		}, this));

		BX.bind(document.getElementById('crm-document-sign'), 'click', BX.proxy(function(e)
		{
			if (this.sendedToSign)
			{
				this.showError(BX.message('CRM_DOCUMENT_VIEW_SIGN_CLICKED'));

				return;
			}

			if (!this.isSigningEnabledInCurrentTariff)
			{
				BX.UI.InfoHelper.show(this.signingInfoHelperSliderCode);
				return;
			}

			this.sendedToSign = true;
			if (!this.rightPanelLoader)
			{
				this.rightPanelLoader = new BX.Loader({size: 100, offset: {left: "33%", top: "-10%"}})
			}
			this.rightPanelLoader.show(e.currentTarget.closest('.--company-information'));

			return new Promise(function(resolve, reject) {
				var convertDealAndStartSign = (function (usePrevious)
				{
					BX.ajax.runAction('crm.api.integration.sign.convertDeal', {
						data: {
							documentId: Number(this.documentId),
							usePrevious: !usePrevious ? 0 : 1,
						}
					}).then(BX.proxy(function(response)
					{
						if (typeof response.data.SMART_DOCUMENT !== 'undefined')
						{
							BX.SidePanel.Instance.open(
								'/sign/doc/0/?docId=' + response.data.SMART_DOCUMENT + '&stepId=changePartner&noRedirect=Y',
								{
									width: 1250,
								},
							);
							this.sendedToSign = false;
							this.rightPanelLoader.hide();
							return;
						}
						this.closeSlider();
					}, this),
						BX.proxy(function(response)
					{
						this.sendedToSign = false;
						this.rightPanelLoader.hide();
						this.showError(response.errors.pop().message);
					}, this));
				}).bind(this);

				BX.ajax.runAction('crm.api.integration.sign.getLinkedBlank', {
					data: {
						documentId: Number(this.documentId)
					}
				}).then(function(response) {
					if (response.data.ID > 0) {
						this.showMessage(BX.Loc.getMessage('CRM_DOCUMENT_VIEW_SIGN_DO_USE_PREVIOUS_MSG_MSGVER_3',
							{
								'%TITLE%': '<b>' + (BX.util.htmlspecialchars(response.data.TITLE || '')) + '</b>',
								'%INITIATOR%': '<b>' + (BX.util.htmlspecialchars(response.data.INITIATOR || '')) + '</b>',
							})
							, [
								new BX.PopupWindowButton({
									text: BX.message('CRM_DOCUMENT_VIEW_SIGN_OLD_BUTTON_MSGVER_2'),
									className: "ui-btn ui-btn-md ui-btn-primary",
									events: {
										click: function ()
										{
											convertDealAndStartSign(true);
											this.popupWindow.destroy();
										}
									}
								}),
								new BX.PopupWindowButton({
									text: BX.message('CRM_DOCUMENT_VIEW_SIGN_NEW_BUTTON_MSGVER_3'),
									className: "ui-btn ui-btn-md ui-btn-info",
									events: {
										click: function ()
										{
											convertDealAndStartSign(false);
											this.popupWindow.destroy();
										}
									}
								})
						], BX.message('CRM_DOCUMENT_VIEW_SIGN_POPUP_TITLE_MSGVER_2'), (function () {
							this.sendedToSign = false;
							this.rightPanelLoader.hide();
						}).bind(this));
					} else {
						convertDealAndStartSign(false);
					}
				}.bind(this)).catch(function (){});
			}.bind(this))
		}, this));

		BX.Event.EventEmitter.subscribe('BX.Crm.ChannelSelector.List:getLink', function() {
			if (this.publicUrl)
			{
				return Promise.resolve(this.publicUrl);
			}
			else
			{
				return new Promise(function(resolve, reject) {
					BX.ajax.runAction('crm.documentgenerator.document.enablePublicUrl', {
						data: {
							status: 1,
							id: Number(this.documentId)
						}
					}).then(BX.proxy(function(response)
					{
						this.publicUrl = response.data.publicUrl;
						resolve(response.data.publicUrl);
					}, this), BX.proxy(function(response)
					{
						reject(response.errors.pop().message);
					}, this));
				}.bind(this))
			}
		}.bind(this));

		this.initBaasButtons();
	};

	/**
	 * @private
	 */
	BX.Crm.DocumentView.initBaasButtons = function()
	{
		const featureCode = 'documentgenerator_fast_transform';

		const speedupButtonInSidebar = document.getElementById('crm-document-speedup-in-sidebar');
		const speedupButtonInPlaceholder = document.getElementById('crm-document-speedup-in-placeholder');

		for (const button of [speedupButtonInPlaceholder, speedupButtonInSidebar])
		{
			BX.Event.bind(button, 'click', () => {
				BX.Runtime.loadExtension('baas.store').then(() => {
					BX.Baas.Store.ServiceWidget.getInstanceByCode(featureCode)
						.bind(button)
						.toggle()
					;
				});
			});
		}

		top.BX.Event.EventEmitter.subscribe('onPullEvent-baas', (event) => {
			const [command, params] = event.getData();

			if (command !== 'updateService' || params.service.code !== featureCode)
			{
				return;
			}

			const captionContainer = document.getElementById('crm-document-speedup-value');

			if (params.service.isActive)
			{
				BX.Dom.hide(speedupButtonInPlaceholder);
				captionContainer.textContent = BX.Loc.getMessage('CRM_DOCUMENT_VIEW_CURRENT_SPEED_VALUE_FAST');
			}
			else
			{
				BX.Dom.show(speedupButtonInPlaceholder);
				captionContainer.textContent = BX.Loc.getMessage('CRM_DOCUMENT_VIEW_CURRENT_SPEED_VALUE_REGULAR');
			}
		});
	};

	BX.Crm.DocumentView.showMessage = function(content, buttons, title, onclose)
	{
		title = title || '';
		if (typeof(buttons) === "undefined" || typeof(buttons) === "object" && buttons.length <= 0)
		{
			buttons = [new BX.PopupWindowButton({
				text : BX.message('CRM_DOCUMENT_VIEW_SIGN_POPUP_CLOSE'),
				className : "ui-btn ui-btn-md ui-btn-default",
				events : { click : function(e) { this.popupWindow.close(); BX.PreventDefault(e) } }
			})];
		}
		if(this.popupConfirm != null)
		{
			this.popupConfirm.destroy();
		}
		if(!BX.type.isDomNode(content))
		{
			var node = document.createElement('div');
			node.innerHTML = content;
			content = node;
		}
		if(!BX.type.isArray(content))
		{
			content = [content];
		}
		this.popupConfirm = new BX.PopupWindow('bx-popup-documentgenerator-popup', null, {
			zIndex: 200,
			autoHide: true,
			closeByEsc: true,
			buttons: buttons,
			closeIcon: true,
			overlay : true,
			events : {
				onPopupClose : function()
				{
					if(BX.type.isFunction(onclose))
					{
						onclose();
					}
					this.destroy();
				}, onPopupDestroy : BX.delegate(function()
				{
					this.popupConfirm = null;
				}, this)},
			content : BX.create('div',{
				attrs:{className:'bx-popup-documentgenerator-popup-content-text'},
				children : content,
			}),
			titleBar: title,
			className : 'bx-popup-documentgenerator-popup',
			maxWidth: 470
		});
		this.popupConfirm.show();
	};

	BX.Crm.DocumentView.showError = function(text)
	{
		if(text === false)
		{
			if(this.transformationErrorMessage.length > 0)
			{
				this.transformationErrorMessage = text;
			}
		}
		var errorNode = document.getElementById('crm-document-view-error');
		if(text === false && errorNode)
		{
			BX.hide(errorNode);
		}
		if(!text)
		{
			return;
		}
		var message = '';
		if(BX.Type.isArray(text))
		{
			message = text.map(function(error){return error.message;}).join("\n");
		}
		else
		{
			message = text;
		}
		if (errorNode)
		{
			document.getElementById('crm-document-view-error-message').innerText = message;
			BX.show(errorNode);
		}
	};

	BX.Crm.DocumentView.showChangeStampsDisabledMessage = function(event)
	{
		if (this.changeStampsEnabled)
		{
			return;
		}
		event.preventDefault();
		if (this.changeStampsDisabledReason)
		{
			BX.Crm.DocumentView.showPopupNotice(
				this.changeStampsDisabledReason
			);
		}
	};
	BX.Crm.DocumentView.showPopupNotice = function(content) {
		BX.UI.Notification.Center.notify({
			content: content,
		});
	};
	BX.Crm.DocumentView.getChannelSelector = function() {
		var ChannelSelectorList = BX.Reflection.getClass('BX.Crm.ChannelSelector.List');
		if (ChannelSelectorList)
		{
			return ChannelSelectorList.getById('document-channel-selector');
		}

		return null;
	};
	BX.Crm.DocumentView.onChangeStamps = function()
	{
		if(this.changeStampsEnabled)
		{
			this.updateDocument();
		}
	};

	BX.Crm.DocumentView.updateDocument = function()
	{
		if(this.progress)
		{
			return;
		}
		if(!this.editTemplateUrl)
		{
			return;
		}
		this.progress = true;
		this.pdfUrl = '';
		document.getElementById('crm-document-stamp').disabled = true;
		var stampsEnabled = 0;
		if(document.getElementById('crm-document-stamp').checked)
		{
			stampsEnabled = 1;
		}
		var qrCodeInput = document.getElementById('crm-document-qr');
		if (qrCodeInput && !qrCodeInput.checked)
		{
			this.values['PaymentQrCode'] = '';
		}
		else
		{
			this.values['PaymentQrCode'] = 'this.SOURCE.PAYMENT_QR_CODE';
		}
		if(BX.type.isDomNode(this.preview.imageNode))
		{
			BX.hide(this.preview.imageNode);
		}
		BX.hide(document.getElementById('crm-document-pdf'));
		BX.hide(this.transformationErrorNode);
		this.initPreviewMessage(1);
		this.preview.imageUrl = null;
		BX.ajax.runAction('crm.documentgenerator.document.update', {
			data: {
				stampsEnabled: stampsEnabled,
				id: this.documentId,
				values: this.values
			}
		}).then(BX.proxy(function(response)
		{
			this.initPreviewMessage(2);
			this.progress = false;
			document.getElementById('crm-document-stamp').disabled = false;
			this.applyOptions(response.data.document);
			var title = document.getElementById('pagetitle');
			if(title && response.data.document && response.data.document.title)
			{
				title.innerText = response.data.document.title;
			}
			BX.Crm.DocumentView.saveDocumentToSliderData();
		}, this), BX.proxy(function(response)
		{
			if(response.data && response.data.document)
			{
				this.applyOptions(response.data.document);
			}
			this.progress = false;
			document.getElementById('crm-document-stamp').disabled = false;
			if(response.data && response.data.document && response.data.document.isTransformationError)
			{
				BX.hide(this.previewNode);
				BX.show(this.transformationErrorNode);
			}
			else
			{
				this.initPreviewMessage(0);
			}
			this.showError(response.errors.pop().message);
		}, this));
	};

	BX.Crm.DocumentView.initEvents = function()
	{
		BX.addCustomEvent('SidePanel.Slider:onMessage', BX.proxy(function(message)
		{
			if(message.getEventId() === 'crm-document-edit')
			{
				this.applyOptions(message.getData());
				this.updateDocument();
			}
		}, this));
	};

	BX.Crm.DocumentView.initPreviewMessage = function(step)
	{
		if(step !== 2 && step !== 0)
		{
			step = 1;
		}

		BX.show(this.previewNode);
		if(step === 0)
		{
			BX.hide(document.getElementById('crm__document-view--node-message'));
			BX.hide(document.getElementById('crm__document-view--node-detail'));
		}
		else if(step === 1)
		{
			document.getElementById('crm__document-view--node-message').innerText = BX.message('CRM_DOCUMENT_VIEW_PREVIEW_GENERATION_MESSAGE');
			BX.show(document.getElementById('crm__document-view--node-message'));
			BX.hide(document.getElementById('crm__document-view--node-detail'));
			this.startProgressBar();
		}
		else
		{
			document.getElementById('crm__document-view--node-message').innerText = BX.message('CRM_DOCUMENT_VIEW_PREVIEW_MESSAGE_PREPARE_MSGVER_1');
			document.getElementById('crm__document-view--node-detail').innerText = BX.message('CRM_DOCUMENT_VIEW_PREVIEW_MESSAGE_READY_MSGVER_1');
			BX.show(document.getElementById('crm__document-view--node-message'));
			BX.show(document.getElementById('crm__document-view--node-detail'));
			this.startProgressBar();
		}
	};

	BX.Crm.DocumentView.startProgressBar = function()
	{
		if (!this.loader)
		{
			this.loader = new BX.UI.ProgressBar({
				color: BX.UI.ProgressBar.Color.PRIMARY,
				size: 10,
				maxValue: 100,
				value: 30,
				infiniteLoading: true,
			});

			this.loader.renderTo(BX('docs-progress-bar'));
		}
	};

	BX.Crm.DocumentView.getViewer = function()
	{
		if (!this.viewer)
		{
			const pdfNode = document.getElementById('crm-document-pdf');
			if (!pdfNode)
			{
				return null;
			}
			this.viewer = new BX.UI.Viewer.SingleDocumentController({baseContainer: pdfNode, stretch: true});
			this.viewer.setItems([BX.UI.Viewer.buildItemByNode(pdfNode)]);
		}

		return this.viewer;
	};

	BX.Crm.DocumentView.showPdf = function()
	{
		BX.show(document.getElementById('crm-document-pdf'));
		if (this.pdfUrl)
		{
			var viewer = BX.Crm.DocumentView.getViewer();
			if (viewer)
			{
				viewer.setPdfSource(this.pdfUrl);
				viewer.setScale(1.2).open(0);
			}
		}
		else if (this.isDisplayTransformationErrors)
		{
			let message;
			if (this.transformationErrorMessage)
			{
				message = this.transformationErrorMessage;
			}
			else
			{
				message = BX.Loc.getMessage('CRM_DOCUMENT_VIEW_COMPONENT_PROCESSED_NO_PDF_ERROR');
			}

			this.showError(message);
		}
	};
	BX.Crm.DocumentView.handleQrCodeInputClick = function(event)
	{
		if (this.changeQrCodeEnabled)
		{
			return;
		}
		if (this.changeQrCodeDisabledReason)
		{
			BX.Crm.DocumentView.showPopupNotice(
				this.changeQrCodeDisabledReason
			);
		}
	};
	BX.Crm.DocumentView.handleQrCodeInputChange = function(event)
	{
		if (this.changeQrCodeEnabled)
		{
			this.updateDocument();
		}
	}

	BX.Crm.DocumentEdit = {

	};

	BX.Crm.DocumentEdit.init = function()
	{
		BX.bind(document.getElementById('crm-document-edit-spoiler'), 'click', function()
		{
			BX.show(document.getElementById('crm-document-edit-all'));
			BX.hide(document.getElementById('crm-document-edit-spoiler'));
		});
		this.initForm();
	};

	BX.Crm.DocumentEdit.initForm = function()
	{
		BX.bind(document.getElementById('crm-document-edit-form'), 'submit', BX.proxy(this.sendForm, this));
		BX.bind(document.getElementById('crm-document-edit-save'), 'click', BX.proxy(this.sendForm, this));
		BX.bind(document.getElementById('crm-document-edit-cancel'), 'click', BX.proxy(this.closeSlider, this));
		BX.bindDelegate(document.getElementById('crm-document-edit-form'), 'change', {className: 'crm-document-edit-select'}, BX.proxy(this.refillValues, this));
	};

	BX.Crm.DocumentEdit.sendForm = function(event)
	{
		var form = document.getElementById('crm-document-edit-form');
		var error = '';
		var values = {};
		for(var i = 0; i < form.length; i++)
		{
			if(form.elements[i].name.indexOf('values') !== 0)
			{
				continue;
			}
			if(form.elements[i].required && form.elements[i].value.length <= 0)
			{
				error += '<br />' + BX.message('CRM_DOCUMENT_VIEW_COMPONENT_EDIT_FIELD_ERROR').replace('\#FIELD\#', form.elements[i].previousSibling.innerText);
			}
			var name = form.elements[i].name.slice(7, -1);
			values[name] = form.elements[i].value;
		}
		if(error.length <= 0)
		{
			if(BX.SidePanel)
			{
				event.preventDefault();
				var editSlider = false;
				var curSlider = BX.SidePanel.Instance.getSliderByWindow(window);
				if(curSlider.options.mode === 'edit' && BX.type.isNotEmptyString(curSlider.options.sliderUrl))
				{
					editSlider = BX.SidePanel.Instance.getSlider(curSlider.options.sliderUrl);
				}
				if(editSlider)
				{
					BX.SidePanel.Instance.postMessage(curSlider, 'crm-document-edit', {values: values});
					this.closeSlider();
				}
				else
				{
					var url = curSlider.getUrl();
					url = BX.util.add_url_param(url, this.collectFormData());
					curSlider.close();
					BX.SidePanel.Instance.open(url, {width: 980, requestMethod: 'post'});
				}
			}
			else
			{
				// do nothing
			}
		}
		else
		{
			this.showError(error);
			event.preventDefault();
		}
	};

	BX.Crm.DocumentEdit.collectFormData = function()
	{
		var form = document.getElementById('crm-document-edit-form');
		var data = {};
		for(var i = 0; i < form.length; i++)
		{
			if(form.elements[i].getAttribute('bx-default') === form.elements[i].value)
			{
				continue;
			}
			data[form.elements[i].name] = form.elements[i].value;
		}
		if(data.documentId && data.documentId > 0)
		{
			data.id = data.documentId;
		}
		else if(data.templateId && data.templateId > 0)
		{
			data.id = data.templateId;
		}

		return data;
	};

	BX.Crm.DocumentEdit.closeSlider = function()
	{
		if(BX.SidePanel)
		{
			var curSlider = BX.SidePanel.Instance.getSliderByWindow(window);
			if(curSlider)
			{
				curSlider.close();
			}
		}
	};

	BX.Crm.DocumentEdit.showError = function(error)
	{
		document.getElementById('crm-document-edit-error').innerHTML = error;
		BX.show(document.getElementById('crm-document-edit-error'));
	};

	BX.Crm.DocumentEdit.refillValues = function()
	{
		var entityName = '';
		var data = this.collectFormData();
		if(data.documentId > 0)
		{
			entityName = 'document';
		}
		else
		{
			entityName = 'template';
		}
		BX.ajax.runAction('crm.documentgenerator.' + entityName + '.getFields', {data: data}).then(function(response)
		{
			var form = document.getElementById('crm-document-edit-form');
			var result = response.data[entityName + 'Fields'];
			for(var name in result)
			{
				if(result.hasOwnProperty(name))
				{
					if(typeof result[name].value === 'object' && BX.type.isNotEmptyObject(result[name].value))
					{
						var select = document.getElementById('field-' + name);
						if(!select)
						{
							var group = result[name].group;
							if(BX.type.isArray(result[name].group))
							{
								group = result[name].group[result[name].group.length - 1];
							}
							var groupNode = document.getElementById('crm-document-edit-group-' + group);
							if(groupNode)
							{
								var header = BX.findChild(groupNode, {tag: 'h3'});
								if(header)
								{
									BX.prepend(BX.create('div', {
										props: {className: 'crm-document-edit-item'},
										children: [
											BX.create('label', {
												props: {className: 'crm-document-edit-label'},
												attrs: {for: 'field-' + name},
												text: result[name].title
											}),
											BX.create('select', {
												props: {className: 'crm-document-edit-select'},
												attrs: {name: 'values[' + name + ']', id: 'field-' + name}
											})
										]
									}), BX.nextSibling(header));
								}
							}
						}
					}
				}
			}
			for(var i = 0; i < form.length; i++)
			{
				var placeholder = form.elements[i].name;
				var input = form.elements[i];
				if(form.elements[i].name.indexOf('values') !== 0)
				{
					if(input.tagName !== 'SELECT')
					{
						continue;
					}
				}
				else
				{
					placeholder = form.elements[i].name.slice(7, -1);
				}
				input.value = '';
				if(input.tagName === 'SELECT')
				{
					BX.cleanNode(input);
					BX.hide(input.parentNode);
				}
				if(result.hasOwnProperty(placeholder))
				{
					if((BX.type.isString(result[placeholder].value) || BX.type.isNumber(result[placeholder].value) || BX.type.isNull(result[placeholder].value)) && input.tagName === 'INPUT' || input.tagName === 'TEXTAREA')
					{
						input.value = result[placeholder].value;
						if(result[placeholder].hasOwnProperty('default'))
						{
							input.setAttribute('bx-default', (result[placeholder].default ? result[placeholder].default : ''));
						}
					}
					else if(typeof result[name].value === 'object' && BX.type.isNotEmptyObject(result[placeholder].value) && input.tagName === 'SELECT')
					{
						var option, attrs;
						for(option in result[placeholder].value)
						{
							if(result[placeholder].value.hasOwnProperty(option))
							{
								attrs = {
									value: result[placeholder]['value'][option]['value']
								};
								if(result[placeholder]['value'][option]['selected'] === true)
								{
									attrs['selected'] = 'selected';
								}
								input.appendChild(BX.create('option', {
									attrs: attrs,
									text: result[placeholder]['value'][option]['title']
								}));
							}
						}
						if(attrs)
						{
							BX.show(input.parentNode);
						}
					}
				}
			}
		}, BX.proxy(function(response)
		{
			this.showError(response.errors.pop().message)
		}, this));
	};

	BX.Crm.DocumentView.downloadPdf = function(downloadButton)
	{
		if (this.pdfUrl)
		{
			window.open(this.pdfUrl,'_blank');

			BX.addClass(downloadButton.getContainer(), "crm__document-view--btn-icon-pdf");
			BX.removeClass(downloadButton.getContainer(), "crm__document-view--btn-icon-doc");
			BX.Crm.DocumentView.rebindDownloadButtonClick(downloadButton);
		}
		else if (this.preview.imageUrl)
		{
			this.showError(BX.message('CRM_DOCUMENT_VIEW_TRANSFORMATION_NO_PDF_ERROR'));
		}
		else
		{
			this.showError(BX.message('CRM_DOCUMENT_VIEW_TRANSFORMATION_PROGRESS'));
		}
	};

	BX.Crm.DocumentView.downloadDoc = function(downloadButton)
	{
		if (this.downloadUrl && !this.progress)
		{
			window.open(this.downloadUrl, '_blank');

			BX.addClass(downloadButton.getContainer(), "crm__document-view--btn-icon-doc");
			BX.removeClass(downloadButton.getContainer(), "crm__document-view--btn-icon-pdf");
			BX.Crm.DocumentView.rebindDownloadButtonClick(downloadButton);
		}
	};

	BX.Crm.DocumentView.rebindDownloadButtonClick = function(downloadButton)
	{
		var isPdf = BX.hasClass(downloadButton.getContainer(), 'crm__document-view--btn-icon-pdf');

		downloadButton.getMainButton().bindEvent('click', function () {
			if (isPdf)
			{
				BX.Crm.DocumentView.downloadPdf(downloadButton);
			}
			else
			{
				BX.Crm.DocumentView.downloadDoc(downloadButton);
			}
		});
	};

})(window);
