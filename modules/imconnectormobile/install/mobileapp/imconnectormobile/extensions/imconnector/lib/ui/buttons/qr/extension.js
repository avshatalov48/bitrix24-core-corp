/**
 * @module imconnector/lib/ui/buttons/qr
 */
jn.define('imconnector/lib/ui/buttons/qr', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Type } = require('type');
	const { withPressed } = require('utils/color');
	const { QrView } = require('imconnector/lib/ui/buttons/qr/qr-view');

	/**
	 * @param {QrButtonProps} props
	 * @return {*}
	 * @constructor
	 */
	function QrButton(props)
	{
		const borderRadius = BX.prop.getNumber(props.style, 'borderRadius', 6);
		const width = BX.prop.getNumber(props.style, 'width', null);
		const height = BX.prop.getNumber(props.style, 'height', null);

		return View(
			{
				style: {
					flexDirection: 'row',
					alignItems: 'center',
					justifyContent: 'center',
					backgroundColor: withPressed('#FFFFFF'),
					borderColor: '#A8ADB4',
					borderWidth: 1,
					borderRadius,
					paddingVertical: 4,
					paddingHorizontal: 25,
					width,
					height,
				},
				clickable: true,
				onClick: () => {
					const parentWidget = props.parentWidget || PageManager;

					parentWidget.openWidget(
						'layout',
						{
							backdrop: {
								horizontalSwipeAllowed: false,
								mediumPositionHeight: 450,
								onlyMediumPosition: true,
								hideNavigationBar: true,
							},
							onReady: (layoutWidget) => {
								layoutWidget.showComponent(new QrView({
									image: props.image,
									layoutWidget,
								}));
							},
						},
					);

					if (Type.isFunction(props.onClick))
					{
						props.onClick();
					}
				},

			},
			Image({
				style: {
					width: 22,
					height: 22,
					marginRight: 5,
				},
				svg: {
					content: icon,
				},
				resizeMode: 'cover',
			}),
			Text({
				style: {
					color: '#333333',
					fontSize: 16,
					fontWeight: 400,
					numberOfLines: 1,
				},
				text: props.text || Loc.getMessage('IMCONNECTORMOBILE_QR_BUTTON'),
			}),
		);
	}

	const icon = `<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path fill-rule="evenodd" clip-rule="evenodd" d="M4.26953 5.90579C4.26953 5.00103 5.00298 4.26758 5.90774 4.26758H9.89499C10.7997 4.26758 11.5332 5.00103 11.5332 5.90579V9.89304C11.5332 10.7978 10.7997 11.5312 9.89499 11.5312H5.90774C5.00298 11.5312 4.26953 10.7978 4.26953 9.89304V5.90579ZM5.61475 5.6123H10.1882V10.1857H5.61475V5.6123ZM9.15055 6.65043H6.65246V9.14851H9.15055V6.65043ZM4.26953 14.6294C4.26953 13.7247 5.00298 12.9912 5.90774 12.9912H9.89499C10.7997 12.9912 11.5332 13.7247 11.5332 14.6294V18.6167C11.5332 19.5214 10.7997 20.2549 9.89499 20.2549H5.90774C5.00298 20.2549 4.26953 19.5214 4.26953 18.6167V14.6294ZM5.61475 14.3359H10.1882V18.9094H5.61475V14.3359ZM9.15042 15.374H6.65234V17.8721H9.15042V15.374ZM14.6703 4.26758C13.7655 4.26758 13.0321 5.00103 13.0321 5.90579V9.89304C13.0321 10.7978 13.7655 11.5312 14.6703 11.5312H18.6576C19.5623 11.5312 20.2958 10.7978 20.2958 9.89304V5.90579C20.2958 5.00103 19.5623 4.26758 18.6576 4.26758H14.6703ZM18.9507 5.6123H14.3773V10.1857H18.9507V5.6123ZM15.415 6.65043H17.9131V9.14851H15.415V6.65043ZM15.9143 12.9912H17.4131V14.1057H18.7968V12.9912C19.6246 12.9912 20.2956 13.6623 20.2956 14.4901V17.3725H18.7968V15.6045H17.4131V17.3725H15.9143V15.6045H14.5308V20.2549C13.703 20.2549 13.032 19.5838 13.032 18.756V14.4901C13.032 13.6623 13.703 12.9912 14.5308 12.9912V14.1057H15.9143V12.9912ZM17.4132 18.4873H15.9143V20.2552H17.4132V18.4873ZM20.2957 18.4873H18.7969V20.2552C19.6247 20.2552 20.2957 19.5841 20.2957 18.7563V18.4873Z" fill="#A8ADB4"/>
</svg>
`;

	module.exports = { QrButton };
});
