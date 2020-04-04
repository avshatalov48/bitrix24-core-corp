;(function(window){

	if (!!window.BX.OpenLinesConfigEdit)
		return;

	var destinationInstance = null;

	var destination = function(params, type) {
		this.p = (!!params ? params : {});
		if (!!params["SELECTED"])
		{
			var res = {}, tp, j;
			for (tp in params["SELECTED"])
			{
				if (params["SELECTED"].hasOwnProperty(tp) && typeof params["SELECTED"][tp] == "object")
				{
					for (j in params["SELECTED"][tp])
					{
						if (params["SELECTED"][tp].hasOwnProperty(j))
						{
							if (tp == 'USERS')
								res['U' + params["SELECTED"][tp][j]] = 'users';
							else if (tp == 'SG')
								res['SG' + params["SELECTED"][tp][j]] = 'sonetgroups';
							else if (tp == 'DR')
								res['DR' + params["SELECTED"][tp][j]] = 'department';
						}
					}
				}
			}
			this.p["SELECTED"] = res;
		}

		this.nodes = {};
		var makeDepartmentTree = function(id, relation)
			{
				var arRelations = {}, relId, arItems, x;
				if (relation[id])
				{
					for (x in relation[id])
					{
						if (relation[id].hasOwnProperty(x))
						{
							relId = relation[id][x];
							arItems = [];
							if (relation[relId] && relation[relId].length > 0)
								arItems = makeDepartmentTree(relId, relation);
							arRelations[relId] = {
								id: relId,
								type: 'category',
								items: arItems
							};
						}
					}
				}
				return arRelations;
			},
			buildDepartmentRelation = function(department)
			{
				var relation = {}, p;
				for(var iid in department)
				{
					if (department.hasOwnProperty(iid))
					{
						p = department[iid]['parent'];
						if (!relation[p])
							relation[p] = [];
						relation[p][relation[p].length] = iid;
					}
				}
				return makeDepartmentTree('DR0', relation);
			};
		if (true || type == 'users')
		{
			this.params = {
				'name' : null,
				'searchInput' : null,
				'extranetUser' :  (this.p['EXTRANET_USER'] == "Y"),
				'bindMainPopup' : { node : null, 'offsetTop' : '5px', 'offsetLeft': '15px'},
				'bindSearchPopup' : { node : null, 'offsetTop' : '5px', 'offsetLeft': '15px'},
				departmentSelectDisable : true,
				'callback' : {
					'select' : BX.delegate(this.select, this),
					'unSelect' : BX.delegate(this.unSelect, this),
					'openDialog' : BX.delegate(this.openDialog, this),
					'closeDialog' : BX.delegate(this.closeDialog, this),
					'openSearch' : BX.delegate(this.openDialog, this),
					'closeSearch' : BX.delegate(this.closeSearch, this)
				},
				items : {
					users : (!!this.p['USERS'] ? this.p['USERS'] : {}),
					groups : {},
					sonetgroups : {},
					department : (!!this.p['DEPARTMENT'] ? this.p['DEPARTMENT'] : {}),
					departmentRelation : (!!this.p['DEPARTMENT'] ? buildDepartmentRelation(this.p['DEPARTMENT']) : {}),
					contacts : {},
					companies : {},
					leads : {},
					deals : {}
				},
				itemsLast : {
					users : (!!this.p['LAST'] && !!this.p['LAST']['USERS'] ? this.p['LAST']['USERS'] : {}),
					sonetgroups : {},
					department : {},
					groups : {},
					contacts : {},
					companies : {},
					leads : {},
					deals : {},
					crm : []
				},
				itemsSelected : (!!this.p['SELECTED'] ? BX.clone(this.p['SELECTED']) : {}),
				isCrmFeed : false,
				destSort : (!!this.p['DEST_SORT'] ? BX.clone(this.p['DEST_SORT']) : {})
			}
		}
	};
	destination.prototype = {
		setInput : function(node, inputName)
		{
			node = BX(node);
			if (!!node && !node.hasAttribute("bx-destination-id"))
			{
				var id = 'destination' + ('' + new Date().getTime()).substr(6), res;
				node.setAttribute('bx-destination-id', id);
				res = new destInput(id, node, inputName);
				this.nodes[id] = node;
				BX.defer_proxy(function(){
					this.params.name = res.id;
					this.params.searchInput = res.nodes.input;
					this.params.bindMainPopup.node = res.nodes.container;
					this.params.bindSearchPopup.node = res.nodes.container;

					BX.SocNetLogDestination.init(this.params);
				}, this)();
			}
		},
		select : function(item, type, search, bUndeleted, id)
		{
			var type1 = type, prefix = 'S';

			if (type == 'groups')
			{
				type1 = 'all-users';
			}
			else if (BX.util.in_array(type, ['contacts', 'companies', 'leads', 'deals']))
			{
				type1 = 'crm';
			}

			if (type == 'sonetgroups')
			{
				prefix = 'SG';
			}
			else if (type == 'groups')
			{
				prefix = 'UA';
			}
			else if (type == 'users')
			{
				prefix = 'U';
			}
			else if (type == 'department')
			{
				prefix = 'DR';
			}
			else if (type == 'contacts')
			{
				prefix = 'CRMCONTACT';
			}
			else if (type == 'companies')
			{
				prefix = 'CRMCOMPANY';
			}
			else if (type == 'leads')
			{
				prefix = 'CRMLEAD';
			}
			else if (type == 'deals')
			{
				prefix = 'CRMDEAL';
			}

			var stl = (bUndeleted ? ' bx-destination-undelete' : '');
			stl += (type == 'sonetgroups' && typeof window['arExtranetGroupID'] != 'undefined' && BX.util.in_array(item.entityId, window['arExtranetGroupID']) ? ' bx-destination-extranet' : '');

			var el = BX.create("div", {
				attrs : {
					'data-id' : item.id,
				},
				props : {
					className : "imconnector-field-user-item bx-destination bx-destination-"+type1+stl
				},
				children: [
					BX.create("div", {
						props : {
							'className' : "imconnector-field-user-icon",
						},
						attrs : {
							style : item.avatar ? "background-image:url(" + item.avatar + ")" : ""
						}
					}),
					BX.create("div", {
						props : {
							'className' : 'imconnector-field-user-info'
						},
						children : [
							BX.create("a", {
								attrs : {
									href : item.link
								},
								props : {
									'className' : "imconnector-field-user-name"
								},
								html : item.name
							}),
							BX.create("div", {
								props : {
									'className' : "imconnector-field-user-desc"
								},
								html : item.desc
							})
						]
					}),
				]
			});

			if(!bUndeleted)
			{
				el.appendChild(BX.create("span", {
					props : {
						'className' : "imconnector-close-icon"
					},
					events : {
						'click' : function(e){
							BX.PreventDefault(e);
							BX.SocNetLogDestination.deleteItem(item.id, type, id);

							var form = new FormData(BX('user-queue-save'));
							var settings = new BX.ImConnectorConnectorSettings();
							settings.saveUsers(form);
						}
					}
				}));
			}
			BX.onCustomEvent(this.nodes[id], 'select', [item, el, prefix]);
		},
		unSelect : function(item, type, search, id)
		{
			BX.onCustomEvent(this.nodes[id], 'unSelect', [item]);
		},
		openDialog : function(id)
		{
			BX.onCustomEvent(this.nodes[id], 'openDialog', []);
		},
		closeDialog : function(id)
		{
			if (!BX.SocNetLogDestination.isOpenSearch())
			{
				BX.onCustomEvent(this.nodes[id], 'closeDialog', []);
				this.disableBackspace();
			}
		},
		closeSearch : function(id)
		{
			if (!BX.SocNetLogDestination.isOpenSearch())
			{
				BX.onCustomEvent(this.nodes[id], 'closeSearch', []);
				this.disableBackspace();
			}
		},
		disableBackspace : function()
		{
			if (BX.SocNetLogDestination.backspaceDisable || BX.SocNetLogDestination.backspaceDisable !== null)
				BX.unbind(window, 'keydown', BX.SocNetLogDestination.backspaceDisable);

			BX.bind(window, 'keydown', BX.SocNetLogDestination.backspaceDisable = function(event){
				if (event.keyCode == 8)
				{
					BX.PreventDefault(event);
					return false;
				}
				return true;
			});
			setTimeout(function(){
				BX.unbind(window, 'keydown', BX.SocNetLogDestination.backspaceDisable);
				BX.SocNetLogDestination.backspaceDisable = null;
			}, 5000);
		}
	};

	var destInput = function(id, node, inputName)
	{
		this.node = node;
		this.id = id;
		this.inputName = inputName;
		this.node.appendChild(BX.create('DIV', {
			props : { className : "imconnector-field-box imconnector-field-user-box" },
			html : [
				'<div class="imconnector-field-box-subtitle" id="bx-imconnector-tooltip">',
				BX.message('IMCONNECTOR_COMPONENT_CONNECTOR_SETTINGS_QUEUE'),
				'</div>',
				'<div id="', this.id, '-container" class="bx-destination-wrap-item imconnector-field-user"></div>',
				'<span class="bx-destination-input-box" id="', this.id, '-input-box" style="display: none;">',
					'<input type="text" value="" class="bx-destination-input imconnector-field-control-input" id="', this.id, '-input">',
				'</span>',
				'<a href="#" class="bx-destination-add imconnector-field-link-grey" id="', this.id, '-add-button"></a>'
			].join('')}));
		BX.defer_proxy(this.bind, this)();
		BX('bx-imconnector-tooltip').appendChild(
			BX.UI.Hint.createNode(BX.message('LM_QUEUE_DESCRIPTION'))
		);
	};
	destInput.prototype = {
		bind : function()
		{
			this.nodes = {
				inputBox : BX(this.id + '-input-box'),
				input : BX(this.id + '-input'),
				container : BX(this.id + '-container'),
				button : BX(this.id + '-add-button'),
				form: BX('user-queue-save')
			};
			BX.bind(this.nodes.input, 'keyup', BX.proxy(this.search, this));
			BX.bind(this.nodes.input, 'keydown', BX.proxy(this.searchBefore, this));
			BX.bind(this.nodes.button, 'click', BX.proxy(function(e){BX.SocNetLogDestination.openDialog(this.id); BX.PreventDefault(e); }, this));
			BX.bind(this.nodes.container, 'click', BX.proxy(function(e){BX.SocNetLogDestination.openDialog(this.id); BX.PreventDefault(e); }, this));
			this.onChangeDestination();
			BX.addCustomEvent(this.node, 'select', BX.proxy(this.select, this));
			BX.addCustomEvent(this.node, 'unSelect', BX.proxy(this.unSelect, this));
			BX.addCustomEvent(this.node, 'delete', BX.proxy(this.delete, this));
			BX.addCustomEvent(this.node, 'openDialog', BX.proxy(this.openDialog, this));
			BX.addCustomEvent(this.node, 'closeDialog', BX.proxy(this.closeDialog, this));
			BX.addCustomEvent(this.node, 'closeSearch', BX.proxy(this.closeSearch, this));
		},
		select : function(item, el, prefix)
		{
			if (BX.message('LM_BUSINESS_USERS_ON') == 'Y' && BX.message('LM_BUSINESS_USERS').split(',').indexOf(item.id) == -1)
			{
				BX.SocNetLogDestination.closeDialog(this.id);
				BX.OpenLinesConfigEdit.openTrialPopup('imol_queue', BX.message('LM_BUSINESS_USERS_TEXT'));
				return false;
			}
			if(!BX.findChild(this.nodes.container, { attr : { 'data-id' : item.id }}, false, false))
			{
				el.appendChild(BX.create("INPUT", { props : {
						type : "hidden",
						name: 'queue[]',
						value : item.id
					}
				}));
				this.nodes.container.appendChild(el);
			}
			this.onChangeDestination();
		},
		unSelect : function(item)
		{
			var elements = BX.findChildren(this.nodes.container, {attribute: {'data-id': ''+item.id+''}}, true);
			if (elements !== null)
			{
				for (var j = 0; j < elements.length; j++)
					BX.remove(elements[j]);
			}
			this.onChangeDestination();
		},
		onChangeDestination : function()
		{
			this.nodes.input.innerHTML = '';
			this.nodes.button.innerHTML = (BX.SocNetLogDestination.getSelectedCount(this.id) <= 0 ? BX.message("LM_ADD1") : BX.message("LM_ADD2"));
		},
		openDialog : function()
		{
			BX.style(this.nodes.inputBox, 'display', 'inline-block');
			BX.style(this.nodes.button, 'display', 'none');
			BX.focus(this.nodes.input);
		},
		closeDialog : function()
		{
			if (this.nodes.input.value.length <= 0)
			{
				BX.style(this.nodes.inputBox, 'display', 'none');
				BX.style(this.nodes.button, 'display', 'inline-block');
				this.nodes.input.value = '';
			}

			var form = new FormData(this.nodes.form);

			this.saveUsers(form);
		},
		closeSearch : function()
		{
			if (this.nodes.input.value.length > 0)
			{
				BX.style(this.nodes.inputBox, 'display', 'none');
				BX.style(this.nodes.button, 'display', 'inline-block');
				this.nodes.input.value = '';
			}

			var form = new FormData(this.nodes.form);

			this.saveUsers(form);
		},
		searchBefore : function(event)
		{
			if (event.keyCode == 8 && this.nodes.input.value.length <= 0)
			{
				BX.SocNetLogDestination.sendEvent = false;
				BX.SocNetLogDestination.deleteLastItem(this.id);
			}
			return true;
		},
		search : function(event)
		{
			if (event.keyCode == 16 || event.keyCode == 17 || event.keyCode == 18 || event.keyCode == 20 || event.keyCode == 244 || event.keyCode == 224 || event.keyCode == 91)
				return false;

			if (event.keyCode == 13)
			{
				BX.SocNetLogDestination.selectFirstSearchItem(this.id);
				return true;
			}
			if (event.keyCode == 27)
			{
				this.nodes.input.value = '';
				BX.style(this.nodes.button, 'display', 'inline');
			}
			else
			{
				BX.SocNetLogDestination.search(this.nodes.input.value, true, this.id);
			}

			if (!BX.SocNetLogDestination.isOpenDialog() && this.nodes.input.value.length <= 0)
			{
				BX.SocNetLogDestination.openDialog(this.id);
			}
			else if (BX.SocNetLogDestination.sendEvent && BX.SocNetLogDestination.isOpenDialog())
			{
				BX.SocNetLogDestination.closeDialog();
			}
			if (event.keyCode == 8)
			{
				BX.SocNetLogDestination.sendEvent = true;
			}
			return true;
		},
		saveUsers: function (formData) {
			BX.ajax.runComponentAction('bitrix:imconnector.connector.settings', 'getSaveUsers', {
				mode: 'ajax',
				data: formData
			}).then(function(response){
			});
		}
	};

	window.BX.OpenLinesConfigEdit = {
		popupTooltip: {},
		openTrialPopup : function(dialogId, text)
		{
			if (typeof(B24) != 'undefined' && typeof(B24.licenseInfoPopup) != 'undefined')
			{
				B24.licenseInfoPopup.show(dialogId, BX.message('LM_QUEUE_TITLE'), text);
			}
			else
			{
				alert(text);
			}
		},
		initDestination : function(node, inputName, params)
		{
			if (destinationInstance === null)
				destinationInstance = new destination(params);
			destinationInstance.setInput(BX(node), inputName);
			this.receiveReloadUsersMessage();
		},
		createLineAction : function(url, isIframe)
		{
			var newLine = new BX.ImConnectorConnectorSettings();
			newLine.createLine(url, isIframe);
		},
		receiveReloadUsersMessage: function()
		{
			BX.addCustomEvent(
				"SidePanel.Slider:onMessage",
				BX.delegate(
					function(event) {
						if (event.getEventId() === "ImOpenlines:reloadUsersList")
						{
							var destinationId = BX('users_for_queue').getAttribute('bx-destination-id');
							var userContainer = BX(destinationId + '-container');
							var users = BX.findChild(userContainer, {class: 'bx-destination-users'}, false, true);

							for (var i = 0; i < users.length; i++)
							{
								BX.SocNetLogDestination.deleteItem(users[i].dataset.id, 'users', destinationId);
							}

							var newUsers = event.getData();

							if (typeof newUsers === "object")
							{
								newUsers = Object.values(newUsers);
							}

							for (i = 0; i < newUsers.length; i++)
							{
								destinationInstance.select(newUsers[i], 'users', false, false, destinationId);
							}
						}
					},
					this
				)
			);
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
					BX.SidePanel.Instance.close();
					BX.SidePanel.Instance.open(detailPageUrlTemplate.replace('#LINE#', data.config_id), {width: 700});
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

					 B24.licenseInfoPopup.show(
						 'imconnector_line_activation',
						 BX.message('IMCONNECTOR_COMPONENT_CONNECTOR_SETTINGS_POPUP_LIMITED_TITLE'),
						 '<span>' + BX.message('IMCONNECTOR_COMPONENT_CONNECTOR_SETTINGS_POPUP_LIMITED_TEXT') + '</span>'
					 );
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
		},
		saveUsers: function (formData) {
		 BX.ajax.runComponentAction('bitrix:imconnector.connector.settings', 'getSaveUsers', {
			 mode: 'ajax',
			 data: formData
		 }).then(function(response){
		 });
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

			newItem.title = item.NAME;
			newItem.text = item.NAME;
			newItem.delimiterAfter = item.DELIMITER_AFTER;
			newItem.delimiterBefore = item.DELIMITER_BEFORE;

			if (item.URL)
			{
				newItem.onclick = BX.delegate(
					function (e) {
						var isIframe = this.iframe;
						if (item.NEW === 'Y')
						{
							BX.OpenLinesConfigEdit.createLineAction(item.URL, isIframe);
						}
						else if (isIframe)
						{
							BX.SidePanel.Instance.close();
							var options = BX.SidePanel.Instance.getSliderByWindow(window).options;
							BX.SidePanel.Instance.open(item.URL, options);
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
	};

})(window);