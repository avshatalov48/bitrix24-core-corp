/**
 * @module crm/communication/floating-button
 */

jn.define('crm/communication/floating-button', (require, exports, module) => {

	const { CommunicationButton } = require('crm/communication/button');
	const { uniqBy } = require('utils/array');
	const { isEmpty } = require('utils/object');

	const testId = 'CommunicationFloatingButton';
	const BUTTON_WIDTH = 108;

	/**
	 * @class CommunicationFloatingButton
	 */
	class CommunicationFloatingButton extends LayoutComponent
	{
		constructor(props = {})
		{
			super(props);
			this.buttonRef = null;
			this.state = {
				value: {},
				ownerInfo: {},
			};
		}

		setValue(value, ownerInfo)
		{
			const { value: prevValue } = this.state;

			this.setState(
				{
					value: this.mergeValues(value, prevValue),
					ownerInfo,
				},
				() => {
					this.show();
				},
			);
		}

		mergeValues(value, prevValue)
		{
			if (isEmpty(prevValue))
			{
				return value;
			}

			const uniqValue = Object.keys(value).reduce((result, key) => ({
				...result,
				[key]: !prevValue[key]
					? prevValue[key]
					: uniqBy([...value[key], ...prevValue[key]], 'id'),
			}), {});

			return { ...prevValue, ...uniqValue };
		}

		show()
		{
			const delay = this.buttonRef ? 0 : 200;

			setTimeout(() => {
				if (this.buttonRef)
				{
					this.buttonRef.animate({
						duration: 500,
						bottom: 22,
					});
				}
				else
				{
					this.show();
				}
			}, delay);
		}

		getCenterPosition()
		{
			if (!device)
			{
				return '50%';
			}

			const deviceCenter = device.screen.width / 2;
			const buttonCenter = BUTTON_WIDTH / 2;

			return deviceCenter - buttonCenter;
		}

		render()
		{
			const { value, ownerInfo } = this.state;

			return new CommunicationButton({
				viewRef: (ref) => this.buttonRef = ref,
				testId,
				border: true,
				horizontal: true,
				styles: {
					main: {
						left: this.getCenterPosition(),
						position: 'absolute',
						bottom: -100,
					},
				},
				value,
				ownerInfo,
			});
		}
	}

	module.exports = { CommunicationFloatingButton };
});
