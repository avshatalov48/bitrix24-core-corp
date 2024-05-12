/**
 * @module tasks/layout/checklist/list/src/buttons/button-add-item
 */
jn.define('tasks/layout/checklist/list/src/buttons/button-add-item', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Color } = require('tokens');
	const { IconView, iconTypes } = require('ui-system/blocks/icon');
	const { PropTypes } = require('utils/validation');
	const { ChecklistItemView } = require('tasks/layout/checklist/list/src/layout/item-view');

	const ICON_SIZE = 24;
	const IS_IOS = Application.getPlatform() === 'ios';

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
			testId: buttonAddItemType.type,
			style: {
				justifyContent: 'center',
				paddingBottom: IS_IOS ? 0 : 12,
			},
			onClick,
			children: [
				View(
					{
						style: {
							flexDirection: 'row',
						},
					},
					IconView({
						icon: iconTypes.outline.plus,
						iconColor: Color.base4,
						iconSize: ICON_SIZE,
					}),
					Text({
						style: {
							marginLeft: 10,
							fontSize: 16,
							color: Color.base4,
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
