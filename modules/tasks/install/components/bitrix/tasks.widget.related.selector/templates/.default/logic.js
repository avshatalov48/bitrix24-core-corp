'use strict';

BX.namespace('Tasks.Component');

(function(){

	if(typeof BX.Tasks.Component.TasksWidgetRelatedSelector != 'undefined')
	{
		return;
	}

	/**
	 * Main js controller for this template
	 */
	BX.Tasks.Component.TasksWidgetRelatedSelector = BX.Tasks.Component.extend({
		sys: {
			code: 'task-sel-is'
		},
		methods: {
			construct: function()
			{
				this.callConstruct(BX.Tasks.Component);

				var manager = this.getManager();

				manager.bindEvent('change', this.onChanged.bind(this));
				manager.bindEvent('selector-discover', this.loadSelector.bind(this));

				this.setTypes(this.option('types'), true);
			},

			onChanged: function(items)
			{
				if(this.option('inputSpecial'))
				{
					var value = '';
					if(items[0])
					{
						value = this.subInstance('items').get(items[0]).data().ID;
					}

					this.control('sole-input').value = value;
				}

				// forward 'change' event
				this.fireEvent('change', arguments);
			},

			getIdByValue: function(value)
			{
				var item = this.getManager().get(value);
				if(item)
				{
					return item.id();
				}

				return null;
			},

			deselect: function()
			{
				this.getManager().unload();
			},

			setTypes: function(types, initial)
			{
				this.vars.types = this.vars.types || {};
				if(!initial && this.vars.types.TASK == types.TASK) // already chosen
				{
					return;
				}

				this.vars.types = {};
				BX.Tasks.each(types, function(item, k){

					if(k == 'TASK' || k == 'TASK_TEMPLATE')
					{
						this.vars.types[k] = true;
					}

				}.bind(this));

				this.changeTypes(types, initial);
			},

			changeTypes: function(types, initial)
			{
				var ctrl = this.subInstance('items');

				if(!initial)
				{
					// todo: remove all items that does not match given new types
					// dropping all for now
					ctrl.unload();

					if(this.option('inputSpecial'))
					{
						var newName = BX.util.htmlspecialchars(
							this.option('inputPrefix') + (types.TASK ? this.option('inputPostfixTask') : this.option('inputPostfixTaskTemplate'))
						);

						this.control('sole-input').setAttribute('name', newName);
					}
				}

				ctrl.option('selectorCode', this.option(types.TASK ? 'selectorCodeTask' : 'selectorCodeTaskTemplate'));
				ctrl.option('types', types);
				ctrl.option('path', this.option(types.TASK ? 'pathTask' : 'pathTaskTemplate'));
			},

			loadSelector: function(type, p)
			{
				p.cancelAutoResolve();

				var ctrl = this.subInstance('items');

				var ids = [];
				ctrl.each(function(item){
					ids.push(item.data().ID);
				});

				this.callRemoteTemplate('getSelector', {type: type, parameters: {
					COMPONENT_PARAMETERS: {
						MULTIPLE: this.option('max') > 1 ? 'Y' : 'N',
						NAME: ctrl.option('selectorCode'),
						VALUE: ids // just initial value
					}
				}}).then(function(result){
					if(result.isSuccess())
					{
						var scope = this.control('picker-content-'+(type.toLowerCase()));
						BX.html(scope, result.getData()).then(function(){
							p.resolve();
						});
					}
					else
					{
						p.reject();
					}
				}.bind(this));
			},

			getManager: function()
			{
				return this.subInstance('items', function(){
					return new this.constructor.ItemManager({
						scope: this.scope(),
						data: this.option('data'),
						preRendered: true
					});
				});
			}
		}
	});

	// legacy popup - task selector
	BX.Tasks.Component.TasksWidgetRelatedSelector.ItemManager = BX.Tasks.PopupItemSet.extend({
		sys: {
			code: 'task-sel'
		},
		options: {
			controlBind: 'class',
			itemFx: 'horizontal',
			selectorCode: '',
			itemFxHoverDelete: true,
			types: {}
		},
		methods: {

			checkSelectorLoaded: function()
			{
				return this.instances.selector && this.instances.selector[this.getType()];
			},

			getSelector: function()
			{
				var p = new BX.Promise();

				var type = this.getType();
				this.instances.selector = this.instances.selector || {};

				var code = this.option('selectorCode');

				if(this.checkSelectorLoaded()) // already loaded
				{
					p.resolve(this.instances.selector[type]);
				}
				else
				{
					var selector = window['O_'+code];

					var processSelector = function(type, selector)
					{
						this.bindSelectorEvents(selector);
						p.resolve(selector);

						this.instances.selector[type] = selector;
					};

					if(selector)
					{
						processSelector.apply(this, [type, selector]);
					}
					else
					{
						// async load here
						var dp = new BX.Promise();
						dp.setAutoResolve(false); // in case of no event catch
						this.fireEvent('selector-discover', [type, dp, this]);

						dp.then(function(){

							var selector = window['O_'+code];
							if(selector)
							{
								processSelector.apply(this, [type, selector]);
							}
							else
							{
								p.reject(); // successful load, but unsuccessful placing
							}

						}.bind(this), function(){
							p.reject();
						});
					}
				}

				return p;
			},

			getPickerContainer: function()
			{
				return this.control('picker-content-'+(this.getType().toLowerCase()));
			},

			getType: function()
			{
				return this.option('types').TASK ? 'T' : 'TT';
			},

			extractItemDisplay: function(data)
			{
				if(typeof data.DISPLAY != 'undefined')
				{
					return data.DISPLAY;
				}

				if(typeof data.name != 'undefined')
				{
					return data.name;
				}

				return data.TITLE;
			},
			extractItemValue: function(data)
			{
				data.ID = data.ID || data.id;

				if('VALUE' in data)
				{
					return data.VALUE;
				}

				return this.getType()+data.ID;
			},

			prepareData: function(data)
			{
				if(!('URL' in data))
				{
					data.URL = this.option('path').toString().replace('#id#', data.ID);
				}

				return data;
			},

			bindSelectorEvents: function(selector)
			{
				BX.addCustomEvent(selector, 'on-change', BX.delegate(this.itemsChanged, this));

				if(typeof this.instances.window != 'undefined')
				{
					BX.addCustomEvent(this.instances.window, "onAfterPopupShow", function(){
						setTimeout(function(){
							selector.searchInput.focus();
						}, 100);
					});
				}
			},

			deleteItem: function(value, parameters)
			{
				if(BX.type.isString(value))
				{
					value = this.get(value);
				}

				var taskId = value.data().ID;

				if(this.callMethod(BX.Tasks.PopupItemSet, 'deleteItem', arguments))
				{
					if(this.checkSelectorLoaded())
					{
						// un-select this item in the popup
						this.getSelector().then(function(selector){
							selector.unselect(taskId);
						}.bind(this), function(){
							BX.debug('unable to get selector');
						});
					}
				}
			},

			getSelectionDelta: function()
			{
				var added = [];
				var deleted = [];

				var temporal = BX.clone(this.vars.temporalItems);

				this.each(function(item){

					var id = item.data().ID;

					if(!this.vars.temporalItems[id]) // that existing ID is not in selected list, remove it!
					{
						deleted.push(item.value());
					}
					else
					{
						delete(temporal[id]);
					}

				}.bind(this));

				for(var k in temporal)
				{
					if(temporal[k])
					{
						// this will be added
						added.push(k);
					}
				}

				return {added: added, deleted: deleted};
			}
		}
	});

}).call(this);