function FormField(params)
{
	this.params = params;
	this.element = null;
	this.caller = params.caller;
	this.caption = params.caption;
	this.name = params.name;
	this.type = params.type;
	this.section = params.section;
	this.formatter = params.formatter || null;
	this.validator = params.validator || null;
	this.checker = params.checker || null;
	this.callbackGetValues = params.callbackGetValues || null;
	this.multiple = params.multiple || false;
	this.required = params.required || false;
	this.hidden = params.hidden || false;
	this.dependences = params.dependences || [];

	this.callbackSetValues = null;

	this.listenChanges = true;

	this.elements = this.findElements();
	if(this.elements.length > 0)
	{
		this.element = this.elements[0];
		this.elements.forEach(function(element){
			this.bindElement(element);
		}, this);
	}

	if(this.dependences.length > 0)
	{
		BX.addCustomEvent(this.caller, 'onDependenceAction', BX.delegate(this.onDependenceAction, this));
	}
}
FormField.prototype = {

	isDependenceSelfCall: false,
	countDependenceSelfCall: 0,

	findElements: function()
	{
		return BX.convert.nodeListToArray(
			document.getElementsByName(this.name + (this.multiple ? '[]' : ''))
		);
	},
	disable: function(isEnable)
	{
		isEnable = isEnable || false;
		this.findElements().forEach(function (element) {
			element.disabled = !isEnable
		});
	},
	addElement: function(element)
	{
		this.bindElement(element);
		this.elements = this.findElements();
	},
	bindElement: function(element)
	{
		BX.bind(element, 'blur', BX.delegate(this.onBlur, this));
		BX.bind(element, 'focus', BX.delegate(this.onFocus, this));
		BX.bind(element, 'bxchange', BX.delegate(this.onChange, this));
		//BX.bind(element, 'input', BX.delegate(this.onChange, this));
	},
	show: function()
	{
		this.hidden = false;

		BX.onCustomEvent(this, 'onShow', [this.name, this.elements]);
		BX.onCustomEvent(this.caller, 'onShow', [this.name, this.elements]);

		this.fireDependenceActionEvent('show');
		this.fireDependenceActionEvent('change');
	},
	hide: function()
	{
		this.hidden = true;

		BX.onCustomEvent(this, 'onHide', [this.name, this.elements]);
		BX.onCustomEvent(this.caller, 'onHide', [this.name, this.elements]);

		this.fireDependenceActionEvent('hide');
		this.fireDependenceActionEvent('change');
	},
	runDependence: function(dependence)
	{
		switch(dependence['do'].action)
		{
			case 'show':
				this.show();
				break;
			case 'hide':
				this.hide();
				break;
			case 'change':
				this.setValues(BX.type.isArray(dependence['do'].value) ? dependence['do'].value : [dependence['do'].value]);
				break;
		}
	},
	fireDependenceActionEvent: function(action)
	{
		BX.onCustomEvent(this.caller, 'onDependenceAction', [action, this]);
	},
	filterDependence: function(action, field, dependence)
	{
		if(dependence['if'].fieldname != field.name)
		{
			return false;
		}

		if(dependence['if'].action != action)
		{
			return false;
		}

		if(action == 'change'/* && dependence['if'].value*/)
		{
			var result = false;
			var d_value = dependence['if'].value;
			var d_operation = dependence['if'].operation ? dependence['if'].operation : '=';
			var values;

			if (field.isVisible())
			{
				values = field.getValues();
				if(values.length == 0)
				{
					values.push('');
				}
			}
			else
			{
				values = [''];
			}

			values.forEach(
				function(value)
				{
					switch(d_operation)
					{
						case '!=':
						case '<>':
							result = d_value != value;
							break;
						case '=':
							result = d_value == value;
							break;
						case '>':
							result = d_value > value;
							break;
						case '<':
							result = d_value < value;
							break;
						case '<=':
							result = d_value <= value;
							break;
						case '>=':
							result = d_value >= value;
							break;
					}
				},
				this
			);

			return result;
		}
		else
		{
			return true;
		}
	},
	checkSelfCall: function(field)
	{
		if (this === field)
		{
			this.isDependenceSelfCall = true;
			this.countDependenceSelfCall++;
		}
		else
		{
			this.isDependenceSelfCall = false;
			this.countDependenceSelfCall = 0;
		}

		if (this.countDependenceSelfCall < 3)
		{
			return true;
		}

		setTimeout(BX.proxy(function () {
			this.countDependenceSelfCall = 0;
		}, this), 100);

		return false;
	},
	onDependenceAction: function(action, field)
	{
		if (!this.checkSelfCall(field))
		{
			return;
		}

		//if(!this.element) return;

		var filterFunction = function(dependence){
			return this.filterDependence(action, field, dependence);
		};

		this.dependences.filter(filterFunction, this).forEach(this.runDependence,this);
	},
	onChange: function()
	{
		if(!this.element) return;
		if(!this.listenChanges) return;

		this.listenChanges = false;

		this.format();
		this.fireDependenceActionEvent('change');

		BX.onCustomEvent(this, 'onChange', [this.name, this]);
		BX.onCustomEvent(this.caller, 'onChange', [this.name, this]);

		this.listenChanges = true;
	},
	onBlur: function()
	{
		if(!this.element) return;

		BX.onCustomEvent(this, 'onBlur', [this.name, this]);
		BX.onCustomEvent(this.caller, 'onBlur', [this.name, this]);
		this.check();
	},
	onFocus: function()
	{
		if(!this.element) return;

		BX.onCustomEvent(this, 'onFocus', [this.name, this.elements]);
		BX.onCustomEvent(this.caller, 'onFocus', [this.name, this.elements]);
	},
	getValues: function()
	{
		if(this.callbackGetValues)
		{
			return this.callbackGetValues.apply(this, [this.name, this.elements]);
		}

		var values = [];
		for(var i in this.elements)
		{
			if (!this.elements.hasOwnProperty(i))
			{
				continue;
			}

			var element = this.elements[i];
			switch(element.type)
			{
				case 'radio':
				case 'checkbox':
					if(element.checked)
					{
						values.push(element.value ? element.value : 'on');
					}
					break;

				case 'select-multiple':
					for (var k=0; k<element.options.length; k++)
					{
						var option = element.options[k];
						if (option && option.selected)
						{
							values.push(option.value);
						}
					}
					break;

				case 'select-one':
				default:
					if(element.value.length !== 0 && element.value.trim() != '')
					{
						values.push(element.value);
					}
					break;
			}
		}

		return values;
	},
	setValues: function(values)
	{
		if(this.callbackSetValues)
		{
			return this.callbackGetValues.apply(this, [this.name, this.elements]);
		}

		for(var i in this.elements)
		{
			var element = this.elements[i];
			switch(element.type)
			{
				case 'radio':
				case 'checkbox':
					element.checked = BX.util.in_array(element.value, values);
					break;

				case 'select-one':
				case 'select-multiple':
					for(var i in element.options)
					{
						if(element.options[i] && element.options[i].selected)
						{
							element.options[i].selected = 0;
						}
					}

					values.forEach(function(value){
						var option = element.querySelector('option[value="'+value+'"]');
						if(option)
						{
							option.selected = 1;
						}
					}, this);
					break;

				default:
					element.value = values[0];
					break;
			}

			BX.fireEvent(element, 'change');
		}

		return values;
	},
	isEmpty: function()
	{
		var values = this.getValues();
		return values.length === 0;
	},
	format: function()
	{
		var values = this.getValues();
		if(this.formatter && values.length > 0)
		{
			var newValues = values.map(function(val){
				if(val && BX.type.isString(val))
				{
					val = this.formatter.apply(this, [val])
				}
				return val;
			}, this);

			if(JSON.stringify(newValues) != JSON.stringify(values))
			{
				this.setValues(newValues);
			}
		}
	},
	isVisible: function()
	{
		return (!this.hidden && (!this.section || !this.section.hidden));
	},
	validate: function()
	{
		var values = this.getValues();
		if(this.validator && values.length > 0 && this.isVisible())
		{
			return values.every(function(val){
				if(!val || !BX.type.isString(val))
				{
					return true;
				}
				return this.validator.apply(this, [val]);
			}, this);
		}

		return true;
	},
	check: function()
	{
		if (BX.type.isFunction(this.checker))
		{
			return this.checker();
		}

		if(!this.element)
		{
			return true;
		}

		var isSuccess = 1;
		var errorCode = 0;

		if(this.isEmpty())
		{
			isSuccess = 2;
			if(this.required && this.isVisible())
			{
				isSuccess = 0;
				errorCode = 1;
			}
		}
		else
		{
			if(!this.validate())
			{
				isSuccess = 0;
				errorCode = 2;
			}
		}

		BX.onCustomEvent(this, 'onCheck', [this.name, this.elements, isSuccess, errorCode]);
		BX.onCustomEvent(this.caller, 'onCheck', [this.name, this.elements, isSuccess, errorCode]);
		return isSuccess;
	},

	registerChecker: function(checker)
	{
		this.checker = checker;
	}
};


