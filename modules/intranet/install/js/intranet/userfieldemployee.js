;(function()
{
	'use strict';

	BX.namespace('BX.Intranet');

	if(!!BX.Intranet.UserFieldEmployee)
	{
		return;
	}

	var instanceStack = {};
	var userStack = {};

	BX.Intranet.UserFieldEmployee = function(id, param)
	{
		this.id = id;
		this.param = param;
		this.inited = false;

		this.callback = {
			select: BX.delegate(this.callbackSelect, this),
			unSelect: BX.delegate(this.callbackUnSelect, this)
		};

		this.value = this.param.multiple ? [] : null;

		instanceStack[id] = this;

		BX.ready(BX.defer(this.init, this))
	};

	BX.Intranet.UserFieldEmployee.instance = function(id)
	{
		return instanceStack[id];
	};

	BX.Intranet.UserFieldEmployee.getInitEvent = function(id)
	{
		return 'BX.UFEMP:' + id + ':init';
	};

	BX.Intranet.UserFieldEmployee.getOpenEvent = function(id)
	{
		return 'BX.UFEMP:' + id + ':open';
	};

	BX.Intranet.UserFieldEmployee.prototype.init = function()
	{
		if(!!BX.Main.selectorManagerV2.controls[this.id])
		{
			var selectorInstance = BX.UI.SelectorManager.instances[this.id];
			if (selectorInstance)
			{
				selectorInstance.reinit();
			}
		}

		BX.onCustomEvent(window, BX.Intranet.UserFieldEmployee.getInitEvent(this.id), [{
			id: this.id,
			openDialogWhenInit: false
		}]);

		this.inited = true;
	};

	BX.Intranet.UserFieldEmployee.prototype.open = function(bindNode)
	{
		BX.onCustomEvent(window, BX.Intranet.UserFieldEmployee.getOpenEvent(this.id), [{
			id: this.id
		}]);

		return false;
	};

	BX.Intranet.UserFieldEmployee.prototype.close = function()
	{
		var selectorInstance = BX.UI.SelectorManager.instances[this.id];
		if (!selectorInstance)
		{
			return;
		}

		if (selectorInstance.popups.main)
		{
			selectorInstance.popups.main.close();
		}
		if (selectorInstance.popups.search)
		{
			selectorInstance.popups.main.search.close();
		}
		if (selectorInstance.popups.container)
		{
			selectorInstance.popups.container.close();
		}
	};

	BX.Intranet.UserFieldEmployee.prototype.storeUser = function(user)
	{
		userStack[user.entityId] = user;
	};

	BX.Intranet.UserFieldEmployee.prototype.setValue = function(value)
	{
		this.value = value;

		var selectorInstance = BX.UI.SelectorManager.instances[this.id];
		if (selectorInstance)
		{
			selectorInstance.itemsSelected = {};
		}

		this.updateValue();
	};

	BX.Intranet.UserFieldEmployee.prototype.callbackSelect = function(params)
	{
		var
			selectorId = params.selectorId,
			user = params.item;

		if (
			!BX.type.isNotEmptyString(selectorId)
			|| selectorId != this.id
			|| !BX.type.isNotEmptyObject(user)
		)
		{
			return;
		}

		var
			userId = user.entityId;

		this.storeUser(user);

		if(this.param.multiple)
		{
			this.value.push(userId);
		}
		else
		{
			this.value = userId;
			this.close();
		}

		this.updateValue();
	};

	BX.Intranet.UserFieldEmployee.prototype.callbackUnSelect = function(params)
	{
		var
			selectorId = params.selectorId,
			user = params.item;

		if (
			!BX.type.isNotEmptyString(selectorId)
			|| selectorId != this.id
			|| !BX.type.isNotEmptyObject(user)
		)
		{
			return;
		}

		var
			userId = user.entityId;

		this.storeUser(user);

		if(this.param.multiple)
		{
			var pos = BX.util.array_search(userId, this.value);
			if(pos >= 0)
			{
				this.value.splice(pos, 1);
			}
		}
		else if(this.value === userId)
		{
			this.value = null;
			this.close();
		}

		this.updateValue();
	};

	BX.Intranet.UserFieldEmployee.prototype.updateValue = function()
	{
		if(this.inited)
		{
			var selectorInstance = BX.UI.SelectorManager.instances[this.id];
			if (selectorInstance)
			{
				if(this.param.multiple)
				{
					this.value = BX.util.array_unique(this.value);
					for(var i = 0; i < this.value.length; i++)
					{
						selectorInstance.itemsSelected['U' + this.value[i]] = 'users';
					}
				}
				else
				{
					if(this.value !== null)
					{
						selectorInstance.itemsSelected['U' + this.value] = 'users';
					}
				}
			}
		}

		BX.onCustomEvent(this, 'onUpdateValue', [this.value, userStack]);
	};


	BX.Intranet.UserFieldEmployeeEntity = function(param)
	{
		this.field = null;
		this.squareClass = 'main-ui-square';
		this.multiple = null;

		if(!!param)
		{
			if(param.field)
			{
				this.setField(param.field);
			}

			if(typeof param.multiple !== 'undefined')
			{
				this.multiple = param.multiple;
			}
		}
	};


	BX.Intranet.UserFieldEmployeeEntity.prototype = {
		setField: function(field)
		{
			if(this.field !== BX(field))
			{
				this.field = BX(field);
				this.reset();

				BX.bind(this.field, 'click', BX.proxy(this._fieldClick, this));
			}
		},

		_fieldClick: function(e)
		{
			e = e || window.event;
			if(BX.hasClass(e.target, 'main-ui-square-delete'))
			{
				this.remove(e.target.parentNode);
			}
		},

		remove: function(square)
		{
			BX.remove(square);
			BX.onCustomEvent(this, 'BX.Intranet.UserFieldEmployeeEntity:remove', [this.getCurrentValues()]);
		},

		isMultiple: function()
		{
			return this.multiple;
		},

		reset: function()
		{
		},

		getField: function()
		{
			return this.field;
		},

		getSquares: function()
		{
			return _getByClass(this.getField(), this.squareClass, true);
		},

		removeSquares: function()
		{
			this.getSquares().forEach(BX.remove);
		},

		setSquare: function(label, value)
		{
			var field = this.getField();
			var squareData = {
				block: 'main-ui-square',
				name: BX.util.htmlspecialcharsback(label),
				item: {
					'_label': BX.util.htmlspecialcharsback(label),
					'_value': value
				}
			};
			var square = BX.decl(squareData);
			var squares = this.getSquares();

			if(!squares.length)
			{
				BX.prepend(square, field);
			}
			else
			{
				BX.insertAfter(square, squares[squares.length - 1]);
			}
		},

		getCurrentValues: function()
		{
			var squareList = this.getSquares();
			var data, result = [];

			try
			{
				for(var i = 0; i < squareList.length; i++)
				{
					data = JSON.parse(BX.data(squareList[i], 'item'));
					result.push({
						label: data._label,
						value: data._value
					});
				}
			}
			catch(err)
			{
				result = [{label: '', value: ''}];
			}

			return result;
		},

		setData: function(label, value)
		{
			return this.isMultiple() ? this.setMultipleData(label) : this.setSingleData(label, value);
		},

		setSingleData: function(label, value)
		{
			this.removeSquares();
			this.setSquare(label, value);
		},

		setMultipleData: function(items)
		{
			var values = [];

			if(BX.type.isArray(items))
			{
				this.removeSquares();

				if(BX.type.isArray(items))
				{
					items.forEach(function(item)
					{
						values.push(item.value);
						this.setSquare(item.label, item.value);
					}, this);
				}
			}
		}
	};


	/**
	 * Gets elements by class name
	 * @param {HTMLElement|HTMLDocument} rootElement
	 * @param {string} className
	 * @param {boolean} [all = false]
	 * @returns {?HTMLElement|?HTMLElement[]}
	 */
	function _getByClass(rootElement, className, all)
	{
		var result = [];

		if(className)
		{
			result = (rootElement || document.body).getElementsByClassName(className);

			if(!all)
			{
				result = result.length ? result[0] : null;
			}
			else
			{
				result = [].slice.call(result);
			}
		}

		return result;
	}
})();
