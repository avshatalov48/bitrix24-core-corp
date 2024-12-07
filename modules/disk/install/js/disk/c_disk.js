BX.namespace("BX.Disk");
if(!BX.Disk.pathToUser)
{
	(function () {
		var firstButtonInModalWindow = null;
		var entityToNewShared = {};
		var moduleTasks = {};

		var windowsWithoutManager = {};

		var insertInTooltipLockedInfo = function(tooltip){
			if(!tooltip.RealAnchor ||  !BX.hasClass(tooltip.RealAnchor, 'js-disk-locked-document-tooltip'))
			{
				return;
			}

			var info = BX.findChildByClassName(tooltip.ROOT_DIV, 'bx-user-info-data-info', true);
			if(!info)
			{
				return;
			}

			if(BX.findChildByClassName(info, 'js-disk-locked-status', true))
			{
				return;
			}

			BX.prepend(
				BX.create('div', {
					html: '<span class="field-name">' + BX.message('DISK_JS_USER_LOCKED_DOCUMENT') + '</span>',
					props: {
						className: 'js-disk-locked-status'
					}
				}),
				info
			);
		};

		var onPullDiskEvent = function(command, params)
		{
			params = params || {};

			if (command === 'onlyoffice' && params.documentSession)
			{
				if (params.event === 'saved')
				{
					BX.onCustomEvent('Disk.OnlyOffice:onSaved', [params.object, params.documentSession]);
				}

				var notify = BX.UI.Notification.Center.getBalloonById('session-' + params.documentSession.hash);
				if (notify)
				{
					BX.UI.Notification.Center.notify({
						content: BX.message('DISK_JS_DOCUMENT_ONLYOFFICE_SAVED').replace('#name#', notify.getData().file.name)
					});
					notify.close();

					return;
				}
			}

			switch (params.action)
			{
				case 'commit':
					if (!params.objectId)
					{
						break;
					}

					if (parseInt(params.contentVersion, 10) < 2)
					{
						break;
					}

					var reloadItem = function (id) {
						viewer.items.forEach(function (item) {
							if (item.sourceNode.dataset.objectId === id)
							{
								viewer.reloadItem(item, {});
							}
						});
					};

					if (!BX.getClass('BX.UI.Viewer.Instance'))
					{
						break;
					}

					var viewer = BX.UI.Viewer.Instance;
					if (!viewer.isOpen())
					{
						return;
					}

					var currentItem = viewer.getCurrentItem();
					if (currentItem.sourceNode.dataset.objectId != params.objectId)
					{
						reloadItem(params.objectId);
						break;
					}

					var message = BX.message('DISK_JS_STATUS_ACTION_SUCCESS');
					if (BX.message.DISK_FOLDER_LIST_LABEL_LIVE_UPDATE_FILE)
					{
						message = BX.message('DISK_FOLDER_LIST_LABEL_LIVE_UPDATE_FILE').replace('#NAME#', currentItem.getTitle());
					}
					BX.Disk.showModalWithStatusAction({
						message: message
					});

					viewer.reloadCurrentItem();

					break;
			}
		};

		BX.addCustomEvent('onPullEvent-disk', onPullDiskEvent);
		BX.addCustomEvent('onTooltipShow', insertInTooltipLockedInfo);
		BX.addCustomEvent('onTooltipInsertData', insertInTooltipLockedInfo);

		BX.addCustomEvent('BX.UI.Viewer.Controller:onBeforeShow', function(viewer, index){
			var item = viewer.getItemByIndex(index);
			if (!item)
			{
				return;
			}
			var actions = item.getActions().filter(function(action){
				if (action.id !== 'edit')
				{
					return true;
				}
				if (!action.buttonIconClass)
				{
					action.buttonIconClass = '';
				}
				action.buttonIconClass += ' disk-viewer-panel-icon-' + BX.Disk.getDocumentService();

				if (!action.params || !action.params.dependsOnService)
				{
					return true;
				}

				return action.params.dependsOnService === BX.Disk.getDocumentService();
			});

			item.setActions(actions);
		});

		BX.addCustomEvent('onTooltipHide', function(tooltip){
			if(!tooltip.RealAnchor ||  !BX.hasClass(tooltip.RealAnchor, 'js-disk-locked-document-tooltip'))
			{
				return;
			}

			var info = BX.findChildByClassName(tooltip.ROOT_DIV, 'js-disk-locked-status', true);
			if(!info)
			{
				return;
			}

			BX.remove(info);
		});

		function modifyAjaxConfig(config)
		{
			config.data = config.data || {};
			config.data['SITE_ID'] = BX.message('SITE_ID');
			config.data['sessid'] = BX.bitrix_sessid();

			return config;
		}

		Object.assign(BX.Disk, {
			apiVersion: 22,
			pathToUser: '/company/personal/user/#user_id#/',
			endEditSession: function(session)
			{
				BX.ajax.runAction('disk.api.onlyoffice.endSession', {
					json: {
						sessionId: session.id,
						documentSessionHash: session.hash,
					}
				}).then(function(response){
					if (!response || response.data.mode !== 'edit')
					{
						return;
					}

					if (response.data.activeSessions > 1)
					{
						return;
					}

					if (!session.documentWasChanged)
					{
						return;
					}

					if (response.data.documentSessionInfo.isFinished)
					{
						return;
					}

					BX.UI.Notification.Center.notify({
						id: 'session-' + session.hash,
						autoHide: false,
						content: BX.message('DISK_JS_DOCUMENT_ONLYOFFICE_SAVE_PROCESS').replace('#name#', response.data.file.name),
						data: {
							file: response.data.file
						}
					});
				});
			},
			hideLoader: function()
			{
				BX.removeClass(document.body, 'disk-body-overlay');
				if (this.loaderWrapper)
				{
					BX.ZIndexManager.unregister(this.loaderWrapper);
					this.loaderWrapper.parentNode.removeChild(this.loaderWrapper);
					this.loaderWrapper = null;
					this.loader = null;
				}
			},
			showLoader: function(params)
			{
				params = params || {};
				BX.addClass(document.body, 'disk-body-overlay');
				var div = document.body.appendChild(this.getLoaderWrapper(params));

				BX.ZIndexManager.register(div);

				this.getLoader(this.loaderNode).show();
			},
			getLoaderWrapper: function (params)
			{
				if (!this.loaderWrapper)
				{
					this.loaderWrapper = BX.create('div', {
						props: {
							className: 'disk-body-overlay-wrapper'
						},
						style: {
							zIndex: params.zIndex
						},
						children: [
							BX.create('div', {
								props: {
									className: 'disk-body-overlay-container'
								},
								children: [
									this.loaderNode = BX.create('div', {
										props: {
											className: 'disk-body-overlay-container-loader'
										}
									}),
									BX.create('div', {
										props: {
											className: 'disk-body-overlay-container-text'
										},
										text: params.text || ''
									})
								]

							})
						]
					})
				}

				return this.loaderWrapper;
			},
			getLoader: function(targetNode)
			{
				if(!this.loader)
				{
					this.loader = new BX.Loader({
						target: targetNode,
						size: 130
					});
				}

				return this.loader;
			},
			ajax: function (config)
			{
				return BX.ajax(modifyAjaxConfig(config));
			},
			ajaxPromise: function (config)
			{
				return BX.ajax.promise(modifyAjaxConfig(config)).then(function (response) {
					if (!response || response.status != 'success')
					{
						BX.Disk.showModalWithStatusAction(response);

						var p = new BX.Promise();
						p.reject(response);

						return p;
					}

					return response;
				});
			},
			isEmptyObject: function (obj)
			{
				if (obj == null) return true;
				if (obj.length && obj.length > 0)
					return false;
				if (obj.length === 0)
					return true;

				for (var key in obj) {
					if (hasOwnProperty.call(obj, key))
						return false;
				}

				return true;
			},
			_keyPress: function (e)
			{
				var destDialog = BX.SocNetLogDestination && (BX.SocNetLogDestination.isOpenDialog() || BX.SocNetLogDestination.isOpenSearch());
				var key = (e || window.event).keyCode || (e || window.event).charCode;
				//enter
				if (key == 13 && firstButtonInModalWindow && !destDialog) {
					BX.fireEvent(firstButtonInModalWindow.buttonNode, 'click');
					return BX.PreventDefault(e);
				}
			},
			modalWindow: function (params)
			{
				params = params || {};
				params.title = params.title || false;
				params.bindElement = params.bindElement || null;
				params.bindOptions = params.bindOptions || {};
				params.overlay = typeof params.overlay == "undefined" ? true : params.overlay;
				params.autoHide = params.autoHide || false;
				params.closeIcon = typeof params.closeIcon == "undefined"? true : params.closeIcon;
				params.modalId = params.modalId || 'disk_modal_window_' + (Math.random() * (200000 - 100) + 100);
				params.withoutContentWrap = typeof params.withoutContentWrap == "undefined" ? false : params.withoutContentWrap;
				params.contentClassName = params.contentClassName || '';
				params.contentStyle = params.contentStyle || {};
				params.content = params.content || [];
				params.buttons = params.buttons || false;
				params.events = params.events || {};
				params.withoutWindowManager = !!params.withoutWindowManager || false;

				if (!BX.type.isArray(params.content))
				{
					params.content = [params.content];
				}

				var contentDialogChildren = [];
				if (params.withoutContentWrap) {
					contentDialogChildren = contentDialogChildren.concat(params.content);
				}
				else {
					contentDialogChildren.push(BX.create('div', {
						props: {
							className: 'bx-disk-popup-content' + params.contentClassName
						},
						style: params.contentStyle,
						children: params.content
					}));
				}
				var buttons = params.buttons;
				if (params.htmlButtons) {
					//support old style of buttons
					var htmlButtons = [];
					for (var i in params.htmlButtons) {
						if (!params.htmlButtons.hasOwnProperty(i)) {
							continue;
						}
						if (i > 0) {
							htmlButtons.push(BX.create('SPAN', {html: '&nbsp;'}));
						}
						htmlButtons.push(params.htmlButtons[i]);
					}

					contentDialogChildren.push(BX.create('div', {
						props: {
							className: 'bx-disk-popup-buttons'
						},
						children: htmlButtons
					}));
				}

				var contentDialog = BX.create('div', {
					props: {
						className: 'bx-disk-popup-container'
					},
					children: contentDialogChildren
				});

				var afterPopupShow = params.events.onAfterPopupShow;
				params.events.onAfterPopupShow = BX.delegate(function () {
					if (buttons.length)
					{
						firstButtonInModalWindow = buttons[0];
						BX.bind(document, 'keydown', BX.proxy(this._keyPress, this));
					}

					if (afterPopupShow)
					{
						BX.delegate(afterPopupShow, BX.proxy_context)();
					}
				}, this);

				var closePopup = params.events.onPopupClose;
				params.events.onPopupClose = BX.delegate(function () {

					firstButtonInModalWindow = null;
					try
					{
						BX.unbind(document, 'keydown', BX.proxy(this._keypress, this));
					}
					catch (e) { }

					if(closePopup)
					{
						BX.delegate(closePopup, BX.proxy_context)();
					}

					if(params.withoutWindowManager)
					{
						delete windowsWithoutManager[params.modalId];
					}

					BX.proxy_context.destroy();
				}, this);

				var destroyPopup = params.events.onPopupDestroy;
				params.events.onPopupDestroy = BX.delegate(function () {
					try
					{
						BX.unbind(document, 'keydown', BX.proxy(this._keypress, this));
					}
					catch (e) { }

					if(destroyPopup)
					{
						BX.delegate(destroyPopup, BX.proxy_context)();
					}

				}, this);

				var modalWindow;
				if(params.withoutWindowManager)
				{
					if(!!windowsWithoutManager[params.modalId])
					{
						return windowsWithoutManager[params.modalId]
					}
					modalWindow = new BX.PopupWindow(params.modalId, params.bindElement, {
						titleBar: params.title,
						content: contentDialog,
						bindOptions: params.bindOptions,
						closeByEsc: true,
						height: params.height,
						closeIcon: params.closeIcon,
						autoHide: params.autoHide,
						overlay: params.overlay,
						events: params.events,
						buttons: params.buttons,
						zIndex : isNaN(params["zIndex"]) ? 0 : params.zIndex
					});
					windowsWithoutManager[params.modalId] = modalWindow;
				}
				else
				{
					modalWindow = BX.PopupWindowManager.create(params.modalId, params.bindElement, {
						titleBar: params.title,
						content: contentDialog,
						bindOptions: params.bindOptions,
						closeByEsc: true,
						height: params.height,
						closeIcon: params.closeIcon,
						autoHide: params.autoHide,
						overlay: params.overlay,
						events: params.events,
						buttons: params.buttons,
						zIndex : isNaN(params["zIndex"]) ? 0 : params.zIndex
					});

				}

				modalWindow.show();

				return modalWindow;
			},

			modalWindowLoader: function (queryUrl, params, bindElement)
			{
				bindElement = bindElement || null;
				params = params || {};
				var modalId = params.id;
				var expectResponseType = params.responseType || 'html';
				var afterSuccessLoad = params.afterSuccessLoad || null;
				var onPopupClose = params.onPopupClose || null;
				var postData = params.postData || {};

				var popup = BX.PopupWindowManager.create(
					'bx-disk-' + modalId,
					bindElement,
					{
						closeIcon: true,
						offsetTop: 5,
						autoHide: true,
						lightShadow: false,
						overlay: true,
						content: BX.create('div', {
							children: [
								BX.create('div', {
										style: {
											display: 'table',
											width: '30px',
											height: '30px'
										},
										children: [
											BX.create('div', {
												style: {
													display: 'table-cell',
													verticalAlign: 'middle',
													textAlign: 'center'
												},
												children: [
													BX.create('div', {
														props: {
															className: 'bx-disk-wrap-loading-modal'
														}
													}),
													BX.create('span', {
														text: ''
													})
												]
											})
										]
									}
								)
							]
						}),
						closeByEsc: true,
						events: {
							onPopupClose: function ()
							{
								if (onPopupClose) {
									BX.delegate(onPopupClose, this)();
								}

								this.destroy();
							}
						}
					}
				);
				popup.show();

				postData['sessid'] = BX.bitrix_sessid();
				postData['SITE_ID'] = BX.message('SITE_ID');

				BX.ajax({
					url: queryUrl,
					method: 'POST',
					dataType: expectResponseType,
					data: postData,
					onsuccess: BX.delegate(function (data)
					{

						if (expectResponseType == 'html') {
							popup.setContent(BX.create('DIV', {html: data}));
							popup.adjustPosition();
						}
						else if(expectResponseType == 'json')
						{
							data = data || {};
						}

						afterSuccessLoad && afterSuccessLoad(data, popup);
					}, this),
					onfailure: function (data)
					{
					}
				});
			},

			modalWindowActionLoader: function (action, params, bindElement)
			{
				bindElement = bindElement || null;
				params = params || {};
				var modalId = params.id;
				var afterSuccessLoad = params.afterSuccessLoad || null;
				var onPopupClose = params.onPopupClose || null;
				var postData = params.postData || {};

				var popup = BX.PopupWindowManager.create(
					'bx-disk-' + modalId,
					bindElement,
					{
						closeIcon: true,
						offsetTop: 5,
						autoHide: true,
						lightShadow: false,
						overlay: true,
						content: BX.create('div', {
							children: [
								BX.create('div', {
										style: {
											display: 'table',
											width: '30px',
											height: '30px'
										},
										children: [
											BX.create('div', {
												style: {
													display: 'table-cell',
													verticalAlign: 'middle',
													textAlign: 'center'
												},
												children: [
													BX.create('div', {
														props: {
															className: 'bx-disk-wrap-loading-modal'
														}
													}),
													BX.create('span', {
														text: ''
													})
												]
											})
										]
									}
								)
							]
						}),
						closeByEsc: true,
						events: {
							onPopupClose: function ()
							{
								if (onPopupClose) {
									BX.delegate(onPopupClose, this)();
								}

								this.destroy();
							}
						}
					}
				);
				popup.show();

				BX.ajax.runAction(action, {
					data: postData
				}).then(function (response) {
					afterSuccessLoad && afterSuccessLoad(response, popup);
				});
			},

			addToLinkParam: function (link, name, value)
			{
				if (!link.length) {
					return '?' + name + '=' + value;
				}
				link = BX.util.remove_url_param(link, name);
				if (link.indexOf('?') != -1) {
					return link + '&' + name + '=' + value;
				}
				return link + '?' + name + '=' + value;
			},

			getUrlParameter: function (name)
			{
				name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
				var regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
				var results = regex.exec(location.search);

				return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
			},

			sendTelemetryEvent: function(options)
			{
				if (!BX.Disk.isAvailableOnlyOffice())
				{
					return;
				}

				var url = (document.location.protocol === "https:" ? "https://" : "http://") + "bitrix.info/bx_stat";
				var request =  new XMLHttpRequest();
				request.open("POST", url, true);
				request.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
				request.withCredentials = true;
				options.op = "doc";
				options.u = BX.message.USER_ID;
				options.t = Date.now();
				options.d = document.location.host;
				var query = BX.util.buildQueryString(options);
				request.send(query);
			},

			getFirstErrorFromResponse: function(reponse)
			{
				reponse = reponse || {};
				if(!reponse.errors)
					return '';

				return reponse.errors.shift().message;
			},

			showModalWithStatusAction: function (response, action)
			{
				response = response || {status: 'success'};
				if (!response.message)
				{
					if (response.status == 'success')
					{
						response.message = BX.message('DISK_JS_STATUS_ACTION_SUCCESS');
					}
					else
					{
						response.message = BX.message('DISK_JS_STATUS_ACTION_ERROR') + '. ' + this.getFirstErrorFromResponse(response);
					}
				}

				BX.UI.Notification.Center.notify({
					content: response.message
				});
			},
			showActionModal: function (params)
			{
				var text = params.text;
				var html = params.html;
				var autoHide = params.autoHide;
				var iconSrc;
				if(params.showLoaderIcon) {
					iconSrc = '/bitrix/js/main/core/images/yell-waiter.gif';
				}
				else if(params.showSuccessIcon) {
					iconSrc = '/bitrix/js/main/core/images/viewer-tick.png';
				}
				else if(!!params.icon)
				{
					iconSrc = params.icon;
				}

				var messageBox = BX.create('div', {
					props: {
						className: 'bx-disk-alert'
					},
					children: [
						BX.create('span', {
							props: {
								className: 'bx-disk-alert-icon'
							},
							children: [
								iconSrc? BX.create('img', {
									props: {
										src: iconSrc
									}
								}) : null
							]
						}),

						BX.create('span', {
							props: {
								className: 'bx-disk-aligner'
							}
						}),
						BX.create('span', {
							props: {
								className: 'bx-disk-alert-text'
							},
							text: text,
							html: html
						}),
						BX.create('div', {
							props: {
								className: 'bx-disk-alert-footer'
							}
						})
					]
				});

				var currentPopup = BX.PopupWindowManager.getCurrentPopup();
				if(currentPopup)
				{
					currentPopup.destroy();
				}

				var idTimeout = setTimeout(function ()
				{
					if(!autoHide)
					{
						return;
					}

					var w = BX.PopupWindowManager.getCurrentPopup();
					if (!w || w.uniquePopupId != 'bx-disk-status-action') {
						return;
					}
					w.close();
					w.destroy();
				}, 3000);
				var popupConfirm = BX.PopupWindowManager.create('bx-disk-status-action', null, {
					content: messageBox,
					onPopupClose: function ()
					{
						this.destroy();
						clearTimeout(idTimeout);
					},
					autoHide: autoHide,
					zIndex: 999999 + 1,
					className: 'bx-disk-alert-popup'
				});
				popupConfirm.show();

				BX('bx-disk-status-action').onmouseover = function (e)
				{
					clearTimeout(idTimeout);
				};

				if(!autoHide)
				{
					return popupConfirm;
				}

				BX('bx-disk-status-action').onmouseout = function (e)
				{
					idTimeout = setTimeout(function ()
					{
						var w = BX.PopupWindowManager.getCurrentPopup();
						if (!w || w.uniquePopupId != 'bx-disk-status-action') {
							return;
						}
						w.close();
						w.destroy();
					}, 3000);
				};

				return popupConfirm;
			},

			storePathToUser: function (link)
			{
				if (link) {
					this.pathToUser = link;
				}
			},

			getUrlToShowObjectInGrid: function (objectId, params)
			{
				params = params || {};

				params.objectId = objectId;
				params.SITE_ID = BX.message('SITE_ID');

				return BX.util.add_url_param('/bitrix/tools/disk/focus.php?ncc=1&action=showObjectInGrid', params);
			},

			getUrlToShowFileDetail: function (fileId, params)
			{
				params = params || {};

				params.fileId = fileId;
				params.SITE_ID = BX.message('SITE_ID');

				return BX.util.add_url_param('/bitrix/tools/disk/focus.php?ncc=1&action=openFileDetail', params);
			},

			isAvailableOnlyOffice: function ()
			{
				return BX.message('disk_onlyoffice_available');
			},

			getDocumentService: function ()
			{
				return BX.message('disk_document_service');
			},

			openBlankDocumentPopup: function ()
			{
				if ((!BX.Disk.getDocumentService() || (BX.Disk.getDocumentService() === 'l' || BX.Disk.getDocumentService() === 'onlyoffice')))
				{
					return null;
				}

				return BX.util.popup('/bitrix/services/main/ajax.php?action=disk.documentService.love', 1030, 700);
			},

			saveDocumentService: function (serviceCode)
			{
				var changed = serviceCode !== BX.Disk.getDocumentService();
				if (BX.Disk.isAvailableOnlyOffice())
				{
					BX.userOptions.save('disk', 'doc_service', 'primary', serviceCode);
				}
				else
				{
					BX.userOptions.save('disk', 'doc_service', 'default', serviceCode);
				}

				BX.message({disk_document_service: serviceCode});

				if (changed)
				{
					BX.onCustomEvent('Disk:onChangeDocumentService', [BX.message('disk_document_service')]);
				}

				BX.userOptions.send(null);
			},

			deactiveBanner: function (name)
			{
				BX.userOptions.save('disk', '~banner-offer', name, true);
				BX.userOptions.send(null);
			},

			getPathToUser: function (userId)
			{
				return this.pathToUser.replace('#USER_ID#', userId).replace('#user_id#', userId);
			},

			getNumericCase: function (number, once, multi_21, multi_2_4, multi_5_20)
			{
				if (number == 1) {
					return once;
				}

				if (number < 0) {
					number = -number;
				}

				number %= 100;
				if (number >= 5 && number <= 20) {
					return multi_5_20;
				}

				number %= 10;
				if (number == 1) {
					return multi_21;
				}

				if (number >= 2 && number <= 4) {
					return multi_2_4;
				}

				return multi_5_20;
			},

			getRightLabelByTaskName: function(name){
				switch(name.toLowerCase())
				{
					case 'disk_access_read':
						return BX.message('DISK_JS_SHARING_LABEL_RIGHT_READ');
					case 'disk_access_add':
						return BX.message('DISK_JS_SHARING_LABEL_RIGHT_ADD');
					case 'disk_access_edit':
						return BX.message('DISK_JS_SHARING_LABEL_RIGHT_EDIT');
					case 'disk_access_full':
						return BX.message('DISK_JS_SHARING_LABEL_RIGHT_FULL');
					default:
						return 'error';
				}
			},

			appendNewShared: function (params) {

				var readOnly = params.readOnly;
				var maxTaskName = params.maxTaskName || 'disk_access_full';
				var destFormName = params.destFormName;

				var entityId = params.item.id;
				var entityName = params.item.name;
				var entityAvatar = params.item.avatar;
				var type = params.type;
				var right = params.right || 'disk_access_read';

				entityToNewShared[entityId] = {
					item: params.item,
					type: params.type,
					right: right
				};

				function pseudoCompareTaskName(taskName1, taskName2)
				{
					var taskName1Pos;
					var taskName2Pos;
					switch(taskName1)
					{
						case 'disk_access_read':
							taskName1Pos = 2;
							break;
						case 'disk_access_add':
							taskName1Pos = 3;
							break;
						case 'disk_access_edit':
							taskName1Pos = 4;
							break;
						case 'disk_access_full':
							taskName1Pos = 5;
							break;
						default:
							//unknown task names
							return 0;
					}
					switch(taskName2)
					{
						case 'disk_access_read':
							taskName2Pos = 2;
							break;
						case 'disk_access_add':
							taskName2Pos = 3;
							break;
						case 'disk_access_edit':
							taskName2Pos = 4;
							break;
						case 'disk_access_full':
							taskName2Pos = 5;
							break;
						default:
							//unknown task names
							return 0;
					}
					if(taskName1Pos == taskName2Pos)
					{
						return 0;
					}

					return taskName1Pos > taskName2Pos? 1 : -1;
				}

				BX('bx-disk-popup-shared-people-list').appendChild(
					BX.create('tr', {
						attrs: {
							'data-dest-id': entityId
						},
						children: [
							BX.create('td', {
								props: {
									className: 'bx-disk-popup-shared-people-list-col1'
								},
								children: [
									BX.create('a', {
										props: {
											className: 'bx-disk-filepage-used-people-link'
										},
										children: [
											BX.create('span', {
												props: {
													className: 'bx-disk-filepage-used-people-avatar ' + (type != 'users'? ' group' : '')
												},
												style: {
													backgroundImage: entityAvatar? 'url("' + encodeURI(entityAvatar) + '")' : null
												}
											}),
											BX.util.htmlspecialchars(entityName)
										]
									})
								]
							}),
							BX.create('td', {
								props: {
									className: 'bx-disk-popup-shared-people-list-col2'
								},
								children: [
									BX.create('a', {
										props: {
											className: 'bx-disk-filepage-used-people-permission'
										},
										style: {
											cursor: 'pointer'
										},
										text: this.getRightLabelByTaskName(right),
										events: {
											click: BX.delegate(function(e){
												if(readOnly)
												{
													return BX.PreventDefault(e);
												}
												var targetElement = e.target || e.srcElement;
												BX.PopupMenu.show('disk_open_menu_with_rights', BX(targetElement), [
														(pseudoCompareTaskName(maxTaskName, 'disk_access_read') >= 0? {
															text: BX.message('DISK_JS_SHARING_LABEL_RIGHT_READ'),
															href: "#",
															onclick: BX.delegate(function (e) {
																BX.PopupMenu.destroy('disk_open_menu_with_rights');
																BX.adjust(targetElement, {text: this.getRightLabelByTaskName('disk_access_read')});

																BX.onCustomEvent('onChangeRightOfSharing', [entityId, 'disk_access_read']);

																entityToNewShared[entityId]['right'] = 'disk_access_read';

																return BX.PreventDefault(e);
															}, this)
														} : null),
														(pseudoCompareTaskName(maxTaskName, 'disk_access_add') >= 0? {
															text: BX.message('DISK_JS_SHARING_LABEL_RIGHT_ADD'),
															href: "#",
															onclick: BX.delegate(function (e) {
																BX.PopupMenu.destroy('disk_open_menu_with_rights');
																BX.adjust(targetElement, {text: this.getRightLabelByTaskName('disk_access_add')});

																BX.onCustomEvent('onChangeRightOfSharing', [entityId, 'disk_access_add']);

																entityToNewShared[entityId]['right'] = 'disk_access_add';

																return BX.PreventDefault(e);
															}, this)
														} : null),
														(pseudoCompareTaskName(maxTaskName, 'disk_access_edit') >= 0? {
															text: BX.message('DISK_JS_SHARING_LABEL_RIGHT_EDIT'),
															href: "#",
															onclick: BX.delegate(function (e) {
																BX.PopupMenu.destroy('disk_open_menu_with_rights');
																BX.adjust(targetElement, {text: this.getRightLabelByTaskName('disk_access_edit')});

																BX.onCustomEvent('onChangeRightOfSharing', [entityId, 'disk_access_edit']);

																entityToNewShared[entityId]['right'] = 'disk_access_edit';

																return BX.PreventDefault(e);
															}, this)
														} : null),
														(pseudoCompareTaskName(maxTaskName, 'disk_access_full') >= 0? {
															text: BX.message('DISK_JS_SHARING_LABEL_RIGHT_FULL'),
															href: "#",
															onclick: BX.delegate(function (e) {
																BX.PopupMenu.destroy('disk_open_menu_with_rights');
																BX.adjust(targetElement, {text: this.getRightLabelByTaskName('disk_access_full')});

																BX.onCustomEvent('onChangeRightOfSharing', [entityId, 'disk_access_full']);

																entityToNewShared[entityId]['right'] = 'disk_access_full';

																return BX.PreventDefault(e);
															}, this)
														} : null)
													],
													{
														angle: {
															position: 'top',
															offset: 45
														},
														autoHide: true,
														overlay: {
															opacity: 0.01
														},
														events: {
															onPopupClose: function() {BX.PopupMenu.destroy('disk_open_menu_with_rights');}
														}
													}
												);

											}, this)
										}
									})
								]
							}),
							BX.create('td', {
								props: {
									className: 'bx-disk-popup-shared-people-list-col3 tar'
								},
								children: [
									(!readOnly? BX.create('span', {
										props: {
											className: 'bx-disk-filepage-used-people-del'
										},
										events: {
											click: BX.delegate(function(e){
												BX.SocNetLogDestination.deleteItem(entityId, type, destFormName);
												var src = e.target || e.srcElement;
												BX.remove(src.parentNode.parentNode);
											}, this)
										}
									}) : null)
								]
							})
						]
					})
				);
			},

			openPopupMenuWithRights: function (e, entityId)
			{
				var items = [];
				var task;
				var targetElement = e.target || e.srcElement;

				for (var i in moduleTasks)
				{
					if(!moduleTasks.hasOwnProperty(i))
					{
						continue;
					}
					task = BX.clone(moduleTasks[i], true);
					items.push({
							task: task,
							text: task.TITLE,
							href: "#",
							onclick: function (e, item)
							{
								BX.adjust(targetElement, {text: item.task.TITLE});

								BX.onCustomEvent('onChangeRight', [entityId, item.task]);
								BX.onCustomEvent('onChangeSystemRight', [entityId, item.task]);

								BX.PopupMenu.destroy('disk_open_menu_with_rights');
								return BX.PreventDefault(e);
							}
						}
					);
				}

				BX.PopupMenu.show('disk_open_menu_with_rights', BX(targetElement), items,
					{
						angle: {
							position: 'top',
							offset: 45
						},
						autoHide: true,
						overlay: {
							opacity: 0.01
						},
						events: {
							onPopupClose: function() {BX.PopupMenu.destroy('disk_open_menu_with_rights');}
						}
					}
				);

			},

			setModuleTasks: function (newModuleTasks)
			{
				moduleTasks = newModuleTasks;
			},

			getFirstModuleTask: function ()
			{
				if(this.isEmptyObject(moduleTasks))
				{
					return {};
				}
				for (var i in moduleTasks)
				{
					if (moduleTasks.hasOwnProperty(i) && typeof(i) !== 'function')
					{
						return moduleTasks[i];
						break;
					}
				}

				return {};
			},

			appendRight: function (params) {

				var readOnly = params.readOnly;
				var detachOnly = params.detachOnly || false;
				var destFormName = params.destFormName;

				var entityId = params.item.id;
				var entityName = params.item.name;
				var entityAvatar = params.item.avatar;
				var type = params.type;
				var right = params.right || {};

				if(!right.title && right.id)
				{
					right.title = moduleTasks[right.id].TITLE;
				}
				else if(!right.title)
				{
					var first = this.getFirstModuleTask();
					right = {
						id: first.ID,
						title: first.TITLE
					};
					BX.onCustomEvent('onChangeRight', [entityId, first]);
				}

				var rightLabel = right.title;

				BX('bx-disk-popup-shared-people-list').appendChild(
					BX.create('tr', {
						attrs: {
							'data-dest-id': entityId
						},
						children: [
							BX.create('td', {
								props: {
									className: 'bx-disk-popup-shared-people-list-col1'
								},
								children: [
									BX.create('a', {
										props: {
											className: 'bx-disk-filepage-used-people-link'
										},
										children: [
											BX.create('span', {
												props: {
													className: 'bx-disk-filepage-used-people-avatar ' + (type != 'users'? ' group' : '')
												},
												style: {
													backgroundImage: entityAvatar? 'url("' + encodeURI(entityAvatar) + '")' : null
												}
											}),
											BX.util.htmlspecialchars(entityName)
										]
									})
								]
							}),
							BX.create('td', {
								props: {
									className: 'bx-disk-popup-shared-people-list-col2'
								},
								children: [
									BX.create('a', {
										props: {
											className: 'bx-disk-filepage-used-people-permission'
										},
										style: {
											cursor: 'pointer'
										},
										text: rightLabel,
										events: {
											click: BX.delegate(function(e){
												BX.PreventDefault(e);
												if(detachOnly)
												{
													return;
												}
												this.openPopupMenuWithRights(e, entityId);
											}, this)
										}
									})
								]
							}),
							BX.create('td', {
								props: {
									className: 'bx-disk-popup-shared-people-list-col3 tar'
								},
								children: [
									(!readOnly || detachOnly? BX.create('span', {
										props: {
											className: 'bx-disk-filepage-used-people-del'
										},
										events: {
											click: BX.delegate(function(e){
												BX.onCustomEvent('onDetachRight', [entityId]);
												if(!detachOnly)
												{
													BX.SocNetLogDestination.deleteItem(entityId, type, destFormName);
												}
												var src = e.target || e.srcElement;
												BX.remove(src.parentNode.parentNode);
											}, this)
										}
									}) : null)
								]
							})
						]
					})
				);
			},
			//system right. Todo refactor
			appendSystemRight: function (params) {
				var destFormName = params.destFormName;

				var isBitrix24 = params.isBitrix24 || false;
				var entityId = params.item.id;
				var entityName = params.item.name;
				var entityAvatar = params.item.avatar;
				var type = params.type;
				var right = params.right || {};

				var readOnly = params.readOnly;

				//todo for B24 only. Don't show user groups
				if(isBitrix24 && entityId && entityId != "G2" && entityId.search('G') == 0)
				{
					return;
				}

				if(!right.title && right.id)
				{
					right.title = moduleTasks[right.id].TITLE;
				}
				else if(!right.title)
				{
					var first = this.getFirstModuleTask();
					right = {
						id: first.ID,
						title: first.TITLE
					};
					BX.onCustomEvent('onChangeSystemRight', [entityId, first]);
				}

				var rightLabel = right.title;

				BX('bx-disk-popup-shared-people-list').appendChild(
					BX.create('tr', {
						attrs: {
							'data-dest-id': entityId
						},
						children: [
							BX.create('td', {
								props: {
									className: 'bx-disk-popup-shared-people-list-col1'
								},
								children: [
									BX.create('a', {
										props: {
											className: 'bx-disk-filepage-used-people-link'
										},
										children: [
											BX.create('span', {
												props: {
													className: 'bx-disk-filepage-used-people-avatar ' + (type != 'users'? ' group' : '')
												},
												style: {
													backgroundImage: entityAvatar? 'url("' + encodeURI(entityAvatar) + '")' : null
												}
											}),
											BX.util.htmlspecialchars(entityName)
										]
									})
								]
							}),
							BX.create('td', {
								props: {
									className: 'bx-disk-popup-shared-people-list-col2'
								},
								children: [
									(readOnly? BX.create('span', {
										props: {
											className: 'bx-disk-filepage-used-people-permission-read-only'
										},
										text: rightLabel
									}) :
									BX.create('a', {
										props: {
											className: 'bx-disk-filepage-used-people-permission'
										},
										text: rightLabel,
										events: {
											click: BX.delegate(function(e){
												BX.PreventDefault(e);
												this.openPopupMenuWithRights(e, entityId);
											}, this)
										}
									}))
								]
							}),
							BX.create('td', {
								props: {
									className: 'bx-disk-popup-shared-people-list-col3 tar'
								},
								children: [
									(!readOnly? BX.create('span', {
										props: {
											className: 'bx-disk-filepage-used-people-del'
										},
										events: {
											click: BX.delegate(function(e){
												BX.onCustomEvent('onDetachSystemRight', [entityId]);
												var src = e.target || e.srcElement;
												BX.remove(src.parentNode.parentNode);
											}, this)
										}
									}) : null)
								]
							})
						]
					})
				);
			},

			showSharingDetailWithoutEdit: function (params) {

				params = params || {};
				var objectId = params.object.id;
				var ajaxUrl = params.ajaxUrl;

				BX.Disk.modalWindowLoader(
					BX.Disk.addToLinkParam(ajaxUrl, 'action', 'showSharingDetail'),
					{
						id: 'folder_list_sharing_detail_object_' + objectId,
						responseType: 'json',
						postData: {
							objectId: objectId
						},
						afterSuccessLoad: BX.delegate(function(response)
						{
							if(response.status != 'success')
							{
								response.errors = response.errors || [{}];
								BX.Disk.showModalWithStatusAction({
									status: 'error',
									message: response.errors.pop().message
								})
							}

							var objectOwner = {
								name: response.owner.name,
								avatar: response.owner.avatar,
								link: response.owner.link
							};

							BX.Disk.modalWindow({
								modalId: 'bx-disk-detail-sharing-folder',
								title: BX.message('DISK_JS_SHARING_LABEL_TITLE_MODAL_3'),
								contentClassName: '',
								contentStyle: {
									//paddingTop: '30px',
									//paddingBottom: '70px'
								},
								events: {
									onAfterPopupShow: BX.delegate(function () {

										for (var i in response.members) {
											if (!response.members.hasOwnProperty(i)) {
												continue;
											}
											BX.Disk.appendNewShared({
												destFormName: this.destFormName,
												readOnly: true,
												item: {
													id: response.members[i].entityId,
													name: response.members[i].name,
													avatar: response.members[i].avatar
												},
												type: response.members[i].type,
												right: response.members[i].right
											})

										}
									}, this),
									onPopupClose: function () {
										this.destroy();
									}
								},
								content: [
									BX.create('div', {
										props: {
											className: 'bx-disk-popup-content'
										},
										children: [
											BX.create('table', {
												props: {
													className: 'bx-disk-popup-shared-people-list'
												},
												children: [
													BX.create('thead', {
														html: '<tr>' +
															'<td class="bx-disk-popup-shared-people-list-head-col1">' + BX.message('DISK_JS_SHARING_LABEL_OWNER') + '</td>' +
														'</tr>'
													}),
													BX.create('tr', {
														html: '<tr>' +
															'<td class="bx-disk-popup-shared-people-list-col1" style="border-bottom: none;"><a class="bx-disk-filepage-used-people-link" href="' + objectOwner.link + '"><span class="bx-disk-filepage-used-people-avatar" style="background-image: url(\'' + encodeURI(objectOwner.avatar) + '\');"></span>' + BX.util.htmlspecialchars(objectOwner.name) + '</a></td>' +
														'</tr>'
													})
												]
											}),
											BX.create('table', {
												props: {
													id: 'bx-disk-popup-shared-people-list',
													className: 'bx-disk-popup-shared-people-list'
												},
												children: [
													BX.create('thead', {
														html: '<tr>' +
															'<td class="bx-disk-popup-shared-people-list-head-col1">' + BX.message('DISK_JS_SHARING_LABEL_NAME_RIGHTS_USER') + '</td>' +
															'<td class="bx-disk-popup-shared-people-list-head-col2">' + BX.message('DISK_JS_SHARING_LABEL_NAME_RIGHTS') + '</td>' +
															'<td class="bx-disk-popup-shared-people-list-head-col3"></td>' +
														'</tr>'
													})
												]
											}),
											BX.create('div', {
												html:
														'<span class="feed-add-destination-input-box" id="feed-add-post-destination-input-box">' +
															'<input autocomplete="off" type="text" value="" class="feed-add-destination-inp" id="feed-add-post-destination-input"/>' +
														'</span>'
											})
										]
									})
								],
								buttons: [
									new BX.PopupWindowButton({
										text: BX.message('DISK_JS_BTN_CLOSE'),
										events: {
											click: function () {
												BX.PopupWindowManager.getCurrentPopup().close();
											}
										}
									})
								]
							});
						}, this)
					}
				);
			}
		});
	})();
}
