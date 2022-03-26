'use strict';

BX.namespace('Tasks.Component');

(function(){

	if(typeof BX.Tasks.Component.TasksWidgetCheckList != 'undefined')
	{
		return;
	}

	/**
	 * Main js controller for this template
	 */
	BX.Tasks.Component.TasksWidgetCheckList = BX.Tasks.Component.extend({
		sys: {
			code: 'checklist'
		},
		options: {
			confirmDelete: false
		},
		methods: {
			construct: function()
			{
				this.callConstruct(BX.Tasks.Component);

				this.getManager();
			},

			getManager: function()
			{
				return this.subInstance('manager', function(){
					var manager = new this.constructor.ItemManager({
						scope: this.scope(),
						data: this.option('data'),
						preRendered: true,
						confirmDelete: this.option('confirmDelete'),
						enableSync: this.option('enableSync'),
						entityRoute: this.option('entityRoute'),
						entityId: this.option('entityId')
					});
					manager.bindEvent('change', this.onManagerChange.bind(this));

					return manager;
				});
			},

			bindEvents: function()
			{
				this.bindControl('toggle-complete', 'click', this.onCompleteTogglerClick.bind(this));
			},

			onCompleteTogglerClick: function()
			{
				var complete = this.control('is-items-complete');
				this.toggleCompleted(BX.hasClass(complete, 'invisible'));
			},

			onManagerChange: function(order)
			{
				var total = 0;
				var completed = 0;
				var index = 1;
				var number = 1;
				this.getManager().each(function(item){

					if(!item.isSeparator())
					{
						if (item.isChecked())
						{
							completed++;
						}

						// update numbers
						total++;

						item.data().NUMBER = number++;
					}

					item.data().SORT = item.data().SORT_INDEX = index++;
					item.sort(item.data().NUMBER);
				});

				// update counters
				this.control('total-counter').innerHTML = total;
				BX.Tasks.each(this.controlAll('complete-counter'), function(item){
					item.innerHTML = completed;
				});

				this.showHideCompleted(!completed);
				if(!completed) // hide completed
				{
					this.toggleCompleted(false);
				}

				BX[completed ? 'removeClass' : 'addClass'](this.control('top-counter-set'), 'invisible');
			},

			showHideCompleted: function(way)
			{
				this.changeCSSFlag('hidden', way, this.control('complete-block'));
			},

			toggleCompleted: function(way)
			{
				var complete = this.control('is-items-complete');

				BX.Tasks.Util.fadeSlideToggleByClass(complete);
				this.changeCSSFlag('down', way, this.control('toggle-complete'));
			},

			count: function()
			{
				return this.getManager().count();
			},

			openForm: function()
			{
				this.getManager().openAddForm();
			}
		}
	});

	BX.Tasks.Component.TasksWidgetCheckList.ItemManager = BX.Tasks.Util.ItemSet.extend({
		sys: {
			code: 'checklist-is'
		},
		options: {
			controlBind: 'class',
			itemFx: 'vertical', // also 'vertical' and 'horizontal',
			confirmDelete: false,
			useDragNDrop: true,
			enableSync: false
		},
		methods: {

			bindEvents: function()
			{
				this.callMethod(BX.Tasks.Util.ItemSet, 'bindEvents');

				this.bindControl('add-separator', 'click', this.onAddSeparatorClick.bind(this));
				this.vars.formInitialized = false;
			},

			bindItemActions: function()
			{
				this.callMethod(BX.Tasks.Util.ItemSet, 'bindItemActions');

				this.bindOnItemEx('i-toggle', 'mouseup', this.onItemToggleClick);
				this.bindOnItemEx('i-toggle', 'change', this.onItemToggleChange);

				this.bindOnItemEx('i-edit', 'click', this.onItemEditClick);
				this.bindOnItemEx('i-apply', 'click', this.onItemApplyClick);
				this.bindOnItemEx('i-new-title', 'keypress', this.onItemNewTitleKeyPress);
			},

			bindDNDDropZones: function(dd)
			{
				this.callMethod(BX.Tasks.Util.ItemSet, 'bindDNDDropZones', arguments);
				dd.bindDropZone(this.control('items-complete'));
			},

			onDragNDropItemRelocatedAfter: function(p, node, neigbours)
			{
				var item = this.getItemByNode(node);
				if(item)
				{
					var acts = [];

					var wasChecked = item.isChecked();
					var nowChecked = neigbours.zone == this.control('items-complete');

					// check item
					if(nowChecked != wasChecked)
					{
						item.isChecked(nowChecked);
						acts.push([nowChecked ? 'complete' : 'renew', {id: item.value()}]);
					}

					// move item
					var afterItem = null;
					if(neigbours.after !== null)
					{
						afterItem = this.getItemByNode(neigbours.after);
					}
					if(nowChecked && afterItem === null) // try to get the last item of an ongoing part, if you insert to the top of complete part
					{
						afterItem = this.findLastUnCheckedItem();
					}

					if(!afterItem || (afterItem.value() != item.value()))
					{
						acts.push(['moveAfter', {id: item.value(), afterId: afterItem ? afterItem.value() : 0}]);
					}

					this.getSyncer().perform(acts);

					this.callMethod(BX.Tasks.Util.ItemSet, 'onDragNDropItemRelocatedAfter', arguments);
				}
			},

			onItemDeleteByCross: function(item)
			{
				if(item.isEditMode())
				{
					this.toggleItemMode(false, item);
				}
				else
				{
					var args = arguments;

					if(this.option('confirmDelete'))
					{
						BX.Tasks.confirmDelete(BX.message('TASKS_TTDP_CHECKLIST_ENTITY_NAME')).then(function(){
							this.deleteItemRemote.apply(this, args);
						}.bind(this));
					}
					else
					{
						this.deleteItemRemote.apply(this, args);
					}
				}
			},

			deleteItemRemote: function(item)
			{
				var args = arguments;
				this.getSyncer().perform([['delete', {id: item.value()}]]).then(function(){

					this.callMethod(BX.Tasks.Util.ItemSet, 'onItemDeleteByCross', args);

				}.bind(this));
			},

			onItemEditClick: function(item)
			{
				this.toggleItemMode(false, item);
			},

			onItemApplyClick: function(item)
			{
				this.toggleItemMode(true, item);
			},

			onItemToggleClick: function(item, node, e)
			{
				// this will prevent checkbox to be checked while animation plays
				if(item.isLockedCheck()) // cant do checking
				{
					BX.eventReturnFalse(e || Window.event);
				}
			},

			onItemToggleChange: function(item)
			{
				var checkSync = function(item)
				{
					this.getSyncer().perform([[item.isChecked() ? 'complete' : 'renew', {id: item.value()}]]);
				}.bind(this);

				this.getStriker().toggle(item.control('title'), function(){
					item.isChecked(true);
					checkSync(item);
					this.insertItemBefore(item, this.findItemBefore(item, true), true);
				}.bind(this), function(){
					item.isChecked(false);
					checkSync(item);
					this.insertItemBefore(item, this.findItemBefore(item, false), false);
				}.bind(this));
			},

			onItemNewTitleKeyPress: function(item, node, e)
			{
				e = e || Window.event;
				if(BX.Tasks.Util.isEnter(e))
				{
					this.toggleItemMode(true, item);
					BX.eventReturnFalse(e);
				}
			},

			insertItemBefore: function(item, beforeItem, amongComplete)
			{
				var p = new BX.Promise();
				var container = this.control(amongComplete ? 'items-complete' : 'items');

				if(container)
				{
					item.isLockedCheck(true); // no ability to un-check while animation plays

					item.disappear().then(function(){

						if(beforeItem === null)
						{
							BX.append(item.scope(), container);
						}
						else
						{
							container.insertBefore(item.scope(), beforeItem.scope());
						}

						item.appear().then(function(){
							item.isLockedCheck(false); // unlock check if resolve
							p.resolve();
							this.fireEvent('change', [this.vars.order]);
						}.bind(this), function(){
							item.isLockedCheck(false); // unlock check if reject
						});
					}.bind(this), function(){
						item.isLockedCheck(false); // unlock check if reject
					});
				}
				else
				{
					p.reject();
				}

				return p;
			},

			findItemBefore: function(item, amongComplete)
			{
				var sort = item.data().SORT;
				var before = null;
				var last = this.first();

				this.each(function(item){

					if(!(amongComplete && !item.isChecked()) && !(!amongComplete && item.isChecked()))
					{
						last = item;
					}

					if(last.data().SORT > sort)
					{
						before = item;
						return false; // break
					}
				});

				return before;
			},

			findLastUnCheckedItem: function()
			{
				var max = 0;
				var lastItemId = false;
				this.each(function(item){
					if(item.sort() > max && !item.isChecked())
					{
						max = item.sort();
						lastItemId = item.value();
					}
				});

				return lastItemId === null ? null : this.get(lastItemId);
			},

			openAddForm: function()
			{
				var form = this.control('add-item-form');
				if(form)
				{
					if(!this.vars.formInitialized)
					{
						this.bindControl('form-close', 'click', this.onFormCloseClick.bind(this));
						this.bindControl('form-submit', 'click', this.onFormSubmitClick.bind(this));
						this.bindControl('form-title', 'keypress', this.onFormTitleKeyPress.bind(this));

						this.vars.formInitialized = true;
					}

					this.addItemByForm();
					this.clearForm();
					this.toggleForm(true).then(function(){
						// if opened, focus form
						if(!BX.hasClass(this.control('add-item-form'), 'invisible'))
						{
							this.focusForm();
						}
					}.bind(this));
				}
			},

			onAddSeparatorClick: function()
			{
				this.addItemRemote(this.makeItemData('==='));
			},

			onFormCloseClick: function()
			{
				// clear form here
				this.toggleForm();
				this.clearForm();
			},

			onFormSubmitClick: function()
			{
				this.addItemByForm();
			},

			onFormTitleKeyPress: function(e)
			{
				e = e || Window.event;
				if(BX.Tasks.Util.isEnter(e))
				{
					this.addItemByForm();
					BX.eventReturnFalse(e);
				}
			},

			addItemByForm: function()
			{
				var title = this.control('form-title').value;
				if(!title.length)
				{
					return false;
				}

				if(!this.opts.enableSync)
				{
                    title = BX.util.htmlspecialchars(title);
				}

				this.addItemRemote(this.makeItemData(title));
				return true;
			},

			addItemRemote: function(data)
			{
				// lock input
				this.lockInput();

				this.getSyncer().perform([['add', {data:{
					_OWNER_ENTITY_ID_: this.option('entityId'),
					TITLE: data.TITLE
				}}, {
					code: 'item-add'
				}]]).then(function(result){

					// set new id from the result
					if(result)
					{
						var res = result.getData()['item-add'].RESULT;
						var id = 0;
						if('DATA' in res)
						{
							id = res.DATA.ID;
						}
						else
						{
							id = res.ID;
						}

						data.VALUE = parseInt(id);
						data.DISPLAY = res.DATA.DISPLAY;
						data.TITLE = res.DATA.TITLE;
					}

					this.addItem(data);
					this.clearForm();
					this.unLockInput();
					this.focusForm();

				}.bind(this), function(){
					this.unLockInput();
				}.bind(this));
			},

			clearForm: function()
			{
				this.control('form-title').value = '';
			},

			toggleForm: function(way)
			{
				var form = this.control('add-item-form');

				if(way && !BX.hasClass(form, 'invisible'))
				{
					var p = new BX.Promise();
					p.resolve();
					return p;
				}

				return BX.Tasks.Util.fadeSlideToggleByClass(this.control('add-item-form'));
			},

			focusForm: function()
			{
				this.control('form-title').focus();
			},

			lockInput: function()
			{
				BX.Tasks.Util.disable(this.control('form-title'));
			},

			unLockInput: function()
			{
				BX.Tasks.Util.enable(this.control('form-title'));
			},

			toggleItemMode: function(apply, item)
			{
				if(item.isEditMode())
				{
					if(apply)
					{
						var title = item.control('new-title').value;
						if(!title.length) // zero-length title is not allowed
						{
							return;
						}

						item.lockInput();
						this.getSyncer().perform([['update', {id: item.value(), data:{TITLE: title}}, {
                            code: 'item-update'
                        }]]).then(function(result){

                        	if(result) {
                                var res = result.getData()['item-update'].RESULT;

                                item.title(res.DATA.TITLE, res.DATA.DISPLAY);
                            }
                            else
							{
								item.title(title, BX.util.htmlspecialchars(title));
							}

							item.toggleMode();
							item.unLockInput();

						}, function(){
							item.unLockInput();
						});
					}
					else
					{
						item.toggleMode();
					}
				}
				else
				{
					item.control('new-title').value = BX.util.htmlspecialcharsback(item.data().TITLE); // rollback
					item.toggleMode();
				}
			},

			makeItemData: function(text)
			{
				var indexes = this.getGreatestSort();
				return {
					CHECKED: 0,
					SORT: indexes["sort"] + 1,
					SORT_INDEX: indexes["sort"] + 1,
					NUMBER: indexes["number"] + 1,
					TITLE: text,
					ACTION_UPDATE: true,
					ACTION_DELETE: true,
					ACTION_TOGGLE: true
				};
			},

			getGreatestSort: function()
			{
				var max = {
					sort: 0,
					number: 0
				};

				this.each(function(item) {
					var sort = item.data().SORT;
					var number = item.data().NUMBER;
					if (sort > max.sort)
					{
						max.sort = sort;
					}

					if (!item.isSeparator() && number > max.number)
					{
						max.number = number;
					}


				});

				return max;
			},

			getItemClass: function()
			{
				return this.constructor.Item;
			},

			getStriker: function()
			{
				return this.subInstance('striker', function(){
					return new Striker({
						duration: 500,
						postTimeout: 100
					});
				});
			},

			getSyncer: function()
			{
				return this.subInstance('sync', function(){
					return new Sync({
						enabled: this.option('enableSync'),
						routePrefix: this.option('entityRoute')
					});
				});
			},

			getItemDeleteControlId: function()
			{
				return 'i-delete';
			}
		}
	});

	BX.Tasks.Component.TasksWidgetCheckList.ItemManager.Item = BX.Tasks.Util.ItemSet.Item.extend({
		sys: {
			code: 'checklist-is-i'
		},
		options: {
			controlBind: 'class'
		},
		methods: {
			isEditMode: function()
			{
				return !!this.vars.modeEdit;
			},
			lockInput: function()
			{
				BX.Tasks.Util.disable(this.control('new-title'));
			},
			unLockInput: function()
			{
				BX.Tasks.Util.enable(this.control('new-title'));
			},
			toggleMode: function()
			{
				this.setCSSMode('mode', this.vars.modeEdit ? 'read' : 'edit');
				this.vars.modeEdit = !this.vars.modeEdit;
			},
			title: function(title, display)
			{
				if(typeof title != 'undefined')
				{
					this.data().TITLE = title;
					this.data().DISPLAY = display; // todo: need to have basic client-side BBCODE->HTML converter here

					// update dom here... where is react?
					this.control('title').innerHTML = this.data().DISPLAY;//BX.util.htmlspecialchars(this.data().DISPLAY);
					this.control('title-field').value = this.data().TITLE;

					// turn the element into a separator
					if(this.constructor.isSeparatorValue(title))
					{
						this.data().APPEARANCE = 'a-separator';
						this.setCSSMode('a', 'separator');
					}

					return;
				}

				return this.data().TITLE;
			},
			sort: function(number)
			{
				if(typeof number != 'undefined')
				{
					number = parseInt(number);

					this.control('number').innerHTML = number;
					this.control('sort-fld').value = this.data().SORT;

					return;
				}

				return this.data().SORT;
			},
			isChecked: function(flag)
			{
				if(typeof flag != 'undefined')
				{
					flag = !!flag;
					this.data().CHECKED = flag ? '1' : '0';
					this.data().IS_COMPLETE = flag ? 'Y' : 'N';
					this.control('toggle').checked = flag;

					var cValue = flag ? '1' : '0';
					if(this.parent().parent().option('compatibilityMode'))
					{
						cValue = flag ? 'Y' : 'N';
					}
					this.control('complete-fld').value = cValue;

					if(flag)
					{
						this.changeCSSFlag('stroke-out', true, this.control('title'));
					}
					else
					{
						this.parent().getStriker().unStrike(this.control('title'));
					}
				}

				return this.data().CHECKED == '1';
			},
			isLockedCheck: function(way)
			{
				if(typeof way !== 'undefined')
				{
					this.vars.checkLocked = !!way;
					return;
				}

				return this.vars.checkLocked;
			},
			isSeparator: function()
			{
				return this.constructor.isSeparatorValue(this.data().TITLE);
			}
		},
		methodsStatic: {
			extractValue: function(data)
			{
				if('VALUE' in data)
				{
					return data.VALUE;
				}
				data.VALUE = 'n'+Math.abs(BX.util.hashCode(Math.random().toString()+Math.random().toString()));

				if(!('ID' in data))
				{
					data.ID = '';
				}

				return data.VALUE;
			},
			prepareDataSt: function(data)
			{
				data.DISPLAY = data.DISPLAY || data.TITLE;
				data.APPEARANCE = this.isSeparatorValue(data.TITLE) ? 'a-separator' : 'a-generic';

				var checked = data.CHECKED == '1';

				data.READONLY = data.ACTION_UPDATE ? '' : 'noedit';
				data.CHECKED_ATTRIBUTE = checked ? 'checked' : '';
				data.DISABLED_ATTRIBUTE = '';
				data.IS_COMPLETE = checked ? 'Y' : 'N';
				data.STROKE_CSS = checked ? 'stroke-out' : '';

				if (this.isSeparatorValue(data.TITLE))
				{
					data.NUMBER = 0;
				}

				data.SORT_INDEX = data.SORT;
				data.ITEM_SET_INVISIBLE = 'invisible';

				return data;
			},
			isSeparatorValue: function(value) // keep in touch with \Bitrix\Tasks\UI\Task\CheckList::checkIsSeparatorValue();
			{
				value = value.toString().replace(/^\s+/, '').replace(/\s+$/, '');

				return !!value.match(/^(-|=|_|\*|\+){3,}$/);
			}
		}
	});

	var Striker = function(options)
	{
		this.duration = options.duration || 500;
		this.postTimeout = options.postTimeout || 1;
		this.textChunkSize = options.textChunkSize || 20;
		this.animations = {};
	};
	Striker.prototype = {

		addAnimation: function(animation)
		{
			var key = BX.util.hashCode(Math.random().toString()+Math.random().toString());
			this.animations[key] = animation;

			return key;
		},

		getAnimation: function(key)
		{
			return this.animations[key];
		},

		removeAnimation: function(key, result)
		{
			var anim = this.getAnimation(key);
			if(anim)
			{
				clearTimeout(anim.timer);

				var p = anim.pr;

				delete(this.animations[key]);

				if(result)
				{
					p.fulfill();
				}
				else
				{
					p.reject();
				}

				return p;
			}

			return null;
		},

		getAnimationKeyByBlock: function(block)
		{
			var found = null;
			BX.Tasks.each(this.animations, function(item, key){
				if(item.block === block)
				{
					found = key;
					return false;
				}
			});

			return found;
		},

		toggle: function(block, cbOn, cbOff)
		{
			var aKey = this.getAnimationKeyByBlock(block);
			if(!aKey)
			{
				// animation already completed or never done
				if(BX.hasClass(block, 'stroke-out'))
				{
					this.unStrike(block).then(cbOff);
				}
				else
				{
					this.strike(block).then(cbOn);
				}
			}
			else
			{
				this.cancelStrikeAnimation(block, aKey);//.then(BX.DoNothing, cbOff);
			}
		},

		strike: function(block)
		{
			var p = new BX.Promise();

			var partCnt = block.childNodes.length;
			if(!partCnt)
			{
				p.resolve();
				return;
			}

			var textParts = [];
			var k, m;
			for(k = 0; k < block.childNodes.length;)
			{
				var nextNode = block.childNodes[k];

				if(nextNode.nodeType == Node.TEXT_NODE)
				{
					// must split on parts...
					textParts = this.strSplit(nextNode.textContent, this.textChunkSize);
					for(m = 0; m < textParts.length; m++)
					{
						block.insertBefore((BX.create('span', {text: textParts[m]})), nextNode);
					}

					block.removeChild(nextNode);
					k += textParts.length;
				}
				else
				{
					k++;
				}
			}

			var finalLength = block.childNodes.length;
			var aKey = this.addAnimation({
				block: block,
				tmOut: Math.floor(this.duration / finalLength),
				k: 0,
				length: finalLength,
				pr: p,
				timer: null
			});
			var anim = this.getAnimation(aKey);

			var nextStep = function()
			{
				anim.timer = setTimeout(function(){

					BX.addClass(anim.block.childNodes[anim.k], 'chunk-stroke-out');
					anim.k++;

					if(anim.k < anim.length)
					{
						nextStep();
					}
					else
					{
						anim.timer = setTimeout(function(){
							this.removeAnimation(aKey, true);
							BX.addClass(anim.block, 'stroke-out');
						}.bind(this), this.postTimeout);
					}

				}.bind(this), anim.tmOut);

			}.bind(this);

			nextStep();

			return p;
		},

		unStrike: function(block)
		{
			this.clearStrike(block);
			// todo: postDelay here? some animation?

			var p = new BX.Promise();
			p.fulfill();

			return  p; // for consistency
		},

		clearStrike: function(block)
		{
			for(k = 0; k < block.childNodes.length; k++)
			{
				BX.removeClass(block.childNodes[k], 'chunk-stroke-out');
			}
			BX.removeClass(block, 'stroke-out');
		},

		cancelStrikeAnimation: function(block, aKey)
		{
			if(!aKey)
			{
				aKey = this.getAnimationKeyByBlock(block);
			}

			if(aKey)
			{
				this.clearStrike(block);
				return this.removeAnimation(aKey, false);
			}

			return null;
		},

		strSplit: function(str, len)
		{
			var parts = [];
			var sub = '';

			var i = 0;
			while(str.length)
			{
				if(i > 100)
				{
					break; // smth went wrong
				}

				sub = str.substr(0, len);
				str = str.slice(len);

				if(!str.length && sub.length < 4 && parts.length > 0) // last piece, too small chunk, include to the previous one, if any
				{
					parts[parts.length - 1] += sub;
				}
				else
				{
					parts.push(sub);
				}

				i++;
			}

			return parts;
		}
	};

	var Sync = function(options)
	{
		this.enabled = !!options.enabled;
		this.prefix = options.routePrefix || '';

		this.sLock = false;
		this.query = null;
	};
	Sync.prototype = {

		getQuery: function()
		{
			if(this.query === null)
			{
				/**
				 * Deprecated since tasks 22.500.0
				 */
				this.query = new BX.Tasks.Util.Query();
			}

			return this.query;
		},

		perform: function(todo)
		{
			var p = new BX.Promise();

			if(!this.enabled)
			{
				p.resolve();
				return p;
			}

			if(this.sLock)
			{
				p.reject();
				return p;
			}

			var q = this.getQuery();

			this.sLock = true;

			var acts = [];
			for(var k = 0; k < todo.length; k++)
			{
				acts.push({m: this.prefix+'.checklist.'+todo[k][0], args: todo[k][1], rp: todo[k][2]});
			}

			q.load(acts).execute().then(function(result){
				this.sLock = false;
				p.resolve(result);
			}.bind(this), function(result){
				this.sLock = false;
				p.reject(result);
			}.bind(this));

			return p;
		}
	};

}).call(this);