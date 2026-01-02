/**
 * @module user-profile/common-tab/src/block/common-fields/src/field/select-field
 */
jn.define('user-profile/common-tab/src/block/common-fields/src/field/select-field', (require, exports, module) => {
	const { BaseField } = require('user-profile/common-tab/src/block/common-fields/src/field/base-field');
	const { Text4 } = require('ui-system/typography/text');
	const { Color } = require('tokens');
	const { SelectFieldClass } = require('layout/ui/fields/select');
	const { Type } = require('type');

	class SelectField extends BaseField
	{
		renderViewModeFieldValue(value, idx)
		{
			return Text4({
				text: this.getValueText(value),
				color: Color.base1,
			});
		}

		getValueText(value)
		{
			const { items } = this.props;
			if (!Type.isNil(value))
			{
				return items[value];
			}

			return null;
		}

		renderEditModeFieldValue(value, idx)
		{
			const { id } = this.props;

			return View(
				{
					testId: this.getTestId(`${id.toLowerCase()}-select-field-${idx}`),
					style: {
						width: '100%',
					},
				},
				new SelectFieldClass({
					value,
					onChange: (newValue) => this.onChange(newValue, idx),
					showTitle: false,
					config: {
						items: this.getPreparedItems(),
						styles: {
							wrapper: {
								padding: 0,
								margin: 0,
							},
						},
					},
				}),
			);
		}

		getPreparedItems = () => {
			const { items } = this.props;

			return Object.keys(items).map((key) => ({
				name: items[key],
				value: key,
			}));
		};
	}

	module.exports = { SelectField };
});
