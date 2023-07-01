/**
 * @module crm/receive-payment/steps/send-message/edit-button
 */
jn.define('crm/receive-payment/steps/send-message/edit-button', (require, exports, module) => {
	const { PureComponent } = require('layout/pure-component');

	const pathToExtension = `${currentDomain}/bitrix/mobileapp/crmmobile/extensions/crm/receive-payment/steps/send-message`;

	/**
	 * @class EditButton
	 */
	class EditButton extends PureComponent
	{
		constructor(props)
		{
			super(props);
			this.state = {
				isEditing: BX.prop.getBoolean(props, 'isEditing', false),
			};
		}

		render()
		{
			const imageName = this.state.isEditing ? 'check' : 'pencil';

			return View(
				{
					style: {
						width: 32,
						height: 32,
						marginTop: 20,
						marginLeft: 4,
						alignItems: 'center',
						justifyContent: 'center',
					},
					onClick: () => {
						const newValue = !this.state.isEditing;
						this.props.onChange(newValue);
					},
				},
				Image({
					svg: { uri: `${pathToExtension}/images/${imageName}.svg` },
					style: {
						width: 14,
						height: 14,
					},
				}),
			);
		}
	}

	module.exports = { EditButton };
});
