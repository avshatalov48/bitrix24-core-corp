/**
 * @module imconnector/lib/ui/buttons/copy
 */
jn.define('imconnector/lib/ui/buttons/copy', (require, exports, module) => {
	const { Type } = require('type');
	const { withPressed } = require('utils/color');

	/**
	 * @param {CopyButtonProps} props
	 * @return {*}
	 * @constructor
	 */
	function CopyButton(props)
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
					backgroundColor: withPressed('#00A2E8'),
					borderRadius,
					paddingVertical: 4,
					paddingHorizontal: 25,
					width,
					height,
				},
				clickable: true,
				onClick: () => {
					Application.copyToClipboard(props.copyText);

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
					color: '#FFFFFF',
					fontSize: 16,
					fontWeight: 400,
					numberOfLines: 1,
				},
				text: props.text,
			}),
		);
	}

	const icon = `<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path fill-rule="evenodd" clip-rule="evenodd" d="M13.5213 14.2443C13.7422 14.2443 13.9213 14.0652 13.9213 13.8443V8.29605H15.9171C16.1903 8.29605 16.3213 7.96059 16.1204 7.77544L12.9812 4.88261C12.8664 4.77676 12.6895 4.77676 12.5746 4.88261L9.43549 7.77544C9.23457 7.96059 9.36557 8.29605 9.63879 8.29605H11.6536V13.8443C11.6536 14.0652 11.8327 14.2443 12.0536 14.2443H13.5213ZM6.16953 12.9762C5.94862 12.9762 5.76953 13.1553 5.76953 13.3762V16.9692C5.76953 18.2395 6.79928 19.2692 8.06953 19.2692H17.4695C18.7398 19.2692 19.7695 18.2395 19.7695 16.9692V13.3762C19.7695 13.1553 19.5904 12.9762 19.3695 12.9762H17.8932C17.6722 12.9762 17.4932 13.1553 17.4932 13.3762V16.0319C17.4932 16.5842 17.0454 17.0319 16.4932 17.0319H9.0459C8.49361 17.0319 8.0459 16.5842 8.0459 16.0319V13.3762C8.0459 13.1553 7.86681 12.9762 7.6459 12.9762H6.16953Z" fill="white"/>
</svg>
`;

	module.exports = { CopyButton };
});
