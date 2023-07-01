/**
 * @module layout/ui/address
 */
jn.define('layout/ui/address', (require, exports, module) => {

	const { location } = require('assets/common');
	const { MapOpener } = require('layout/ui/map-opener');
	const { stringify } = require('utils/string');

	const AddressViewType = {
		DEFAULT: 'default',
		BLENDING: 'blending',
		getTextColor: (type) => {
			switch (type) {
				case AddressViewType.BLENDING:
					return '#828B95';
				default:
					return '#2066B0';
			}
		},
		getIconColor: (type) => {
			switch (type) {
				case AddressViewType.BLENDING:
					return '#BDC1C6';
				default:
					return '#559BE6';
			}
		}
	};

	/**
	 * @function AddressView
	 *
	 * @param {{address, clickable, parentWidget}} props
	 *
	 * @returns {null|*}
	 */
	const AddressView = (props) => {
		const { coords, clickable = true, parentWidget, viewType = AddressViewType.DEFAULT } = props;
		let { address, type } = props;

		address = stringify(address);
		if (address === '')
		{
			return null;
		}

		return View(
			{
				style: {
					flexDirection: 'row',
					marginTop: 4,
					alignItems: 'flex-start',
				},
				onClick: clickable && (() => {
					const mapOpener = new MapOpener(parentWidget);
					mapOpener.open({ address, coords });
				}),
			},
			Image({
				style: {
					marginRight: viewType === AddressViewType.BLENDING ? 2 : 10,
					height: 19,
					width: 22,
				},
				svg: {
					content: location(AddressViewType.getIconColor(viewType)),
				},
			}),
			Text({
				style: {
					flexShrink: 2,
					fontSize: 14,
					color: clickable ? AddressViewType.getTextColor(viewType) : '#828b95',
					textDecorationLine: viewType === AddressViewType.BLENDING ? 'underline' : 'none',
				},
				text: address,
			}),
			type && Text(
				{
					style: {
						fontSize: 12,
						color: '#A8ADB4',
						maxWidth: 90,
						textAlign: 'right',
						marginLeft: 5,
					},
					numberOfLines: 2,
					ellipsize: 'end',
					text: type,
				}
			)
		);
	};

	module.exports = { AddressView, AddressViewType };

});
