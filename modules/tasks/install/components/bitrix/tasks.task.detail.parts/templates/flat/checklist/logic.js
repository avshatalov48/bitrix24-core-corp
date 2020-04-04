BX.namespace('Tasks.Component');

// todo: move this class to BX.Tasks.Util.ItemSet

(function(){

	if(typeof BX.Tasks.Component.TaskDetailPartsChecklist != 'undefined')
	{
		return;
	}

	// nested object
	var checkListItem = function(itemData, parent)
	{
		this.vars = {};
		this.ctrls = {};

		this.data(itemData.data);
		this.can(itemData.can || {
				MODIFY: true,
				REMOVE: true,
				TOGGLE: true
			});

		// we got no templating mechanism yet, so a couple of temporal spikes
		var tData = BX.clone(this.data());

		tData.CHECKED = this.isComplete() ? 'checked' : '';
        tData.READONLY = this.vars.can.MODIFY ? '' : 'noedit'; // this.can.REMOVE == this.can.MODIFY always
		tData.APPEARANCE = this.isSeparatorValue(tData.TITLE) ? 'a-separator' : 'a-generic';

		this.mode = 'read';
		this.parent = parent;

		this.scope = parent.getNodeByTemplate('item', tData)[0];
	};
	BX.mergeEx(checkListItem.prototype, {

		data: function(data)
		{
			if(typeof data !== 'undefined')
			{
				this.vars.data = data;

				this.blockRedraw = true;
				this.title(data.TITLE, false);
				this.blockRedraw = false;

				return;
			}

			return this.vars.data;
		},

		can: function(can)
		{
			if(typeof can !== 'undefined')
			{
				this.vars.can = can;
				return;
			}

			return this.vars.can;
		},

		isComplete: function(flag)
		{
			if(typeof flag !== 'undefined')
			{
				this.vars.data.IS_COMPLETE = flag ? 'Y' : 'N';
				this.redraw();

				return;
			}

			return this.data().IS_COMPLETE == 'Y';
		},
		sortIndex: function(index)
		{
			if(typeof index !== 'undefined')
			{
				this.vars.data.SORT_INDEX = parseInt(index);
				this.redraw();

				return;
			}

			return parseInt(this.data().SORT_INDEX);
		},
		id: function()
		{
			return this.data().ID;
		},
		number: function(num)
		{
			num = parseInt(num);
			if(!isNaN(num))
			{
				this.control('number').innerHTML = num;
			}
		},
		title: function(title, overwriteHtml)
		{
			if(typeof title != 'undefined')
			{
				this.vars.data.TITLE = title;
				if(typeof this.vars.data.TITLE_HTML == 'undefined' || overwriteHtml !== false)
				{
					// todo: bbcode => html parser here, at least BIU+ANCHOR
					this.vars.data.TITLE_HTML = BX.util.htmlspecialchars(this.data().TITLE);
				}
				this.redraw();

				return;
			}

			return this.data().TITLE;
		},
		titleTmp: function()
		{
			return this.control('title-edit').value.toString();
		},
		destruct: function()
		{
			BX.remove(this.scope);
			this.scope = null;
			this.vars.data = null;
		},
		isSeparator: function()
		{
			return this.isSeparatorValue(this.data().TITLE);
		},
		setAppearance: function(app)
		{
			app = app == 'separator' ? 'a-separator' : 'a-generic';

			this.parent.dropCSSFlags('a-*', this.scope);
			this.parent.setCSSFlag(app, this.scope);
		},
		setEditMode: function()
		{
			if(this.mode == 'edit')
			{
				return;
			}

			this.mode = 'edit';
			this.control('title-edit').value = this.data().TITLE;;

			this.redraw();
		},
		setReadMode: function()
		{
			if(this.mode == 'read')
			{
				return;
			}

			this.mode = 'read';
			this.redraw();
		},
		applyTitleChange: function()
		{
			this.setReadMode();
			this.title(this.titleTmp());
		},
		handleDelete: function()
		{
			if(this.mode == 'edit') // discard changes
			{
				this.control('title-edit').value = this.data().TITLE;
				this.setReadMode();
				return false;
			}
			else
			{
				return true;
			}
		},
		isSeparatorValue: function(value) // keep in touch with \Bitrix\Tasks\UI\Task\CheckList::checkIsSeparatorValue();
		{
			value = value.toString().replace(/^\s+/, '').replace(/\s+$/, '');

			return !!value.match(/^(-|=|_|\*|\+){3,}$/);
		},
		control: function(id)
		{
			id = 'item-'+id;

			if(typeof this.ctrls[id] == 'undefined')
			{
				this.ctrls[id] = this.parent.control(id, this.scope);
			}

			return this.ctrls[id];
		},
		redraw: function()
		{
			if(this.blockRedraw)
			{
				return;
			}

			if(this.mode == 'edit')
			{
                this.parent.setCSSMode('mode', 'edit', this.scope);
			}
			else
			{
                this.parent.setCSSMode('mode', 'read', this.scope);
			}

			// in try-catch, because some items may be undefined

			try
			{
				this.control('title').innerHTML = this.data().TITLE_HTML;
			}
			catch(e)
			{
			}

			try
			{
				this.control('btn-check').checked = this.isComplete();
			}
			catch(e)
			{
			}

			try
			{
				this.control('is-complete-fld').value = this.isComplete() ? 'Y' : 'N';
			}
			catch(e)
			{
			}

			try
			{
				this.control('sort-index-fld').value = parseInt(this.sortIndex());
			}
			catch(e)
			{
			}

			if(this.isSeparator())
			{
				this.setAppearance('separator');
			}
		}
	});

	BX.Tasks.Component.TaskDetailPartsChecklist = BX.Tasks.Util.Widget.extend({
		sys: {
			code: 'checklist'
		},
		options: {
			data: 		false,
			autoSync: 	false,
			taskId: 	false,
            taskCanEdit: false
		},
		methods: {
			construct: function()
			{
				this.callConstruct(BX.Tasks.Util.Widget);

				BX.mergeEx(this.vars, {
					items: {},
					newIncrement: 0,
					syncLock: false
				});

				var dd = new BX.Tasks.Util.DragAndDrop({
					createFlying: BX.delegate(function(node){

						var itemId = BX.data(node, 'item-id');
						var item = this.vars.items[itemId];

						return this.getNodeByTemplate((item.isSeparator() ? 'separator' : 'item')+'-flying', {
							'TITLE_HTML': item.data().TITLE_HTML,
							'CHECKED': item.isComplete() ? 'checked' : '',
							'ID': item.id()
						})[0];

					}, this),
					autoMarkItemAfter: true,
					autoMarkZoneTopBottom: true
				});
				dd.bindDropZone(this.control('items-ongoing'));
				dd.bindDropZone(this.control('items-complete'));

				if(typeof this.instances == 'undefined')
				{
					this.instances = {};
				}

				this.instances.dragNDrop = dd;
				this.instances.query = false;

				this.bindEvents();

				this.load(this.option('data'));
			},

			bindEvents: function()
			{
				// for each existing item
				this.bindDelegateControl('item-btn-edit', 'click', this.passCtx(this.setItemEdit));
				this.bindDelegateControl('item-btn-delete', 'click', this.passCtx(this.setItemCancel));
				this.bindDelegateControl('item-btn-apply', 'click', this.passCtx(this.setItemApply));
				this.bindDelegateControl('item-btn-check', 'change', this.passCtx(this.setItemToggle));
				this.bindDelegateControl('item-title-edit', 'keydown', this.passCtx(this.setItemApplyOnKeydown));

				// new item form
				this.bindDelegateControl('add-item-form-open', 'click', this.passCtx(this.newItemOpenForm));
				this.bindDelegateControl('add-item-form-close', 'click', this.passCtx(this.newItemCloseForm));
				this.bindDelegateControl('add-item', 'click', this.passCtx(this.newItemAdd));
				this.bindDelegateControl('add-item-title', 'keydown', this.passCtx(this.newItemTitleKeydown));

				this.bindDelegateControl('toggle-complete', 'click', BX.delegate(this.onCompleteToggle, this));
				this.bindDelegateControl('add-separator', 'click', BX.delegate(this.newSeparatorAdd, this));

				// dropdown
				this.instances.dragNDrop.bindEvent('item-relocated', BX.delegate(this.itemRelocated, this));
			},

			itemRelocated: function(node, listNode, nodeScope)
			{
				var itemId = BX.data(node, 'item-id');
				var itemInst = this.vars.items[itemId];

				var acts = [];

				var toComplete = (listNode == this.control('items-complete'));
				if(itemInst.isComplete() != toComplete)
				{
					itemInst.isComplete(toComplete);
					acts.push([toComplete ? 'complete' : 'renew', {id: itemId}]);
				}

				// relocate
				var afterItemId = false;
				if(nodeScope.after !== null)
				{
					afterItemId = BX.data(nodeScope.after, 'item-id');
				}
				if(toComplete && afterItemId === false) // try to get the last item of an ongoing part, if you insert to the top of complete part
				{
					afterItemId = this.getOngoingItemByGreatestSortIndex();
				}

				if(afterItemId != itemId)
				{
					acts.push(['moveAfter', {id: itemInst.id(), afterId: afterItemId}]);
					this.sync(acts);

					this.shiftSortIndexes(itemId, afterItemId);
				}

				this.redraw();
			},

			shiftSortIndexes: function(itemId, afterItemId)
			{
				var index = this.getSortedItemList();
				var newArr = [];

				if(afterItemId === false)
				{
					newArr.push(itemId);
				}

				for(var k = 0; k < index.length; k++)
				{
					if(index[k].id == itemId)
					{
						continue;
					}

					newArr.push(index[k].id);

					if(afterItemId !== false && index[k].id == afterItemId)
					{
						newArr.push(itemId);
					}
				}

				var i = 0;
				for(var k = 0; k < newArr.length; k++)
				{
					this.vars.items[newArr[k]].sortIndex(i);
					i++;
				}
			},

			setItemEdit: function(btn)
			{
				this.newItemCloseForm();

				var itemInst = this.getInstanceByNode(btn);

				if(itemInst)
				{
					itemInst.setEditMode();
				}
			},

			setItemCancel: function(btn)
			{
				var itemInst = this.getInstanceByNode(btn);

				if(itemInst)
				{
					if(itemInst.handleDelete())
					{
						var itemId = itemInst.id();

						if(!this.getQuery()) // we are in task edit mode, dont ask annoying questions
						{
							this.deleteItem(itemId);
						}
						else // we are in view mode, ask to avoid data loose
						{
							BX.Tasks.confirm(BX.message('TASKS_COMMON_CONFIRM_DELETE').replace('#ENTITY_NAME#', BX.message('TASKS_TTDP_CHECKLIST_ENTITY_NAME')), function(way){
								if(way)
								{
									this.deleteItem(itemId);
								}
							}, {ctx: this});
						}
					}
				}
			},

			setItemApply: function(btn)
			{
				var itemInst = this.getInstanceByNode(btn);

				if(itemInst)
				{
					var title = itemInst.titleTmp();

					if(title.length > 0)
					{
						this.sync([['update', {id: itemInst.id(), data:{TITLE: title}}]], function(){
							itemInst.applyTitleChange();
							this.redraw();
						});
					}
				}
			},

			setItemApplyOnKeydown: function(btn, e)
			{
				if(BX.Tasks.Util.isEnter(e))
				{
					this.setItemApply(btn);

					BX.PreventDefault(e);
				}
			},

			setItemToggle: function(btn)
			{
				var itemInst = this.getInstanceByNode(btn);

				if(itemInst)
				{
                    if(!itemInst.can().TOGGLE)
                    {
                        btn.checked = !btn.checked;
                        return;
                    }

					this.sync([[btn.checked ? 'complete' : 'renew', {id: itemInst.id()}]]);
					itemInst.isComplete(btn.checked);
					this.redraw();
				}
			},

			getInstanceByNode: function(node)
			{
				var itemScope = this.controlP('item-appearance', node);
				if(BX.type.isElementNode(itemScope))
				{
					var itemId = BX.data(itemScope, 'item-id');

					if(typeof itemId != 'undefined' && itemId !== null)
					{
						return this.vars.items[itemId];
					}
				}
			},

			getQuery: function()
			{
				if(!this.option('autoSync') || !parseInt(this.option('taskId')))
				{
					return null;
				}

				if(!this.instances.query)
				{
					this.instances.query = new BX.Tasks.Util.Query();
				}

				return this.instances.query;
			},

			addItem: function(item, params)
			{
				params = params || {};

				if(item.data.TITLE.toString().length == 0)
				{
					return false;
				}

				if(typeof item.data.ID == 'undefined')
				{
					item.data.ID = "n"+(this.vars.newIncrement++);
				}

				var itemInst = new checkListItem(item, this);

				this.vars.items[itemInst.id()] = itemInst;

				if(this.option('taskCan')['CHECKLIST.REORDER'])
				{
					this.instances.dragNDrop.bindNode(itemInst.scope, {handle: this.controlAll('item-drag', itemInst.scope)});
				}

				if(!params.load)
				{
					this.redraw();
				}

				return itemInst.id();
			},

			deleteItem: function(id)
			{
				if(typeof this.vars.items[id] == 'undefined')
				{
					return;
				}

				this.sync([['delete', {id: id}]], function(){

					var itemInst = this.vars.items[id];
					this.instances.dragNDrop.unBindNode(itemInst.scope);
					itemInst.destruct();

					this.vars.items[id] = null;
					delete(this.vars.items[id]);

					this.redraw();
				});
			},

			syncAddItem: function(item, onAdd, onToggle)
			{
				this.sync([['add', {data:{TASK_ID: parseInt(this.option('taskId')), TITLE: item.data.TITLE, IS_COMPLETE: item.data.IS_COMPLETE}}, {code: 'task_chl_add'}]], onAdd, onToggle);
			},

			sync: function(todo, callback, syncToggle)
			{
				if(this.vars.syncLock)
				{
					return;
				}

				callback = BX.type.isFunction(callback) ? callback : BX.DoNothing;

				var q = this.getQuery();
				if(q)
				{
					var self = this;

					syncToggle = BX.type.isFunction(syncToggle) ? syncToggle : BX.DoNothing;
					self.vars.syncLock = true;
					syncToggle.apply(self, [true]);

					var acts = [];
					for(var k = 0; k < todo.length; k++)
					{
						acts.push({m: 'task.checklist.'+todo[k][0], args: todo[k][1], rp: todo[k][2]});
					}

					q.load(acts).execute({done: function(errors, result){

						self.vars.syncLock = false;
						syncToggle.apply(self, [false]);

						if(!errors.length)
						{
							callback.apply(self, [result]);
						}
					}});
				}
				else
				{
					this.vars.syncLock = false;
					callback.call(this);
				}
			},

			getSortedItemList: function()
			{
				var index = [];

				// first, resort items by SORT_INDEX
				for(var k in this.vars.items)
				{
					index.push({
						ix: this.vars.items[k].sortIndex(),
						id: this.vars.items[k].id()
					});
				}

				return index.sort(function(a,b){
					if(a.ix < b.ix)
					{
						return -1;
					}
					else if(a.ix > b.ix)
					{
						return 1;
					}

					return 0;
				});
			},

			redrawControls: function()
			{
				var complete = 0;
				var total = 0;
				var index = this.getSortedItemList();

				for(var k = 0; k < index.length; k++)
				{
					if(!this.vars.items[index[k].id].isSeparator())
					{
						total++;
						if(this.vars.items[index[k].id].isComplete())
						{
							complete++;
						}
					}
				}

				// update counters

				this.showControlIf('complete-block', complete > 0);
				this.showControlIf('counterset', complete > 0);

				// try-catch, because some elements may be null-ed

				try
				{
					this.control('total-counter').innerHTML = total;
				}
				catch(e)
				{
				}

				try
				{
					this.control('ongoing-counter').innerHTML = total - complete;
				}
				catch(e)
				{
				}

				var completeCounters = this.controlAll('complete-counter');
				for(var k in completeCounters)
				{
					completeCounters[k].innerHTML = complete;
				}
			},

			redrawPool: function()
			{
				var index = this.getSortedItemList();

				// then reorder and classify nodes without physical delete
				var ongoingPool = BX.create('div');
				var completePool = BX.create('div');

				var i = 1;
				for(var k = 0; k < index.length; k++)
				{
					var itemInst = this.vars.items[index[k].id];

					if(!itemInst.isSeparator())
					{
						itemInst.number(i++);
					}

					BX.append(itemInst.scope, itemInst.isComplete() ? completePool : ongoingPool);
				}

				this.moveNodePool(completePool, this.control('items-complete'));
				this.moveNodePool(ongoingPool, this.control('items-ongoing'));
			},

			redraw: function()
			{
				this.redrawPool();
				this.redrawControls();
			},

			moveNodePool: function(from, to)
			{
				while(from.childNodes.length > 0)
				{
					BX.append(from.childNodes[0], to);
				}
			},

			load: function(data)
			{
				if(BX.type.isPlainObject(data))
				{
					var cnt = 0;
					for(var id in data)
					{
						var item = {data: BX.clone(data[id]), can: data[id].ACTION};

						this.addItem(item, {load: true});
						cnt++;
					}

					this.vars.newIncrement = cnt;
					this.redraw();
				}
			},

			getOngoingItemByGreatestSortIndex: function()
			{
				var max = 0;
				var maxItemId = false;
				for(var k in this.vars.items)
				{
					var index = this.vars.items[k].sortIndex();

					if(index > max && !this.vars.items[k].isComplete())
					{
						max = index;
						maxItemId = k;
					}
				}

				return maxItemId;
			},

			getGreatestSortIndex: function()
			{
				var max = 0;
				for(var k in this.vars.items)
				{
					var index = this.vars.items[k].sortIndex();

					if(index > max)
					{
						max = index;
					}
				}

				return max;
			},

			// new item form

			newItemOpenForm: function()
			{
				this.switchControl(this.control('add-item-form'), 'on');

				this.control('add-item-title').focus();
			},

			newItemCloseForm: function()
			{
				this.switchControl(this.control('add-item-form'), 'off');
			},

			newItemTitleKeydown: function(node, e)
			{
				if(BX.Tasks.Util.isEnter(e))
				{
					this.newItemAdd();

					BX.PreventDefault(e);
				}
			},

			newSeparatorAdd: function()
			{
				var data = this.makeItemData('===');

				this.syncAddItem({data: data}, function(result){

					try
					{
						data.ID = parseInt(result.data['task_chl_add'].RESULT.DATA.ID); // switch to real ID
					}
					catch(e)
					{
					}

					// todo: read ACTUAL "can" from server response, as it may depend on other data sources
					this.addItem({data:data});
				});
			},
			
			newItemAdd: function()
			{
				if(this.control('add-item-title').value.toString().length < 1)
				{
					return;
				}

				var data = this.makeItemData(this.control('add-item-title').value);

				this.syncAddItem({data: data}, function(result){

					try
					{
						data.ID = parseInt(result.data['task_chl_add'].RESULT.DATA.ID); // switch to real ID
					}
					catch(e)
					{
					}

					this.control('add-item-title').value = '';
					this.control('add-item-title').focus();

					// todo: read ACTUAL "can" from server response, as it may depend on other data sources
					this.addItem({data: data});
				}, function(way){
					if(way)
					{
						BX.Tasks.Util.disable(this.control('add-item-title'));
					}
					else
					{
						BX.Tasks.Util.enable(this.control('add-item-title'));
					}
				});
			},

			makeItemData: function(text)
			{
				return {
					IS_COMPLETE: "N",
					SORT_INDEX: this.getGreatestSortIndex() + 1,
					TITLE: text
				};
			},

			onCompleteToggle: function()
			{
				BX.toggleClass(this.control('complete-block'), 'open');
			},

			// util

			showControlIf: function(id, condition)
			{
				BX[condition ? 'removeClass' : 'addClass'](this.control(id), 'hidden');
			},

			switchControl: function(node, way)
			{
				way = way == 'on';

				if(way)
				{
					BX.addClass(node, 'on');
					BX.removeClass(node, 'off');
				}
				else
				{
					BX.removeClass(node, 'on');
					BX.addClass(node, 'off');
				}
			},

			count: function()
			{
				var i = 0;
				for(var k in this.vars.items)
				{
					i++;
				}

				return i;
			}
		}
	});

})();