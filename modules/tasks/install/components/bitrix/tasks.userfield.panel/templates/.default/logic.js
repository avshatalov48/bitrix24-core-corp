'use strict';

BX.namespace('Tasks.Component');

(function(){

	if(typeof BX.Tasks.Component.UserFieldPanel != 'undefined')
	{
		return;
	}

	/**
	 * Main js controller for this template
	 */
	BX.Tasks.Component.UserFieldPanel = BX.Tasks.Component.extend({
		sys: {
			code: 'uf-panel'
		},
		methods: {
			construct: function()
			{
				this.callConstruct(BX.Tasks.Component);

				this.instances.items = new BX.Tasks.Component.UserFieldPanel.ItemSet({
					scope: this.scope(),
					data: this.option('scheme'),
					itemFx: 'vertical',
					parent: this
				});
				this.instances.items.bindEvent('item-save', BX.delegate(this.onItemSave, this));
				this.instances.items.bindEvent('item-hide', BX.delegate(this.onItemHide, this));
				this.instances.items.bindEvent('item-delete', BX.delegate(this.onItemDelete, this));

				this.saveState = BX.debounce(this.saveStateInstant, 1200, this); // saving only the last state after repeated action

				this.redrawButtons();
			},

			bindEvents: function()
			{
				this.bindControlPassCtx('add-field', 'click', this.showAddPopup);
				this.bindControlPassCtx('action', 'click', this.showActionPopup);
				this.bindControlPassCtx('un-hide-field', 'click', this.showUnHideFieldPopup);
				this.bindDelegateControl('item-label-edit', 'keypress', this.jamEnter, this.control('new-item-place'));
			},

			jamEnter: function(e)
			{
				if(BX.Tasks.Util.isEnter(e))
				{
					BX.eventReturnFalse(e);
				}
			},

			redrawButtons: function()
			{
				var haveHidden = false;
				var allHidden = true;
				this.instances.items.each(function(item){

					if(!item.data().STATE.D)
					{
						haveHidden = true;
					}
					else
					{
						allHidden = false;
					}
				});

				this.changeCSSFlag('invisible', !haveHidden, this.control('un-hide-field'));
				this.changeCSSFlag('not-empty', !allHidden, this.control('items'));
			},

			openAddForm: function(menu, e, item)
			{
				menu.popupWindow.close();

				item.options = item.options || {};

				this.instances.items.openAddForm(item.options.code || item.code);
			},

			onItemSave: function(p, id, data)
			{
				data.ENTITY_CODE = this.option('entityCode');

				this.callRemote('this.saveField', {
					id: id,
					data: data,
					parameters: {
						INPUT_PREFIX: this.option('inputPrefix'),
						RELATED_ENTITIES: this.option('relatedEntities')
					}
				}).then(function(result){

					if(result.isSuccess())
					{
						p.fulfill(result.getData());
					}
					else
					{
						p.reject(result.getErrors());
					}
				});
			},

			onItemHide: function(p, id)
			{
				var user = this.option('user');

				var useDelete = this.option('restriction').MANAGE && user.IS_SUPER;

				var title = BX.message(useDelete ? 'TASKS_TUFP_FIELD_HIDE_DELETE_CONFIRM' : 'TASKS_TUFP_FIELD_HIDE_CONFIRM');
				var params = {
					id: this.code()+'-fh-confirm-v2',
					ctx: this,
					isDisposable: !useDelete,
					buttonSet: useDelete ? [
						{text: BX.message('TASKS_COMMON_DELETE'), type: 'red', code: 'delete'},
						{text: BX.message('TASKS_COMMON_HIDE'), type: 'green', code: 'continue', default: true}
					] : false
				};

				BX.Tasks.confirm(
					title,
					null,
					params
				).then(function(code){

					if(code == 'continue')
					{
						this.setItemStateDisplay(id, false); // hide item
						p.fulfill('hide'); // play animation
					}
					else if(code == 'delete')
					{
						return this.callRemote('this.deleteField', {
							id: id,
							parameters: {
								RELATED_ENTITIES: this.option('relatedEntities')
							}
						}).then(function(result){

							if(!result.isSuccess())
							{
								BX.Tasks.alert(result.getErrors());
								p.reject();
							}
							else
							{
								p.fulfill('delete'); // remove item from js object, play animation, fire item-delete event
							}
						});
					}
				}.bind(this));
			},

			onItemDelete: function(id)
			{
				this.redrawButtons();
			},

			setItemStateDisplay: function(id, way, instant)
			{
				this.instances.items.get(id).data().STATE.D = way;
				this.redrawButtons();

				if(instant)
				{
					this.saveStateInstant();
				}
				else
				{
					this.saveState();
				}
			},
			setItemSortBefore: function(item, beforeItem)
			{
				var index = [];
				this.instances.items.each(function(i){
					index.push({value: parseInt(i.value()), sort: i.data().STATE.S});
				});

				index = index.sort(function(a, b){
					if(a.sort > b.sort)
					{
						return 1;
					}
					else if(a.sort < b.sort)
					{
						return -1;
					}
					return 0;
				});

				var itemValue = parseInt(item.value());
				var beforeItemValue = beforeItem ? parseInt(beforeItem.value()) : -1;

				// resort state
				var j = 1;
				for(var k in index)
				{
					if (index.hasOwnProperty(k))
					{
						if(index[k].value != itemValue)
						{
							if(index[k].value == beforeItemValue)
							{
								this.instances.items.get(itemValue).data().STATE.S = j;
								j = j + 1;
							}

							this.instances.items.get(index[k].value).data().STATE.S = j;
							j = j + 1;
						}
					}
				}

				if(beforeItemValue < 0) // to the bottom
				{
					this.instances.items.get(itemValue).data().STATE.S = j;
				}

				// save state
				this.saveState();
			},

			saveStateInstant: function(dropAll)
			{
				this.callRemote('this.setState', {
					state: this.packState(),
					dropAll: !!dropAll,
					entityCode: this.option('entityCode')
				}, {code: 'setstate'});
			},

			packState: function()
			{
				var state = {};
				this.instances.items.each(function(i){
					state[i.value()] = i.data().STATE;
				});

				return state;
			},

			initDragNDrop: function()
			{
				if(!this.instances.dragNDrop)
				{
					var dd = new BX.Tasks.Util.DragAndDrop({
						createFlying: BX.delegate(function(node){

							var item = this.getItemByNode(node);

							return this.getNodeByTemplate('item-flying', item.data())[0];

						}, this),
						autoMarkItemAfter: true,
						autoMarkZoneTopBottom: true
					});
					dd.bindDropZone(this.control('items'));
					dd.bindEvent('item-relocation-before', this.onDragNDropItemRelocatedBefore, this);
					dd.bindEvent('item-relocation-after', this.onDragNDropItemRelocatedAfter, this);

					this.instances.dragNDrop = dd;

					// bind all items
					this.instances.items.each(BX.delegate(this.bindDragNDropNode, this));
				}
			},

			onDragNDropItemRelocatedBefore: function(p, node, neigbours)
			{
				var item = this.getItemByNode(node);
				if(item)
				{
					p.cancelAutoResolve(); // we`ll handle it
					item.disappear().then(function(){
						p.fulfill(); // resume drag
					});
				}
			},

			onDragNDropItemRelocatedAfter: function(p, node, neigbours)
			{
				var item = this.getItemByNode(node);
				if(item)
				{
					p.cancelAutoResolve(); // we`ll handle it
					item.appear().then(function(){
						p.fulfill(); // complete drag
					});

					this.setItemSortBefore(item, this.getItemByNode(neigbours.before));
				}
			},

			getItemByNode: function(node)
			{
				var item = null;
				if(node)
				{
					var value = BX.data(node, 'item-value');
					if(value)
					{
						item = this.instances.items.get(value);
					}
				}

				return item;
			},

			bindDragNDropNode: function(item)
			{
				if(this.instances.dragNDrop)
				{
					this.instances.dragNDrop.bindNode(item.scope(), {handle: this.controlAll('item-drag', item.scope())});
				}
			},

			registerItem: function(item)
			{
				this.bindDragNDropNode(item);
				this.saveStateInstant(); // update state
			},

			doMenuAction: function(menu, e, item)
			{
				menu.popupWindow.close();

				var action = item.action;

				if(action == 'dragToggle')
				{
					item.layout.text.innerHTML = BX.message('TASKS_TUFP_DRAG_N_DROP_'+(item.enabled ? 'ON' : 'OFF'));
					item.enabled = !item.enabled;

					if(item.enabled)
					{
						this.initDragNDrop();
					}

					this.changeCSSFlag('drag-mode-on', item.enabled, this.scope());
				}
				else if(action == 'saveToAll')
				{
					BX.Tasks.confirm(
						BX.message('TASKS_TUFP_SAVE_TO_ALL_CONFIRM'),
						null,
						{isDisposable: true, id: this.code()+'-savetoall-confirm', ctx: this}
					).then(function(){
						this.saveState(true);
					});
				}
			},

			getItem: function(id)
			{
				var p = new BX.Promise(null, this);
				var item = this.instances.items.get(id);

				if(item.data().NO_FIELD_HTML) // have to download field html first
				{
					this.callRemote('this.getFieldHtml', {
						id: id,
						entityCode: this.option('entityCode'),
						entityId: this.option('entityId'),
						parameters: {
							INPUT_PREFIX: this.option('inputPrefix'),
							RELATED_ENTITIES: this.option('relatedEntities')
						}
					}).then(function(result){

						var html = '';
						if(result.isSuccess())
						{
							html = result.getData();
						}
						else
						{
							html = '<div class="task-message-label error">'+result.getErrors().getMessages(true).join('<br />')+'</div>';
						}

						item.setFieldHtml(html);

						p.fulfill(item);
					});

					item.data().NO_FIELD_HTML = false;
				}
				else
				{
					p.fulfill(item);
				}

				return p;
			},

			unHideField: function(node)
			{
				var id = BX.data(node, 'id');
				if(BX.type.isNotEmptyString(id))
				{
					// change state
					this.setItemStateDisplay(id, true, true);

					this.getItem(id).then(function(item){
						this.instances.items.showItem(item);
					});
				}

				if(this.instances.unHideMenu)
				{
					this.instances.unHideMenu.hide();
				}
			},

			showActionPopup: function(node)
			{
				var user = this.option('user');

				var menu = [{
					action: 'dragToggle',
					enabled: false,
					text: BX.message('TASKS_TUFP_DRAG_N_DROP_ON'),
					onclick: this.passCtx(this.doMenuAction)
				}];
				if(user.IS_SUPER)
				{
					menu.unshift({
						action: 'saveToAll',
						text: BX.message('TASKS_TUFP_SAVE_SCHEME_TO_EVERYONE'),
						onclick: this.passCtx(this.doMenuAction)
					});
				}

				BX.PopupMenu.show(
					this.id() + 'action',
					node,
					menu,
					{angle: true, position: 'right', offsetLeft: 18, offsetTop: 0}
				);
			},

			canManage: function()
			{
				if(!this.option('restriction').MANAGE && B24)
				{
					B24.licenseInfoPopup.show(this.code(), BX.message('TASKS_TUFP_LICENSE_TITLE'), '<span>'+BX.message('TASKS_TUFP_LICENSE_BODY')+'</span>');
					return false;
				}

				return true;
			},

			showAddPopup: function(node)
			{
				if(!this.canManage())
				{
					return;
				}

				var menu = [];
				var types = this.option('typesToCreate');
				if(BX.type.isPlainObject(types))
				{
					for(var k in types)
					{
						if(types.hasOwnProperty(k))
						{
							menu.push({
								code: k,
								text: types[k],
								title: types[k],
								onclick: this.passCtx(this.openAddForm)
							});
						}
					}
				}

				BX.PopupMenu.show(
					this.id() + 'add',
					node,
					menu,
					{
						angle: true,
						closeByEsc: true,
						position: 'top',
						offsetLeft: 40,
						offsetTop: 5
					}
				);
			},

			showUnHideFieldPopup: function(node)
			{
				var html = '';

				var items = this.subInstance('items').exportItemData();
				if(!items.count())
				{
					return;
				}

				items.sort([
					['STATE.S', 'asc']
				]).each(BX.delegate(function(data){

					if(!data.STATE.D)
					{
						html += this.getHTMLByTemplate('menu-item', {
							LABEL: data.LABEL,
							ID: data.ID,
							LABEL_EXT: data.LABEL+(data.MANDATORY ? ' ('+BX.message('TASKS_TUFP_FIELD_MANDATORY')+')' : ''),

							// template logic emulation
							STAR_INVISIBLE: data.MANDATORY ? '' : 'invisible'
						});
					}
				}, this));

				this.control('uhmenu').innerHTML = html;

				if(!this.instances.unHideMenu)
				{
					this.instances.unHideMenu = new BX.Tasks.Util.ScrollPanePopup({
						scope: this.control('un-hide-menu'),
						maxHeight: 300,
						popupParameters: {
							angle: true, position: 'top', offsetLeft: 40, offsetTop: 5,
							noAllPaddings: true
						}
					});
					this.instances.unHideMenu.bindDelegateControl('item', 'click', this.passCtx(this.unHideField));
				}
				this.instances.unHideMenu.show(node);
			}
		}
	});

	BX.Tasks.Component.UserFieldPanel.ItemSet = BX.Tasks.Util.ItemSet.extend({
		options: {
			controlBind: 'class',
			preRendered: true,
			itemAppearFxSpeed: 300,
			itemDisappearFxSpeed: 300
		},
		methods: {
			getItemClass: function()
			{
				return BX.Tasks.Component.UserFieldPanel.ItemSet.Item;
			},
			bindItemActions: function()
			{
				this.bindDelegateControl('item-hide', 'click', this.bindOnItem(this.hideItem));
				this.bindDelegateControl('item-edit', 'click', this.bindOnItem(this.enterEditItem));
				this.bindDelegateControl('item-cancel', 'click', this.bindOnItem(this.leaveEditItem, this.leaveEditItemNew));
				this.bindDelegateControl('item-save', 'click', this.bindOnItem(this.saveItem, this.saveItemNew));
				this.bindDelegateControl('item-label-edit', 'keypress', this.bindOnItem(this.itemLabelChanged));
			},
			hideItem: function(item)
			{
				var p = new BX.Promise(null, this);
				this.fireEvent('item-hide', [p, item.value()]);

				p.then(function(code){

					if(code == 'hide')
					{
						item.disappear();
					}
					else if(code == 'delete')
					{
						this.deleteItem(item.value());
					}
				});
			},
			showItem: function(item)
			{
				item.appear();
			},
			enterEditItem: function(item)
			{
				if(this.parent() && !this.parent().canManage())
				{
					return;
				}

				item.toggleEditMode(true);
				item.initForm();
			},
			leaveEditItem: function(item)
			{
				item.toggleEditMode(false);
			},
			leaveEditItemNew: function()
			{
				if(this.instances.newItem)
				{
					this.instances.newItem.disappear();
				}
			},
			itemLabelChanged: function(item, e)
			{
				if(BX.Tasks.Util.isEnter(e))
				{
					this.saveItem(item);
					BX.eventReturnFalse(e);
				}
			},
			saveItem: function(item)
			{
				if(item.isLoading()) // already in progress
				{
					return;
				}

				item.setLoading(true);
				item.hideErrors();

				// event with promise here
				var p = new BX.Promise(null, this);
				this.fireEvent('item-save', [p, item.value(), item.getFormData()]);

				// then someone will handle request, do p.fulfill() and we will continue:
				p.then(function(newData){

					item.setLoading(false);
					item.update({
						LABEL: newData.LABEL,
						MANDATORY: newData.MANDATORY != '0' // boolean
					});

					item.toggleEditMode(false);
				}, function(errors){
					item.setLoading(false);
					item.showErrors(errors);
				});
			},
			saveItemNew: function()
			{
				if(this.instances.newItem)
				{
					var item = this.instances.newItem;

					if(item.isLoading()) // already in progress
					{
						return;
					}

					item.setLoading(true);
					item.hideErrors();

					var p = new BX.Promise(null, this);
					this.fireEvent('item-save', [p, 0, item.getFormData()]);

					// then someone will handle request, do p.fulfill() and we will continue:
					p.then(function(newData){

						item.setLoading(false);

						// update item with new data
						item.update({
							ID: newData.ID,
							FIELD_HTML: newData.FIELD_HTML,
							LABEL: newData.LABEL,
							MULTIPLE: newData.MULTIPLE != '0', // boolean
							MANDATORY: newData.MANDATORY != '0', // boolean
							STATE: {
								D: true,
								S: 999999
							}
						});

						// assign state here an then update state

						item.toggleEditMode(false).then(BX.delegate(function(){

							this.registerItem(item, {useAppear: false});
							if(this.parent())
							{
								this.parent().registerItem(item)
							}
							this.instances.newItem = null;

						}, this));

					}, function(errors){
						item.setLoading(false);
						item.showErrors(errors);
					});
				}
			},
			openAddForm: function(type)
			{
				var label = BX.message('TASKS_TUFP_NEW_FIELD_'+type.toUpperCase());

				if(this.instances.newItem)
				{
					this.instances.newItem.update({
						USER_TYPE_ID: type,
						LABEL: label,
						DISPLAY: label
					});
				}
				else
				{
					this.instances.newItem = this.createItem({
						VALUE: 'new',

						DISPLAY: label,
						LABEL: label,

						USER_TYPE_ID: type,
						MANDATORY: false,
						MULTIPLE: false,
						FIELD_HTML: this.getHTMLByTemplate('item-field-stub'),

						// template logic emulation
						REQUIRED: '',
						EDIT: 'edit',
						INVISIBLE: 'invisible',
						DEFACEABLE: 'defaceable',
						DISPLAY_MULTIPLE: '0'
					});
					BX.append(this.instances.newItem.scope(), this.control('new-item-place'));
					this.instances.newItem.toggleEditMode(true, true);
				}

				this.instances.newItem.initForm();

				if(!this.instances.newItem.isShown())
				{
					this.instances.newItem.appear();
				}
			}
		}
	});

	BX.Tasks.Component.UserFieldPanel.ItemSet.Item = BX.Tasks.Util.ItemSet.Item.extend({
		options: {
			controlBind: 'class'
		},
		methods: {
			toggleEditMode: function(way, noFx)
			{
				this.changeCSSFlag('edit', way);

				var p = new BX.Promise(null, this);
				var formPlace = this.control('form-place');

				if(noFx)
				{
					this.changeCSSFlag('invisible', !way, formPlace);
					p.fulfill();
				}
				else
				{
					BX.Tasks.Util.fadeSlideToggleByClass(formPlace).then(function(){
						p.fulfill();
					});
				}

				if(way)
				{
					this.initForm();
				}

				return p;
			},

			initForm: function()
			{
				var data = this.data();
				var multipleCtrl = this.control('multiple-edit');

				this.control('label-edit').value = data.DISPLAY;
				this.control('required-edit').checked = data.MANDATORY;
				multipleCtrl.checked = data.MULTIPLE;

				var disableMultiple = parseInt(data.VALUE) || data.USER_TYPE_ID == 'boolean';

				// disable multiple edit
				multipleCtrl.disabled = disableMultiple;
				BX[disableMultiple ? 'addClass' : 'removeClass'](multipleCtrl.parentNode, 'disabled');

				// restrict multiple in case of type boolean
				if(data.USER_TYPE_ID == 'boolean')
				{
					multipleCtrl.checked = false;
				}

				this.control('label-edit').focus();
				this.hideErrors();
			},

			getFormData: function()
			{
				var data = BX.clone(this.data());

				data.DISPLAY = data.LABEL = this.control('label-edit').value;
				data.MANDATORY = this.control('required-edit').checked;
				data.MULTIPLE = this.control('multiple-edit').checked;

				return data;
			},

			update: function(data)
			{
				var itemData = this.data();

				// todo: replace it with BX.mergeEx()
				if('LABEL' in data)
				{
					itemData.LABEL = itemData.DISPLAY = data.LABEL;
				}
				if('MANDATORY' in data)
				{
					itemData.MANDATORY = data.MANDATORY;
				}
				if('MULTIPLE' in data)
				{
					itemData.MULTIPLE = data.MULTIPLE;
				}
				if('FIELD_HTML' in data)
				{
					itemData.FIELD_HTML = data.FIELD_HTML;
				}
				if('USER_TYPE_ID' in data)
				{
					itemData.USER_TYPE_ID = data.USER_TYPE_ID;
				}
				if('STATE' in data)
				{
					itemData.STATE = data.STATE;
				}

				if('ID' in data)
				{
					this.value(data.ID);
				}

				this.redraw();
			},

			redraw: function()
			{
				// item dom update, eww...
				this.control('label').innerHTML = BX.util.htmlspecialchars(this.data().LABEL);
				BX[this.data().MANDATORY ? 'addClass' : 'removeClass'](this.scope(), 'required');
				this.scope().setAttribute('data-type', this.data().USER_TYPE_ID);
				this.scope().setAttribute('data-multiple', this.data().MULTIPLE ? '1' : '0');
				this.scope().setAttribute('data-item-value', this.data().VALUE);

				this.setFieldHtml(this.data().FIELD_HTML);
				delete(this.data().FIELD_HTML); // no need to keep it in memory
			},

			value: function(value)
			{
				if(value)
				{
					this.data().ID = value;
				}

				return this.callMethod(BX.Tasks.Util.ItemSet.Item, 'value', arguments);
			},

			isLoading: function()
			{
				return !!this.vars.actionInProgress;
			},

			setLoading: function(way)
			{
				this.vars.actionInProgress = way;

				if(!this.vars.loading)
				{
					this.vars.loading = BX.Tasks.Util.delay(function(){
						BX.addClass(this.control('save'), 'webform-small-button-wait');
					}, function(){
						BX.removeClass(this.control('save'), 'webform-small-button-wait');
					}, 300, this);
				}

				if(way)
				{
					this.vars.loading.call(this);
				}
				else
				{
					this.vars.loading.cancel();
				}
			},

			setFieldHtml: function(html)
			{
				if(!html)
				{
					return;
				}

				// replace date icon, no ability to do it in other way
				html = html.replace(new RegExp('/bitrix/js/main/core/images/calendar-icon.gif', 'g'), '/bitrix/js/tasks/css/images/calendar.png');

				BX.html(this.control('field-html'), html);
			},

			hideErrors: function()
			{
				BX.Tasks.Util.hideByClass(this.control('error'));
			},

			showErrors: function(errors)
			{
				this.control('error').innerHTML = errors.getMessages(true).join('<br />');
				BX.Tasks.Util.showByClass(this.control('error'));
			}
		}
	});
	BX.Tasks.Component.UserFieldPanel.ItemSet.Item.prepareData = function(data)
	{
		data.VALUE = data.ID;
		data.DISPLAY = data.LABEL;

		return data;
	};

}).call(this);