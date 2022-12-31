/**
 * @module layout/ui/fields/address
 */
jn.define('layout/ui/fields/address', (require, exports, module) => {

	const { BaseField } = require('layout/ui/fields/base');
	const { AddressView } = require('layout/ui/address');

	/**
	 * @class AddressField
	 */
	class AddressField extends BaseField
	{
		isReadOnly()
		{
			return true;
		}

		isDisabled()
		{
			return true;
		}

		prepareSingleValue(value)
		{
			if (!Array.isArray(value))
			{
				value = [value];
			}

			return value;
		}

		isEmptyValue(value)
		{
			return !Array.isArray(value) || value.length === 0 || !value[0];
		}

		renderEditableContent()
		{
			return this.renderReadOnlyContent();
		}

		renderReadOnlyContent()
		{
			if (this.isEmpty())
			{
				return this.renderEmptyContent();
			}

			return View(
				{
					style: {
						flex: 1,
						flexDirection: 'column',
					},
				},
				this.renderAddress(),
			);
		}

		renderAddress()
		{
			const [address, coords] = this.getValue();
			const parentWidget = this.getParentWidget();

			return AddressView({ address, coords, parentWidget });
		}
	}

	module.exports = {
		AddressType: 'address',
		AddressField: (props) => new AddressField(props),
	};

});
