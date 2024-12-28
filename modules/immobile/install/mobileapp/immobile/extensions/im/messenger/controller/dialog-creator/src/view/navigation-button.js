/**
 * @module im/messenger/controller/dialog-creator/navigation-button
 */
jn.define('im/messenger/controller/dialog-creator/navigation-button', (require, exports, module) => {
	const { Theme } = require('im/lib/theme');
	const { withPressed } = require('utils/color');
	const { Loc } = require('loc');
	const { BadgeCounter, BadgeCounterDesign } = require('ui-system/blocks/badges/counter');
	const { Text5 } = require('ui-system/typography/text');
	const { Color } = require('tokens');

	function navigationButton({ iconSvg, text, subtitle, onClick, withSeparator, testId = '', isNew = false })
	{
		return View(
			{
				testId,
				style: {
					flexDirection: 'row',
					paddingLeft: 18,
					backgroundColor: withPressed(Theme.colors.bgContentPrimary),
				},
				clickable: true,
				onClick,
			},
			View(
				{
					style: {
						alignItems: 'center',
						justifyContent: 'center',
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
						flexDirection: 'column',
						justifyContent: 'center',
						flexGrow: 1,
						paddingVertical: 15,
						marginLeft: 12,
						borderBottomWidth: withSeparator ? 1 : 0,
						borderBottomColor: withSeparator ? Theme.colors.bgSeparatorPrimary : null,
					},
				},
				View(
					{
						style: {
							justifyContent: 'flex-start',
							alignItems: 'center',
							flexDirection: 'row',
							flexGrow: 1,
						},
					},
					Text({
						text,
						style: {
							color: Theme.colors.base1,
							fontSize: 18,
						},
					}),
					isNew && BadgeCounter({
						value: Loc.getMessage('IMMOBILE_DIALOG_CREATOR_BRAND_NEW_LABEL'),
						testId: `${testId}_badge`,
						showRawValue: true,
						design: BadgeCounterDesign.COLLAB_SUCCESS, // todo use proper tokens
						style: {
							marginLeft: 4,
							top: 1,
						},
					}),
				),
				subtitle && Text5({
					text: subtitle.replaceAll('#BR#', '\n'),
					color: Color.base3,
					numberOfLines: 2,
					style: {
						marginTop: 2,
					},
				}),
			),
		);
	}

	module.exports = { navigationButton };
});
