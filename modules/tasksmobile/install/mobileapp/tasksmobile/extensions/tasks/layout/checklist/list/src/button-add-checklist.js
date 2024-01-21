/**
 * @module tasks/layout/checklist/list/src/button-add-checklist
 */
jn.define('tasks/layout/checklist/list/src/button-add-checklist', (require, exports, module) => {
	const { Loc } = require('loc');
	const AppTheme = require('apptheme');
	/**
	 * @function buttonAddCheckList
	 */
	const buttonAddCheckList = (props) => {
		const { isDisabled, onClick } = props;

		return View(
			{
				testId: 'checklist-add-btn',
				onClick: () => {
					if (!isDisabled && onClick)
					{
						onClick();
					}
				},
				style: {
					flexDirection: 'row',
					justifyContent: 'space-between',
					paddingVertical: 14,
					// paddingHorizontal: 11,
					// minHeight: 52,
					// marginTop: 6,
					opacity: isDisabled ? 0.5 : 1,
					width: '100%',
					background: AppTheme.colors.bgContentPrimary,
					borderRadius: 6,
					borderWidth: 1,
					borderColor: AppTheme.colors.bgSeparatorSecondary,
				},
			},
			BBCodeText({
				style: {
					fontSize: 16,
					fontWeight: '400',
					color: AppTheme.colors.base3,
				},
				value: `[d type=dot color=#828B95]${Loc.getMessage('TASKSMOBILE_LAYOUT_CHECKLIST_ADD_TEXT')}[/d]`,
			}),
		);
	};

	module.exports = { buttonAddCheckList };
});

