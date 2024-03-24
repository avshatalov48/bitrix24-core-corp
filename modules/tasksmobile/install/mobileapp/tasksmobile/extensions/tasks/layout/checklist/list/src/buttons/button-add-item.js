/**
 * @module tasks/layout/checklist/list/src/buttons/button-add-item
 */
jn.define('tasks/layout/checklist/list/src/buttons/button-add-item', (require, exports, module) => {
	const { Loc } = require('loc');
	const { outline: { plus } } = require('assets/icons');
	const { PropTypes } = require('utils/validation');
	const AppTheme = require('apptheme');
	const { ChecklistItemView } = require('tasks/layout/checklist/list/src/layout/item-view');

	const ICON_SIZE = 24;

	/**
	 * @object buttonAddItem
	 */
	const buttonAddItemType = {
		key: 'addChecklistItemButton',
		type: 'checklist-addItem-btn',
	};

	/**
	 * @function ButtonAdd
	 * @param {Object} [props]
	 * @param {function} [props.onClick]
	 * @return ChecklistItemView
	 */
	const ButtonAdd = (props = {}) => {
		const { onClick } = props;

		return ChecklistItemView({
			style: {
				justifyContent: 'center',
			},
			children: [
				View(
					{
						testId: buttonAddItemType.type,
						onClick,
						style: {
							flexDirection: 'row',
						},
					},
					Image({
						tintColor: AppTheme.colors.base4,
						style: {
							width: ICON_SIZE,
							height: ICON_SIZE,
						},
						svg: {
							content: plus(),
						},
					}),
					Text({
						style: {
							marginLeft: 10,
							fontSize: 16,
							color: AppTheme.colors.base4,
							fontWeight: '400',
						},
						text: Loc.getMessage('TASKSMOBILE_LAYOUT_CHECKLIST_ADD_ITEM_TEXT'),
					}),
				),
			],
		});
	};

	ButtonAdd.propTypes = {
		onClick: PropTypes.func,
	};

	module.exports = { ButtonAdd, buttonAddItemType };
});
