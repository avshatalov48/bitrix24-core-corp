/**
 * @module user-profile/common-tab/src/block/common-fields/src/field/string-field
 */
jn.define('user-profile/common-tab/src/block/common-fields/src/field/string-field', (require, exports, module) => {
	const { BaseField } = require('user-profile/common-tab/src/block/common-fields/src/field/base-field');
	const { Text4 } = require('ui-system/typography/text');
	const { Color } = require('tokens');
	const { TextField } = require('ui-system/typography/text-field');

	class StringField extends BaseField
	{
		renderViewModeFieldValue(value, idx)
		{
			return Text4({
				key: `value-${idx}`,
				text: value,
				color: Color.base1,
				style: { marginBottom: 4 },
			});
		}

		renderEditModeFieldValue(value, idx)
		{
			const { id } = this.props;

			return TextField({
				ref: (ref) => this.bindRef(ref, idx),
				testId: this.getTestId(`${id.toLowerCase()}-input-${idx}`),
				value,
				size: 4,
				onChangeText: (newValue) => this.onChange(newValue, idx),
				style: {
					width: '100%',
				},
				onFocus: () => this.onFocus(idx),
			});
		}

		getDefaultValue()
		{
			return '';
		}
	}

	module.exports = { StringField };
});
