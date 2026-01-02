/**
 * @module user-profile/common-tab/src/block/common-fields/src/field/factory
 */
jn.define('user-profile/common-tab/src/block/common-fields/src/field/factory', (require, exports, module) => {
	const { StringField } = require('user-profile/common-tab/src/block/common-fields/src/field/string-field');
	const { DateField } = require('user-profile/common-tab/src/block/common-fields/src/field/date-field');
	const { SelectField } = require('user-profile/common-tab/src/block/common-fields/src/field/select-field');
	const { EmailField } = require('user-profile/common-tab/src/block/common-fields/src/field/email-field');
	const { PhoneField } = require('user-profile/common-tab/src/block/common-fields/src/field/phone-field');

	const FieldType = {
		STRING: 'string',
		DATE: 'date',
		SELECT: 'select',
		EMAIL: 'email',
		PHONE: 'phone',
	};

	class FieldFactory
	{
		static create(type, props)
		{
			switch (type)
			{
				case FieldType.STRING:
					return new StringField(props);
				case FieldType.DATE:
					return new DateField(props);
				case FieldType.SELECT:
					return new SelectField(props);
				case FieldType.EMAIL:
					return new EmailField(props);
				case FieldType.PHONE:
					return new PhoneField(props);
				default:
					return null;
			}
		}
	}

	module.exports = {
		FieldFactory,
		StringField,
		DateField,
		SelectField,
		EmailField,
		PhoneField,

		FieldType,
	};
});
