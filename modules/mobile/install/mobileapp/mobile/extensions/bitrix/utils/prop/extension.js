(() => {

	BX.prop = {
		get: function (object, key, defaultValue)
		{
			return object && object.hasOwnProperty(key) ? object[key] : defaultValue;
		},
		getObject: function (object, key, defaultValue)
		{
			return object && BX.type.isPlainObject(object[key]) ? object[key] : defaultValue;
		},
		getElementNode: function (object, key, defaultValue)
		{
			return object && BX.type.isElementNode(object[key]) ? object[key] : defaultValue;
		},
		getArray: function (object, key, defaultValue)
		{
			return object && BX.type.isArray(object[key]) ? object[key] : defaultValue;
		},
		getFunction: function (object, key, defaultValue)
		{
			return object && BX.type.isFunction(object[key]) ? object[key] : defaultValue;
		},
		getNumber: function (object, key, defaultValue)
		{
			if (!(object && object.hasOwnProperty(key)))
			{
				return defaultValue;
			}

			var value = object[key];
			if (BX.type.isNumber(value))
			{
				return value;
			}

			value = parseFloat(value);
			return !isNaN(value) ? value : defaultValue;
		},
		getInteger: function (object, key, defaultValue)
		{
			if (!(object && object.hasOwnProperty(key)))
			{
				return defaultValue;
			}

			var value = object[key];
			if (BX.type.isNumber(value))
			{
				return value;
			}

			value = parseInt(value);
			return !isNaN(value) ? value : defaultValue;
		},
		getBoolean: function (object, key, defaultValue)
		{
			if (!(object && object.hasOwnProperty(key)))
			{
				return defaultValue;
			}

			var value = object[key];
			return (BX.type.isBoolean(value)
					? value
					: (BX.type.isString(value) ? (value.toLowerCase() === "true") : !!value)
			);
		},
		getString: function (object, key, defaultValue)
		{
			if (!(object && object.hasOwnProperty(key)))
			{
				return defaultValue;
			}

			var value = object[key];
			return BX.type.isString(value) ? value : (value ? value.toString() : "");
		},
		extractDate: function (datetime)
		{
			if (!BX.type.isDate(datetime))
			{
				datetime = new Date();
			}

			datetime.setHours(0);
			datetime.setMinutes(0);
			datetime.setSeconds(0);
			datetime.setMilliseconds(0);

			return datetime;
		},
	};

})();