function FormChecker(params)
{
	this.params = params;
	this.form = BX(params.form);
	this.showOnlyFirstError = !!(params.showOnlyFirstError || false);
	this.fields = [];
	this.validators = {
		'url': this.validateUrl,
		'integer': this.validateInteger,
		'double': this.validateDouble,
		'email': this.validateEmail,
		'phone': this.validatePhone,
		'date': this.validateDate
	};
	this.formatters = {
		'email': this.normalizeEmail
		//'phone': this.formatPhone
	};

	if(params.onCheck)
	{
		BX.addCustomEvent(this, 'onCheck', params.onCheck);
	}
	if(params.onFocus)
	{
		BX.addCustomEvent(this, 'onFocus', params.onFocus);
	}
	if(params.onBlur)
	{
		BX.addCustomEvent(this, 'onBlur', params.onBlur);
	}
	if(params.onShow)
	{
		BX.addCustomEvent(this, 'onShow', params.onShow);
	}
	if(params.onHide)
	{
		BX.addCustomEvent(this, 'onHide', params.onHide);
	}
	if(params.onChange)
	{
		BX.addCustomEvent(this, 'onChange', params.onChange);
	}
	if(params.onSubmit)
	{
		BX.addCustomEvent(this, 'onSubmit', params.onSubmit);
	}
	if(params.onSubmitSuccess)
	{
		BX.addCustomEvent(this, 'onSubmitSuccess', params.onSubmitSuccess);
	}

	var fields = params.fields || [];
	this.addFields(fields);

	if(this.form)
	{
		BX.bind(this.form, 'submit', BX.proxy(this.onSubmit, this));
	}
}

