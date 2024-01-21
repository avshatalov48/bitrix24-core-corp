/**
 * @module tasks/layout/checklist/list/src/add-button
 */
jn.define('tasks/layout/checklist/list/src/add-button', (require, exports, module) => {
	const { Loc } = require('loc');
	const AppTheme = require('apptheme');
	const { pathToExtension } = require('tasks/layout/checklist/list/src/constants');

	/**
	 * @function addButton
	 */
	const addButton = (props) => {
		const { nodeId, isDisabled, onClick, isEmpty } = props;

		return View(
			{
				testId: `list-add-item-btn-${nodeId}`,
				onClick: () => {
					if (!isDisabled && onClick)
					{
						onClick();
					}
				},
				style: {
					flexDirection: 'row',
					// height: 52,
					opacity: isDisabled ? 0.5 : 1,
					// paddingHorizontal: 11,
					borderTopWidth: isEmpty ? 1 : 0,
					borderTopColor: AppTheme.colors.bgSeparatorSecondary,
				},

			},
			Image({
				style: {
					marginVertical: 19,
					marginLeft: 4,
					marginRight: 13,
					width: 14,
					height: 14,
				},
				svg: {
					uri: `${pathToExtension}images/add.svg`,
				},
			}),
			Text({
				text: Loc.getMessage('TASKSMOBILE_LAYOUT_CHECKLIST_ADD_ITEM_TEXT'),
				style: {
					fontSize: 16,
					fontWeight: '400',
					color: AppTheme.colors.base3,
					textAlignVertical: 'center',
				},
			}),
		);
	};

	module.exports = { addButton };
});

