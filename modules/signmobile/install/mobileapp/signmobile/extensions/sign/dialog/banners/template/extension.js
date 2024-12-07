/**
 * @module sign/dialog/banners/template
 */
jn.define('sign/dialog/banners/template', (require, exports, module) => {
	const { Card } = require('ui-system/layout/card');
	const { EmptyScreen } = require('layout/ui/empty-screen');
	const { Indent, Color } = require('tokens');
	const { ConfirmNavigator, ButtonType } = require('alert/confirm');
	const IS_IOS = Application.getPlatform() === 'ios';

	function showConfirm(props)
	{
		const {
			title = '',
			description = '',
			confirmTitle = '',
			cancelTitle = '',
			onConfirm = () => {},
			onCancel = () => {},
		} = props;

		const confirm = new ConfirmNavigator({
			title,
			description,
			buttons: [
				{
					text: confirmTitle,
					type: ButtonType.DEFAULT,
					onPress: onConfirm,
				},
				{
					text: cancelTitle,
					type: ButtonType.CANCEL,
					onPress: onCancel,
				},
			].filter(Boolean),
		});

		confirm.open();
	}

	function BannerTemplate(props)
	{
		const {
			iconPathName,
			title,
			description,
			buttonsView,
		} = props;

		return View(
			{
				style: {
					backgroundColor: Color.bgPrimary.toHex(),
					flexDirection: 'column',
				},
			},
			View(
				{
					style:
						{
							flex: 1,
						},
				},
				View(
					{
						style:
							{
								paddingHorizontal: 13,
							},
					},
					new EmptyScreen({
						image: {
							svg: {
								uri: EmptyScreen.makeLibraryImagePath(iconPathName, 'sign'),
							},
							style: {
								width: 138,
								height: 138,
							},
						},
						title: () => Text({
							style: {
								fontWeight: '500',
								fontSize: 18,
								textAlign: 'center',
								marginBottom: 10,
								color: Color.base2.toHex(),
							},
							text: title,
						}),
						description: () => BBCodeText({
							style: {
								fontWeight: '400',
								fontSize: 15,
								textAlign: 'center',
								color: Color.base3.toHex(),
							},
							value: description,
						}),
					}),
				),
			),
			Card(
				{
					style:
					{
						paddingBottom: IS_IOS ? device.screen.safeArea.bottom : Indent.L.toNumber(),
					},
				},
				buttonsView,
			),
		);
	}

	module.exports = { BannerTemplate, showConfirm };
});
