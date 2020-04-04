BX.namespace('Tasks.Util');

/**
 * This class is a prototype for the future-planned "view" class
 */

(function(){

	BX.Tasks.Util.ItemSet = BX.Tasks.Util.Widget.extend({
		options: {
			min: 			    false,
			max: 			    false,
            preRendered:        false,
            autoSync:           false,
            itemFx:             'none', // also 'vertical' and 'horizontal',
			itemAppearFxSpeed:      200,
			itemDisappearFxSpeed:   200,
            itemFxHoverDelete:  false,
			useDragNDrop:       false, // drag-n-drop works only for vertical lists
			useSmartCodeNaming: false
		},
		sys: {
			code: 'item-set'
		},
		methods: {
			construct: function()
			{
				this.callConstruct(BX.Tasks.Util.Widget);

				BX.mergeEx(this.vars, {
					items: {},
					order: [],
					checkRestrictions: true,
                    syncLock: true,
                    idOffset: 1,
					readOnly: !!this.option('readOnly')
				});

				this.bindEvents();
                this.load(this.option('data'), {
                    dontRender: this.option('preRendered'),
                    loadInitial: true
                });

				this.checkConstraints();
			},

			checkConstraints: function()
			{
				var min = parseInt(this.option('min'));
				var max = parseInt(this.option('max'));

				if(isNaN(min))
				{
					min = 0;
				}
				if(isNaN(max))
				{
					max = Number.POSITIVE_INFINITY;
				}

				if(min > max)
				{
					throw new TypeError('Min constraint cannot be greater than max. Check options.');
				}

				this.vars.constraint = {min: min, max: max};
			},

			bindEvents: function()
			{
                this.bindItemActions();
                this.bindFx();
				this.bindDelegateControl('open-form', 'click', this.passCtx(this.openAddForm));

				// for each new instance it should be a different function
				this.fireChangeDeferredEvent = BX.debounce(this.fireChangeDeferredEvent, 5, this);
			},

            bindItemActions: function()
            {
                // standard item actions, like delete, etc...
                this.bindDelegateControl(this.getItemDeleteControlId(), 'click', this.passCtx(this.onItemDeleteClicked));
            },

			getItemDeleteControlId: function()
			{
				return this.option('useSmartCodeNaming') ? 'i-delete' : 'item-delete';
			},

            bindFx: function()
            {
                if(this.option('itemFxHoverDelete'))
                {
                    this.bindDelegateControl('item-delete', 'mouseover', this.passCtx(this.onItemDeleteOver));
                    this.bindDelegateControl('item-delete', 'mouseout', this.passCtx(this.onItemDeleteOut));
                }
            },

            getItemClass: function()
            {
                return BX.Tasks.Util.ItemSet.Item;
            },

			getDragNDrop: function()
			{
				if(typeof BX.Tasks.Util.DragAndDrop == 'undefined')
				{
					throw new ReferenceError('Optional drag-n-drop API does not seem to be included (include \'tasks_util_draganddrop\' asset)');
				}

				return this.subInstance('dd', function(){
					var dd = new BX.Tasks.Util.DragAndDrop({
						createFlying: BX.delegate(function(node){

							var item = this.getItemByNode(node);

							return this.getNodeByTemplate('item-flying', item.data())[0];

						}, this),
						autoMarkItemAfter: true,
						autoMarkZoneTopBottom: true
					});
					this.bindDNDDropZones(dd);
					dd.bindEvent('item-relocation-before', this.onDragNDropItemRelocatedBefore, this);
					dd.bindEvent('item-relocation-after', this.onDragNDropItemRelocatedAfter, this);

					return dd;
				});
			},

			bindDNDDropZones: function(dd)
			{
				dd.bindDropZone(this.control('items'));
			},

			createItem: function(data, parameters)
			{
				var cl = this.getItemClass();
                var itemScope = false;

				parameters = parameters || {};

                if(parameters.dontRender)
                {
                    itemScope = (this.option('useSmartCodeNaming') ? 'i-' : 'item-')+data.VALUE; // it will be searched later
                }
                else
                {
                    // generate scope here...
                    itemScope = this.getNodeByTemplate('item', data)[0];
                }

				var options = {
					scope: itemScope,
					data: data,
					controlBind: this.option('controlBind'),
					parent: this
				};

				if(this.option('useSmartCodeNaming'))
				{
					options.overrideCodeWith = this.code()+'-i';
				}

				return new cl(options);
			},

			openAddForm: function()
			{
				// open some form or just add a new item
			},

			addItem: function(data, parameters)
			{
                parameters = parameters || {};
				if(typeof parameters.itemFx == 'undefined')
				{
					parameters.itemFx = true; // do animation by default
				}

				var returnInst = parameters.returnInstance;

				var cl = this.getItemClass();

				if('extractValue' in cl)
				{
					data.VALUE = cl.extractValue(data);
				}
                else if('extractItemValue' in this) // backward compatibility, prepare VALUE
                {
                    data.VALUE = this.extractItemValue(data);
                }
				// backward compatibility, prepare DISPLAY
				if('extractItemDisplay' in this)
				{
					data.DISPLAY = this.extractItemDisplay(data);
				}

                if('prepareData' in this)
                {
                    data = this.prepareData(data);
                }
                else
                {
                    if('prepareData' in cl)
                    {
                        data = cl.prepareData.apply(this, [data]);
                    }
	                else if('prepareDataSt' in cl)
                    {
	                    data = cl.prepareDataSt(data);
                    }
                }

				if(this.has(data.VALUE) || parameters.checkRestrictions !== false && !this.checkCanAddItems())
				{
					return returnInst ? null : false;
				}

				if(typeof data.VALUE == 'undefined' || typeof data.DISPLAY == 'undefined')
				{
					return returnInst ? null : false;
				}

				data.ITEM_SET_INVISIBLE = '';
                if(this.option('itemFx') != 'none' && !parameters.load)
                {
                    data.ITEM_SET_INVISIBLE = 'invisible';
                }

				var itemInst = this.createItem(data, parameters);
				if(itemInst == null)
				{
					return returnInst ? null : false;
				}

				var result = this.registerItem(itemInst, parameters);

				return returnInst ? itemInst : result;
			},

			registerItem: function(itemInst, parameters)
			{
				parameters = parameters || {};

				if(BX.type.isElementNode(itemInst.option('scope')))
				{
					// todo: when items are massively added, buffierize this and then append for all items at once
					BX.append(itemInst.scope(), this.control('items'));
				}

				// backward compatibility
				itemInst.bindEvent('delete', BX.delegate(this.deleteItem, this));

				this.vars.items[itemInst.value()] = itemInst;
				this.vars.order.push(itemInst.value());

				this.processItemAfterCreate(itemInst.value(), parameters);

				// todo: call appear() anyway, use promise
				if('appear' in itemInst) // commented out, need to have "instant" fx to set opacity to 1 instantly
				{
					itemInst.appear(parameters);
				}

				if(this.option('useDragNDrop'))
				{
					this.getDragNDrop().bindNode(itemInst.scope(), {handle: itemInst.controlAll('drag-handle')});
				}

				if(!parameters.load) // .fireChangeEvent() will be called inside .load() instead
				{
					this.fireChangeEvent(parameters);
				}

				return true;
			},

			hasItem: function(value)
			{
				return typeof this.vars.items[value] != 'undefined';
			},

			getItem: function(value)
			{
				var item = this.vars.items[value];
				if(typeof item == 'undefined')
				{
					return null;
				}

				return item;
			},

			getItemValueByNode: function(node)
			{
				if(!node)
				{
					return null;
				}

				var itemValue = BX.data(node, 'item-value');
				if(!itemValue) // try to find item scope
				{
					var scope = this.controlP(this.option('useSmartCodeNaming') ? 'i' : 'item', node, this.scope());
					if(scope)
					{
						itemValue = BX.data(scope, 'item-value');
					}
				}

				return itemValue;
			},

			getItemByNode: function(node)
			{
				var value = this.getItemValueByNode(node);
				if(value)
				{
					return this.get(value);
				}

				return null;
			},

			getItemFirst: function()
			{
				if(this.vars.order.length == 0)
				{
					return null;
				}

				var value = this.vars.order[0];
				return this.getItem(value);
			},

			getItemLast: function()
			{
				if(this.vars.order.length == 0)
				{
					return null;
				}

				var value = this.vars.order[this.vars.order.length - 1];
				return this.getItem(value);
			},

            itemCount: function()
            {
                return this.vars.order.length;
            },

            onItemDestroy: function()
            {
            },

			deleteItem: function(value, parameters)
			{
                parameters = parameters || {};
				if(typeof parameters.itemFx == 'undefined')
				{
					parameters.itemFx = true; // do animation by default
				}

				if(parameters.checkRestrictions !== false && !this.checkCanDeleteItems())
				{
					return false;
				}

                var itemInst = false;
                if(typeof value == 'object')
                {
                    itemInst = value;
                    value = itemInst.value();
                }
                else
                {
                    itemInst = this.vars.items[value];
                }

				if(typeof itemInst != 'undefined')
				{
                    // remove item from item set

					this.vars.items[value] = null;
					delete(this.vars.items[value]);

					for(var k in this.vars.order)
					{
						if(this.vars.order.hasOwnProperty(k))
						{
							if(this.vars.order[k] == value)
							{
								this.vars.order.splice(k, 1);
								break;
							}
						}
					}

					// un-register drag and drop
					if(this.option('useDragNDrop'))
					{
						this.getDragNDrop().unBindNode(itemInst.scope());
					}

                    // remove item itself

                    var ctx = this;

                    var itemDestroyer = function(){
                        var scope = this.scope();
                        var value = this.value();
                        this.destroy();
                        BX.remove(scope);

                        ctx.onItemDestroy(value);
                    };

					// todo: call disappear() anyway (even if does nothing), use promise
                    if('disappear' in itemInst && parameters.itemFx)
                    {
                        itemInst.disappear(itemDestroyer, parameters);
                    }
                    else
                    {
                        itemDestroyer.call(itemInst);
                    }

					//todo: if(parameters.unload)
					this.fireChangeEvent(parameters);
					this.fireEvent('item-delete', [value]);

					return true;
				}

				return false;
			},

			replaceItem: function(value, data, parameters)
			{
				var replaceParameters = {itemFx: false};
				BX.merge(replaceParameters, parameters || {});

				replaceParameters.checkRestrictions = false;

				if(this.deleteItem(value, replaceParameters))
				{
					this.addItem(data, replaceParameters);
				}
			},

			updateItem: function(value, data)
			{
			},

			load: function(data, parameters)
			{
				if(BX.type.isPlainObject(data) || BX.type.isArray(data) || (data instanceof BX.Tasks.Util.Collection))
				{
					if(data instanceof BX.Tasks.Util.Collection)
					{
						data = data.export(); // todo: make normal iterator over BX.Tasks.Util.Collection
					}
					data = BX.clone(data);
                    parameters = BX.clone(parameters) || {};
                    parameters.load = true;

                    this.vars.syncLock = true;

					var i = 0;
					BX.Tasks.each(data, function(item){

						if(BX.type.isPlainObject(item))
						{
							if(this.addItem(item, parameters))
							{
								i++;
							}
						}

					}.bind(this));

					if(i > 0)
					{
						parameters.doInstantUpdate = false;
						this.fireChangeEvent(parameters);
					}

                    this.vars.syncLock = false;
				}

				this.updateInstant();
			},

			unload: function(parameters)
			{
				this.vars.syncLock = true;

				parameters = BX.clone(parameters) || {};
				parameters.unload = true;

				var values = BX.clone(this.vars.order);
				if(values.length)
				{
					var i = 0;
					BX.Tasks.each(values, function(value){
						if(this.deleteItem(value, parameters))
						{
							i++;
						}
					}.bind(this));

					if(i > 0)
					{
						this.fireChangeEvent({
							doInstantUpdate: false
						});
					}

					this.vars.syncLock = false;
				}

				this.updateInstant();
			},

            processItemAfterCreate: function()
            {
            },

			// do not use this every time, it repaints the whole item set which is generally not necessary
			redraw: function()
			{
				var pool = BX.create('div');
				for(var k in this.vars.order)
				{
					var id = this.vars.order[k];

					BX.append(this.vars.items[id].scope(), pool);
				}

				this.moveNodePool(pool, this.control('items'));

				this.updateInstant();
			},

			// utils
			checkCanAddItems: function()
			{
				var max = this.option('max');

				if(max === false)
				{
					return true;
				}

				return this.vars.order.length < parseInt(max);
			},

			checkCanDeleteItems: function()
			{
				var min = this.option('min');

				return !(this.vars.order.length == 0 || (min !== false && this.vars.order.length <= parseInt(min)));
			},

			updateInstant: function()
			{
				this.setCSSFlagEmpty();
				this.setCSSFlagLimits();
			},

			setCSSFlagLimits: function()
			{
				var canAdd = this.checkCanAddItems();
				var canDelete = this.checkCanDeleteItems();

				this.changeCSSFlag('t-min', !canDelete);
				this.changeCSSFlag('t-max', !canAdd);

				// todo: left for compatibility, need to be removed
				this.changeCSSFlag(this.getFullBxId('min'), !canDelete);
				this.changeCSSFlag(this.getFullBxId('max'), !canAdd);
			},

			setCSSFlagEmpty: function()
			{
				var filled = this.vars.order.length > 0;

				this.changeCSSFlag('t-filled', filled);
				this.changeCSSFlag('t-empty', !filled);

				// todo: left for compatibility, need to be removed
				var className = this.getFullBxId('empty');
				this.dropCSSFlags(className+'-*');
				this.setCSSFlag(className+'-'+(filled ? 'false' : 'true'));
			},

			moveNodePool: function(from, to)
			{
				while(from.childNodes.length > 0)
				{
					BX.append(from.childNodes[0], to);
				}
			},

			fireChangeEvent: function(parameters)
			{
				parameters = parameters || {};

				this.fireEvent('change', [this.vars.order, parameters]);
				this.fireChangeDeferredEvent(parameters);

				// this.updateInstant() should not be here, but for compatibility...
				if(parameters.doInstantUpdate !== false)
				{
					this.updateInstant();
				}
			},

			fireChangeDeferredEvent: function(parameters)
			{
				this.fireEvent('change-deferred', [this.vars.order, parameters]);
			},

			isEnter: function(e)
			{
				e = e || window.event;

				return e.keyCode == 13;
			},

			doOnItem: function(node, callback, callbackNew)
			{
				if(this.vars.readOnly)
				{
					return false;
				}

				var itemValue = this.getItemValueByNode(node);

				var args = Array.prototype.slice.call(arguments);
				args.shift();
				args.shift();
				args.shift();

				if(typeof itemValue != 'undefined' && itemValue !== null)
				{
					if(itemValue == 'new')
					{
						if(BX.type.isFunction(callbackNew))
						{
							args.unshift(node);
							args.unshift(null);

							callbackNew.apply(this, args);
						}
					}
					else
					{
						BX.data(node, 'item-value', itemValue);

						var item = this.vars.items[itemValue];
						if(typeof item != 'undefined') // item may be in process of destruction
						{
							args.unshift(node);
							args.unshift(this.vars.items[itemValue]);

							callback.apply(this, args);
						}
					}

					return itemValue;
				}

                return false;
			},

			bindOnItemEx: function(id, event, cb)
			{
				this.bindDelegateControl(id, event, this.bindOnItem(cb, cb));
			},

			bindOnItem: function(cb, cbNew)
			{
				var this_ = this;
				return function()
				{
					var args = Array.prototype.slice.call(arguments);
					args.unshift(cbNew);
					args.unshift(cb);
					args.unshift(this); // this is a ctx of the node event happened on
					return this_.doOnItem.apply(this_, args);
				}
			},

            onItemDeleteClicked: function(node)
            {
                this.doOnItem(node, this.onItemDeleteByCross);
            },

            onItemDeleteOver: function(node)
            {
                this.doOnItem(node, function(item){
                    BX.addClass(item.scope(), 'hover-delete');
                });
            },

            onItemDeleteOut: function(node)
            {
                this.doOnItem(node, function(item){
                    BX.removeClass(item.scope(), 'hover-delete');
                });
            },

			onItemDeleteByCross: function(value)
			{
				return this.deleteItem(value);
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

					this.insertItemBeforeOrder(item, this.getItemByNode(neigbours.before));
					this.fireChangeEvent();
				}
			},

            getQuery: function()
            {
                if(!this.instances.query)
                {
                    this.instances.query = new BX.Tasks.Util.Query({
                        autoExec: true
                    });
                }

                return this.instances.query;
            },

            checkCanSync: function()
            {
                return this.option('autoSync') && !this.vars.syncLock;
            },

            syncAllIfCan: function()
            {
                if(this.checkCanSync())
                {
                    this.syncAll();
                }
            },

            syncAll: function(items)
            {
                //var q = this.getQuery();
                // do smth
            },

            extractItemValue: function(data)
            {
                return data.ID;
            },

			setField: function(name, data, value)
			{
				if(!(name in data))
				{
					data[name] = BX.type.isFunction(value) ? value.apply(this, [data, name]) : value;
				}
			},

			getRandomHash: function()
			{
				return Math.abs(BX.util.hashCode(Math.random().toString()+Math.random().toString()));
			},

			setReadOnly: function(way)
			{
				return this.readonly(way);
			},

			readonly: function(way)
			{
				if(typeof way == 'undefined')
				{
					return this.vars.readOnly;
				}
				else
				{
					way = !!way;

					this.changeCSSFlag('readonly', way);
					this.vars.readOnly = way;
				}
			},

			value: function()
			{
				return this.vars.order;
			},

			// iterator functions

			first: function()
			{
				return this.getItemFirst();
			},

			last: function()
			{
				return this.getItemLast();
			},

			nth: function(num)
			{
				return this.getItem(this.vars.order[num]);
			},

			has: function(value)
			{
				return this.hasItem(value);
			},

			/**
			 * Get a single item by its value.
			 * Note: the behaviour of this function differs from BX.Tasks.Util.Collection.get(), so avoid using it
			 *
			 * @param value
			 * @returns {*}
			 */
			get: function(value)
			{
				return this.getItem(value);
			},

			getByKey: function(value)
			{
				return this.get(value);
			},

			each: function(cb)
			{
				if(!BX.type.isFunction(cb))
				{
					return;
				}

				for(var k = 0; k < this.vars.order.length; k++)
				{
					if(!this.vars.order.hasOwnProperty(k))
					{
						continue;
					}

					if(cb.apply(this, [this.vars.items[this.vars.order[k]]]) === false)
					{
						break;
					}
				}
			},

			count: function()
			{
				return this.vars.order.length;
			},

			/**
			 * Returns a collection of item data objects
			 * @param clone
			 * @returns {BX.Tasks.Util.Collection|*}
			 */
			exportItemData: function(clone)
			{
				var data = [];

				this.each(function(item){
					var iData = item.data();
					data.push(clone ? BX.clone(iData) : iData);
				});

				var c = new BX.Tasks.Util.Collection({keyField: 'VALUE'});
				c.load(data);

				return c;
			},

			/**
			 * Returns a collection of item instances
			 */
			export: function()
			{
				// todo
			},

			/**
			 * @access private
			 * @param item
			 * @param beforeItem
			 */
			insertItemBeforeOrder: function(item, beforeItem)
			{
				var itemValue = item.value();
				//console.dir('moving '+itemValue);

				var beforeItemValue = null;
				if(beforeItem)
				{
					beforeItemValue = beforeItem.value();
					//console.dir('before '+beforeItemValue);
				}
				else
				{
					//console.dir('to the end');
				}

				var newOrder = [];
				for(var k = 0; k < this.vars.order.length; k++)
				{
					if(this.vars.order.hasOwnProperty(k))
					{
						var value = this.vars.order[k];
						if(value == itemValue)
						{
							continue;
						}
						if(value == beforeItemValue)
						{
							newOrder.push(itemValue);
						}

						newOrder.push(value);
					}
				}

				if(!beforeItemValue)
				{
					newOrder.push(itemValue);
				}
				//console.dir(newOrder);

				this.vars.order = newOrder;
			}
		}
	});

    /////////////////////
    // predefined items
    /////////////////////

    // standard item
    BX.Tasks.Util.ItemSet.Item = BX.Tasks.Util.Widget.extend({
        sys: {
            code: 'item-set-item'
        },
        methods: {
            value: function(value)
            {
	            if(typeof value != 'undefined')
	            {
		            this.option('data').VALUE = value;
		            BX.data(this.scope(), 'item-value', value);
		            this.addId(value);
	            }
	            else
	            {
		            return this.option('data').VALUE;
	            }
            },
            display: function()
            {
                return this.option('data').DISPLAY;
            },
	        id: function()
	        {
		        var data = this.option('data');
		        return data.ID || data.id;
	        },
            data: function(data)
            {
	            if(BX.type.isPlainObject(data))
	            {
		            this.vars.data = data;
	            }
	            else
	            {
		            if(typeof this.vars.data != 'undefined')
		            {
			            return this.vars.data;
		            }

		            return this.option('data');
	            }
            },

            // use this to show item gracefully
            appear: function(parameters)
            {
	            parameters = parameters || {};

	            var p = new BX.Promise();

	            if(parameters.useAppear === false)
	            {
		            p.fulfill();
		            return p;
	            }

                if(!parameters.dontRender && !parameters.load) // no fx on pre-rendered and .load()-ed blocks
                {
	                var fx = this.optionP('itemFx');

                    if(fx != 'none')
                    {
	                    var fn = 'fadeToggleByClass';
	                    if(fx == 'horizontal' || fx == 'vertical')
	                    {
							fn = fx == 'horizontal' ? 'fadeSlideHToggleByClass' : 'fadeSlideToggleByClass';
	                    }

                        BX.Tasks.Util[fn](this.scope(), this.optionP('itemAppearFxSpeed')).then(function(){
	                        p.fulfill();
                        }, function(){
	                        p.reject();
                        });
                    }
	                else
                    {
	                    p.fulfill(); // no fx, just resume
                    }
                }
	            else
                {
	                p.reject();
                }

	            return p;
            },
            // use this to hide item gracefully
            disappear: function(complete, parameters)
            {
                var fx = this.optionP('itemFx');

                if(fx != 'horizontal' && fx != 'vertical')
                {
	                if(BX.type.isFunction(complete))
	                {
		                complete.apply(this);
	                }

	                var p = new BX.Promise(); // just for consistency
	                p.fulfill(); // no fx, just resume

	                return p;
                }
                else
                {
                    return BX.Tasks.Util[fx == 'horizontal' ? 'fadeSlideHToggleByClass' : 'fadeSlideToggleByClass'](this.scope(), this.optionP('itemDisappearFxSpeed'), BX.delegate(complete, this));
                }
            },

	        isShown: function()
	        {
		        return !BX.hasClass(this.scope(), 'invisible');
	        }
        }
    });
    // the following function must define VALUE and DISPLAY inside data structure, basing on data itself
    BX.Tasks.Util.ItemSet.Item.prepareData = function(data)
    {
        return data;
    };

})();

/**
 Usage memo

 <div id="widget-scope">
     <div data-bx-id="item-set-items">
        <script type="text/html" data-bx-id="item-set-item">
            <div data-bx-id="item-set-item" data-item-value="{{VALUE}}">
                Item named "{{DISPLAY}}" with value={{VALUE}}
                <div data-bx-id="item-set-item-delete">Delete</div>
            </div>
        </script>
     </div>
     <div data-bx-id="item-set-open-form">Change or add</div>
 </div>

 If you got items pre-rendered on server side, you may add "item-set-item-{{VALUE}}" to "data-bx-id" attribute (or class), and
 then pass preRendered:true in options
 */