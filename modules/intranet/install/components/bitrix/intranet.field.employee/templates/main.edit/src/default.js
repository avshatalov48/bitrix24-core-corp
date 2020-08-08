BX.Default.Field.Employee = function (params)
{
	this.init(params);
};

BX.Default.Field.Employee.prototype = {
	init: function (params)
	{
		this.selectorName = (params['selectorName'] || '');
		this.isMultiple = (params['isMultiple'] || '');
		this.fieldNameJs = (params['fieldNameJs'] || '');
		this.selectControl = this.createSelectControl();
		this.entity = this.createEntity();

		BX.addCustomEvent(
			this.selectControl,
			'onUpdateValue',
			BX.delegate(this.updateHandler, this)
		);

		BX.addCustomEvent(
			this.entity,
			'BX.Intranet.UserFieldEmployeeEntity:remove',
			BX.delegate(this.removeHandler, this)
		);
	},

	createSelectControl: function ()
	{
		return new BX.Intranet.UserFieldEmployee(this.selectorName, {
			multiple: this.isMultiple
		});
	},

	createEntity: function ()
	{
		return new BX.Intranet.UserFieldEmployeeEntity({
			field: `field_${this.selectorName}`,
			multiple: this.isMultiple
		});
	},

	updateHandler: function (value, userStack)
	{
		if (this.isMultiple)
		{
			let result = [];
			for (let i = 0; i < value.length; i++)
			{
				result.push({
					name: userStack[value[i]].name,
					value: value[i]
				});
			}
			this.setData(result);
		}
		else
		{
			if (value === null)
			{
				this.setData(null);
			}
			else
			{
				this.setData({
					name: userStack[value].name,
					value: value
				});
			}
		}
	},

	removeHandler: function (value)
	{
		let result = (this.isMultiple ? [] : null);
		let	selectControlValue = (this.isMultiple ? [] : null);

		for (let i = 0; i < value.length; i++)
		{
			let item = {
				name: value[i].label,
				value: value[i].value
			};

			if (!this.isMultiple)
			{
				selectControlValue = item.value;
				result = item;
				break;
			}
			else
			{
				selectControlValue.push(item.value);
				result.push(item);
			}
		}
		this.selectControl.setValue(selectControlValue);
		this.setData(result);
	},

	setData: function (value)
	{
		let valueContainer = BX(`value_${this.selectorName}`);
		let html = '';

		if (this.isMultiple)
		{
			if (value.length > 0)
			{
				let entityValue = [];
				for (let i = 0; i < value.length; i++)
				{
					entityValue.push({
						value: value[i].value,
						label: value[i].name
					});

					html += `<input type="hidden" name="${this.fieldNameJs}" value="${BX.util.htmlspecialchars(value[i].value)}">`;
				}

				this.entity.setData(entityValue);
			}
			else
			{
				this.entity.removeSquares();
			}
		}
		else
		{
			if (value !== null)
			{
				this.entity.setData(value.name, value.value);
				html += `<input type="hidden" name="${this.fieldNameJs}" value="${BX.util.htmlspecialchars(value.value)}">`;
			}
			else
			{
				this.entity.removeSquares();
			}
		}

		if (html.length <= 0)
		{
			html = `<input type="hidden" name="${this.fieldNameJs}" value="">`
		}

		valueContainer.innerHTML = html;

		BX.defer(function ()
		{
			BX.fireEvent(valueContainer.firstChild, 'change');
		})();
	}
};