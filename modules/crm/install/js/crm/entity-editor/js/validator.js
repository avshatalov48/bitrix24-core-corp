BX.namespace("BX.Crm");

//region VALIDATION
if(typeof BX.Crm.EntityValidator === "undefined")
{
	BX.Crm.EntityValidator = function()
	{
		this._settings = {};
		this._editor = null;
		this._data = null;
	};
	BX.Crm.EntityValidator.prototype =
		{
			initialize: function(settings)
			{
				this._settings = settings ? settings : {};
				this._editor = BX.prop.get(this._settings, "editor", null);
				this._data = BX.prop.getObject(this._settings, "data", {});

				this.doInitialize();
			},
			doInitialize: function()
			{
			},
			release: function()
			{
			},
			getData: function()
			{
				return this._data;
			},
			getDataStringParam: function(name, defaultValue)
			{
				return BX.prop.getString(this._data, name, defaultValue);
			},
			getErrorMessage: function()
			{
				return BX.prop.getString(this._settings, "message", "");
			},
			validate: function(result)
			{
				return true;
			},
			processControlChange: function(control)
			{
			}
		};
}

if(typeof BX.Crm.EntityPersonValidator === "undefined")
{
	BX.Crm.EntityPersonValidator = function()
	{
		BX.Crm.EntityPersonValidator.superclass.constructor.apply(this);
	};

	BX.extend(BX.Crm.EntityPersonValidator, BX.Crm.EntityValidator);

	BX.Crm.EntityPersonValidator.prototype.doInitialize = function()
	{
		this._nameField = this._editor.getControlById(
			this.getDataStringParam("nameField", "")
		);
		if(this._nameField)
		{
			this._nameField.addValidator(this);
		}

		this._lastNameField = this._editor.getControlById(
			this.getDataStringParam("lastNameField", "")
		);
		if(this._lastNameField)
		{
			this._lastNameField.addValidator(this);
		}
	};
	BX.Crm.EntityPersonValidator.prototype.release = function()
	{
		if(this._nameField)
		{
			this._nameField.removeValidator(this);
		}

		if(this._lastNameField)
		{
			this._lastNameField.removeValidator(this);
		}
	};
	BX.Crm.EntityPersonValidator.prototype.validate = function(result)
	{
		var isNameActive = this._nameField.isActive();
		var isLastNameActive = this._lastNameField.isActive();

		if(!isNameActive && !isLastNameActive)
		{
			return true;
		}

		var name = isNameActive ? this._nameField.getRuntimeValue() : this._nameField.getValue();
		var lastName = isLastNameActive ? this._lastNameField.getRuntimeValue() : this._lastNameField.getValue();

		if(name !== "" || lastName !== "")
		{
			return true;
		}

		if(name === "" && isNameActive)
		{
			result.addError(BX.Crm.EntityValidationError.create({ field: this._nameField }));
			this._nameField.showError(this.getErrorMessage());
		}

		if(lastName === "" && isLastNameActive)
		{
			result.addError(BX.Crm.EntityValidationError.create({ field: this._lastNameField }));
			this._lastNameField.showError(this.getErrorMessage());
		}

		return false;
	};
	BX.Crm.EntityPersonValidator.prototype.processFieldChange = function(field)
	{
		if(field !== this._nameField && field !== this._lastNameField)
		{
			return;
		}

		if(this._nameField)
		{
			this._nameField.clearError();
		}

		if(this._lastNameField)
		{
			this._lastNameField.clearError();
		}
	};
	BX.Crm.EntityPersonValidator.create = function(settings)
	{
		var self = new BX.Crm.EntityPersonValidator();
		self.initialize(settings);
		return self;
	};
}

if(typeof BX.Crm.EntityValidationError === "undefined")
{
	BX.Crm.EntityValidationError = function()
	{
		this._settings = {};
		this._field = null;
		this._message = "";
	};
	BX.Crm.EntityValidationError.prototype =
		{
			initialize: function(settings)
			{
				this._settings = settings ? settings : {};
				this._field = BX.prop.get(this._settings, "field", null);
				this._message = BX.prop.getString(this._settings, "message", "");
			},
			getField: function()
			{
				return this._field;
			},
			getMessage: function()
			{
				return this._message;
			}
		};
	BX.Crm.EntityValidationError.create = function(settings)
	{
		var self = new BX.Crm.EntityValidationError();
		self.initialize(settings);
		return self;
	};
}

if(typeof BX.Crm.EntityValidationResult === "undefined")
{
	BX.Crm.EntityValidationResult = function()
	{
		this._settings = {};
		this._errors = [];
	};
	BX.Crm.EntityValidationResult.prototype =
		{
			initialize: function(settings)
			{
				this._settings = settings ? settings : {};
			},
			getStatus: function()
			{
				return this._errors.length === 0;
			},
			addError: function(error)
			{
				this._errors.push(error);
			},
			getErrors: function()
			{
				return this._errors;
			},
			addResult: function(result)
			{
				var errors = result.getErrors();
				for(var i = 0, length = errors.length; i < length; i++)
				{
					this._errors.push(errors[i]);
				}
			},
			getTopmostField: function()
			{
				var field = null;
				var top = null;
				for(var i = 0, length = this._errors.length; i < length; i++)
				{
					var currentField = this._errors[i].getField();
					if(!field)
					{
						field = currentField;
						top = currentField.getPosition()["top"];
						continue;

					}
					var pos = currentField.getPosition();
					if(!pos)
					{
						continue;
					}

					var currentFieldTop = currentField.getPosition()["top"];
					if(currentFieldTop < top)
					{
						field = currentField;
						top = currentFieldTop;
					}
				}

				return field;
			}
		};
	BX.Crm.EntityValidationResult.create = function(settings)
	{
		var self = new BX.Crm.EntityValidationResult();
		self.initialize(settings);
		return self;
	};
}
if(typeof BX.Crm.EntityAsyncValidator === "undefined")
{
	BX.Crm.EntityAsyncValidator = function()
	{
		this.promisesList = [];
		this.isValid = true;
	};
	BX.Crm.EntityAsyncValidator.prototype =
	{
		addResult: function(validationResult)
		{
			if (validationResult instanceof Promise || validationResult instanceof BX.Promise)
			{
				this.promisesList.push(validationResult);
			}
			else
			{
				this.isValid = (this.isValid && validationResult);
			}
		},
		validate: function()
		{
			if (this.promisesList.length)
			{
				return Promise.all(this.promisesList);
			}

			return this.isValid;
		}
	};
	BX.Crm.EntityAsyncValidator.create = function()
	{
		return new BX.Crm.EntityAsyncValidator();
	};
}
//endregion
