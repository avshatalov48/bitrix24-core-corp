/**
 * @module layout/ui/fields/client/elements/info
 */
jn.define('layout/ui/fields/client/elements/info', (require, exports, module) => {

	const { AddressView, AddressViewType } = require('layout/ui/address');
	const { phoneUtils } = require('native/phonenumber');

	/**
	 * @class ClientItemInfo
	 */
	class ClientItemInfo extends LayoutComponent
	{
		render()
		{
			const { addresses = [], subtitle, testId } = this.props;

			return View(
				{
					testId: `${testId}-info`,
					style: {
						flexShrink: 2,
					},
				},
				Boolean(subtitle) && Text({
					style: style.text,
					text: subtitle,
				}),
				this.renderConnections(),
				...addresses.map((address) => AddressView({
					address,
					clickable: true,
					viewType: AddressViewType.BLENDING,
				})),
			);
		}

		renderConnections()
		{
			const { phone, email } = this.props;

			const phones = this.getValue(phone);
			const emails = this.getValue(email);

			let formattedNumbers = phones;
			if (Array.isArray(formattedNumbers))
			{
				formattedNumbers = phones && phones.map(phone => phoneUtils.getFormattedNumber(phone, phoneUtils.getCountryCode(phone)));
			}


			return View(
				{
					style: {
						flexDirection: 'column',
					},
				},
				this.renderText(formattedNumbers),
				this.renderText(emails),
			);

		}

		renderText(value)
		{
			const text = this.arrayToString(value);

			return Boolean(text) && Text({
				text,
				style: style.text,
				numberOfLines: 1,
				ellipsize: 'end',
			});
		}

		arrayToString(array)
		{
			if (!Array.isArray(array))
			{
				return array;
			}

			return array.filter(Boolean).join(', ');
		}

		getValue(value)
		{
			if (Array.isArray(value))
			{
				return value.map(this.getText);
			}

			return this.getText(value);
		}

		getText(value)
		{
			if (!value)
			{
				return '';
			}

			return typeof value === 'string' ? value : value.value;
		}
	}

	const style = {
		text: {
			color: '#a8adb4',
			fontSize: 14,
			marginTop: 4,
			flexShrink: 2,
		},
	};

	module.exports = { ClientItemInfo };
});