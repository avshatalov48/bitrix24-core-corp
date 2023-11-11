/**
 * @module layout/ui/fields/requisite-address
 */
jn.define('layout/ui/fields/requisite-address', (require, exports, module) => {

	const { AddressView } = require('layout/ui/address');
	const { BaseField } = require('layout/ui/fields/base');
	const { stringify } = require('utils/string');
	const { Loc } = require('loc');

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

			return AddressView(
				{
					address,
					type,
					coords,
					parentWidget,
					onLongClick: () => {
						const callback = this.getContentLongClickHandler();
						if (callback)
						{
							callback(address);
						}
					},
				},
			);
		}

		getTypeValueById(id)
		{
			if (this.types && this.types[id])
			{
				return this.types[id].DESCRIPTION;
			}

			return null;
		}

		canCopyValue()
		{
			return true;
		}

		prepareValueToCopy()
		{
			const [address] = this.getValue();

			return stringify(address);
		}

		copyMessage()
		{
			return Loc.getMessage('FIELDS_REQUISITE_ADDRESS_VALUE_COPIED');
		}
	}

	module.exports = {
		RequisiteAddressType: 'requisite_address',
		RequisiteAddressField: (props) => new RequisiteAddressField(props),
	};
});
