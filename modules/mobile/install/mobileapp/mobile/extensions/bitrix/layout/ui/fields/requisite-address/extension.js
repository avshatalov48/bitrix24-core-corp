/**
 * @module layout/ui/fields/requisite-address
 */
jn.define('layout/ui/fields/requisite-address', (require, exports, module) => {

	const { AddressView } = require('layout/ui/address');
	const { BaseField } = require('layout/ui/fields/base');

	/**
	 * @class RequisiteAddressField
	 */
	class RequisiteAddressField extends BaseField
	{
		get types()
		{
			return BX.prop.getObject(this.getConfig(), 'types', null);
		}

		isReadOnly()
		{
			return true;
		}

		isDisabled()
		{
			return true;
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
				...this.getValue().map((location) => this.renderAddress(location)),
			);
		}

		renderAddress(location)
		{
			const { id, address, longitude: lng, latitude: lat } = location;
			const coords = { lng, lat };
			const parentWidget = this.getParentWidget();
			const type = this.getTypeValueById(id);

			return AddressView({ address, type, coords, parentWidget });
		}

		getTypeValueById(id)
		{
			if (this.types && this.types[id])
			{
				return this.types[id].DESCRIPTION;
			}

			return null;
		}
	}

	module.exports = {
		RequisiteAddressType: 'requisite_address',
		RequisiteAddressField: (props) => new RequisiteAddressField(props),
	};

});
