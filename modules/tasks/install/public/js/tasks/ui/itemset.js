BX.namespace('Tasks.UI');

/**
 * This class is a prototype for the future-planned "view" class
 */

(function(){

	BX.Tasks.UI.ItemSet = BX.Tasks.UI.Widget.extend({
		options: {
			min: 			    false,
			max: 			    false,
            preRendered:        false,
            autoSync:           false,
            itemFx:             'none', // also 'vertical' and 'horizontal',
            itemFxHoverDelete:  false
		},
		sys: {
			code: 'item-set'
		},
		methods: {
			construct: function()
			{
				BX.mergeEx(this.vars, {
					items: {},
					order: [],
					checkRestrictions: true,
                    syncLock: true,
                    idOffset: 1,
					readonly: false
				});

				this.bindEvents();
                this.load(this.option('data'), {
                    dontRender: this.option('preRendered'),
                    loadInitial: true
                });
			},

			bindEvents: function()
			{
                this.bindItemActions();
                this.bindFx();
				this.bindDelegateControl('click', 'open-form', this.passCtx(this.openAddForm));

				// for each new instance it should be a different function
				this.fireChangeDeferredEvent = BX.debounce(this.fireChangeDeferredEvent, 5, this);
			},

            bindItemActions: function()
            {
                // standard item actions, like delete, etc...
                this.bindDelegateControl('click', 'item-delete', this.passCtx(this.onItemDeleteClicked));
            },

            bindFx: function()
            {
                if(this.option('itemFxHoverDelete'))
                {
                    this.bindDelegateControl('mouseover', 'item-delete', this.passCtx(this.onItemDeleteOver));
                    this.bindDelegateControl('mouseout', 'item-delete', this.passCtx(this.onItemDeleteOut));
                }
            },

            getItemClass: function()
            {
                return BX.Tasks.UI.ItemSet.Item;
            },

			createItem: function(data, parameters)
			{
                var cl = this.getItemClass();
                var itemScope = false;

                if(parameters.dontRender)
                {
                    itemScope = 'item-'+data.VALUE;
                }
                else
                {
                    // generate scope here...
                    itemScope = this.getNodeByTemplate('item', data)[0];
                    // todo: when items are massively added, buffierize this and then append for all items at once
                    BX.append(itemScope, this.control('items'));
                }

				return new cl({
                    scope: itemScope,
                    data: data,
                    parent: this
                });
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

				if(parameters.checkRestrictions !== false && !this.checkCanAddItems())
				{
					return false;
				}

				// backward compatibility, prepare VALUE
                if('extractItemValue' in this)
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
                    var cl = this.getItemClass();
                    if('prepareData' in cl)
                    {
                        data = cl.prepareData.apply(this, [data]);
                    }
                }

				if(typeof data.VALUE == 'undefined' || typeof data.DISPLAY == 'undefined')
				{
					return false;
				}

                if(this.option('itemFx') != 'none')
                {
                    data.ITEM_SET_INVISIBLE = parameters.load ? '' : 'invisible';
                }

				var itemInst = this.createItem(data, parameters);
				if(itemInst == null)
				{
					return false;
				}

                // backward compatibility
				itemInst.bindEvent('delete', BX.delegate(this.deleteItem, this));

				this.vars.items[itemInst.value()] = itemInst;
				this.vars.order.push(itemInst.value());

                this.processItemAfterCreate(itemInst.value(), parameters);

                if('appear' in itemInst/* && parameters.itemFx*/) // commented out, need to have "instant" fx to set opacity to 1 instantly
                {
                    itemInst.appear(parameters);
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
						if(this.vars.order[k] == value)
						{
							this.vars.order.splice(k, 1);
							break;
						}
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
				if(BX.type.isPlainObject(data) || BX.type.isArray(data))
				{
					data = BX.clone(data);
                    parameters = BX.clone(parameters) || {};
                    parameters.load = true;

                    this.vars.syncLock = true;

					var i = 0;
					for(var k in data)
					{
						if(BX.type.isPlainObject(data[k]))
						{
							if(this.addItem(data[k], parameters))
							{
								i++;
							}
						}
					}

					if(i > 0)
					{
						this.fireChangeEvent(parameters);
					}

                    this.vars.syncLock = false;
				}
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

				if(this.vars.order.length == 0 || (min !== false && this.vars.order.length <= parseInt(min)))
				{
					return false;
				}

				return true;
			},

			updateDelayed: function()
			{
			},

			updateInstant: function(items)
			{
				this.setCSSFlagEmpty();
				this.setCSSFlagLimits();
			},

			setCSSFlagLimits: function()
			{
				this.changeCSSFlag(this.getFullBxId('min'), !this.checkCanDeleteItems());
				this.changeCSSFlag(this.getFullBxId('max'), !this.checkCanAddItems());
			},

			setCSSFlagEmpty: function()
			{
				var className = this.getFullBxId('empty');

				this.dropCSSFlags(className+'-*');
				this.setCSSFlag(className+'-'+(this.vars.order.length > 0 ? 'false' : 'true'));
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
				this.fireEvent('change', [this.vars.order, parameters]);
				this.fireChangeDeferredEvent(parameters);

				this.updateInstant();
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

			doOnItem: function(node, callback)
			{
				if(this.vars.readonly)
				{
					return false;
				}

                // find item value
				var itemValue = BX.data(node, 'item-value');
                if(!itemValue) // try to find item scope
                {
                    var scope = this.controlP('item', node, this.scope());
                    if(scope)
                    {
                        itemValue = BX.data(scope, 'item-value');
                    }
                }

				if(typeof itemValue != 'undefined' && itemValue !== null)
				{
                    BX.data(node, 'item-value', itemValue);

                    var item = this.vars.items[itemValue];
                    if(typeof item != 'undefined') // item may be in process of destruction
                    {
                        callback.apply(this, [this.vars.items[itemValue]]);
                    }

                    return itemValue;
				}

                return false;
			},

            onItemDeleteClicked: function(node)
            {
                this.doOnItem(node, this.deleteItem);
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

			readonly: function(way)
			{
				if(typeof way == 'undefined')
				{
					return this.vars.readonly;
				}
				else
				{
					way = !!way;

					this.changeCSSFlag('readonly', way);
					this.vars.readonly = way;
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

			has: function(value)
			{
				return this.hasItem(value);
			},

			get: function(value)
			{
				return this.getItem(value);
			},

			each: function(cb)
			{
				if(!BX.type.isFunction(cb))
				{
					return;
				}

				for(var k = 0; k < this.vars.order.length; k++)
				{
					cb.apply(this, [this.vars.items[this.vars.order[k]]]);
				}
			}
		}
	});

    /////////////////////
    // predefined items
    /////////////////////

    // standard item
    BX.Tasks.UI.ItemSet.Item = BX.Tasks.UI.Widget.extend({
        sys: {
            code: 'item-set-item'
        },
        methods: {
            // for backward compatibility
            value: function()
            {
                return this.option('data').VALUE;
            },
            // for backward compatibility
            display: function()
            {
                return this.option('data').DISPLAY;
            },
            data: function()
            {
                return this.option('data');
            },

            // use this to show item gracefully
            appear: function(parameters)
            {
                if(!parameters.dontRender && !parameters.load) // no fx on pre-rendered and .load()-ed blocks
                {
                    if(this.optionP('itemFx') != 'none')
                    {
                        BX.Tasks.UI.Util.fadeToggle(this.scope(), null, 200);
                    }
                }
            },
            // use this to hide item gracefully
            disappear: function(complete, parameters)
            {
                var fx = this.optionP('itemFx');

                if(fx != 'horizontal' && fx != 'vertical')
                {
                    complete.apply(this);
                }
                else
                {
                    BX.Tasks.UI.Util[fx == 'horizontal' ? 'fadeSlideHToggle' : 'fadeSlideToggle'](this.scope(), false, 200, BX.delegate(complete, this));
                }
            }
        }
    });
    // the following function must define VALUE and DISPLAY inside data structure, basing on data itself
    BX.Tasks.UI.ItemSet.Item.prepareData = function(data)
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

 If you got items pre-rendered on server side, you may add "item-set-item-{{VALUE}}" to "data-bx-id" attribute, and
 then do this.load(this.option('data'), {dontRender: true})
 */