/**
 * @module bizproc/workflow/timeline/components/step/title
 * */

jn.define('bizproc/workflow/timeline/components/step/title', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Type } = require('type');

	/**
	 * @param {{
	 *     text: ?string,
	 *     testId: ?string,
	 *     button: ?{
	 *     		id: any,
	 *     		text: string,
	 *     		testId: ?string,
	 *     		onclick: (id: any) => void,
	 *     },
	 * }}
	 * @return {object}
	 */
	function Title({ text, testId, button })
	{
		const hasButton = Type.isObjectLike(button);

		return View(
			{
				style: {
					flexDirection: 'row',
					alignItems: 'center',
					marginBottom: 4,
					justifyContent: 'space-between',
				},
			},
			text && Text({
				testId,
				text: jnComponent.convertHtmlEntities(text),
				numberOfLines: 3,
				ellipsize: 'end',
				style: {
					paddingTop: hasButton ? 5 : null,
					alignSelf: 'flex-start',
					flexShrink: 2,
					fontSize: 15,
					fontWeight: '600',
					color: AppTheme.colors.base1,
				},
			}),
			// title button + counter
			hasButton && View(
				{
					style: {
						height: 36,
						justifyContent: 'center',
						marginLeft: 15,
					},
				},
				// button
				View(
					{
						testId: button.testId,
						style: {
							width: 110,
							height: 22,
							justifyContent: 'center',
							alignItems: 'center',
							borderStyle: 'solid',
							borderWidth: 1.2,
							borderColor: AppTheme.colors.accentMainPrimary,
							opacity: button.readOnly ? 0.4 : 1,
							borderRadius: 24,
						},
						onClick()
						{
							button.onclick(button.id);
						},
					},
					Text({
						text: button.text,
						style: {
							fontSize: 12,
							fontWeight: '500',
							color: AppTheme.colors.accentMainPrimary,
						},
					}),
				),
				// counter
				Text(
					{
						style: {
							position: 'absolute',
							top: 0,
							right: 0,
							width: 18,
							height: 18,
							borderRadius: 9,
							backgroundColor: AppTheme.colors.accentMainAlert,
							textAlign: 'center',
							color: AppTheme.colors.baseWhiteFixed,
							fontSize: 12,
							fontWeight: '500',
							opacity: button.readOnly ? 0.4 : 1,
						},
						text: '1',
					},
				),
			),
		);
	}

	module.exports = {
		Title,
	};
});
