BX.namespace('Tasks.UI');

(function(){

	// nested object
	Item = function(data, parent)
	{
		this.data = data;

		this.scope = parent.getNodeByTemplate('item', this.data)[0];

		this.ctrls = {
			btnDelete: parent.control('item-btn-delete', this.scope)
		};

		// save item value to data-* for each significant node used
		BX.data(this.scope, 'item-value', data.VALUE);
		for(var k in this.ctrls)
		{
			if(this.ctrls[k] != null)
			{
				BX.data(this.ctrls[k], 'item-value', data.VALUE);
			}
		}
	};
	BX.merge(Item.prototype, {
		value: function()
		{
			return this.data.VALUE;
		},
		display: function()
		{
			return this.data.DISPLAY;
		},

		destruct: function()
		{
			var value = this.value();

			BX.remove(this.scope);
			this.scope = null;
			this.ctrls = null;
			this.data = null;

			return value;
		}
	});

	BX.Tasks.UI.ItemSet = BX.Tasks.UI.Widget.extend({
		options: {
			multiple: true
		},
		sys: {
			code: 'item-set'
		},
		methods: {
			construct: function()
			{
				BX.merge(this.vars, {
					items: {},
					order: [],
				});

				this.bindEvents();
			},

			getDisplay: function(data)
			{
				return data.DISPLAY;
			},
			getValue: function(data)
			{
				return data.VALUE;
			},

			bindEvents: function()
			{
				this.bindDelegateControl('click', 'item-btn-delete', this.passCtx(this.setItemDelete));
				this.bindDelegateControl('click', 'open-form', this.passCtx(this.openAddForm));
			},

			createItem: function(data)
			{
				return new Item(data, this);
			},

			setItemDelete: function(btn)
			{
				this.doOnItem(btn, function(itemInst){
					this.deleteItem(itemInst.value());
				});
			},

			deleteItem: function(value)
			{
				var itemInst = this.vars.items[value];

				if(typeof itemInst != 'undefined')
				{
					itemInst.destruct();

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

					this.setCSSFlagEmpty();

					return true;
				}

				return false;
			},

			doOnItem: function(node, callback)
			{
				var itemValue = BX.data(node, 'item-value');

				if(typeof itemValue != 'undefined' && itemValue !== null)
				{
					callback.apply(this, [this.vars.items[itemValue]]);
				}
			},

			openAddForm: function()
			{
				// open some form or just add a new item
			},

			addItem: function(data)
			{
				data.VALUE = this.getValue.apply(this, [data]);
				data.DISPLAY = this.getDisplay.apply(this, [data]);

				var itemInst = this.createItem(data);

				this.vars.items[itemInst.value()] = itemInst;
				this.vars.order.push(itemInst.value());
			},

			hasItem: function(value)
			{
				return typeof this.vars.items[value] != 'undefined';
			},

			getValue: function(data)
			{
				return data.VALUE;
			},

			getDisplay: function(data)
			{
				return data.DISPLAY;
			},

			load: function(data, can)
			{
				if(BX.type.isPlainObject(data) || BX.type.isArray(data))
				{
					data = BX.clone(data);

					if(!this.option('multiple'))
					{
						this.addItem(data);
					}
					else
					{
						for(var k in data)
						{
							this.addItem(data[k]);
						}
					}

					this.redraw();
				}
			},

			redraw: function()
			{
				var pool = BX.create('div');
				for(var k in this.vars.order)
				{
					var id = this.vars.order[k];

					BX.append(this.vars.items[id].scope, pool);
				}

				this.moveNodePool(pool, this.control('items'));
				this.setCSSFlagEmpty();
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

			isEnter: function(e)
			{
				e = e || window.event;

				return e.keyCode == 13;
			}
		}
	});

})();