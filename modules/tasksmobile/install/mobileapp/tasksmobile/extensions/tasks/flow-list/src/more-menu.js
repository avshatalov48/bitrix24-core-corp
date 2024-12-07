/**
 * @module tasks/flow-list/src/more-menu
 */
jn.define('tasks/flow-list/src/more-menu', (require, exports, module) => {
	const { Loc } = require('loc');
	const { BaseListMoreMenu } = require('layout/ui/list/base-more-menu');
	const { TasksFlowListFilter } = require('tasks/flow-list/src/filter');
	const { Color } = require('tokens');
	const { Views } = require('tasks/statemanager/redux/types');
	const { Feature } = require('feature');

	const airStyleSupported = Feature.isAirStyleSupported();

	const iconPrefix = `${currentDomain}/bitrix/mobileapp/tasksmobile/extensions/tasks/flow-list/images/more-menu-`;

	/**
	 * @class TasksFlowListMoreMenu
	 */
	class TasksFlowListMoreMenu extends BaseListMoreMenu
	{
		get icons()
		{
			return {
				[TasksFlowListFilter.counterType.expired]: `${iconPrefix}expired.png`,
				[TasksFlowListFilter.counterType.newComments]: `${iconPrefix}new-comments.png`,
			};
		}

		/**
		 * @param {Array} counters
		 * @param {String} selectedCounter
		 * @param {String} selectedSorting
		 * @param {Object} callbacks
		 */
		constructor(
			counters,
			selectedCounter,
			selectedSorting,
			callbacks = {},
		)
		{
			super(counters, selectedCounter, selectedSorting, callbacks);

			this.onReadAllClick = callbacks.onReadAllClick;
			this.getSelectedView = callbacks.getSelectedView;
		}

		/**
		 * @public
		 * @returns {{svg: {content: string}, callback: ((function(): void)|*), type: string}}
		 */
		getMenuButton()
		{
			return {
				type: 'more',
				id: 'task-flow-list-more',
				testId: 'task-flow-list-more',
				dot: this.hasCountersValue(),
				callback: this.openMoreMenu,
				accent: this.isCounterSelected(),
			};
		}

		isCounterSelected()
		{
			return (
				this.selectedCounter === TasksFlowListFilter.counterType.expired
				|| this.selectedCounter === TasksFlowListFilter.counterType.newComments
			);
		}

		/**
		 * @private
		 * @returns {string|null}
		 */
		getMenuBackgroundColor()
		{
			return (this.isCounterSelected() ? Color.accentMainPrimary.toHex() : null);
		}

		/**
		 * @private
		 * @returns {boolean}
		 */
		hasCountersValue()
		{
			return (
				this.counters[TasksFlowListFilter.counterType.expired]
				+ this.counters[TasksFlowListFilter.counterType.newComments] > 0
			);
		}

		/**
		 * @private
		 * @returns {array}
		 */
		getMenuItems()
		{
			return [
				this.createMenuItem({
					id: TasksFlowListFilter.counterType.expired,
					title: Loc.getMessage('TASKSMOBILE_FLOW_LIST_MORE_MENU_EXPIRED'),
					counterColor: Color.accentMainAlert.toHex(),
					sectionCode: 'counters',
					sectionTitle: Loc.getMessage('TASKSMOBILE_FLOW_LIST_MORE_MENU_MY_COUNTER_TITLE'),
					showIcon: !airStyleSupported,
				}),
				this.createMenuItem({
					id: TasksFlowListFilter.counterType.newComments,
					title: Loc.getMessage('TASKSMOBILE_FLOW_LIST_MORE_MENU_NEW_COMMENTS'),
					counterColor: Color.accentMainSuccess.toHex(),
					sectionCode: 'counters',
					showIcon: !airStyleSupported,
				}),
			].filter(Boolean);
		}

		/**
		 * @private
		 * @return {null|string}
		 */
		getHelpdeskArticleCode()
		{
			const selectedView = this.getSelectedView();

			switch (selectedView)
			{
				case Views.KANBAN:
					return '19507218';

				case Views.PLANNER:
					return '19143166';

				case Views.DEADLINE:
					return '19134714';

				default:
					return null;
			}
		}

		/**
		 * @private
		 * @param event
		 * @param item
		 */
		onMenuItemSelected(event, item)
		{
			switch (item.id)
			{
				case TasksFlowListFilter.counterType.expired:
				case TasksFlowListFilter.counterType.newComments:
					this.onCounterClick(item.id);
					break;
				default:
					break;
			}
		}
	}

	module.exports = { TasksFlowListMoreMenu };
});
