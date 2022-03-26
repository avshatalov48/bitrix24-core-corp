(() => {
	const Type = {
		STRING: 'string',
		TEXTAREA: 'textarea',
		NUMBER: 'number',
		DATE: 'date',
		DATETIME: 'datetime',
		FILE: 'file',
		STATUS: 'status',
		SELECT: 'select',
		USER: 'user',
		IMAGE_SELECT: 'imageSelect',
		MENU_SELECT: 'menuSelect',
		COMBINED: 'combined',
		ENTITY_SELECTOR: 'entity-selector',
		MONEY: 'money',
		BARCODE: 'barcode',
	};

	const WRAPPED_WITH_MULTIPLE_FIELD = [
		Type.STRING,
		Type.TEXT,
		Type.NUMBER,
		Type.DATE,
		Type.DATETIME,
		Type.COMBINED,
		Type.MONEY,
		Type.BARCODE
	];


	/**
	 * @class FieldFactory
	 */
	class FieldFactory
	{
		static has(type)
		{
			return Object.values(Type).includes(type);
		}

		static create(type, data)
		{
			if (data.multiple && WRAPPED_WITH_MULTIPLE_FIELD.find(fieldType => type === fieldType))
			{
				return new Fields.MultipleField({
					...data,
					renderField: (fieldData) => FieldFactory.create(type, fieldData)
				});
			}

			if (type === Type.STRING)
			{
				return new Fields.StringInput(data);
			}

			if (type === Type.TEXTAREA)
			{
				return new Fields.TextArea(data);
			}

			if (type === Type.DATE)
			{
				return new Fields.DateField(data);
			}

			if (type === Type.DATETIME)
			{
				return new Fields.DateField({
					...data,
					config: {
						...data.config,
						datePickerType: 'datetime',
						dateFormat: 'd MMMM yyyy HH:mm'
					}
				});
			}

			if (type === Type.FILE)
			{
				return new Fields.FileField(data);
			}

			if (type === Type.STATUS)
			{
				return new Fields.StatusField(data);
			}

			if (type === Type.SELECT)
			{
				return new Fields.Select(data);
			}

			if (type === Type.USER)
			{
				return new Fields.User(data);
			}

			if (type === Type.IMAGE_SELECT)
			{
				return new Fields.ImageSelect(data);
			}

			if (type === Type.MENU_SELECT)
			{
				return new Fields.MenuSelect(data);
			}

			if (type === Type.COMBINED)
			{
				return new Fields.CombinedField(data);
			}

			if (type === Type.NUMBER)
			{
				return new Fields.NumberField(data);
			}

			if (type === Type.ENTITY_SELECTOR)
			{
				return new Fields.EntitySelector(data);
			}

			if (type === Type.MONEY)
			{
				return new Fields.MoneyField(data);
			}

			if (type === Type.BARCODE)
			{
				return new Fields.BarcodeInput(data);
			}

			console.error('Type not found. Trying to render the field as a StringInput.');
			return new Fields.StringInput(data); //
		}
	}

	this.FieldFactory = FieldFactory;
	this.FieldFactory.Type = Type;
})();
