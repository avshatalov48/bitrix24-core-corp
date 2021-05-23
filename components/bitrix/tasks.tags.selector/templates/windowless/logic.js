BX.namespace('Tasks.Component');

(function(){

	// nested object
	TagPreItem = function(data, parent)
	{
		data.ID = BX.util.hashCode(data.NAME);
		if(typeof data.CHECKED == 'undefined')
		{
			data.CHECKED = true;
		}

		data.CHECKED_ATTR = data.CHECKED ? 'checked' : '';

		this.data = data;

		this.scope = parent.getNodeByTemplate('item', this.data)[0];

		this.ctrls = {
			btnDelete: parent.control('item-delete', this.scope),
			btnToggle: parent.control('item-btn-toggle', this.scope),
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
	BX.merge(TagPreItem.prototype, {
		value: function()
		{
			return this.data.VALUE;
		},
		display: function()
		{
			return this.data.DISPLAY;
		},

		checked: function(flag)
		{
			if(typeof flag == 'undefined')
			{
				return this.data.CHECKED;
			}

			this.data.CHECKED = !!flag;

			this.redraw();
		},

		destruct: function()
		{
			var value = this.value();

			BX.remove(this.scope);
			this.scope = null;
			this.ctrls = null;
			this.data = null;

			return value;
		},

		redraw: function()
		{
			this.ctrls.btnToggle.checked = this.data.CHECKED;
		},

		bindEvent: function()
		{
		}
	});

	BX.Tasks.Component.TaskTagsSelector = BX.Tasks.UI.ItemSet.extend({
		options: {
			registerDispatcher: true
		},
		sys: {
			code: 'tag-item-set-pre'
		},
		methods: {
			construct: function()
			{
				this.vars.checked = {};
				this.ctrls.staticItemsCheckBoxes = {};
			},
			bindEvents: function()
			{
				this.callMethod(BX.Tasks.UI.ItemSet, 'bindEvents');

				this.bindDelegateControl('click', 'item-btn-toggle', this.passCtx(this.setItemToggle));
				this.bindDelegateControl('click', 'item-add', this.passCtx(this.addNewItem));
				this.bindDelegateControl('keydown', 'form-item-name', BX.delegate(this.itemNameKeydown, this));
			},

			createItem: function(data)
			{
				return new TagPreItem(data, this);
			},

			focusAndClear: function()
			{
				var nameInput = this.control('form-item-name');

				if(BX.type.isElementNode(nameInput))
				{
					nameInput.value = '';
					setTimeout(function(){nameInput.focus();}, 50);
				}
			},

			setItemToggle: function(btn)
			{
				var itemValue = BX.data(btn, 'item-value');
				var checked = btn.checked;

				if(BX.type.isNotEmptyString(itemValue))
				{
					if(this.hasItem(itemValue))
					{
						this.vars.items[itemValue].checked(checked);
					}
					if(typeof this.ctrls.staticItemsCheckBoxes[itemValue] != 'undefined')
					{
						this.ctrls.staticItemsCheckBoxes[itemValue].checked = checked;
					}

					this.updateCheckedIndex();
					this.fireOnChange();
				}
			},

			itemNameKeydown: function(e)
			{
				if(this.isEnter(e))
				{
					this.addNewItem();
				}
			},

			addNewItem: function()
			{
				var nameInput = this.control('form-item-name');
				if(BX.type.isElementNode(nameInput))
				{
					if(nameInput.value.toString().length > 1)
					{
						var name = nameInput.value.toString();
						this.addItem({
							NAME: name,
						});
						nameInput.value = '';

						this.redraw();
						this.updateCheckedIndex();
						this.fireOnChange();
					}
				}
			},

			redraw: function()
			{
				var left = this.control('left-list');
				var right = this.control('right-list');

				var tmpLeft = BX.create('div');
				var tmpRight = BX.create('div');

				var i = 0;
				for(var k in this.vars.order)
				{
					var itemId = this.vars.order[k];
					BX.append(this.vars.items[itemId].scope, (!(i % 2) ? tmpLeft : tmpRight));
					i++;
				}

				this.moveNodePool(tmpLeft, left);
				this.moveNodePool(tmpRight, right);
			},

			load: function(data)
			{
				this.callMethod(BX.Tasks.UI.ItemSet, 'load', [data]);

                // todo: tempral fix, untill constructor fixed
                if(typeof this.ctrls.staticItemsCheckBoxes == 'undefined')
                {
                    this.ctrls.staticItemsCheckBoxes = {};
                }

				this.readStatic();
				this.updateCheckedIndex();
			},

			updateCheckedIndex: function()
			{
				this.vars.checked = {};

				for(var k in this.vars.items)
				{
					if(this.vars.items[k].checked())
					{
						this.vars.checked[k] = true;
					}
				}

				for(var k in this.ctrls.staticItemsCheckBoxes)
				{
					if(this.ctrls.staticItemsCheckBoxes[k].checked)
					{
						this.vars.checked[k] = true;
					}
				}
			},

			fireOnChange: function()
			{
				var checked = {};
				for(var k in this.vars.checked)
				{
					checked[k] = {
						NAME: k,
						VALUE: k
					};
				}

				this.fireEvent('on-change', [checked]);
			},

			readStatic: function()
			{
				var staticList = this.control('static');

				if(BX.type.isElementNode(staticList))
				{
					var items = this.controlAll('item-btn-toggle', staticList);
					for(var k = 0; k < items.length; k++)
					{
						var itemValue = BX.data(items[k], 'item-value');
						if(BX.type.isNotEmptyString(itemValue))
						{
							this.ctrls.staticItemsCheckBoxes[itemValue] = items[k];
						}
					}
				}
			},

			setSelected: function(items)
			{
				for(var k in this.vars.items)
				{
					this.vars.items[k].checked(typeof items[k] != 'undefined');
				}

				for(var k in this.ctrls.staticItemsCheckBoxes)
				{
					this.ctrls.staticItemsCheckBoxes[k].checked = typeof items[k] != 'undefined';
				}

				this.updateCheckedIndex();
				//this.fireOnChange();
			},

			unselect: function(value)
			{
				this.vars.checked[value] = false;

				// in good way, there should be smth like this.vars.items[value].checked(false);
				// but we got no our own item class, so... meet dirty hack
				if(typeof this.vars.items[value] != 'undefined') // if it is a real item
				{
					this.vars.items[value].checked(false);
				}

				if(typeof this.ctrls.staticItemsCheckBoxes[value] != 'undefined')
				{
					this.ctrls.staticItemsCheckBoxes[value].checked = false;
				}
			},

			getDisplay: function(data)
			{
				return data.NAME;
			},
			getValue: function(data)
			{
				return data.NAME;
			}
		}
	});

})();