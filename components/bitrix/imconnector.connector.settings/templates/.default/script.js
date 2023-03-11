;(function(window){

	if (!!window.BX.ImConnectorLinesConfigEdit)
		return;

	var selectorQueueInstance = null;
	var configMenu = null;

	window.BX.ImConnectorLinesConfigEdit = {
		initDestination : function(node, params)
		{
			if (selectorQueueInstance === null)
			{
				selectorQueueInstance = new Queue(params);
			}
			selectorQueueInstance.setInput(BX(node));

			this.receiveReloadUsersMessage();
		},

		createLineAction : function(url, isIframe)
		{
			var newLine = new BX.ImConnectorConnectorSettings();
			newLine.createLine(url, isIframe);
		},
		initConfigMenu : function(params)
		{
			configMenu = new BX.ImConnectorLinesMenu(params);
		},
		receiveReloadUsersMessage: function()
		{
			BX.addCustomEvent(
				"SidePanel.Slider:onMessage",
				BX.delegate(
					function(event) {
						if (event.getEventId() === "ImOpenlines:reloadUsersList")
						{
							var items = event.getData();

							if(
								!!items &&
								!!selectorQueueInstance
							)
							{
								selectorQueueInstance.params.queueItems = [];
								selectorQueueInstance.destroy();
								items.forEach(BX.proxy(function (item)
								{
									selectorQueueInstance.params.queueItems.push(JSON.parse(JSON.stringify(item)));
								}, this));
								selectorQueueInstance.createSelector();
							}
						}
						else if (event.getEventId() === "ImOpenlines:updateLinesSubmit")
						{
							if (configMenu !== null)
							{
								var lineId = event.getData();
								setTimeout(function(){
									configMenu.reloadItem(lineId);
								}, 500);
							}
						}
					},
					this
				)
			);
		},
	};

	var Queue = function(params)
	{
		this.id = params.lineId;
		this.nodes = {};
		this.nodesType = {
			destInput : '',
			userInputContainer : '',
			userInputButton : '',
		};

		this.popupDepartment = false;

		this.params = {
			'queueItems' : params.queueItems,
			'readOnly' : params.readOnly,
			'popupDepartment' : params.popupDepartment,
		};
	};
	Queue.prototype =
		{
			getDialog: function()
			{
				if(!this.dialog)
				{
					this.dialog = new BX.UI.EntitySelector.Dialog({
						targetNode: this.nodes[this.nodesType.userInputButton],
						context: 'IMOL_QUEUE_USERS',
						enableSearch: true,
						selectedItems: this.params.queueItems,
						events: {
							'Item:onSelect': BX.proxy(function(event) {
								this.addItemQueue(event);
							}, this),
							'Item:onDeselect': BX.proxy(function(event) {
								this.deleteItemQueue(event);
							}, this),
						},
						entities: [
							{
								id: 'user',
								options: {
									inviteEmployeeLink: false,
									intranetUsersOnly: true,
								}
							},
							{
								id: 'department',
								options: {
									inviteEmployeeLink: false,
									selectMode: 'usersAndDepartments',
								}
							},
						],
					});
				}

				return this.dialog;
			},
			destroy: function()
			{
				if(!!this.dialog)
				{
					this.dialog.destroy();
					delete this.dialog;
					BX.cleanNode(this.nodes[this.nodesType.userInputContainer]);
				}
			},
			createSelector: function()
			{
				var previousNode;
				this.getDialog().getSelectedItems().forEach(BX.proxy(function (entity)
				{
					previousNode = this.selectInputNodes(entity, previousNode);
				}, this));
			},
			setInput: function (node)
			{
				node = BX(node);
				if (!!node && !node.hasAttribute("bx-destination-id")) {
					var locked = false;
					if (this.params.readOnly) {
						locked = true;
					}

					var id = 'destination' + ('' + new Date().getTime()).substr(6), res;
					node.setAttribute('bx-destination-id', id);
					this.nodesType.destInput = node.id;
					this.nodesType.userInputContainer = id + '-container';
					this.nodesType.userInputButton = id + '-add-button';

					node.appendChild(BX.create('DIV', {
						props : { className : "imconnector-field-box imconnector-field-user-box" },
						html : [
							'<div class="imconnector-field-box-subtitle" id="bx-imconnector-tooltip-queue">',
							BX.message('IMCONNECTOR_COMPONENT_CONNECTOR_SETTINGS_QUEUE'),
							'</div>',
							'<div id="', this.nodesType.userInputContainer, '" class="bx-destination-wrap-item imconnector-field-user"></div>',
							locked ? '' : '<span class="bx-destination-add imconnector-field-link-grey" id="' + this.nodesType.userInputButton + '">' + BX.message("LM_ADD") + '</span>'
						].join('')}));

					BX('bx-imconnector-tooltip-queue').appendChild(
						BX.UI.Hint.createNode(BX.message('LM_QUEUE_DESCRIPTION'))
					);

					this.nodes[node.id] = node;
					this.nodes[this.nodesType.userInputContainer] = BX(this.nodesType.userInputContainer);
					this.nodes[this.nodesType.userInputButton] = BX(this.nodesType.userInputButton);

					if(!locked)
					{
						this.nodes[this.nodesType.userInputButton].addEventListener('click', BX.delegate(function() {
							this.getDialog().show();
						}, this));
					}

					BX.addCustomEvent("ItemUser:onDeselect", BX.delegate(function (id) {
						this.getDialog().getItem({id: id, entityId: "user"}).deselect();
					}, this));

					this.createSelector();
				}
			},
			selectInputNodes : function(params, previousNode)
			{
				var currentNode = BX.findChild(this.nodes[this.nodesType.userInputContainer], { attr : { 'data-id' : params.id }}, false, false);
				if(!currentNode)
				{
					var el = this.createInputNode(params);
				}
				else
				{
					var el = currentNode;
				}

				if(!previousNode)
				{
					BX.prepend(el, BX(this.nodes[this.nodesType.userInputContainer]));
				}
				else
				{
					BX.insertAfter(el, previousNode)
				}

				return el;
			},
			createInputNode : function(item)
			{
				var el = BX.create("div", {
					attrs : {
						'data-id' : item.getId(),
					},
					props : {
						className : "imconnector-field-user-item bx-destination bx-destination-" + BX.util.htmlspecialchars(item.getEntityId())
					},
					children: [
						BX.create("div", {
							props : {
								'className' : "imconnector-field-user-icon",
							},
							attrs : {
								style : item.getAvatar() ? "background-image:url('" + encodeURI(BX.util.htmlspecialchars(item.getAvatar())) + "')" : ""
							}
						}),
						BX.create("div", {
							props : {
								'className' : 'imconnector-field-user-info'
							},
							children : [
								item.getTagLink() !== null ?
									BX.create("a", {
										attrs : {
											href : BX.util.htmlspecialchars(item.getTagLink())
										},
										props : {
											'className' : "imconnector-field-user-name"
										},
										html : BX.util.htmlspecialchars(item.getTitle())
									}) :
									BX.create("span", {
										props : {
											'className' : "imconnector-field-user-name"
										},
										text : item.getTitle()
									}),
								BX.create("div", {
									props : {
										'className' : "imconnector-field-user-desc"
									},
									text: item.getCustomData().get('position') || ''
								})
							]
						}),
					]
				});

				if(!this.params.readOnly)
				{
					el.appendChild(BX.create("span", {
						props : {
							'className' : "imconnector-close-icon"
						},
						events : {
							'click' : function(){
								item.deselect();
								BX.Dom.remove(el);
							}
						}
					}));
				}

				return el;
			},
			addItemQueue: function(event)
			{
				this.showPopupDepartment(event.getData().item);
				this.reloadUserInputQueue(event.getData().item.getDialog());
			},
			deleteItemQueue: function(event)
			{
				this.reloadUserInputQueue(event.getData().item.getDialog());
			},
			reloadUserInputQueue: function(dialog)
			{
				var items = dialog.getSelectedItems();

				var queue = [];
				items.forEach(function (entity) {
					queue.push({type : entity.entityId, id : entity.id})
				});

				this.sendActionRequest('saveUsers', {'queue': queue}, BX.proxy(this.setResultRequestSaveUsers, this));
			},
			showPopupDepartment : function (item)
			{
				var entity = {
					'id' : item.getId(),
					'entityId' : item.getEntityId()
				};
				if(
					this.params.popupDepartment.valueDisables !== true &&
					item.getEntityId() === 'department' &&
					!!this.dialog
				)
				{
					if(!!this.popupDepartment)
					{
						this.popupDepartment.close();
						this.popupDepartment.destroy();
					}

					var container = this.dialog.getTagSelector().getTag(entity).getContainer();
					if(container)
					{
						var content = [
							BX.create('DIV',
								{
									html: BX.message('LM_HEAD_DEPARTMENT_EXCLUDED_QUEUE'),
									style: {margin: '10px 20px 10px 5px'}
								}
							),
							BX.create('DIV',
								{
									style: {margin: '10px 20px 10px 5px'},
									children: [
										BX.create("A",
											{
												props: {href: 'javascript:void(0)'},
												text: this.params.popupDepartment.titleOption,
												events: {'click': BX.proxy(function(){
														this.setDisablesPopupDepartment();
													}, this)
												}
											}
										)
									]
								}
							)
						];

						this.popupDepartment = BX.PopupWindowManager.create('popup-department', container, {
							content:  BX.create('DIV', {attrs: {className: 'imconnector-hint-popup-contents'}, children: content}),
							zIndex: 100,
							closeIcon: {
								opacity: 1
							},
							closeByEsc: true,
							darkMode: false,
							autoHide: true,
							angle: true,
							offsetLeft: 20,
							offsetTop: 10,
							events: {
								onPopupClose: BX.proxy(function() {
									this.popupDepartment.destroy();
								}, this)
							}
						});

						this.popupDepartment.show();
					}
				}
			},
			setDisablesPopupDepartment : function ()
			{
				if(!!this.popupDepartment)
				{
					BX.userOptions.save(
						this.params.popupDepartment.nameOption.category,
						this.params.popupDepartment.nameOption.name,
						this.params.popupDepartment.nameOption.nameValue,
						'N',
						false
					);
					this.params.popupDepartment.valueDisables = true;
					this.popupDepartment.close();
					this.popupDepartment.destroy();
				}
			},
			setResultRequestSaveUsers: function(data)
			{
				if(!data.error)
				{
					BX.cleanNode(this.nodes[this.nodesType.userInputContainer]);
					this.createSelector();
				}
			},
			sendActionRequest: function (action, sendData, callbackSuccess, callbackFailure)
			{
				callbackSuccess = callbackSuccess || null;
				callbackFailure = callbackFailure || null;
				sendData = sendData || {};

				if (sendData instanceof FormData)
				{
					sendData.append('action', action);
					sendData.append('configId', this.id);
				}
				else
				{
					sendData.action = action;
					sendData.configId = this.id;
				}

				BX.ajax.runComponentAction('bitrix:imconnector.connector.settings', action, {
					mode: 'ajax',
					data: sendData
				}).then(BX.proxy(function(data){
					data = data || {};
					if(data.error)
					{
						callbackFailure.apply(this, [data]);
					}
					else if(callbackSuccess)
					{
						callbackSuccess.apply(this, [data]);
					}
				}, this)).then(BX.proxy(function(data){
					var applyData = {'error': true, 'text': data.error};
					callbackFailure.apply(this, [applyData]);
				}, this));
			}
		};

	BX.ready(function(){
		BX.UI.Hint.init(BX('bx-connector-user-list'));
	});

	BX.ImConnectorConnectorSettings = function(params)
	{
		this.ajaxUrl = '/bitrix/components/bitrix/imopenlines.lines/ajax.php';

		return this;
	};

	BX.ImConnectorConnectorSettings.prototype =
	{
		createLine: function (detailPageUrlTemplate, isIframe)
		{
		 if(this.isActiveControlLocked)
		 {
			 return;
		 }

		 this.isActiveControlLocked = true;
		 this.sendActionRequest(
			 'create',
			 function(data)
			 {
				if (isIframe)
				{
					BX.SidePanel.Instance.reload();
					window.location.href = detailPageUrlTemplate.replace('#LINE#', data.config_id) + '&IFRAME=Y';
				}
				else
				{
					window.location.href = detailPageUrlTemplate.replace('#LINE#', data.config_id);
				}
			 },
			 function(data)
			 {
				 data = data || {'error': true, 'text': ''};
				 this.isActiveControlLocked = false;

				 if(data.limited)
				 {
					 if(!B24 || !B24['licenseInfoPopup'])
					 {
						 return;
					 }

					 if(BX.message('IMCONNECTOR_COMPONENT_CONNECTOR_SETTINGS_LIMIT_INFO_HELPER'))
					 {
						 BX.UI.InfoHelper.show(BX.message('IMCONNECTOR_COMPONENT_CONNECTOR_SETTINGS_LIMIT_INFO_HELPER'));
					 }
				 }
				 else
				 {
					 this.showErrorPopup(data);
				 }
			 }
		 );
		},
		sendActionRequest: function (action, callbackSuccess, callbackFailure)
		{
			callbackSuccess = callbackSuccess || null;
			callbackFailure = callbackFailure || BX.proxy(this.showErrorPopup, this);

			BX.ajax({
				url: this.ajaxUrl,
				method: 'POST',
				data: {
					'action': action,
					'config_id': this.id,
					'sessid': BX.bitrix_sessid()
				},
				timeout: 30,
				dataType: 'json',
				processData: true,
				onsuccess: BX.proxy(function(data){
					data = data || {};
					if(data.error)
					{
						callbackFailure.apply(this, [data]);
					}
					else if(callbackSuccess)
					{
						callbackSuccess.apply(this, [data]);
					}
				}, this),
				onfailure: BX.proxy(function(){
					var data = {'error': true, 'text': ''};
					callbackFailure.apply(this, [data]);
				}, this)
			});
		},
		showErrorPopup: function (data)
		{
		 data = data || {};
		 var text = data.text || BX.message('IMCONNECTOR_COMPONENT_CONNECTOR_SETTINGS_ERROR_ACTION');
		 var popup = BX.PopupWindowManager.create(
			 'crm_webform_list_error',
			 null,
			 {
				 autoHide: true,
				 lightShadow: true,
				 closeByEsc: true,
				 overlay: {backgroundColor: 'black', opacity: 500}
			 }
		 );
		 popup.setButtons([
			 new BX.PopupWindowButton({
				 text: BX.message('IMCONNECTOR_COMPONENT_CONNECTOR_SETTINGS_CLOSE'),
				 events: {click: function(){this.popupWindow.close();}}
			 })
		 ]);
		 popup.setContent('<span class="crm-webform-edit-warning-popup-alert">' + text + '</span>');
		 popup.show();
		}
	};

	BX.ImConnectorLinesMenu = function(params)
	{
		this.element = params.element;
		this.bindElement = document.getElementById(params.bindElement);
		this.items = this.prepareItems(params.items);
		this.iframe = params.iframe;

		this.init();
	};

	BX.ImConnectorLinesMenu.prototype =
	{
		init: function ()
		{
			var params = {
				maxHeight: 400,
				minWidth: this.bindElement.offsetWidth
			};

			this.menu = new BX.PopupMenuWindow(
				this.element,
				this.bindElement,
				this.items,
				params
			);

			BX.bind(this.bindElement, 'click', BX.delegate(this.show, this));
		},

		show: function()
		{
			this.menu.show();
		},

		close: function()
		{
			this.menu.close();
		},

		prepareItems: function (items)
		{
			if (typeof items === "object")
			{
				items = Object.values(items)
			}

			var newItems = [];
			var newItem;

			for (var i = 0; i < items.length; i++)
			{
				newItem = this.prepareItem(items[i]);

				if (newItem.delimiterBefore)
				{
					newItems.push({delimiter: true});
				}

				newItems.push(newItem);

				if (newItem.delimiterAfter)
				{
					newItems.push({delimiter: true});
				}
			}

			return newItems;
		},

		prepareItem: function (item)
		{
			var newItem = {};

			item.NAME = BX.util.htmlspecialchars(item.NAME);

			newItem.id = item.ID;
			newItem.title = BX.util.htmlspecialcharsback(item.NAME);
			newItem.text = item.NAME;
			newItem.delimiterAfter = item.DELIMITER_AFTER;
			newItem.delimiterBefore = item.DELIMITER_BEFORE;
			newItem.dataset = {id: item.ID};

			if (item.IS_LINE_ACTIVE === 'Y')
			{
				newItem.className = 'imconnector-line-status-active';
			}
			else if (item.IS_LINE_ACTIVE === 'N')
			{
				newItem.className = 'imconnector-line-status-inactive';
			}

			if (item.URL)
			{
				// workaround for imconnector.notifications
				// without it scenario code will be lost on line change
				var scenarioCode = (new URL(document.location)).searchParams.get('scenario');
				if (scenarioCode)
				{
					item.URL = BX.util.add_url_param(item.URL, {scenario: scenarioCode});
				}

				newItem.onclick = BX.delegate(
					function (e) {
						var isIframe = this.iframe;
						if (item.NEW === 'Y')
						{
							BX.ImConnectorLinesConfigEdit.createLineAction(item.URL, isIframe);
						}
						else if (isIframe)
						{
							if (item.IS_LINE_ACTIVE === 'N')
							{
								this.activatePopupShow(item);
							}
							else
							{
								this.changeLine(item.URL);
							}
						}
						else
						{
							window.location.href = item.URL;
						}

						this.close();
					},
					this
				);
			}

			return newItem;
		},

		changeLine: function(url)
		{
			BX.SidePanel.Instance.getSliderByWindow(window).showLoader();
			window.location = `${url}&IFRAME=Y`;
		},

		activateLine: function(lineId)
		{
			BX.ajax.runComponentAction('bitrix:imconnector.connector.settings', 'activateLine', {
				mode: 'ajax',
				data: {
					lineId: lineId
				}
			  }).then(BX.proxy(function(data){
				if (data.data.result === false && data.data.error)
				{
					alert(data.data.error);
				}
			}, this)).then(BX.proxy(function(data){

			}, this));
		},

		activatePopupShow: function(item)
		{
			var lineId = item.ID,
				url = item.URL;
			var popupShowTrue = new BX.PopupWindow('uid-config-activate-' + lineId, null, {
				closeIcon: { right : '5px', top : '5px'},
				titleBar: BX.message('IMCONNECTOR_COMPONENT_CONNECTOR_LINE_ACTIVATION_TITLE'),
				closeByEsc : true,
				autoHide : true,
				content: '<p class=\"imconnector-popup-text\">' + BX.message('IMCONNECTOR_COMPONENT_CONNECTOR_LINE_ACTIVATION_DESCRIPTION') + '</p>',
				overlay: {
					backgroundColor: 'black', opacity: '80'
				},
				buttons: [
					new BX.PopupWindowButton({
						text: BX.message('IMCONNECTOR_COMPONENT_CONNECTOR_LINE_ACTIVATION_BUTTON_ACTIVE'),
						className : 'popup-window-button-accept',
						events:{
							click: BX.proxy(function() {
								this.activateLine(lineId);
								setTimeout(
									BX.delegate(
										function(){
											this.changeLine(url);
										}, this),
									200
								);
								//this.popupWindow.close();
							}, this)
						}
					}),
					new BX.PopupWindowButton({
						text: BX.message('IMCONNECTOR_COMPONENT_CONNECTOR_LINE_ACTIVATION_BUTTON_NO'),
						className : 'popup-window-button-link',
						events:{
							click: BX.proxy(function() {
								this.changeLine(url);
								//this.popupWindow.close();
							}, this)
						}
					})
				]
			});
			popupShowTrue.show();
		},

		reloadItem: function(lineId)
		{
			BX.ajax.runComponentAction('bitrix:imconnector.connector.settings', 'getConfigItem', {
				mode: 'ajax',
				data: {
					lineId: lineId
				}
			}).then(BX.proxy(function(data){
				if (data.data.ID)
				{
					var itemData = data.data;
					itemData.URL = window.location.href;

					var item = this.prepareItem(itemData);
					if (item.id && item.id == lineId)
					{
						var curPosition = this.menu.getMenuItemPosition(lineId);
						this.menu.removeMenuItem(lineId);
						var itemAfter = this.menu.menuItems[curPosition];

						if (itemAfter.id)
						{
							this.menu.addMenuItem(item, itemAfter.id);
						}
						else
						{
							this.menu.addMenuItem(item);
						}

						var titleBlock = document.getElementById('imconnector-lines-list');
						titleBlock.innerHTML = item.title;
					}
				}
			}, this)).then(BX.proxy(function(data){

			}, this));
		}
	};

})(window);