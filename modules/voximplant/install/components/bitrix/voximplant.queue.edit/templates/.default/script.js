(function()
{
	if (window.BX.ViGroupEdit)
		return;

	var AJAX_URL = '/bitrix/components/bitrix/voximplant.queue.edit/ajax.php';

	var Rule = {
		wait: 'wait',
		talk: 'talk',
		hungup: 'hungup',
		pstn: 'pstn',
		pstn_specific: 'pstn_specific',
		user: 'user',
		voicemail: 'voicemail',
		queue: 'queue',
		next_queue: 'next_queue'
	};

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
	};

	var buildDepartmentRelation = function(department)
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

	var Destination = function(params, type)
	{
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
		this.maximumGroupMembers = params.maximumGroupMembers;

		this.nodes = {};
		if (true || type == 'users') // TODO Other types for searching
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

	Destination.prototype = {
		setInput : function(node, inputName)
		{
			if (BX.type.isDomNode(node) && !node.hasAttribute("bx-destination-id"))
			{
				var id = 'destination' + ('' + new Date().getTime()).substr(6);
				node.setAttribute('bx-destination-id', id);
				var res = new DestinationInput({
					id: id,
					node: node,
					inputName: inputName,
					destination: this
				});
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
			if (this.maximumGroupMembers == -1)
			{
				// unlimit group members
			}
			else if (this.getSelectedCount() > this.maximumGroupMembers)
			{
				if (this.maximumGroupMembers == 0)
				{
					BX.UI.InfoHelper.show('limit_contact_center_telephony_group_size_zero');
				}
				else
				{
					BX.UI.InfoHelper.show('limit_contact_center_telephony_group_size');
				}
				this.deleteLastItem();
				BX.SocNetLogDestination.closeDialog(this.params.name);
				return;
			}

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

			var el = BX.create("span", {
				attrs : {
					'data-id' : item.id
				},
				props : {
					className : "bx-destination bx-destination-"+type1+stl
				},
				children: [
					BX.create("span", {
						props : {
							'className' : "bx-destination-text"
						},
						html : item.name
					})
				]
			});

			if(!bUndeleted)
			{
				el.appendChild(BX.create("span", {
					props : {
						'className' : "bx-destination-del-but"
					},
					events : {
						'click' : function(e){
							BX.SocNetLogDestination.deleteItem(item.id, type, id);
							BX.PreventDefault(e)
						},
						'mouseover' : function(){
							BX.addClass(this.parentNode, 'bx-destination-hover');
						},
						'mouseout' : function(){
							BX.removeClass(this.parentNode, 'bx-destination-hover');
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
		},
		getSelectedCount: function()
		{
			return BX.SocNetLogDestination.getSelectedCount(this.params.name);
		},
		deleteLastItem: function()
		{
			return BX.SocNetLogDestination.deleteLastItem(this.params.name);
		}
	};
	var DestinationInput = function(params)
	{
		this.node = params.node;
		this.nodes = {
			inputBox: null,
			input: null,
			container: null,
			button: null
		};
		this.id = params.id;
		this.inputName = params.inputName;
		this.destination = params.destination;
		this.render();
		this.bind();
	};
	DestinationInput.prototype = {
		render: function()
		{
			BX.cleanNode(this.node);
			this.node.appendChild(BX.create('span', {
				props : { className : "bx-destination-wrap" },
				children: [
					this.nodes.container = BX.create('span', {
						events: {click: this.onAddButtonClick.bind(this)},
						children: [
							BX.create('span', {props: {className: 'bx-destination-wrap-item'}})
						]
					}),
					this.nodes.inputBox = BX.create('span', {
						props: {className: 'bx-destination-input-box'},
						children: [
							this.nodes.input = BX.create('input', {
								props: {className: 'bx-destination-input'},
								events: {
									keyup: this.onInputKeyUp.bind(this),
									keydown: this.onInputKeyDown.bind(this)
								}
							})
						]
					}),
					this.nodes.button = BX.create('span')
				]
			}));
		},
		renderLock: function()
		{
			var result = [
				BX.create("span", {
					props: {className: 'bx-destination-add'},
					text: this.destination.getSelectedCount() <= 0 ? BX.message("LM_ADD1") : BX.message("LM_ADD2"),
					events: {click: this.onAddButtonClick.bind(this)}
				})
			];

			if(this.destination.maximumGroupMembers == -1)
			{
				return result;
			}

			if(this.destination.getSelectedCount() < this.destination.maximumGroupMembers)
			{
				result.push(BX.create('span', {
					props: {className: 'tel-lock-holder-destination'},
					children: [
						BX.create('span', {
							props: {className: 'tel-lock tel-lock-half'},
							events: {
								click: function(e)
								{
									if (this.destination.maximumGroupMembers == 0)
									{
										BX.UI.InfoHelper.show('limit_contact_center_telephony_group_size_zero');
									}
									else
									{
										BX.UI.InfoHelper.show('limit_contact_center_telephony_group_size');
									}
								}.bind(this)
							}
						})
					]
				}));
			}
			else
			{
				result.push(BX.create('span', {
					props: {className: 'tel-lock-holder-destination'},
					children: [
						BX.create('span', {
							props: {className: 'tel-lock'},
							events: {
								click: function(e)
								{
									if (this.destination.maximumGroupMembers == 0)
									{
										BX.UI.InfoHelper.show('limit_contact_center_telephony_group_size_zero');
									}
									else
									{
										BX.UI.InfoHelper.show('limit_contact_center_telephony_group_size');
									}
								}.bind(this)
							}
						})
					]
				}));
			}
			return result;
		},
		bind : function()
		{
			this.onChangeDestination();
			BX.addCustomEvent(this.node, 'select', this.onSelect.bind(this));
			BX.addCustomEvent(this.node, 'unSelect', this.onUnSelect.bind(this));
			//BX.addCustomEvent(this.node, 'delete', this.delete.bind(this));
			BX.addCustomEvent(this.node, 'openDialog', this.onOpenDialog.bind(this));
			BX.addCustomEvent(this.node, 'closeDialog', this.onCloseDialog.bind(this));
			BX.addCustomEvent(this.node, 'closeSearch', this.onCloseSearch.bind(this));
		},
		onAddButtonClick: function(e)
		{
			if(this.destination.maximumGroupMembers > 0 && this.destination.getSelectedCount() >= this.destination.maximumGroupMembers)
			{
				if (this.destination.maximumGroupMembers == 0)
				{
					BX.UI.InfoHelper.show('limit_contact_center_telephony_group_size_zero');
				}
				else
				{
					BX.UI.InfoHelper.show('limit_contact_center_telephony_group_size');
				}
			}
			else
			{
				BX.SocNetLogDestination.openDialog(this.id);
			}
			e.preventDefault();
			e.stopPropagation();
		},
		onSelect : function(item, el, prefix)
		{
			if(!BX.findChild(this.nodes.container, { attr : { 'data-id' : item.id }}, false, false))
			{
				el.appendChild(BX.create("INPUT", { props : {
					type : "hidden",
					name : (this.inputName + '[]'),
					value : item.entityId
				}
				}));
				this.nodes.container.appendChild(el);
			}
			this.onChangeDestination();
		},
		onUnSelect : function(item)
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
			BX.cleanNode(this.nodes.button);
			BX.adjust(this.nodes.button, {
				children: this.renderLock()
			});
		},
		onOpenDialog : function()
		{
			BX.style(this.nodes.inputBox, 'display', 'inline-block');
			this.nodes.button.style.display = 'none';
			BX.focus(this.nodes.input);
		},
		onCloseDialog : function()
		{
			if (this.nodes.input.value.length <= 0)
			{
				BX.style(this.nodes.inputBox, 'display', 'none');
				this.nodes.button.style.removeProperty('display');
				this.nodes.input.value = '';
			}
		},
		onCloseSearch : function()
		{
			if (this.nodes.input.value.length > 0)
			{
				BX.style(this.nodes.inputBox, 'display', 'none');
				this.nodes.button.style.removeProperty('display');
				this.nodes.input.value = '';
			}
		},
		onInputKeyDown : function(event)
		{
			if (event.keyCode == 8 && this.nodes.input.value.length <= 0)
			{
				BX.SocNetLogDestination.sendEvent = false;
				BX.SocNetLogDestination.deleteLastItem(this.id);
			}
			return true;
		},
		onInputKeyUp : function(event)
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
		}
	};

	BX.ViGroupEdit = function(params)
	{
		this.node = params.node;
		this.destinationParams = params.destinationParams;
		this.groupListUrl = params.groupListUrl;
		this.inlineMode = params.inlineMode;
		this.externalRequestId = params.externalRequestId;
		this.destinationParams.maximumGroupMembers = params.maximumGroupMembers;
		this.popupTooltip = {};
		this.init();
	};

	BX.ViGroupEdit.prototype.init = function()
	{
		this.bindEvents();

		this.destination = new Destination(this.destinationParams);
		this.destination.setInput(BX('users_for_queue'), 'USERS');

		BX.onCustomEvent(window, 'onViGroupEditInit', [this]);
	};

	BX.ViGroupEdit.prototype.bindEvents = function()
	{
		var self = this;
		var contextHelpNodes = BX.findChildrenByClassName(BX('tel-set-main-wrap'), "tel-context-help");
		if(BX.type.isArray(contextHelpNodes))
		{
			contextHelpNodes.forEach(function(helpNode, i)
			{
				helpNode.setAttribute('data-id', i);
				BX.bind(helpNode, 'mouseover', function()
				{
					var id = this.getAttribute('data-id');
					var text = this.getAttribute('data-text');
					self.showTooltip(id, this, text);
				});
				BX.bind(helpNode, 'mouseout', function()
				{
					var id = this.getAttribute('data-id');
					self.hideTooltip(id);
				});
			});
		}

		BX.bind(BX('vi_no_answer_rule'), 'change', function(e)
		{
			BX.PreventDefault(e);

			switch (e.target.value)
			{
				case Rule.pstn_specific:
					BX('vi_forward_number').classList.remove('inactive');
					BX('vi_next_queue').classList.add('inactive');
					break;
				case Rule.next_queue:
					BX('vi_forward_number').classList.add('inactive');
					BX('vi_next_queue').classList.remove('inactive');
					break;
				default:
					BX('vi_forward_number').classList.add('inactive');
					BX('vi_next_queue').classList.add('inactive');
					break;
			}
		});

		BX.bind(BX('vi_allow_intercept'), 'bxchange', function(e)
		{
			if(e.target.dataset.locked == 1)
			{
				BX.PreventDefault(e);
				e.target.checked = false;
				BX.UI.InfoHelper.show('limit_contact_center_telephony_intercept')
				return false;
			}
		});

		var submitNode = this.getNode('vi-group-edit-submit');
		if(submitNode)
			BX.bind(submitNode, 'click', this._onSubmitClick.bind(this));

		var cancelNode = this.getNode('vi-group-edit-cancel');
		if(cancelNode)
			BX.bind(cancelNode, 'click', this._onCancelClick.bind(this));
	};

	BX.ViGroupEdit.prototype.getNode = function(role, context)
	{
		if (!context)
			context = this.node;

		return context ? context.querySelector('[data-role="' + role + '"]') : null;
	};

	BX.ViGroupEdit.prototype.showTooltip = function(id, bind, text)
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
			events : {
				onPopupClose : function() {this.destroy()}
			},
			content : BX.create("div", { attrs : { style : "padding-right: 5px; width: 250px;" }, html: text})
		});
		this.popupTooltip[id].setAngle({offset:13, position: 'bottom'});
		this.popupTooltip[id].show();

		return true;
	};

	BX.ViGroupEdit.prototype.hideTooltip = function(id)
	{
		this.popupTooltip[id].close();
		this.popupTooltip[id] = null;
	};

	BX.ViGroupEdit.prototype._onSubmitClick = function(e)
	{
		this.save();
	};

	BX.ViGroupEdit.prototype.save = function(successCallback)
	{
		var self = this;
		var formData = new FormData();
		var formElements = this.node.querySelectorAll('input, select');
		var element;

		for (var i = 0; i < formElements.length; i++)
		{
			element = formElements.item(i);
			if(element.tagName.toUpperCase() == 'INPUT')
			{
				switch(element.type.toUpperCase())
				{
					case 'TEXT':
						formData.append(element.name, element.value);
						break;
					case 'HIDDEN':
						formData.append(element.name, element.value);
						break;
					case 'CHECKBOX':
						if(element.checked)
							formData.append(element.name, element.value);
						break;
				}
			}
			else if(element.tagName.toUpperCase() == 'SELECT')
			{
				formData.append(element.name, element.value);
			}
		}

		var saveButton = this.getNode("vi-group-edit-submit");
		var waitNode = BX.create('span', {props : {className : "wait"}});

		BX.addClass(saveButton, "webform-small-button-wait webform-small-button-active");
		saveButton.appendChild(waitNode);

		BX.ajax({
			url: AJAX_URL,
			method: 'POST',
			data: formData,
			preparePost: false,
			onsuccess: function(response)
			{
				BX.removeClass(saveButton, "webform-small-button-wait webform-small-button-active");
				BX.remove(waitNode);
				try
				{
					response = JSON.parse(response)
				}
				catch (e)
				{
					BX.debug('Error decoding server response');
					return false;
				}

				if(response.SUCCESS === true)
				{
					if(BX.type.isFunction(successCallback))
					{
						successCallback(response.DATA, self.externalRequestId);
					}
					else if(BX.SidePanel.Instance.isOpen())
					{
						BX.SidePanel.Instance.postMessage(
							window,
							"QueueEditor::onSave",
							{
								DATA: response.DATA
							}
						);
						BX.SidePanel.Instance.close();
					}
					else
					{
						jsUtils.Redirect([], self.groupListUrl);
					}
				}
				else
				{
					alert(response.ERROR)
				}
			},
			onfailure: function()
			{
				BX.removeClass(saveButton, "webform-small-button-wait webform-small-button-active");
				BX.remove(waitNode);
				BX.debug('Failed to save group');
			}
		});
	};

	BX.ViGroupEdit.prototype._onCancelClick = function(e)
	{
		BX.onCustomEvent(this, 'onCancel', [this.externalRequestId]);
		BX.SidePanel.Instance.close();
	};

	BX.ViGroupEdit.prototype.destroy = function()
	{
		this.destination = null; // socnet finder could not be destroyed, not a big problem yet.
		BX.onCustomEvent(this, 'onDestroy', []);
	};
})();