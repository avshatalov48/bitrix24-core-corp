/**
 * @module im/messenger/controller/dialog-creator/navigation-button
 */
jn.define('im/messenger/controller/dialog-creator/navigation-button', (require, exports, module) => {
	const { Theme } = require('im/lib/theme');
	const { withPressed } = require('utils/color');

	function navigationButton({ iconSvg, text, onClick, withSeparator, testId = '' })
	{
		return View(
			{
				testId,
				style: {
					flexDirection: 'row',
					paddingLeft: 18,
					backgroundColor: withPressed(Theme.colors.bgContentPrimary),
					height: 60,
				},
				clickable: true,
				onClick,
			},
			View(
				{
					style: {
						paddingTop: 10,
						paddingBottom: 10,
					},
				},
				Image({
					style: {
						width: 40,
						height: 40,
					},
					svg: {
						content: iconSvg,
					},
				}),
			),
			View(
				{
					style: {
						justifyContent: 'center',
						flex: 1,
						paddingTop: 10,
						paddingBottom: 10,
						marginLeft: 12,
						borderBottomWidth: withSeparator ? 1 : 0,
						borderBottomColor: withSeparator ? Theme.colors.bgSeparatorPrimary : null,
					},
				},
				Text({
					text,
					style: {
						color: Theme.colors.base1,
						fontSize: 18,
					},
				}),
			)
		);
	}

	module.exports = {navigationButton};
});