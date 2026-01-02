BX.namespace('BX.Disk');
if (!BX.Disk.pathToUser)
{
	(function() {
		let firstButtonInModalWindow = null;
		const entityToNewShared = {};
		let moduleTasks = {};

		const windowsWithoutManager = {};

		const insertInTooltipLockedInfo = function(tooltip) {
			if (!tooltip.RealAnchor || !BX.hasClass(tooltip.RealAnchor, 'js-disk-locked-document-tooltip'))
			{
				return;
			}

			const info = BX.findChildByClassName(tooltip.ROOT_DIV, 'bx-user-info-data-info', true);
			if (!info)
			{
				return;
			}

			if (BX.findChildByClassName(info, 'js-disk-locked-status', true))
			{
				return;
			}

			BX.prepend(
				BX.create('div', {
					html: `<span class="field-name">${BX.message('DISK_JS_USER_LOCKED_DOCUMENT')}</span>`,
					props: {
						className: 'js-disk-locked-status',
					},
				}),
				info,
			);
		};

		const onPullDiskEvent = function(command, params)
		{
			params = params || {};

			if (command === 'onlyoffice' && params.documentSession)
			{
				if (params.event === 'saved')
				{
					BX.onCustomEvent('Disk.OnlyOffice:onSaved', [params.object, params.documentSession]);
				}

				const notify = BX.UI.Notification.Center.getBalloonById(`session-${params.documentSession.hash}`);
				if (notify)
				{
					BX.UI.Notification.Center.notify({
						content: BX.message('DISK_JS_DOCUMENT_ONLYOFFICE_SAVED').replace('#name#', notify.getData().file.name),
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

					var reloadItem = function(id) {
						viewer.items.forEach((item) => {
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
						message,
					});

					viewer.reloadCurrentItem();

					break;
			}
		};

		BX.addCustomEvent('onPullEvent-disk', onPullDiskEvent);
		BX.addCustomEvent('onTooltipShow', insertInTooltipLockedInfo);
		BX.addCustomEvent('onTooltipInsertData', insertInTooltipLockedInfo);

		BX.addCustomEvent('BX.UI.Viewer.Controller:onBeforeShow', (viewer, index) => {
			const item = viewer.getItemByIndex(index);
			if (!item)
			{
				return;
			}
			const actions = item.getActions().filter((action) => {
				if (action.id !== 'edit')
				{
					return true;
				}

				if (!action.buttonIconClass)
				{
					action.buttonIconClass = '';
				}
				action.buttonIconClass += ` disk-viewer-panel-icon-${BX.Disk.getDocumentService()}`;

				if (!action.params || !action.params.dependsOnService)
				{
					return true;
				}

				return action.params.dependsOnService === BX.Disk.getDocumentService();
			});

			item.setActions(actions);
		});

		BX.addCustomEvent('onTooltipHide', (tooltip) => {
			if (!tooltip.RealAnchor || !BX.hasClass(tooltip.RealAnchor, 'js-disk-locked-document-tooltip'))
			{
				return;
			}

			const info = BX.findChildByClassName(tooltip.ROOT_DIV, 'js-disk-locked-status', true);
			if (!info)
			{
				return;
			}

			BX.remove(info);
		});

		function modifyAjaxConfig(config)
		{
			config.data = config.data || {};
			config.data.SITE_ID = BX.message('SITE_ID');
			config.data.sessid = BX.bitrix_sessid();

			return config;
		}

		Object.assign(BX.Disk, {
			apiVersion: 22,
			pathToUser: '/company/personal/user/#user_id#/',
			endEditSession(session)
			{
				BX.ajax.runAction('disk.api.onlyoffice.endSession', {
					json: {
						sessionId: session.id,
						documentSessionHash: session.hash,
					},
				}).then((response) => {
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
						id: `session-${session.hash}`,
						autoHide: false,
						content: BX.message('DISK_JS_DOCUMENT_ONLYOFFICE_SAVE_PROCESS').replace('#name#', response.data.file.name),
						data: {
							file: response.data.file,
						},
					});
				});
			},
			hideLoader()
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
			showLoader(params)
			{
				params = params || {};
				BX.addClass(document.body, 'disk-body-overlay');
				const div = document.body.appendChild(this.getLoaderWrapper(params));

				BX.ZIndexManager.register(div);

				this.getLoader(this.loaderNode).show();
			},
			getLoaderWrapper(params)
			{
				if (!this.loaderWrapper)
				{
					this.loaderWrapper = BX.create('div', {
						props: {
							className: 'disk-body-overlay-wrapper',
						},
						style: {
							zIndex: params.zIndex,
						},
						children: [
							BX.create('div', {
								props: {
									className: 'disk-body-overlay-container',
								},
								children: [
									this.loaderNode = BX.create('div', {
										props: {
											className: 'disk-body-overlay-container-loader',
										},
									}),
									BX.create('div', {
										props: {
											className: 'disk-body-overlay-container-text',
										},
										text: params.text || '',
									}),
								],

							}),
						],
					});
				}

				return this.loaderWrapper;
			},
			getLoader(targetNode)
			{
				if (!this.loader)
				{
					this.loader = new BX.Loader({
						target: targetNode,
						size: 130,
					});
				}

				return this.loader;
			},
			ajax(config)
			{
				return BX.ajax(modifyAjaxConfig(config));
			},
			ajaxPromise(config)
			{
				return BX.ajax.promise(modifyAjaxConfig(config)).then((response) => {
					if (!response || response.status != 'success')
					{
						BX.Disk.showModalWithStatusAction(response);

						const p = new BX.Promise();
						p.reject(response);

						return p;
					}

					return response;
				});
			},
			isEmptyObject(obj)
			{
				if (obj == null)

				
      { return true;
				}

				if (obj.length > 0 && obj.length > 0)

				
      { return false;
				}

				if (obj.length === 0)

				
      { return true;
				}

				for (const key in obj)
				{
					if (hasOwnProperty.call(obj, key))

					
       { return false;
					}
				}

				return true;
			},
			_keyPress(e)
			{
				const destDialog = BX.SocNetLogDestination && (BX.SocNetLogDestination.isOpenDialog() || BX.SocNetLogDestination.isOpenSearch());
				const key = (e || window.event).keyCode || (e || window.event).charCode;
				// enter
				if (key == 13 && firstButtonInModalWindow && !destDialog)
				{
					BX.fireEvent(firstButtonInModalWindow.buttonNode, 'click');

					return BX.PreventDefault(e);
				}
			},
			modalWindow(params)
			{
				params = params || {};
				params.title = params.title || false;
				params.bindElement = params.bindElement || null;
				params.bindOptions = params.bindOptions || {};
				params.overlay = typeof params.overlay === 'undefined' ? true : params.overlay;
				params.autoHide = params.autoHide || false;
				params.closeIcon = typeof params.closeIcon === 'undefined' ? true : params.closeIcon;
				params.modalId = params.modalId || `disk_modal_window_${Math.random() * (200_000 - 100) + 100}`;
				params.withoutContentWrap = typeof params.withoutContentWrap === 'undefined' ? false : params.withoutContentWrap;
				params.contentClassName = params.contentClassName || '';
				params.contentStyle = params.contentStyle || {};
				params.content = params.content || [];
				params.buttons = params.buttons || false;
				params.events = params.events || {};
				params.withoutWindowManager = Boolean(params.withoutWindowManager) || false;

				if (!BX.type.isArray(params.content))
				{
					params.content = [params.content];
				}

				let contentDialogChildren = [];
				if (params.withoutContentWrap)
				{
					contentDialogChildren = contentDialogChildren.concat(params.content);
				}
				else
				{
					contentDialogChildren.push(BX.create('div', {
						props: {
							className: `bx-disk-popup-content${params.contentClassName}`,
						},
						style: params.contentStyle,
						children: params.content,
					}));
				}
				const buttons = params.buttons;
				if (params.htmlButtons)
				{
					// support old style of buttons
					const htmlButtons = [];
					for (const i in params.htmlButtons)
					{
						if (!params.htmlButtons.hasOwnProperty(i))
						{
							continue;
						}

						if (i > 0)
						{
							htmlButtons.push(BX.create('SPAN', { html: '&nbsp;' }));
						}
						htmlButtons.push(params.htmlButtons[i]);
					}

					contentDialogChildren.push(BX.create('div', {
						props: {
							className: 'bx-disk-popup-buttons',
						},
						children: htmlButtons,
					}));
				}

				const contentDialog = BX.create('div', {
					props: {
						className: 'bx-disk-popup-container',
					},
					children: contentDialogChildren,
				});

				const afterPopupShow = params.events.onAfterPopupShow;
				params.events.onAfterPopupShow = BX.delegate(function() {
					if (buttons.length > 0)
					{
						firstButtonInModalWindow = buttons[0];
						BX.bind(document, 'keydown', BX.proxy(this._keyPress, this));
					}

					if (afterPopupShow)
					{
						BX.delegate(afterPopupShow, BX.proxy_context)();
					}
				}, this);

				const closePopup = params.events.onPopupClose;
				params.events.onPopupClose = BX.delegate(function() {
					firstButtonInModalWindow = null;
					try
					{
						BX.unbind(document, 'keydown', BX.proxy(this._keypress, this));
					}
					catch
					{}

					if (closePopup)
					{
						BX.delegate(closePopup, BX.proxy_context)();
					}

					if (params.withoutWindowManager)
					{
						delete windowsWithoutManager[params.modalId];
					}

					BX.proxy_context.destroy();
				}, this);

				const destroyPopup = params.events.onPopupDestroy;
				params.events.onPopupDestroy = BX.delegate(function() {
					try
					{
						BX.unbind(document, 'keydown', BX.proxy(this._keypress, this));
					}
					catch
					{}

					if (destroyPopup)
					{
						BX.delegate(destroyPopup, BX.proxy_context)();
					}
				}, this);

				let modalWindow;
				if (params.withoutWindowManager)
				{
					if (windowsWithoutManager[params.modalId])
					{
						return windowsWithoutManager[params.modalId];
					}
					modalWindow = new BX.PopupWindow(params.modalId, params.bindElement, {
						titleBar: params.title,
						className: params.className,
						content: contentDialog,
						bindOptions: params.bindOptions,
						closeByEsc: true,
						height: params.height,
						closeIcon: params.closeIcon,
						autoHide: params.autoHide,
						overlay: params.overlay,
						events: params.events,
						buttons: params.buttons,
						zIndex: isNaN(params.zIndex) ? 0 : params.zIndex,
					});
					windowsWithoutManager[params.modalId] = modalWindow;
				}
				else
				{
					console.log('params', params);
					modalWindow = BX.PopupWindowManager.create(params.modalId, params.bindElement, {
						titleBar: params.title,
						className: params.className,
						content: contentDialog,
						bindOptions: params.bindOptions,
						closeByEsc: true,
						height: params.height,
						closeIcon: params.closeIcon,
						autoHide: params.autoHide,
						overlay: params.overlay,
						events: params.events,
						buttons: params.buttons,
						zIndex: isNaN(params.zIndex) ? 0 : params.zIndex,
					});
				}

				modalWindow.show();

				return modalWindow;
			},

			modalWindowLoader(queryUrl, params, bindElement)
			{
				bindElement = bindElement || null;
				params = params || {};
				const modalId = params.id;
				const expectResponseType = params.responseType || 'html';
				const afterSuccessLoad = params.afterSuccessLoad || null;
				const onPopupClose = params.onPopupClose || null;
				const postData = params.postData || {};

				const popup = BX.PopupWindowManager.create(
					`bx-disk-${modalId}`,
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
										height: '30px',
									},
									children: [
										BX.create('div', {
											style: {
												display: 'table-cell',
												verticalAlign: 'middle',
												textAlign: 'center',
											},
											children: [
												BX.create('div', {
													props: {
														className: 'bx-disk-wrap-loading-modal',
													},
												}),
												BX.create('span', {
													text: '',
												}),
											],
										}),
									],
								}),
							],
						}),
						closeByEsc: true,
						events: {
							onPopupClose()
							{
								if (onPopupClose)
								{
									BX.delegate(onPopupClose, this)();
								}

								this.destroy();
							},
						},
					},
				);
				popup.show();

				postData.sessid = BX.bitrix_sessid();
				postData.SITE_ID = BX.message('SITE_ID');

				BX.ajax({
					url: queryUrl,
					method: 'POST',
					dataType: expectResponseType,
					data: postData,
					onsuccess: BX.delegate((data) => {
						if (expectResponseType == 'html')
						{
							popup.setContent(BX.create('DIV', { html: data }));
							popup.adjustPosition();
						}
						else if (expectResponseType == 'json')
						{
							data = data || {};
						}

						afterSuccessLoad && afterSuccessLoad(data, popup);
					}, this),
					onfailure(data)
					{},
				});
			},

			modalWindowActionLoader(action, params, bindElement)
			{
				bindElement = bindElement || null;
				params = params || {};
				const modalId = params.id;
				const afterSuccessLoad = params.afterSuccessLoad || null;
				const onPopupClose = params.onPopupClose || null;
				const postData = params.postData || {};

				const popup = BX.PopupWindowManager.create(
					`bx-disk-${modalId}`,
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
										height: '30px',
									},
									children: [
										BX.create('div', {
											style: {
												display: 'table-cell',
												verticalAlign: 'middle',
												textAlign: 'center',
											},
											children: [
												BX.create('div', {
													props: {
														className: 'bx-disk-wrap-loading-modal',
													},
												}),
												BX.create('span', {
													text: '',
												}),
											],
										}),
									],
								}),
							],
						}),
						closeByEsc: true,
						events: {
							onPopupClose()
							{
								if (onPopupClose)
								{
									BX.delegate(onPopupClose, this)();
								}

								this.destroy();
							},
						},
					},
				);
				popup.show();

				BX.ajax.runAction(action, {
					data: postData,
				}).then((response) => {
					afterSuccessLoad && afterSuccessLoad(response, popup);
				});
			},

			addToLinkParam(link, name, value)
			{
				if (link.length === 0)
				{
					return `?${name}=${value}`;
				}
				link = BX.util.remove_url_param(link, name);
				if (link.includes('?'))
				{
					return `${link}&${name}=${value}`;
				}

				return `${link}?${name}=${value}`;
			},

			getUrlParameter(name)
			{
				name = name.replace(/\[/, '\\[').replace(/]/, '\\]');
				const regex = new RegExp(`[\\?&]${name}=([^&#]*)`);
				const results = regex.exec(location.search);

				return results === null ? '' : decodeURIComponent(results[1].replaceAll('+', ' '));
			},

			sendTelemetryEvent(options)
			{},

			getFirstErrorFromResponse(reponse)
			{
				reponse = reponse || {};
				if (!reponse.errors)

				
      { return '';
				}

				return reponse.errors.shift().message;
			},

			showModalWithStatusAction(response, action)
			{
				response = response || { status: 'success' };
				if (!response.message)
				{
					if (response.status == 'success')
					{
						response.message = BX.message('DISK_JS_STATUS_ACTION_SUCCESS');
					}
					else
					{
						response.message = `${BX.message('DISK_JS_STATUS_ACTION_ERROR')}. ${this.getFirstErrorFromResponse(response)}`;
					}
				}

				BX.UI.Notification.Center.notify({
					content: response.message,
				});
			},
			showActionModal(params)
			{
				const text = params.text;
				const html = params.html;
				const autoHide = params.autoHide;
				let iconSrc;
				if (params.showLoaderIcon)
				{
					iconSrc = '/bitrix/js/main/core/images/yell-waiter.gif';
				}
				else if (params.showSuccessIcon)
				{
					iconSrc = '/bitrix/js/main/core/images/viewer-tick.png';
				}
				else if (params.icon)
				{
					iconSrc = params.icon;
				}

				const messageBox = BX.create('div', {
					props: {
						className: 'bx-disk-alert',
					},
					children: [
						BX.create('span', {
							props: {
								className: 'bx-disk-alert-icon',
							},
							children: [
								iconSrc ? BX.create('img', {
									props: {
										src: iconSrc,
									},
								}) : null,
							],
						}),

						BX.create('span', {
							props: {
								className: 'bx-disk-aligner',
							},
						}),
						BX.create('span', {
							props: {
								className: 'bx-disk-alert-text',
							},
							text,
							html,
						}),
						BX.create('div', {
							props: {
								className: 'bx-disk-alert-footer',
							},
						}),
					],
				});

				const currentPopup = BX.PopupWindowManager.getCurrentPopup();
				if (currentPopup)
				{
					currentPopup.destroy();
				}

				let idTimeout = setTimeout(() => {
					if (!autoHide)
					{
						return;
					}

					const w = BX.PopupWindowManager.getCurrentPopup();
					if (!w || w.uniquePopupId != 'bx-disk-status-action')
					{
						return;
					}
					w.close();
					w.destroy();
				}, 3000);
				const popupConfirm = BX.PopupWindowManager.create('bx-disk-status-action', null, {
					content: messageBox,
					onPopupClose()
					{
						this.destroy();
						clearTimeout(idTimeout);
					},
					autoHide,
					zIndex: 999_999 + 1,
					className: 'bx-disk-alert-popup',
				});
				popupConfirm.show();

				BX('bx-disk-status-action').onmouseover = function(e)
				{
					clearTimeout(idTimeout);
				};

				if (!autoHide)
				{
					return popupConfirm;
				}

				BX('bx-disk-status-action').onmouseout = function(e)
				{
					idTimeout = setTimeout(() => {
						const w = BX.PopupWindowManager.getCurrentPopup();
						if (!w || w.uniquePopupId != 'bx-disk-status-action')
						{
							return;
						}
						w.close();
						w.destroy();
					}, 3000);
				};

				return popupConfirm;
			},

			storePathToUser(link)
			{
				if (link)
				{
					this.pathToUser = link;
				}
			},

			getUrlToShowObjectInGrid(objectId, params)
			{
				params = params || {};

				params.objectId = objectId;
				params.SITE_ID = BX.message('SITE_ID');

				return BX.util.add_url_param('/bitrix/tools/disk/focus.php?ncc=1&action=showObjectInGrid', params);
			},

			getUrlToShowFileDetail(fileId, params)
			{
				params = params || {};

				params.fileId = fileId;
				params.SITE_ID = BX.message('SITE_ID');

				return BX.util.add_url_param('/bitrix/tools/disk/focus.php?ncc=1&action=openFileDetail', params);
			},

			isAvailableOnlyOffice()
			{
				return BX.message('disk_onlyoffice_available');
			},

			getDocumentService()
			{
				return BX.message('disk_document_service');
			},

			openBlankDocumentPopup()
			{
				if ((!BX.Disk.getDocumentService() || (BX.Disk.getDocumentService() === 'l' || BX.Disk.getDocumentService() === 'onlyoffice')))
				{
					return null;
				}

				return BX.util.popup('/bitrix/services/main/ajax.php?action=disk.documentService.love', 1030, 700);
			},

			saveDocumentService(serviceCode)
			{
				const changed = serviceCode !== BX.Disk.getDocumentService();
				if (BX.Disk.isAvailableOnlyOffice())
				{
					BX.userOptions.save('disk', 'doc_service', 'primary', serviceCode);
				}
				else
				{
					BX.userOptions.save('disk', 'doc_service', 'default', serviceCode);
				}

				BX.message({ disk_document_service: serviceCode });

				if (changed)
				{
					BX.onCustomEvent('Disk:onChangeDocumentService', [BX.message('disk_document_service')]);
				}

				BX.userOptions.send(null);
			},

			deactiveBanner(name)
			{
				BX.userOptions.save('disk', '~banner-offer', name, true);
				BX.userOptions.send(null);
			},

			getPathToUser(userId)
			{
				return this.pathToUser.replace('#USER_ID#', userId).replace('#user_id#', userId);
			},

			getNumericCase(number, once, multi_21, multi_2_4, multi_5_20)
			{
				if (number == 1)
				{
					return once;
				}

				if (number < 0)
				{
					number = -number;
				}

				number %= 100;
				if (number >= 5 && number <= 20)
				{
					return multi_5_20;
				}

				number %= 10;
				if (number == 1)
				{
					return multi_21;
				}

				if (number >= 2 && number <= 4)
				{
					return multi_2_4;
				}

				return multi_5_20;
			},

			getRightLabelByTaskName(name) {
				switch (name.toLowerCase())
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

			appendNewShared(params) {
				const readOnly = params.readOnly;
				const maxTaskName = params.maxTaskName || 'disk_access_full';
				const destFormName = params.destFormName;

				const entityId = params.item.id;
				const entityName = params.item.name;
				const entityAvatar = params.item.avatar;
				const type = params.type;
				const right = params.right || 'disk_access_read';

				entityToNewShared[entityId] = {
					item: params.item,
					type: params.type,
					right,
				};

				function pseudoCompareTaskName(taskName1, taskName2)
				{
					let taskName1Pos;
					let taskName2Pos;
					switch (taskName1)
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
							// unknown task names
							return 0;
					}

					switch (taskName2)
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
							// unknown task names
							return 0;
					}

					if (taskName1Pos == taskName2Pos)
					{
						return 0;
					}

					return taskName1Pos > taskName2Pos ? 1 : -1;
				}

				BX('bx-disk-popup-shared-people-list').appendChild(
					BX.create('tr', {
						attrs: {
							'data-dest-id': entityId,
						},
						children: [
							BX.create('td', {
								props: {
									className: 'bx-disk-popup-shared-people-list-col1',
								},
								children: [
									BX.create('a', {
										props: {
											className: 'bx-disk-filepage-used-people-link',
										},
										children: [
											BX.create('span', {
												props: {
													className: `bx-disk-filepage-used-people-avatar ${type == 'users' ? '' : ' group'}`,
												},
												style: {
													backgroundImage: entityAvatar ? `url("${encodeURI(entityAvatar)}")` : null,
												},
											}),
											BX.util.htmlspecialchars(entityName),
										],
									}),
								],
							}),
							BX.create('td', {
								props: {
									className: 'bx-disk-popup-shared-people-list-col2',
								},
								children: [
									BX.create(readOnly ? 'span' : 'a', {
										props: {
											className: readOnly ? 'bx-disk-filepage-used-people-permission-read-only' : 'bx-disk-filepage-used-people-permission',
										},
										style: {
											cursor: readOnly ? '' : 'pointer',
										},
										text: this.getRightLabelByTaskName(right),
										events: {
											click: BX.delegate(function(e) {
												if (readOnly)
												{
													return BX.PreventDefault(e);
												}
												const targetElement = e.target || e.srcElement;
												BX.PopupMenu.show(
													'disk_open_menu_with_rights',
													BX(targetElement),
													[
														(pseudoCompareTaskName(maxTaskName, 'disk_access_read') >= 0 ? {
															text: BX.message('DISK_JS_SHARING_LABEL_RIGHT_READ'),
															href: '#',
															onclick: BX.delegate(function(e) {
																BX.PopupMenu.destroy('disk_open_menu_with_rights');
																BX.adjust(targetElement, { text: this.getRightLabelByTaskName('disk_access_read') });

																BX.onCustomEvent('onChangeRightOfSharing', [entityId, 'disk_access_read']);

																entityToNewShared[entityId].right = 'disk_access_read';

																return BX.PreventDefault(e);
															}, this),
														} : null),
														(pseudoCompareTaskName(maxTaskName, 'disk_access_add') >= 0 ? {
															text: BX.message('DISK_JS_SHARING_LABEL_RIGHT_ADD'),
															href: '#',
															onclick: BX.delegate(function(e) {
																BX.PopupMenu.destroy('disk_open_menu_with_rights');
																BX.adjust(targetElement, { text: this.getRightLabelByTaskName('disk_access_add') });

																BX.onCustomEvent('onChangeRightOfSharing', [entityId, 'disk_access_add']);

																entityToNewShared[entityId].right = 'disk_access_add';

																return BX.PreventDefault(e);
															}, this),
														} : null),
														(pseudoCompareTaskName(maxTaskName, 'disk_access_edit') >= 0 ? {
															text: BX.message('DISK_JS_SHARING_LABEL_RIGHT_EDIT'),
															href: '#',
															onclick: BX.delegate(function(e) {
																BX.PopupMenu.destroy('disk_open_menu_with_rights');
																BX.adjust(targetElement, { text: this.getRightLabelByTaskName('disk_access_edit') });

																BX.onCustomEvent('onChangeRightOfSharing', [entityId, 'disk_access_edit']);

																entityToNewShared[entityId].right = 'disk_access_edit';

																return BX.PreventDefault(e);
															}, this),
														} : null),
														(pseudoCompareTaskName(maxTaskName, 'disk_access_full') >= 0 ? {
															text: BX.message('DISK_JS_SHARING_LABEL_RIGHT_FULL'),
															href: '#',
															onclick: BX.delegate(function(e) {
																BX.PopupMenu.destroy('disk_open_menu_with_rights');
																BX.adjust(targetElement, { text: this.getRightLabelByTaskName('disk_access_full') });

																BX.onCustomEvent('onChangeRightOfSharing', [entityId, 'disk_access_full']);

																entityToNewShared[entityId].right = 'disk_access_full';

																return BX.PreventDefault(e);
															}, this),
														} : null),
													],
													{
														angle: {
															position: 'top',
															offset: 45,
														},
														autoHide: true,
														overlay: {
															opacity: 0.01,
														},
														events: {
															onPopupClose() { BX.PopupMenu.destroy('disk_open_menu_with_rights'); },
														},
													},
												);
											}, this),
										},
									}),
								],
							}),
							BX.create('td', {
								props: {
									className: 'bx-disk-popup-shared-people-list-col3 tar',
								},
								children: [
									(readOnly ? null : BX.create('span', {
										props: {
											className: 'bx-disk-filepage-used-people-del',
										},
										events: {
											click: BX.delegate((e) => {
												BX.SocNetLogDestination.deleteItem(entityId, type, destFormName);
												const src = e.target || e.srcElement;
												BX.remove(src.parentNode.parentNode);
											}, this),
										},
									})),
								],
							}),
						],
					}),
				);
			},

			openPopupMenuWithRights(e, entityId)
			{
				const items = [];
				let task;
				const targetElement = e.target || e.srcElement;

				for (const i in moduleTasks)
				{
					if (!moduleTasks.hasOwnProperty(i))
					{
						continue;
					}
					task = BX.clone(moduleTasks[i], true);
					items.push({
						task,
						text: task.TITLE,
						href: '#',
						onclick(e, item)
						{
							BX.adjust(targetElement, { text: item.task.TITLE });

							BX.onCustomEvent('onChangeRight', [entityId, item.task]);
							BX.onCustomEvent('onChangeSystemRight', [entityId, item.task]);

							BX.PopupMenu.destroy('disk_open_menu_with_rights');

							return BX.PreventDefault(e);
						},
					});
				}

				BX.PopupMenu.show(
					'disk_open_menu_with_rights',
					BX(targetElement),
					items,
					{
						angle: {
							position: 'top',
							offset: 45,
						},
						autoHide: true,
						overlay: {
							opacity: 0.01,
						},
						events: {
							onPopupClose() { BX.PopupMenu.destroy('disk_open_menu_with_rights'); },
						},
					},
				);
			},

			setModuleTasks(newModuleTasks)
			{
				moduleTasks = newModuleTasks;
			},

			getFirstModuleTask()
			{
				if (this.isEmptyObject(moduleTasks))
				{
					return {};
				}

				for (const i in moduleTasks)
				{
					if (moduleTasks.hasOwnProperty(i) && typeof (i) !== 'function')
					{
						return moduleTasks[i];
						break;
					}
				}

				return {};
			},

			appendRight(params) {
				const readOnly = params.readOnly;
				const detachOnly = params.detachOnly || false;
				const destFormName = params.destFormName;

				const entityId = params.item.id;
				const entityName = params.item.name;
				const entityAvatar = params.item.avatar;
				const type = params.type;
				let right = params.right || {};

				if (!right.title && right.id)
				{
					right.title = moduleTasks[right.id].TITLE;
				}
				else if (!right.title)
				{
					const first = this.getFirstModuleTask();
					right = {
						id: first.ID,
						title: first.TITLE,
					};
					BX.onCustomEvent('onChangeRight', [entityId, first]);
				}

				const rightLabel = right.title;

				BX('bx-disk-popup-shared-people-list').appendChild(
					BX.create('tr', {
						attrs: {
							'data-dest-id': entityId,
						},
						children: [
							BX.create('td', {
								props: {
									className: 'bx-disk-popup-shared-people-list-col1',
								},
								children: [
									BX.create('a', {
										props: {
											className: 'bx-disk-filepage-used-people-link',
										},
										children: [
											BX.create('span', {
												props: {
													className: `bx-disk-filepage-used-people-avatar ${type == 'users' ? '' : ' group'}`,
												},
												style: {
													backgroundImage: entityAvatar ? `url("${encodeURI(entityAvatar)}")` : null,
												},
											}),
											BX.util.htmlspecialchars(entityName),
										],
									}),
								],
							}),
							BX.create('td', {
								props: {
									className: 'bx-disk-popup-shared-people-list-col2',
								},
								children: [
									BX.create('a', {
										props: {
											className: 'bx-disk-filepage-used-people-permission',
										},
										style: {
											cursor: 'pointer',
										},
										text: rightLabel,
										events: {
											click: BX.delegate(function(e) {
												BX.PreventDefault(e);
												if (detachOnly)
												{
													return;
												}
												this.openPopupMenuWithRights(e, entityId);
											}, this),
										},
									}),
								],
							}),
							BX.create('td', {
								props: {
									className: 'bx-disk-popup-shared-people-list-col3 tar',
								},
								children: [
									(!readOnly || detachOnly ? BX.create('span', {
										props: {
											className: 'bx-disk-filepage-used-people-del',
										},
										events: {
											click: BX.delegate((e) => {
												BX.onCustomEvent('onDetachRight', [entityId]);
												if (!detachOnly)
												{
													BX.SocNetLogDestination.deleteItem(entityId, type, destFormName);
												}
												const src = e.target || e.srcElement;
												BX.remove(src.parentNode.parentNode);
											}, this),
										},
									}) : null),
								],
							}),
						],
					}),
				);
			},
			// system right. Todo refactor
			appendSystemRight(params) {
				const destFormName = params.destFormName;

				const isBitrix24 = params.isBitrix24 || false;
				const entityId = params.item.id;
				const entityName = params.item.name;
				const entityAvatar = params.item.avatar;
				const type = params.type;
				let right = params.right || {};

				const readOnly = params.readOnly;

				// todo for B24 only. Don't show user groups
				if (isBitrix24 && entityId && entityId != 'G2' && entityId.search('G') == 0)
				{
					return;
				}

				if (!right.title && right.id)
				{
					right.title = moduleTasks[right.id].TITLE;
				}
				else if (!right.title)
				{
					const first = this.getFirstModuleTask();
					right = {
						id: first.ID,
						title: first.TITLE,
					};
					BX.onCustomEvent('onChangeSystemRight', [entityId, first]);
				}

				const rightLabel = right.title;

				BX('bx-disk-popup-shared-people-list').appendChild(
					BX.create('tr', {
						attrs: {
							'data-dest-id': entityId,
						},
						children: [
							BX.create('td', {
								props: {
									className: 'bx-disk-popup-shared-people-list-col1',
								},
								children: [
									BX.create('a', {
										props: {
											className: 'bx-disk-filepage-used-people-link',
										},
										children: [
											BX.create('span', {
												props: {
													className: `bx-disk-filepage-used-people-avatar ${type == 'users' ? '' : ' group'}`,
												},
												style: {
													backgroundImage: entityAvatar ? `url("${encodeURI(entityAvatar)}")` : null,
												},
											}),
											BX.util.htmlspecialchars(entityName),
										],
									}),
								],
							}),
							BX.create('td', {
								props: {
									className: 'bx-disk-popup-shared-people-list-col2',
								},
								children: [
									(readOnly ? BX.create('span', {
										props: {
											className: 'bx-disk-filepage-used-people-permission-read-only',
										},
										text: rightLabel,
									})
										: BX.create('a', {
											props: {
												className: 'bx-disk-filepage-used-people-permission',
											},
											text: rightLabel,
											events: {
												click: BX.delegate(function(e) {
													BX.PreventDefault(e);
													this.openPopupMenuWithRights(e, entityId);
												}, this),
											},
										})),
								],
							}),
							BX.create('td', {
								props: {
									className: 'bx-disk-popup-shared-people-list-col3 tar',
								},
								children: [
									(readOnly ? null : BX.create('span', {
										props: {
											className: 'bx-disk-filepage-used-people-del',
										},
										events: {
											click: BX.delegate((e) => {
												BX.onCustomEvent('onDetachSystemRight', [entityId]);
												const src = e.target || e.srcElement;
												BX.remove(src.parentNode.parentNode);
											}, this),
										},
									})),
								],
							}),
						],
					}),
				);
			},

			showSharingDetailWithoutEdit(params) {
				params = params || {};

				const objectId = params.object.id;
				const ajaxUrl = params.ajaxUrl;

				BX.Disk.modalWindowLoader(
					BX.Disk.addToLinkParam(ajaxUrl, 'action', 'showSharingDetail'),
					{
						id: `folder_list_sharing_detail_object_${objectId}`,
						responseType: 'json',
						postData: {
							objectId,
						},
						afterSuccessLoad: BX.delegate(function(response)
						{
							if (response.status != 'success')
							{
								response.errors = response.errors || [{}];
								BX.Disk.showModalWithStatusAction({
									status: 'error',
									message: response.errors.pop().message,
								});
							}

							const objectOwner = {
								name: response.owner.name,
								avatar: response.owner.avatar,
								link: response.owner.link,
							};

							if (response.unifiedLink)
							{
								const accessLevel = response.unifiedLink.availableAccessLevels.find((accessLevel) => { // eslint-disable-next-line no-mixed-spaces-and-tabs
									return accessLevel.value === response.unifiedLink.currentAccessLevel;
								});

								const accessLevelNode = BX.create('span', {
									props: {
										className: 'bx-disk-filepage-used-people-permission-read-only',
									},
									text: accessLevel.name,
								});

								var unifiedLinkElement = BX.create('table', {
									props: {
										id: 'bx-disk-popup-shared-universal-list',
										className: 'bx-disk-popup-shared-people-list',
									},
									children: [
										BX.create('thead', {
											children: [
												BX.create('tr', {
													children: [
														BX.create('td', {
															props: { className: 'bx-disk-popup-shared-people-list-head-col1' },
														}),
														BX.create('td', {
															props: { className: 'bx-disk-popup-shared-people-list-head-col2' },
															text: BX.message('DISK_JS_SHARING_LABEL_NAME_RIGHTS'),
														}),
														BX.create('td', {
															props: { className: 'bx-disk-popup-shared-people-list-head-col3' },
														}),
													],
												}),
												BX.create('tr', {
													children: [
														BX.create('td', {
															props: { className: 'bx-disk-popup-shared-people-list-head-col1' },
															children: [
																BX.create('span', {
																	props: { className: 'bx-disk-filepage-used-people-link' },
																	children: [
																		BX.create('span', {
																			props: {
																				className: 'bx-disk-filepage-used-people-avatar link',
																				style: '--ui-icon-set__icon-size: 15px;',
																			},
																		}),
																		BX.create('span', {
																			text: BX.message('DISK_JS_SHARING_UNIFIED_RIGHT_USERS'),
																		}),
																	],
																}),
															],
														}),
														BX.create('td', {
															props: { className: 'bx-disk-popup-shared-people-list-head-col2' },
															children: [accessLevelNode],
														}),
														BX.create('td', {
															props: { className: 'bx-disk-popup-shared-people-list-head-col3' },
														}),
													],
												}),
											],
										}),
									],
								});
							}

							BX.Disk.modalWindow({
								modalId: 'bx-disk-detail-sharing-folder',
								title: BX.message('DISK_JS_SHARING_LABEL_TITLE_MODAL_3'),
								contentClassName: '',
								contentStyle: {
									// paddingTop: '30px',
									// paddingBottom: '70px'
								},
								events: {
									onAfterPopupShow: BX.delegate(function() {
										for (const i in response.members)
										{
											if (!response.members.hasOwnProperty(i))
											{
												continue;
											}
											BX.Disk.appendNewShared({
												destFormName: this.destFormName,
												readOnly: true,
												item: {
													id: response.members[i].entityId,
													name: response.members[i].name,
													avatar: response.members[i].avatar,
												},
												type: response.members[i].type,
												right: response.members[i].right,
											});
										}
									}, this),
									onPopupClose() {
										this.destroy();
									},
								},
								content: [
									BX.create('div', {
										props: {
											className: 'bx-disk-popup-content',
										},
										children: [
											BX.create('table', {
												props: {
													className: 'bx-disk-popup-shared-people-list',
												},
												children: [
													BX.create('thead', {
														html: '<tr>'
															+ `<td class="bx-disk-popup-shared-people-list-head-col1">${BX.message('DISK_JS_SHARING_LABEL_OWNER')}</td>`
														+ '</tr>',
													}),
													BX.create('tr', {
														html: '<tr>'
															+ `<td class="bx-disk-popup-shared-people-list-col1" style="border-bottom: none;"><a class="bx-disk-filepage-used-people-link" href="${objectOwner.link}"><span class="bx-disk-filepage-used-people-avatar" style="background-image: url('${encodeURI(objectOwner.avatar)}');"></span>${BX.util.htmlspecialchars(objectOwner.name)}</a></td>`
														+ '</tr>',
													}),
												],
											}),
											unifiedLinkElement,
											BX.create('table', {
												props: {
													id: 'bx-disk-popup-shared-people-list',
													className: 'bx-disk-popup-shared-people-list',
												},
												children: [
													BX.create('thead', {
														html: '<tr>'
															+ `<td class="bx-disk-popup-shared-people-list-head-col1">${BX.message('DISK_JS_SHARING_LABEL_NAME_RIGHTS_USER')}</td>`
															+ `<td class="bx-disk-popup-shared-people-list-head-col2">${BX.message('DISK_JS_SHARING_LABEL_NAME_RIGHTS')}</td>`
															+ '<td class="bx-disk-popup-shared-people-list-head-col3"></td>'
														+ '</tr>',
													}),
												],
											}),
											BX.create('div', {
												html:
														'<span class="feed-add-destination-input-box" id="feed-add-post-destination-input-box">'
															+ '<input autocomplete="off" type="text" value="" class="feed-add-destination-inp" id="feed-add-post-destination-input"/>'
														+ '</span>',
											}),
										],
									}),
								],
								buttons: [
									new BX.PopupWindowButton({
										text: BX.message('DISK_JS_BTN_CLOSE'),
										events: {
											click() {
												BX.PopupWindowManager.getCurrentPopup().close();
											},
										},
									}),
								],
							});
						}, this),
					},
				);
			},
		});
	})();
}
