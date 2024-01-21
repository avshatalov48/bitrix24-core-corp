/**
 * @module tasks/layout/checklist/list/src/menu
 */
jn.define('tasks/layout/checklist/list/src/menu', (require, exports, module) => {
	const { Loc } = require('loc');
	const AppTheme = require('apptheme');
	const { SlidingButtonList } = require('layout/ui/button-list');
	const { PureComponent } = require('layout/pure-component');
	const { files, checklist, fire, shiftRight, shiftLeft, user } = require('assets/icons');
	const { chevronDown } = require('assets/common');
	const { directions } = require('tasks/layout/checklist/list/src/constants');

	/**
	 * @class CheckListMenu
	 */
	class CheckListMenu extends PureComponent
	{
		render()
		{
			return View(
				{
					style: {
						marginTop: 8,
					},
				},
				SlidingButtonList({
					buttonsData: this.getButtons(),
				}),
			);
		}

		getButtons()
		{
			const {
				item,
				onMoveToCheckList,
				onAddFile,
				onTabMove,
				onToggleImportant,
				openUserSelectionManager,
			} = this.props;
			const { canUpdate, canAdd, canTabOut, canTabIn, hasAnotherCheckLists } = this.getPermissions();

			return [
				{
					id: 'files',
					isDisabled: !canUpdate,
					mainSvgIcon: files(),
					onClick: onAddFile,
				},
				{
					id: 'shiftButtons',
					content: [
						{
							id: 'shiftRight',
							mainSvgIcon: shiftRight(),
							isDisabled: !canUpdate || !canTabIn,
							onClick: () => {
								onTabMove(item, directions.RIGHT);
							},
						},
						{
							id: 'shiftLeft',
							mainSvgIcon: shiftLeft(),
							isDisabled: !canUpdate || !canTabOut,
							onClick: () => {
								onTabMove(item, directions.LEFT);
							},
						},
					],
				},
				{
					id: 'members',
					mainSvgIcon: user(),
					additionalSvgIcon: chevronDown(AppTheme.colors.base4, { box: true }),
					content: Loc.getMessage('TASKSMOBILE_LAYOUT_CHECKLIST_MEMBERS'),
					onClick: openUserSelectionManager,
				},
				{
					id: 'important',
					mainSvgIcon: fire(),
					content: Loc.getMessage('TASKSMOBILE_LAYOUT_CHECKLIST_IMPORTANT'),
					isDisabled: !canUpdate,
					onClick: onToggleImportant,
				},
				{
					id: 'moveToCheckList',
					mainSvgIcon: checklist(),
					isDisabled: (canUpdate && !canAdd && !hasAnotherCheckLists) || !canUpdate,
					content: Loc.getMessage('TASKSMOBILE_LAYOUT_CHECKLIST_MOVE_TO'),
					onClick: onMoveToCheckList,
				},
			];
		}

		getPermissions()
		{
			const { item } = this.props;

			return {
				canTabOut: item.checkCanTabOut(),
				canTabIn: item.checkCanTabIn(),
				canAdd: item.checkCanAdd(),
				canUpdate: item.checkCanUpdate(),
				canAddAccomplice: item.checkCanAddAccomplice(),
				hasAnotherCheckLists: item.hasAnotherCheckLists(),
				canRemove: item.checkCanRemove(),
			};
		}
	}

	module.exports = { CheckListMenu };
});

