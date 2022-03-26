;(function(){

	BX.namespace('BX.Crm.DocumentViewComponent');

	BX.Crm.DocumentView = function()
	{
		this.pdfUrl = '';
		this.printUrl = '';
		this.downloadUrl = '';
		this.editTemplateUrl = '';
		this.editDocumentUrl = '';
		this.emailCommunication = [];
		this.storageTypeID = 0;
		this.emailDiskFile = 0;
		this.title = '';
		this.sendSmsUrl = '';
		this.values = {};
		this.progress = false;
		this.progressInterval = 0;
		this.changeStampsEnabled = false;
		this.changeStampsDisabledReason = '';
		this.myCompanyEditUrl = '';
		this.isTransformationError = false;
		this.transformationErrorMessage = '';
		this.transformationErrorCode = 0;
	};

	BX.Crm.DocumentView.init = function(options)
	{
		this.transformationErrorNode = BX('docs-preview-transform-error');
		this.previewNode = BX('docs-preview-node');
		this.imageContainer = BX('crm-document-image');
		this.documentId = options.id;
		options.imageContainer = this.imageContainer;
		options.previewNode = this.previewNode;
		options.transformationErrorNode = this.transformationErrorNode;
		options.onReady = BX.proxy(function(options)
		{
			this.applyOptions(options);
			this.showError(false);
			if(this.progressInterval)
			{
				clearInterval(this.progressInterval);
			}
		}, this);
		this.preview = new BX.DocumentGenerator.DocumentPreview(options);
		this.applyOptions(options);
		this.initSendButton();
		this.initButtons();
		this.initEvents();
		if(!options.imageUrl && !this.isTransformationError)
		{
			if(options.pdfUrl)
			{
				this.showPdf();
			}
			else
			{
				this.initPreviewMessage(2);
			}
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
		if(options.emailCommunication)
		{
			this.emailCommunication = options.emailCommunication;
		}
		if(options.emailDiskFile)
		{
			this.emailDiskFile = options.emailDiskFile;
		}
		if(options.storageTypeID)
		{
			this.storageTypeID = options.storageTypeID;
		}
		if(options.title)
		{
			this.title = options.title;
		}
		if(options.sendSmsUrl)
		{
			this.sendSmsUrl = options.sendSmsUrl;
		}
		if(BX.type.isBoolean(options.changeStampsEnabled))
		{
			this.changeStampsEnabled = options.changeStampsEnabled;
		}
		if(BX.type.isBoolean(options.isTransformationError))
		{
			this.isTransformationError = options.isTransformationError;
		}
		if(options.changeStampsDisabledReason)
		{
			this.changeStampsDisabledReason = options.changeStampsDisabledReason;
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
		this.preview.applyOptions(options);
	};

	BX.Crm.DocumentView.initSendButton = function()
	{
		var sendButton = BX('crm-document-send');
		if(this.storageTypeID > 0 || this.sendSmsUrl)
		{
			BX.bind(sendButton, 'click', BX.proxy(function()
			{
				BX.PopupMenu.show('crm-document-send-menu', sendButton, [
						(this.storageTypeID > 0 ? {text: BX.message('CRM_DOCUMENT_VIEW_SEND_EMAIL'), onclick: BX.proxy(this.sendEmail, this)} : null),
						(this.sendSmsUrl ? {text: BX.message('CRM_DOCUMENT_VIEW_SEND_SMS'), onclick: BX.proxy(this.sendSms, this)} : null)
					],
					{
						offsetLeft: 0,
						offsetTop: 0,
						closeByEsc: true
					}
				);
			}, this));
		}
		else
		{
			BX.hide(sendButton);
		}
	};

	BX.Crm.DocumentView.sendEmail = function()
	{
		if(this.emailDiskFile > 0)
		{
			var settings = {
				'subject': this.title,
				'communications': this.emailCommunication,
				'diskfiles': [this.emailDiskFile],
				'storageTypeID': this.storageTypeID
			};
			BX.CrmActivityEditor.items['document-send-email'].addEmail(settings);
		}
		else
		{
			this.showError(BX.message('CRM_DOCUMENT_VIEW_NO_AVAILABLE_FILES'));
		}
	};

	BX.Crm.DocumentView.sendSms = function()
	{
		if(this.sendSmsUrl)
		{
			if(!BX.hasClass(BX('docs-preview-switcher'), 'docs-preview-switcher-off'))
			{
				if(BX.SidePanel)
				{
					BX.SidePanel.Instance.open(this.sendSmsUrl, {width: 443});
				}
				else
				{
					top.location.href = this.sendSmsUrl;
				}
				this.showError(false);
				return;
			}
		}

		this.showError(BX.message('CRM_DOCUMENT_VIEW_SMS_PUBLIC_URL_NECESSARY'));
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
				title: BX('pagetitle') ? BX('pagetitle').innerText : '',
				detailUrl: BX.Uri.removeParam(this.editDocumentUrl || '', ['mode']),
				isWithStamps: BX('crm-document-stamp').checked,
			})
		}
	}

	BX.Crm.DocumentView.initButtons = function()
	{
		BX.bind(BX('crm-document-stamp'), 'click', BX.proxy(this.showChangeStampsDisabledMessage, this));
		BX.bind(BX('crm-document-stamp'), 'change', BX.proxy(this.onChangeStamps, this));
		BX.bind(BX('crm-document-edit-template'), 'click', BX.proxy(function(event)
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
		BX.bind(BX('crm-document-print'), 'click', BX.proxy(function()
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
		BX.bind(BX('crm-document-download-file'), 'click', BX.proxy(function()
		{
			if(this.downloadUrl && !this.progress)
			{
				window.open(this.downloadUrl, '_blank');
			}
		}, this));
		BX.bind(BX('crm-document-download-pdf'), 'click', BX.proxy(function()
		{
			if(this.pdfUrl)
			{
				window.open(this.pdfUrl,'_blank');
			}
			else if (this.preview.imageUrl)
			{
				this.showError(BX.message('CRM_DOCUMENT_VIEW_TRANSFORMATION_NO_PDF_ERROR'));
			}
			else
			{
				this.showError(BX.message('CRM_DOCUMENT_VIEW_TRANSFORMATION_PROGRESS'));
			}
		}, this));
		BX.bind(BX('crm-document-edit-document'), 'click', BX.proxy(function()
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
		BX.bind(BX('docs-preview-switcher'), 'click', BX.proxy(this.enablePublicUrl, this));
		BX.bind(BX('crm-document-public-url-container'), 'click', BX.proxy(this.handleClickInput, this));
		BX.bind(BX('crm-document-copy-public-url'), 'click', BX.proxy(this.copyPublicUrl, this));
		BX.bind(BX('crm-document-show-pdf'), 'click', BX.proxy(this.showPdf, this));
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
		if(text === false)
		{
			BX.hide(BX('crm-document-view-error'));
		}
		if(!text)
		{
			return;
		}
		var message = '';
		if(BX.type.isArray(text))
		{
			message = text.map(function(error){return error.message;}).join("\n");
		}
		else
		{
			message = text;
		}
		BX('crm-document-view-error-message').innerText = message;
		BX.show(BX('crm-document-view-error'));
	};

	BX.Crm.DocumentView.showChangeStampsDisabledMessage = function(event)
	{
		if(this.changeStampsEnabled)
		{
			return;
		}
		event.preventDefault();
		if(this.changeStampsDisabledReason)
		{
			if(this.popupChangeStampsMessage && this.popupChangeStampsMessage.isShown())
			{
				return;
			}
			this.popupChangeStampsMessage = new BX.PopupWindow('crm-popup-change-stamps', BX('crm-document-stamp'), {
				className: 'crm-popup-stamps-disabled',
				bindOptions: {
					position: 'top'
				},
				width: 230,
				offsetLeft: 90,
				darkMode: true,
				angle: true,
				content: this.changeStampsDisabledReason,
				autoHide: true
			});

			this.popupChangeStampsMessage.show();
		}
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
		this.printUrl = '';
		this.emailDiskFile = 0;
		BX('crm-document-stamp').disabled = true;
		var stampsEnabled = 0;
		if(BX('crm-document-stamp').checked)
		{
			stampsEnabled = 1;
		}
		if(BX.type.isDomNode(this.preview.imageNode))
		{
			BX.hide(this.preview.imageNode);
		}
		BX.hide(BX('crm-document-pdf'));
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
			BX('crm-document-stamp').disabled = false;
			this.applyOptions(response.data.document);
			BX.show(BX('crm-document-show-pdf'));
			var title = BX('pagetitle');
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
			BX('crm-document-stamp').disabled = false;
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

	BX.Crm.DocumentView.enablePublicUrl = function()
	{
		if(this.progress)
		{
			return;
		}

		BX('crm-document-stamp').disabled = true;

		var status = 0, analyticsLabel;
		if(BX.hasClass(BX('docs-preview-switcher'), 'docs-preview-switcher-off'))
		{
			status = 1;
			BX.removeClass(BX('docs-preview-switcher'), 'docs-preview-switcher-off');
			BX('crm-document-public-value-container').style.height = '47px';
			analyticsLabel = 'enablePublicUrl';
		}
		else
		{
			BX.addClass(BX('docs-preview-switcher'), 'docs-preview-switcher-off');
			BX('crm-document-public-value-container').style.height = 0;
			analyticsLabel = 'disablePublicUrl';
		}
		this.preview.showLoader();
		BX.ajax.runAction('crm.documentgenerator.document.enablePublicUrl', {
			analyticsLabel: analyticsLabel,
			data: {
				status: status,
				id: this.documentId
			}
		}).then(BX.proxy(function(response)
		{
			this.progress = false;
			BX('crm-document-stamp').disabled = false;
			BX('crm-document-public-url-container').value = response.data.publicUrl || '';
			this.preview.hideLoader();
		}, this), BX.proxy(function(response)
		{
			this.progress = false;
			BX('crm-document-stamp').disabled = false;
			this.showError(response.errors.pop().message);
			this.preview.hideLoader();
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

	BX.Crm.DocumentView.handleClickInput = function()
	{
		var input = BX('crm-document-public-url-container');
		BX.focus(input);
		input.setSelectionRange(0, input.value.length);
	};

	BX.Crm.DocumentView.copyPublicUrl = function()
	{
		this.handleClickInput();
		document.execCommand("copy");

		this.showCopyLinkPopup(BX('crm-document-copy-public-url'), BX.message('CRM_DOCUMENT_VIEW_COPY_PUBLIC_URL_MESSAGE'));
	};

	BX.Crm.DocumentView.showCopyLinkPopup = function(node, message) {
		if(this.popupOuterLink)
		{
			return;
		}

		this.popupOuterLink = new BX.PopupWindow('crm-popup-copy-link', node, {
			className: 'crm-popup-copy-link',
			bindPosition: {
				position: 'top'
			},
			offsetLeft: 15,
			darkMode: true,
			angle: true,
			content: message
		});

		this.popupOuterLink.show();

		setTimeout(function() {
			BX.hide(BX(this.popupOuterLink.uniquePopupId));
		}.bind(this), 2000);

		setTimeout(function() {
			this.popupOuterLink.destroy();
			this.popupOuterLink = null;
		}.bind(this), 2200)
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
			BX.hide(BX('docs-preview-node-message'));
			BX.hide(BX('docs-preview-node-detail'));
			if(this.progressInterval > 0)
			{
				clearInterval(this.progressInterval);
			}
		}
		else if(step === 1)
		{
			BX('docs-preview-node-message').innerText = BX.message('CRM_DOCUMENT_VIEW_PREVIEW_GENERATION_MESSAGE');
			BX.show(BX('docs-preview-node-message'));
			BX.hide(BX('docs-preview-node-detail'));
			this.startProgressBar(BX('docs-progress-bar'), 10);
		}
		else
		{
			BX('docs-preview-node-message').innerText = BX.message('CRM_DOCUMENT_VIEW_PREVIEW_TIME_MESSAGE');
			BX('docs-preview-node-detail').innerText = BX.message('CRM_DOCUMENT_VIEW_PREVIEW_READY_MESSAGE');
			BX.show(BX('docs-preview-node-message'));
			BX.show(BX('docs-preview-node-detail'));
			this.startProgressBar(BX('docs-progress-bar'), 20);
		}
	};

	BX.Crm.DocumentView.startProgressBar = function(node, limit, start, interval)
	{
		if(this.progressInterval > 0)
		{
			clearInterval(this.progressInterval);
		}
		if(!BX.type.isDomNode(node))
		{
			return;
		}
		if(!BX.type.isNumber(limit))
		{
			limit = 20;
		}
		if(!BX.type.isNumber(start) || start > 100)
		{
			start = 0;
		}
		if(!BX.type.isNumber(interval))
		{
			interval = 100;
		}
		node.style.width = start + '%';
		var stepSize = 100 / (limit / (interval / 1000));
		this.progressInterval = setInterval(BX.proxy(function()
		{
			var width;
			var oldWidth = parseFloat(node.style.width);
			if(oldWidth === 100)
			{
				width = 0;
			}
			else
			{
				width = oldWidth + stepSize;
				if(width > 100)
				{
					width = 100;
				}
			}
			node.style.width = width + '%';
		}, this), interval);
	};

	BX.Crm.DocumentView.showPdf = function()
	{
		if(this.pdfUrl)
		{
			if(BX('crm-document-pdf').style.display === 'block')
			{
				return;
			}
			BX.ajax.runAction('crm.documentgenerator.document.showpdf', {
				data: {
					id: this.documentId
				}
			}).then(BX.proxy(function(response)
			{
				var imageNode = this.preview.imageNode;
				if(imageNode)
				{
					BX.hide(imageNode);
				}
				var html = BX.processHTML(response.data.html);
				BX('crm-document-pdf').innerHTML = html.HTML;
				BX.hide(BX('crm-document-show-pdf'));
				BX.show(BX('crm-document-pdf'));
				if(!!html.SCRIPT)
				{
					BX.ajax.processScripts(html.SCRIPT);
				}
			}, this)).then(function(response)
			{
				BX.Crm.DocumentView.showError(response.errors.pop().message);
			});
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

	BX.Crm.DocumentEdit = {

	};

	BX.Crm.DocumentEdit.init = function()
	{
		BX.bind(BX('crm-document-edit-spoiler'), 'click', function()
		{
			BX.show(BX('crm-document-edit-all'));
			BX.hide(BX('crm-document-edit-spoiler'));
		});
		this.initForm();
	};

	BX.Crm.DocumentEdit.initForm = function()
	{
		BX.bind(BX('crm-document-edit-form'), 'submit', BX.proxy(this.sendForm, this));
		BX.bind(BX('crm-document-edit-save'), 'click', BX.proxy(this.sendForm, this));
		BX.bind(BX('crm-document-edit-cancel'), 'click', BX.proxy(this.closeSlider, this));
		BX.bindDelegate(BX('crm-document-edit-form'), 'change', {className: 'crm-document-edit-select'}, BX.proxy(this.refillValues, this));
	};

	BX.Crm.DocumentEdit.sendForm = function(event)
	{
		var form = BX('crm-document-edit-form');
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
		var form = BX('crm-document-edit-form');
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
		BX('crm-document-edit-error').innerHTML = error;
		BX.show(BX('crm-document-edit-error'));
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
			var form = BX('crm-document-edit-form');
			var result = response.data[entityName + 'Fields'];
			for(var name in result)
			{
				if(result.hasOwnProperty(name))
				{
					if(typeof result[name].value === 'object' && BX.type.isNotEmptyObject(result[name].value))
					{
						var select = BX('field-' + name);
						if(!select)
						{
							var group = result[name].group;
							if(BX.type.isArray(result[name].group))
							{
								group = result[name].group[result[name].group.length - 1];
							}
							var groupNode = BX('crm-document-edit-group-' + group);
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

})(window);