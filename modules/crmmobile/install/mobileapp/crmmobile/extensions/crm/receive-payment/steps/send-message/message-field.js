/**
 * @module crm/receive-payment/steps/send-message/message-field
 */
jn.define('crm/receive-payment/steps/send-message/message-field', (require, exports, module) => {
	const { Loc } = require('loc');
	const AppTheme = require('apptheme');
	const { PureComponent } = require('layout/pure-component');
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
			const { isEditing } = this.state;

			return View(
				{
					style: {
						flexDirection: 'row',
					},
				},
				this.renderCornerImage(),
				isEditing ? this.renderEditingField() : this.renderReadOnlyField(),
			);
		}

		renderEditingField()
		{
			return TextInput({
				value: this.state.value,
				ref: (ref) => {
					this.textInputRef = ref;
				},
				style: {
					width: '100%',
					flexShrink: 1,
					borderWidth: 1,
					borderColor: AppTheme.colors.bgSeparatorPrimary,
					borderRadius: 18,
					left: -15,
					marginRight: -15,
					paddingTop: 10,
					paddingLeft: 16,
					paddingRight: 16,
					fontSize: 14,
					color: AppTheme.colors.base1,
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
						borderColor: AppTheme.colors.accentBrandBlue,
						borderRadius: 18,
						left: -15,
						marginRight: -15,
						paddingTop: 10,
						paddingBottom: 15,
						paddingLeft: 16,
						paddingRight: 16,
						backgroundColor: AppTheme.colors.accentSoftBlue2,
						zIndex: 1,
					},
				},
				BBCodeText({
					value: this.getReadOnlyValue(),
					style: {
						fontSize: 14,
						color: AppTheme.colors.base1,
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
				return `[color=${AppTheme.colors.base1}]${Loc.getMessage('M_RP_SM_TEMPLATE_BASED_MESSAGE_WILL_BE_SENT')}[/color] `
					+ `[C type=dot textColor=${AppTheme.colors.accentMainLinks} lineColor=${AppTheme.colors.accentBrandBlue}][URL="#"]${Loc.getMessage(
						'M_RP_SM_MORE_DETAILS',
					)}[/URL][/C]`;
			}

			if (this.currentSenderCode === SenderCodes.SMS_PROVIDER)
			{
				return this.state.value.replaceAll(
					'#LINK#',
					`[color=${AppTheme.colors.accentMainLinks}]${this.orderPublicUrl}[/color][color=${AppTheme.colors.base4}]xxxxx[/color]`,
				);
			}

			return '';
		}

		renderCornerImage(uri)
		{
			return Image({
				svg: {
					content: this.getCornerSvg(),
				},
				style: {
					width: 22,
					height: 27,
					top: -4,
					zIndex: 2,
					left: -6,
				},
			});
		}

		getCornerSvg()
		{
			const { isEditing } = this.state;

			const fillColor = isEditing ? AppTheme.colors.bgContentPrimary : AppTheme.colors.accentSoftBlue2;
			const strokeColor = isEditing ? AppTheme.colors.bgSeparatorPrimary : AppTheme.colors.accentBrandBlue;

			return `<svg width="22" height="27" viewBox="0 0 22 27" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_602_161738)"><g filter="url(#filter0_d_602_161738)"><path fill-rule="evenodd" clip-rule="evenodd" d="M15.428 22.8932C14.9229 23.0686 14.3661 22.8213 14.1331 22.3401C13.202 20.4162 10.5881 15.7288 6.04215 12.807C5.41833 12.4061 5.35325 11.4914 6.00156 11.1314C11.7517 7.93847 19.2094 9.58215 20.9149 10.0221C21.1503 10.0829 21.3439 10.2334 21.4727 10.4395L26.1255 17.885C26.4658 18.4295 26.2121 19.149 25.6055 19.3596L15.428 22.8932Z" fill="${fillColor}"/><path d="M15.264 22.4209C15.0111 22.5087 14.7116 22.3875 14.5832 22.1222C13.6394 20.1724 10.9757 15.3836 6.31249 12.3864C6.14066 12.276 6.05111 12.0981 6.04439 11.9335C6.03805 11.7779 6.10257 11.6472 6.24429 11.5685C9.01125 10.0321 12.213 9.64859 14.963 9.71428C17.708 9.77985 19.9587 10.2918 20.79 10.5063C20.8912 10.5324 20.982 10.5978 21.0487 10.7045L25.7015 18.15C25.8717 18.4222 25.7448 18.782 25.4415 18.8873L15.264 22.4209Z" stroke="${strokeColor}" stroke-linejoin="round"/></g></g><defs><filter id="filter0_d_602_161738" x="2.54395" y="7.20703" width="26.7339" height="19.7422" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB"><feFlood flood-opacity="0" result="BackgroundImageFix"/><feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0" result="hardAlpha"/><feOffset dy="1"/><feGaussianBlur stdDeviation="1.5"/><feColorMatrix type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0.07 0"/><feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow_602_161738"/><feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow_602_161738" result="shape"/></filter><clipPath id="clip0_602_161738"><rect width="11.7028" height="23" fill="white" transform="matrix(0.899448 0.437028 0.437028 -0.899448 0.443359 21.417)"/></clipPath></defs></svg>`;
		}
	}

	module.exports = { MessageField };
});
