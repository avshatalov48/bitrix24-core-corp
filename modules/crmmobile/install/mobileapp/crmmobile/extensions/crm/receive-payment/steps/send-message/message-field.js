/**
 * @module crm/receive-payment/steps/send-message/message-field
 */
jn.define('crm/receive-payment/steps/send-message/message-field', (require, exports, module) => {
	const { PureComponent } = require('layout/pure-component');
	const pathToExtension = `${currentDomain}/bitrix/mobileapp/crmmobile/extensions/crm/receive-payment/steps/send-message`;
	const { Loc } = require('loc');
	const { SenderCodes } = require('crm/receive-payment/steps/send-message/sender-codes');

	/**
	 * @class MessageField
	 */
	class MessageField extends PureComponent
	{
		constructor(props)
		{
			super(props);
			this.orderPublicUrl = props.orderPublicUrl;
			this.currentSenderCode = props.currentSenderCode;
			this.state = {
				isEditing: BX.prop.getBoolean(props, 'isEditing', false),
				value: BX.prop.getString(props, 'value', ''),
			};
		}

		render()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
					},
				},
				this.renderCornerImage(this.state.isEditing ? 'corner-white.svg' : 'corner.svg'),
				this.state.isEditing ? this.renderEditingField() : this.renderReadOnlyField(),
			);
		}

		renderEditingField()
		{
			return TextInput({
				value: this.state.value,
				ref: (ref) => this.textInputRef = ref,
				style: {
					width: '100%',
					flexShrink: 1,
					borderWidth: 1,
					borderColor: '#525c69',
					borderRadius: 18,
					left: -15,
					marginRight: -15,
					paddingTop: 10,
					paddingLeft: 16,
					paddingRight: 16,
					fontSize: 14,
					color: '#333333',
				},
			});
		}

		renderReadOnlyField()
		{
			return View(
				{
					style: {
						width: '100%',
						flexShrink: 1,
						borderWidth: 1,
						borderColor: '#2fc6f6',
						borderRadius: 18,
						left: -15,
						marginRight: -15,
						paddingTop: 10,
						paddingBottom: 15,
						paddingLeft: 16,
						paddingRight: 16,
						backgroundColor: '#e5f9ff',
						zIndex: 1,
					},
				},
				BBCodeText({
					value: this.getReadOnlyValue(),
					style: {
						fontSize: 14,
						color: '#333333',
					},
					onLinkClick: (url) => {
						helpdesk.openHelpArticle('17537000', 'helpdesk');
					},
				}),
			);
		}

		getReadOnlyValue()
		{
			if (this.currentSenderCode === SenderCodes.BITRIX24)
			{
				return `[color="#333333"]${Loc.getMessage('M_RP_SM_TEMPLATE_BASED_MESSAGE_WILL_BE_SENT')}[/color] `
					+ `[C type=dot textColor=#0b66c3 lineColor=#93c1e8][URL="#"]${Loc.getMessage('M_RP_SM_MORE_DETAILS')}[/URL][/C] `;
			}

			if (this.currentSenderCode === SenderCodes.SMS_PROVIDER)
			{
				return this.state.value.replace(
					/#LINK#/g,
					`[color="#0b66c3"]${this.orderPublicUrl}[/color][color="#959ca4"]xxxxx[/color]`,
				);
			}
		}

		renderCornerImage(uri)
		{
			return Image({
				svg: { uri: `${pathToExtension}/images/${uri}` },
				style: {
					width: 22,
					height: 27,
					top: -4,
					zIndex: 2,
					left: -6,
				},
			});
		}
	}

	module.exports = { MessageField };
});
