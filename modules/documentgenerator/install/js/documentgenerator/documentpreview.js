;(function(){
	"use strict";

	BX.namespace('BX.DocumentGenerator');

	if (typeof BX.DocumentGenerator.DocumentPreview !== "undefined")
	{
		return;
	}

	var isPushEventInited = false;

	BX.DocumentGenerator.DocumentPreview = function(options)
	{
		this.loader = null;
		this.documentId = null;
		this.pullTag = null;
		this.startImageUrl = null;
		this.imageUrl = null;
		this.imageContainer = null;
		this.imageNode = null;
		this.printUrl = null;
		this.pdfUrl = null;
		this.hash = (options.hash ? options.hash : '' );
		this.isPublicMode = (options.isPublicMode ? options.isPublicMode : false);
		this.isTransformationError = false;
		this.transformationErrorNode = null;
		this.transformationErrorMessage = '';
		this.transformationErrorCode = 0;
		this.previewNode = null;
		this.onReady = BX.DoNothing;
		this.applyOptions(options);
		this.initPushEvent();
		this.start();
	};

	BX.DocumentGenerator.DocumentPreview.prototype = {};

	BX.DocumentGenerator.DocumentPreview.prototype.isPullConnected = function()
	{
		if(top.BX.PULL)
		{
			// pull_v2
			if(BX.type.isFunction(top.BX.PULL.isConnected))
			{
				return top.BX.PULL.isConnected();
			}
			else
			{
				var debugInfo = top.BX.PULL.getDebugInfoArray();
				return debugInfo.connected;
			}
		}

		return false;
	};

	BX.DocumentGenerator.DocumentPreview.prototype.initPushEvent = function()
	{
		if(!isPushEventInited)
		{
			if(this.isPullConnected())
			{
				isPushEventInited = true;
				top.BX.addCustomEvent("onPullEvent-documentgenerator", BX.proxy(this.showImage, this));
			}
			else if(this.documentId > 0 && !this.imageUrl)
			{
				var action = 'documentgenerator.api.document.get';
				var data = {
					id: this.documentId
				};
				if(this.isPublicMode && BX.type.isString(this.hash) && this.hash.length > 10)
				{
					action = 'documentgenerator.api.publicdocument.get';
					data.hash = this.hash;
				}
				isPushEventInited = true;
				setTimeout(BX.proxy(function(){
					BX.ajax.runAction(action, {
						data: data
					}).then(BX.proxy(function(response){
						isPushEventInited = false;
						if(response.data.document.imageUrl)
						{
							this.showImage('showImage', response.data.document);
						}
						else
						{
							this.initPushEvent();
						}
					}, this), function()
					{
						isPushEventInited = false;
					});
				}, this), 5000);
			}
		}
	};

	BX.DocumentGenerator.DocumentPreview.prototype.applyOptions = function(options)
	{
		if(options.id)
		{
			this.documentId = options.id;
		}
		if(options.pullTag)
		{
			this.pullTag = options.pullTag;
		}
		if(options.imageUrl)
		{
			this.imageUrl = options.imageUrl;
		}
		if(options.startImageUrl)
		{
			this.startImageUrl = options.startImageUrl;
		}
		if(options.printUrl)
		{
			this.printUrl = options.printUrl;
		}
		if(options.pdfUrl)
		{
			this.pdfUrl = options.pdfUrl;
		}
		if(options.emailDiskFile)
		{
			this.emailDiskFile = options.emailDiskFile;
		}
		if(BX.type.isDomNode(options.imageContainer))
		{
			this.imageContainer = options.imageContainer;
		}
		if(BX.type.isDomNode(options.previewNode))
		{
			this.previewNode = options.previewNode;
		}
		if(BX.type.isDomNode(options.transformationErrorNode))
		{
			this.transformationErrorNode = options.transformationErrorNode;
		}
		if(BX.type.isBoolean(options.isTransformationError))
		{
			this.isTransformationError = options.isTransformationError;
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
		else
		{
			this.transformationErrorCode = 0;
		}
		if(BX.type.isFunction(options.onReady))
		{
			this.onReady = options.onReady;
		}
		this.initPushEvent();
	};

	BX.DocumentGenerator.DocumentPreview.prototype.getLoader = function()
	{
		if(!this.loader)
		{
			this.loader = new BX.Loader({size: 100, offset: {left: "-8%", top: "6%"}});
		}

		return this.loader;
	};

	BX.DocumentGenerator.DocumentPreview.prototype.showLoader = function()
	{
		if(this.imageContainer)
		{
			if(!this.getLoader().isShown())
			{
				this.getLoader().show(this.imageContainer);
			}
		}
		if(BX.type.isDomNode(this.imageNode))
		{
			this.imageNode.style.opacity = 0.5;
		}
	};

	BX.DocumentGenerator.DocumentPreview.prototype.hideLoader = function()
	{
		if(this.getLoader().isShown())
		{
			this.getLoader().hide();
		}
		if(BX.type.isDomNode(this.imageNode))
		{
			this.imageNode.style.opacity = 1;
		}
	};

	BX.DocumentGenerator.DocumentPreview.prototype.isValidPullTag = function(command, params)
	{
		return(command === 'showImage' && params['pullTag'] === this.pullTag)
	};

	BX.DocumentGenerator.DocumentPreview.prototype.showImage = function(command, params)
	{
		if(this.isValidPullTag(command, params))
		{
			this.applyOptions(params);
			if(BX.type.isDomNode(this.previewNode))
			{
				BX.hide(this.previewNode);
			}
			if(BX.type.isDomNode(this.transformationErrorNode))
			{
				if(this.isTransformationError)
				{
					BX.show(this.transformationErrorNode);
				}
				else
				{
					BX.hide(this.transformationErrorNode);
				}
			}
			this.showImageNode();
			this.onReady(params);
			if (this.loader && this.loader.isShown())
			{
				this.loader.hide();
			}
		}
	};

	BX.DocumentGenerator.DocumentPreview.prototype.start = function()
	{
		if(this.imageUrl)
		{
			this.showImageNode();
		}
		else if(this.startImageUrl)
		{
			this.imageUrl = this.startImageUrl;
			this.startImageUrl = null;
			this.showImageNode();
			if(BX.type.isDomNode(this.imageNode))
			{
				this.imageNode.style.opacity = 0.2;
			}
			if(this.pullTag)
			{
				this.showLoader();
			}
		}
		else if(!this.isTransformationError && !this.previewNode)
		{
			this.showLoader();
		}
	};

	BX.DocumentGenerator.DocumentPreview.prototype.showImageNode = function()
	{
		if(!BX.type.isDomNode(this.imageContainer))
		{
			return;
		}
		if(!BX.type.isDomNode(this.imageNode))
		{
			this.imageNode = BX.create('img', {
				style: {
					opacity: 0.1,
					display: 'none'
				}
			});
			BX.append(this.imageNode, this.imageContainer);
		}
		if(this.imageUrl)
		{
			this.imageNode.src = this.imageUrl;
			BX.show(this.imageNode);
			this.imageNode.style.opacity = 1;
		}
	};

	BX.DocumentGenerator.Document = {
		isProcessing: false
	};

	BX.DocumentGenerator.Document.askAboutUsingPreviousDocumentNumber = function(provider, templateId, value, onsuccess, ondecline)
	{
		if(BX.type.isString(provider) && (parseInt(templateId) > 0) && BX.type.isFunction(onsuccess))
		{
			if(BX.DocumentGenerator.Document.isProcessing === true)
			{
				return;
			}
			try
			{
				provider = provider.replace(/\\/g, '\\\\');
				BX.DocumentGenerator.Document.isProcessing = true;
				BX.ajax.runAction('documentgenerator.api.document.list', {
					data: {
						select: ['id', 'number'],
						filter: {
							provider: provider,
							templateId: templateId,
							value: value
						},
						order: {id: 'desc'}
					},
					navigation: {
						size: 1
					}
				}).then(function(response)
				{
					BX.DocumentGenerator.Document.isProcessing = false;
					if(response.data.documents.length > 0)
					{
						var previousNumber = response.data.documents[0].number;
						BX.DocumentGenerator.showMessage(BX.message('DOCGEN_POPUP_DO_USE_OLD_NUMBER'), [
							new BX.PopupWindowButton({
								text : BX.message('DOCGEN_POPUP_NEW_BUTTON'),
								className : "ui-btn ui-btn-md ui-btn-primary",
								events : {
									click : function()
									{
										onsuccess();
										this.popupWindow.destroy();
									}}
							}),
							new BX.PopupWindowButton({
								text : BX.message('DOCGEN_POPUP_OLD_BUTTON'),
								className : "ui-btn ui-btn-md ui-btn-primary",
								events : {
									click : function()
									{
										onsuccess(previousNumber);
										this.popupWindow.destroy();
									}}
							})
						], BX.message('DOCGEN_POPUP_NUMBER_TITLE'), ondecline);
					}
					else
					{
						onsuccess();
					}
				}).catch(function()
				{
					BX.DocumentGenerator.Document.isProcessing = false;
					if(BX.type.isFunction(ondecline))
					{
						ondecline();
					}
				});
			}
			catch (e)
			{
				BX.DocumentGenerator.Document.isProcessing = false;
				if(BX.type.isFunction(ondecline))
				{
					ondecline();
				}
			}
		}
	};

	BX.DocumentGenerator.Document.onBeforeCreate = function(viewUrl, params, loaderPath, moduleId)
	{
		var urlParams = BX.DocumentGenerator.parseUrl(viewUrl, 'params');
		var provider = decodeURIComponent(urlParams.providerClassName).toLowerCase();
		var templateId = urlParams.templateId;
		var value = urlParams.value;
		var sliderWidth = params.hasOwnProperty('sliderWidth') ? params.sliderWidth : null;

		if (!urlParams.hasOwnProperty('documentId'))
		{
			BX.ajax.runAction('documentgenerator.api.dataprovider.isPrintable', {
				data: {
					provider: provider,
					value: value,
					options: {},
					module: moduleId,
				}
			}).then(function(response)
			{
				if(!urlParams.hasOwnProperty('documentId'))
				{
					BX.DocumentGenerator.Document.askAboutUsingPreviousDocumentNumber(provider, templateId, value, function(previousNumber)
					{
						if(previousNumber)
						{
							viewUrl = BX.util.add_url_param(viewUrl, {number: previousNumber});
						}

						BX.DocumentGenerator.openUrl(viewUrl, loaderPath, sliderWidth);
					});
				}
				else
				{
					BX.DocumentGenerator.openUrl(viewUrl, loaderPath, sliderWidth);
				}
			}).catch(function(reason)
			{
				BX.DocumentGenerator.showMessage(
					reason.errors.map(function (error) { return error.message; }).join("<br>"),
					[new BX.PopupWindowButton({
						text : BX.message('DOCGEN_POPUP_CONTINUE_BUTTON'),
						className : "ui-btn ui-btn-md ui-btn-success",
						events : { click : function(e) { this.popupWindow.close(); BX.PreventDefault(e) } }
					})],
					BX.message('DOCGEN_POPUP_PRINT_TITLE')
				);
			});
		}
		else
		{
			BX.DocumentGenerator.openUrl(viewUrl, loaderPath, sliderWidth);
		}
	};

	BX.DocumentGenerator.Feedback =
	{
		open: function(provider, templateName, templateCode)
		{
			var url = '/bitrix/components/bitrix/documentgenerator.feedback/slider.php';
			url = BX.util.add_url_param(url, {
				provider: provider || '',
				templateName: templateName || '',
				templateCode: templateCode || ''
			});
			if(BX.SidePanel)
			{
				BX.SidePanel.Instance.open(url, {width: 735});
			}
			else
			{
				location.href = url;
			}
		}
	};

	BX.DocumentGenerator.parseUrl = function(url, key)
	{
		var parser = document.createElement('a'),
			params = {},
			queries, split, i;
		parser.href = url;
		queries = parser.search.replace(/^\?/, '').split('&');
		for( i = 0; i < queries.length; i++ ) {
			split = queries[i].split('=');
			params[split[0]] = split[1];
		}
		var result = {
			protocol: parser.protocol,
			host: parser.host,
			hostname: parser.hostname,
			port: parser.port,
			pathname: parser.pathname,
			search: parser.search,
			params: params,
			hash: parser.hash
		};

		if(key && result.hasOwnProperty(key))
		{
			return result[key];
		}

		return result;
	};

	BX.DocumentGenerator.openUrl = function(viewUrl, loaderPath, width)
	{
		if(BX.SidePanel)
		{
			if(!BX.type.isNumber(width))
			{
				width = 810;
			}
			BX.SidePanel.Instance.open(viewUrl, {width: width, cacheable: false, loader: loaderPath});
			var menu = BX.PopupMenu.getCurrentMenu();
			if(menu && menu.popupWindow)
			{
				menu.popupWindow.close();
			}
		}
		else
		{
			location.href = viewUrl;
		}
	};

	BX.DocumentGenerator.showMessage = function(content, buttons, title, onclose)
	{
		title = title || '';
		if (typeof(buttons) === "undefined" || typeof(buttons) === "object" && buttons.length <= 0)
		{
			buttons = [new BX.PopupWindowButton({
				text : BX.message('DOCGEN_POPUP_CLOSE_BUTTON'),
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
			content : BX.create('span',{
				attrs:{className:'bx-popup-documentgenerator-popup-content-text'},
				children : content,
			}),
			titleBar: title,
			contentColor: 'white',
			className : 'bx-popup-documentgenerator-popup',
			maxWidth: 470
		});
		this.popupConfirm.show();


	};

	/**
	 * @param id
	 * @param params
	 * @constructor
	 */
	BX.DocumentGenerator.Button = function(id, params)
	{
		this.progress = false;
		this.links = {};
		this.linksLoaded = false;
		this.intranetExtensions = null;
		this.id = id;
		this.text = 'Document';
		this.className = '';
		this.menuClassName = null;
		this.provider = null;
		this.value = null;
		this.loaderPath = null;
		this.documentUrl = null;
		this.templateListUrl = null;
		this.moduleId = null;
		this.templatesText = 'Templates';
		this.documentsText = 'Documents';
		this.sliderWidth = null;
		this.fillParameters(params);
	};

	/**
	 * @param params
	 */
	BX.DocumentGenerator.Button.prototype.fillParameters = function(params)
	{
		if(params.links && BX.type.isNotEmptyObject(params.links))
		{
			this.links = params.links;
			if(this.links.length > 0)
			{
				this.linksLoaded = true;
			}
		}
		if(params.menuClassName && BX.type.isString(params.menuClassName))
		{
			this.menuClassName = params.menuClassName;
		}
		if(params.className && BX.type.isString(params.className))
		{
			this.className = params.className;
		}
		if(params.moduleId && BX.type.isString(params.moduleId))
		{
			this.moduleId = params.moduleId;
		}
		if(params.text && BX.type.isString(params.text))
		{
			this.text = params.text;
		}
		if(params.templatesText && BX.type.isString(params.templatesText))
		{
			this.templatesText = params.templatesText;
		}
		if(params.documentsText && BX.type.isString(params.documentsText))
		{
			this.documentsText = params.documentsText;
		}
		this.value = params.value || null;
		this.provider = params.provider || null;
		this.loaderPath = params.loaderPath || null;
		this.templateListUrl = params.templateListUrl || null;
		this.documentUrl = params.documentUrl || null;
		this.sliderWidth = params.hasOwnProperty('sliderWidth') ? parseInt(params.sliderWidth) : null;
	};

	/**
	 * @returns Node|null
	 */
	BX.DocumentGenerator.Button.prototype.getElement = function()
	{
		return BX(this.id);
	};

	/**
	 * @returns Node|null
	 */
	BX.DocumentGenerator.Button.prototype.createElement = function()
	{
		var node = this.getElement();
		if(node)
		{
			return node;
		}
		if(!this.id)
		{
			return null;
		}
		var tagName = 'button';
		var className = 'ui-btn ui-btn-md ui-btn-light-border ui-btn-dropdown ui-btn-themes';
		if(this.className)
		{
			className += ' ' + this.className;
		}
		var attrs = {
			'id': this.id,
			'className': className,
		};
		node = BX.create(tagName, {
			attrs: attrs,
			text: this.text,
		});

		return node;
	};

	BX.DocumentGenerator.Button.prototype.init = function()
	{
		BX.bind(this.getElement(), 'click', BX.proxy(function()
		{
			if(this.linksLoaded)
			{
				this.showPopup();
			}
			else
			{
				if(this.progress)
				{
					return;
				}
				this.progress = true;
				this.showLoader();
				BX.ajax.runAction('documentgenerator.api.document.getButtonTemplates', {
					data: {
						moduleId: this.moduleId,
						provider: this.provider,
						value: this.value,
					}
				}).then(BX.proxy(function(response)
				{
					this.fillLinksFromResponse(response);
					this.hideLoader();
					this.progress = false;
					setTimeout(BX.proxy(this.showPopup, this), 10);
				}, this)).catch(BX.proxy(function(response)
				{
					this.hideLoader();
					this.progress = false;
					alert(response.errors.pop().message);
				}, this));
			}
		}, this));
		BX.addCustomEvent("onPullEvent-documentgenerator", BX.proxy(function(command, params)
		{
			if(command === 'updateTemplate')
			{
				this.linksLoaded = false;
				this.links = {};
				BX.PopupMenu.destroy(this.getPopupMenuId());
			}
		}, this));
	};

	BX.DocumentGenerator.Button.prototype.fillLinksFromResponse = function(response)
	{
		this.linksLoaded = true;
		this.links = {};
		if(this.documentUrl && response.data.templates && BX.type.isArray(response.data.templates))
		{
			this.links.templates = [];
			var i, length = response.data.templates.length, url;
			for(i = 0; i < length; i++)
			{
				url = BX.util.add_url_param(this.documentUrl, {
					templateId: parseInt(response.data.templates[i]['id']),
					providerClassName: this.provider.replace(/\\/g, '\\\\'),
					value: this.value,
					analyticsLabel: 'generateDocument',
					templateCode: response.data.templates[i]['code'],
				});
				var docParams = {};
				if (this.sliderWidth)
				{
					docParams.sliderWidth = this.sliderWidth;
				}
				this.links.templates[i] = {
					text: BX.util.htmlspecialchars(response.data.templates[i]['name']),
					title: BX.util.htmlspecialchars(response.data.templates[i]['name']),
					onclick: 'BX.DocumentGenerator.Document.onBeforeCreate(' +
						'\'' + url + '\',' +
						JSON.stringify(docParams) + ',' +
						'\'' + this.loaderPath + '\',' +
						'\'' + this.moduleId
					+ '\')'
				};
			}
		}
		if(response.data.documentList && this.documentUrl)
		{
			this.links.documentList = BX.util.add_url_param(response.data.documentList, {
				provider:  this.provider.replace(/\\/g, '\\\\'),
				module: this.moduleId,
				value: this.value,
				viewUrl: this.documentUrl,
				loaderPath: this.loaderPath,
			});
		}
		if(response.data.canEditTemplate && this.templateListUrl)
		{
			this.links.templateList = this.templateListUrl;
		}
		if(response.data.intranetExtensions)
		{
			this.intranetExtensions = response.data.intranetExtensions;
		}
	};

	BX.DocumentGenerator.Button.prototype.prepareLinksForPopup = function()
	{
		var result = [], addDelimiter = false;
		if(!this.linksLoaded)
		{
			return result;
		}
		if(this.links.templates && BX.type.isArray(this.links.templates))
		{
			result = this.links.templates;
			addDelimiter = true;
		}
		if(this.links.documentList)
		{
			if(addDelimiter)
			{
				result[result.length] = {
					'delimiter': true
				};
				addDelimiter = false;
			}
			result[result.length] = {
				text: this.documentsText,
				onclick: 'BX.DocumentGenerator.openUrl(\'' + this.links.documentList + '\', null, 930)'
			};
		}
		if(this.links.templateList)
		{
			if(addDelimiter)
			{
				result[result.length] = {
					'delimiter': true
				};
			}
			result[result.length] = {
				text: this.templatesText,
				onclick: 'BX.DocumentGenerator.openUrl(\'' + this.links.templateList + '\', null, 930)'
			}
		}

		if(this.intranetExtensions)
		{
			result.push({
				delimiter: true
			});
			result.push(this.intranetExtensions);
		}

		return result;
	};

	BX.DocumentGenerator.Button.prototype.showPopup = function()
	{
		BX.PopupMenu.show(this.getPopupMenuId(), this.getElement(), this.prepareLinksForPopup(), {
			offsetLeft: 0,
			offsetTop: 0,
			closeByEsc: true,
			className: 'document-toolbar-menu',
			maxWidth: 600
		});
	};

	BX.DocumentGenerator.Button.prototype.getPopupMenuId = function()
	{
		return this.id + '_menu';
	}

	BX.DocumentGenerator.Button.prototype.getLoader = function()
	{
		if(!this.loader)
		{
			this.loader = new BX.Loader({size: 50});
		}

		return this.loader;
	};

	BX.DocumentGenerator.Button.prototype.showLoader = function()
	{
		if(this.getElement() && !this.getLoader().isShown())
		{
			this.getLoader().show(this.getElement());
		}
	};

	BX.DocumentGenerator.Button.prototype.hideLoader = function()
	{
		if(this.getLoader().isShown())
		{
			this.getLoader().hide();
		}
	};

})(window);