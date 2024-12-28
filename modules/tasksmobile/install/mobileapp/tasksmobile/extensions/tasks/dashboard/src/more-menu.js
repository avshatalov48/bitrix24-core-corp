/**
 * @module tasks/dashboard/src/more-menu
 */
jn.define('tasks/dashboard/src/more-menu', (require, exports, module) => {
	const { UIMenuType } = require('layout/ui/menu');
	const { BaseListMoreMenu } = require('layout/ui/list/base-more-menu');
	const { TasksDashboardFilter } = require('tasks/dashboard/filter');
	const { TasksDashboardSorting } = require('tasks/dashboard/src/sorting');
	const { Feature } = require('feature');
	const { Color } = require('tokens');
	const { Views } = require('tasks/statemanager/redux/types');
	const { Loc } = require('tasks/loc');
	const { AnalyticsEvent } = require('analytics');

	const airStyleSupported = Feature.isAirStyleSupported();

	let iconPrefix = `${currentDomain}/bitrix/mobileapp/tasksmobile/extensions/tasks/dashboard/images/more-menu-`;

	if (airStyleSupported)
	{
		iconPrefix += 'outline-';
	}

	/**
	 * @class TasksDashboardMoreMenu
	 */
	class TasksDashboardMoreMenu extends BaseListMoreMenu
	{
		get icons()
		{
			return {
				[TasksDashboardFilter.counterType.expired]: airStyleSupported ? null : `${iconPrefix}expired.png`,
				[TasksDashboardFilter.counterType.newComments]: airStyleSupported ? null : `${iconPrefix}new-comments.png`,
				sortByActivity: `${iconPrefix}sort-by-activity.png`,
				sortByDeadline: `${iconPrefix}sort-by-deadline.png`,
				readAll: `${iconPrefix}read-all.png`,
				[Views.DEADLINE]: `${iconPrefix}view-deadline.png`,
				[Views.KANBAN]: `${iconPrefix}view-kanban.png`,
				[Views.LIST]: `${iconPrefix}view-list.png`,
				[Views.PLANNER]: `${iconPrefix}view-planner.png`,
			};
		}

		/**
		 * @param {Array} counters
		 * @param {String} selectedCounter
		 * @param {String} selectedSorting
		 * @param {Object} callbacks
		 * @param {Object} analyticsLabel
		 */
		constructor(
			counters,
			selectedCounter,
			selectedSorting,
			callbacks = {},
			analyticsLabel = {},
		)
		{
			super(counters, selectedCounter, selectedSorting, callbacks);

			this.onReadAllClick = callbacks.onReadAllClick;
			this.getSelectedView = callbacks.getSelectedView;
			this.getOwnerId = callbacks.getOwnerId;
			this.getProjectId = callbacks.getProjectId;

			this.openViewSwitcher = callbacks.openViewSwitcher;
			this.onListClick = callbacks.onListClick;
			this.onKanbanClick = callbacks.onKanbanClick;
			this.onPlannerClick = callbacks.onPlannerClick;
			this.onDeadlineClick = callbacks.onDeadlineClick;
			this.analyticsLabel = analyticsLabel;
		}

		/**
		 * @public
		 * @returns {{svg: {content: string}, callback: ((function(): void)|*), type: string}}
		 */
		getMenuButton()
		{
			return {
				type: 'more',
				id: 'button_more',
				testId: 'button_more',
				dot: this.hasCountersValue(),
				callback: this.openMoreMenu,
				accent: this.isCounterSelected(),
			};
		}

		isCounterSelected()
		{
			return (
				this.selectedCounter === TasksDashboardFilter.counterType.expired
				|| this.selectedCounter === TasksDashboardFilter.counterType.newComments
			);
		}

		/**
		 * @private
		 * @returns {bool|null}
		 */
		hasCountersValue()
		{
			return (
				this.counters[TasksDashboardFilter.counterType.expired]
				+ this.counters[TasksDashboardFilter.counterType.newComments] > 0
			);
		}

		/**
		 * @private
		 * @returns {array}
		 */
		getMenuItems()
		{
			const articleCode = this.getHelpdeskArticleCode();


			const viewSwitcherNextMenu = {
				items: this.getViewSwitcherMenuItems(),
				sections: [
					{ id: 'changeView' },
				],
				title: Loc.getMessage('M_TASKS_BACK'),
			};

			return [
				this.createMenuItem({
					id: TasksDashboardFilter.counterType.expired,
					title: Loc.getMessage('TASKSMOBILE_TASK_VIEW_ROUTER_MORE_MENU_EXPIRED'),
					counterColor: Color.accentMainAlert.toHex(),
					sectionCode: 'counters',
					sectionTitle: (
						Number(this.getOwnerId()) === Number(env.userId)
							? Loc.getMessage('TASKSMOBILE_DASHBOARD_MORE_MENU_MY_COUNTER_TITLE')
							: Loc.getMessage('TASKSMOBILE_DASHBOARD_MORE_MENU_COUNTER_TITLE')
					),
					showIcon: !airStyleSupported,
				}),
				this.createMenuItem({
					id: TasksDashboardFilter.counterType.newComments,
					title: Loc.getMessage('TASKSMOBILE_TASK_VIEW_ROUTER_MORE_MENU_NEW_COMMENTS'),
					counterColor: Color.accentMainSuccess.toHex(),
					sectionCode: 'counters',
					showIcon: !airStyleSupported,
				}),
				this.createMenuItem({
					id: 'sortByActivity',
					title: Loc.getMessage('TASKSMOBILE_TASK_VIEW_ROUTER_MORE_MENU_SORT_ACTIVITY_MSGVER_1'),
					checked: this.selectedSorting === 'ACTIVITY',
					showTopSeparator: true,
					sectionCode: 'sorting',
					sectionTitle: Loc.getMessage('TASKSMOBILE_DASHBOARD_MORE_MENU_SORT_TITLE'),
				}),
				this.createMenuItem({
					id: 'sortByDeadline',
					title: Loc.getMessage('TASKSMOBILE_TASK_VIEW_ROUTER_MORE_MENU_SORT_DEADLINE'),
					checked: this.selectedSorting === 'DEADLINE',
					sectionCode: 'sorting',
				}),
				this.createMenuItem({
					id: 'readAll',
					title: Loc.getMessage('TASKSMOBILE_TASK_VIEW_ROUTER_MORE_MENU_READ_ALL'),
					showTopSeparator: true,
					sectionCode: 'settings',
				}),
				this.createMenuItem({
					id: 'view-switcher',
					title: Loc.getMessage('M_TASKS_VIEW_ROUTER_MENU_TITLE'),
					iconUrl: this.icons[this.getSelectedView()],
					showTopSeparator: true,
					sectionCode: 'changeView',
					nextMenu: airStyleSupported ? viewSwitcherNextMenu : null,
				}),
				articleCode && {
					type: UIMenuType.HELPDESK,
					data: { articleCode },
					sectionCode: 'settings',
				},
			].filter(Boolean);
		}

		getViewSwitcherMenuItems = () => {
			return Object.values(Views)
				.filter((view) => !(view === Views.KANBAN && this.getProjectId() === null))
				.map((view) => ({
					id: view,
					title: Loc.getMessage(`M_TASKS_VIEW_ROUTER_MENU_TITLE_${view}`),
					checked: this.getSelectedView() === view,
					showCheckedIcon: false,
					sectionCode: 'changeView',
				}))
				.map((item) => this.createMenuItem(item));
		};

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
		onMenuItemSelected = (event, item) => {
			const analyticsEvent = new AnalyticsEvent({
				...this.analyticsLabel,
				tool: 'tasks',
				category: 'task_operations',
				type: 'task',
				status: 'success',
				c_sub_section: this.getSelectedView()?.toLowerCase(),
			});

			switch (item.id)
			{
				case TasksDashboardFilter.counterType.expired:
					analyticsEvent
						.setEvent('overdue_counters_on')
						.setElement('overdue_counters_filter')
						.send();

					this.onCounterClick(item.id);
					break;

				case TasksDashboardFilter.counterType.newComments:
					analyticsEvent
						.setEvent('comments_counters_on')
						.setElement('comments_counters_filter')
						.send();

					this.onCounterClick(item.id);
					break;

				case 'sortByActivity':
					this.onSortingClick(TasksDashboardSorting.types.ACTIVITY);
					break;

				case 'sortByDeadline':
				{
					this.onSortingClick(TasksDashboardSorting.types.DEADLINE);
					break;
				}

				case 'readAll':
					this.onReadAllClick();
					break;

				case 'view-switcher':
				{
					if (!airStyleSupported)
					{
						this.openViewSwitcher();
					}
					break;
				}

				case Views.LIST:
					this.onListClick();
					break;

				case Views.KANBAN:
					this.onKanbanClick();
					break;

				case Views.PLANNER:
					this.onPlannerClick();
					break;

				case Views.DEADLINE:
					this.onDeadlineClick();
					break;

				default:
					break;
			}
		};
	}

	module.exports = { TasksDashboardMoreMenu };
});