FormChecker.prototype = {

	onSubmit: function(e)
	{
		var eventData = {isSuccess: true};
		BX.onCustomEvent(this, 'onSubmit', [e, eventData]);

		var result = this.check();
		result = result && eventData.isSuccess;
		if(!result)
		{
			BX.PreventDefault(e);
		}
		else
		{
			this.disableHiddenFields();
			BX.onCustomEvent(this, 'onSubmitSuccess', [e]);
		}

		return result;
	},

	onSubmitAjaxError: function()
	{
		this.disableHiddenFields(true);
	},

	submit: function()
	{
		BX.submit(this.form);
	},

	check: function()
	{
		var isSuccess = true;
		for(var i in this.fields)
		{
			if(this.fields[i] && !this.fields[i].check())
			{
				isSuccess = false;
				if(this.showOnlyFirstError)
				{
					break;
				}
			}
		}

		return isSuccess;
	},

	disableHiddenFields: function(isEnable)
	{
		isEnable = isEnable || false;
		this.fields.forEach(function(field){
			if (field.isVisible()) return;
			field.disable(isEnable);
		});
	},

	fireChangeEvent: function()
	{
		this.fields.forEach(function(field){
			field.onChange();
		});
	},

	registerTypeValidator: function(type, validator)
	{
		this.validators[type] = validator;
	},

	addField: function(params)
	{
		if(!params.formatter && this.formatters[params.type])
		{
			params.formatter = this.formatters[params.type];
		}

		if(!params.validator && this.validators[params.type])
		{
			params.validator = this.validators[params.type];
		}

		params.caller = this;

		var field = new FormField(params);

		this.fields.push(field);

		return field;
	},

	getField: function(name)
	{
		var list = this.fields.filter(function(field){
			return (field.name && field.name == name);
		});

		return !list ? list : list[0];
	},

	getFields: function()
	{
		return this.fields;
	},

	addFields: function(listParams)
	{
		var currentSection = null;
		for(var i in listParams)
		{
			if(!listParams[i])
			{
				break;
			}

			listParams[i].section = currentSection;
			var field = this.addField(listParams[i]);
			if(field.type == 'section')
			{
				currentSection = field;
			}
		}
	},

	normalizeEmail: function(value)
	{
		return value.replace(/ /g, '');
	},

	validateRegexp: function(value, regexp)
	{
		return (null !== value.match(regexp));
	},

	validateEmail: function(value)
	{
		return (null !== value.match(/^[\w\.\d-_]+@[\w\.\d-_]+\.\w{2,15}$/i));
	},

	validateDate: function(value)
	{
		return true;
	},

	validateDouble: function(value)
	{
		value = value || '';
		value = value.replace(/,/g, '.');
		var dotIndex = value.indexOf('.');
		if (dotIndex === 0)
		{
			value = '0' + value;
		}
		else if (dotIndex < 0)
		{
			value += '.0';
		}

		return value.match(/^\d+\.\d+$/);
	},

	validateInteger: function(value)
	{
		return (value && value.match(/^-?\d+$/));
	},

	validateUrl: function(value)
	{
		return true;
	},

	validatePhone: function(value)
	{
		return true;
	},

	formatPhone: function(value)
	{
		return value;
	}
};