/**
 * @module tasks/layout/checklist/list/src/buttons/button-add-checklist
 */
jn.define('tasks/layout/checklist/list/src/buttons/button-add-checklist', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Color } = require('tokens');

	/**
	 * @function buttonAddCheckList
	 */
	const buttonAddCheckList = (props) => {
		const { isDisabled, onClick } = props;

		return View(
			{
				testId: 'checklist-addChecklist-btn',
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
					opacity: isDisabled ? 0.5 : 1,
					width: '100%',
					background: Color.bgContentPrimary.toHex(),
					borderRadius: 6,
					borderWidth: 1,
					borderColor: Color.bgSeparatorSecondary.toHex(),
				},
			},
			BBCodeText({
				style: {
					fontSize: 16,
					fontWeight: '400',
					color: Color.base3,
				},
				value: `[d type=dot color=#828B95]${Loc.getMessage('TASKSMOBILE_LAYOUT_CHECKLIST_ADD_TEXT')}[/d]`,
			}),
		);
	};

	module.exports = { buttonAddCheckList };
});
