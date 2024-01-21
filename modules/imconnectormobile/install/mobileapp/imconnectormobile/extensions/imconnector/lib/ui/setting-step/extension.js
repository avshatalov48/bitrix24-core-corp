/**
 * @module imconnector/lib/ui/setting-step
 */
jn.define('imconnector/lib/ui/setting-step', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Type } = require('type');
	const AppTheme = require('apptheme');

	/**
	 * @param {SettingStepProps} props
	 * @constructor
	 */
	function SettingStep(props)
	{
		const additionalComponents = Type.isArrayFilled(props.additionalComponents)
			? props.additionalComponents
			: []
		;
		const linksUnderline = BX.prop.getBoolean(props, 'linksUnderline', true);
		const onLinkClick = BX.prop.getFunction(props, 'onLinkClick', null);

		return View(
			{
				style: {
					backgroundColor: AppTheme.colors.bgContentPrimary,
					borderRadius: 12,
					flexDirection: 'row',
					alignItems: 'center',
					marginBottom: 12,
					paddingTop: 21,
					paddingLeft: 16,
					paddingBottom: 24,
					paddingRight: 64,
				},

			},
			Image({
				style: {
					alignSelf: 'flex-start',
					width: 34,
					height: 34,
					marginRight: 16,
				},
				resizeMode: 'center',
				svg: {
					content: props.icon,
				},
			}),
			View(
				{
					style: {
						flexDirection: 'column',
					},
				},
				View(
					{
						style: {
							flexDirection: 'row',
							marginBottom: 6,
						},
					},
					props.withStep
						? Text({
							style: {
								color: AppTheme.colors.accentSoftElementBlue1,
								fontSize: 16,
								fontWeight: '500',
								numberOfLines: 1,
								ellipsize: 'end',
							},
							text: Loc.getMessage('IMCONNECTORMOBILE_SETTING_STEP_STEP')
								.replace('#NUMBER#', props.number.toString()),
						})
						: null,
					Text(
						{
							style: {
								color: AppTheme.colors.base1,
								fontSize: 16,
								fontWeight: '500',
								numberOfLines: 1,
								ellipsize: 'end',
							},
							text: props.title,
						},
					),
				),
				View(
					{
						style: {
							flexDirection: 'row',
							marginBottom: 13,
						},
					},
					BBCodeText({
						style: {
							width: '100%',
							color: AppTheme.colors.base3,
							fontSize: 13,
							numberOfLines: 0,
						},
						value: props.description,
						linksUnderline,
						onLinkClick,
					}),
				),
				View(
					{
						style: {
							flexDirection: 'column',
						},
					},
					...additionalComponents,
				),
			),
		);
	}

	module.exports = { SettingStep };
});
