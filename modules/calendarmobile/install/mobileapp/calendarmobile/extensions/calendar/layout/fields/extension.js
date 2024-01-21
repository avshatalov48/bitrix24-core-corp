/**
 * @module calendar/layout/fields
 */
jn.define('calendar/layout/fields', (require, exports, module) => {
	const { SelectField } = require('calendar/layout/fields/select-field');
	const { MultipleSelectField } = require('calendar/layout/fields/multiple-select-field');
	const { StringField } = require('calendar/layout/fields/string-field');

	module.exports = {
		SelectField,
		MultipleSelectField,
		StringField,
	};
});
