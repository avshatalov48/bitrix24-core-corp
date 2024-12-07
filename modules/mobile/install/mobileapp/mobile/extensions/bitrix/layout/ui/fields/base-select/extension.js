/**
 * @module layout/ui/fields/base-select
 */
jn.define('layout/ui/fields/base-select', (require, exports, module) => {
	const { BaseField } = require('layout/ui/fields/base');

	/**
	 * @class BaseSelectField
	 * @abstract
	 */
	class BaseSelectField extends BaseField
	{
		getValue()
		{
			let value = super.getValue();

			if (this.isEmptyValue(value) && this.shouldPreselectFirstItem())
			{
				value = this.getFirstItemValue();
			}

			return value;
		}

		getFirstItemValue()
		{
			const firstItem = this.getItems()[0];
			if (firstItem)
			{
				return this.prepareSingleValue(this.getItemId(firstItem));
			}

			return null;
		}

		getItemId(item)
		{
			return item.value;
		}

		getItems()
		{
			return this.getConfig().items;
		}

		getSelectedItems()
		{
			let values = this.getValuesArray();
			if (this.shouldPreselectFirstItem())
			{
				values = [values[0] || this.getFirstItemValue()];
			}

			return this.getItems().filter((item) => values.includes(this.getItemId(item)));
		}

		getValuesArray()
		{
			const value = this.getValue();

			if (this.isMultiple())
			{
				return value;
			}

			return this.isEmptyValue(value) ? [] : [value];
		}

		shouldPreselectFirstItem()
		{
			return !this.isMultiple() && this.isRequired() && !this.showRequired();
		}
	}

	BaseSelectField.propTypes = {
		...BaseField.propTypes,
		config: PropTypes.shape({
			showAll: PropTypes.bool, // show more button with count if it's multiple
			styles: PropTypes.shape({
				externalWrapperBorderColor: PropTypes.string,
				externalWrapperBorderColorFocused: PropTypes.string,
				externalWrapperBackgroundColor: PropTypes.string,
				externalWrapperMarginHorizontal: PropTypes.number,
			}),
			deepMergeStyles: PropTypes.object, // override styles
			parentWidget: PropTypes.object, // parent layout widget
			copyingOnLongClick: PropTypes.bool,
			titleIcon: PropTypes.object,

			items: PropTypes.arrayOf(PropTypes.shape({
				value: PropTypes.any,
			})),
		}),
	};

	BaseSelectField.defaultProps = {
		...BaseField.defaultProps,
		config: {
			...BaseField.defaultProps.config,
		},
	};

	module.exports = { BaseSelectField };
});
