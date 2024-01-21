/**
 * @module tasks/dashboard/src/more-menu
 */
jn.define('tasks/dashboard/src/more-menu', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { downloadImages } = require('asset-manager');
	const { TaskFilter } = require('tasks/filter/task');
	const { moreWithDot } = require('assets/common');
	const { Loc } = require('loc');
	const { Sorting } = require('tasks/dashboard/src/sorting');

	const iconPrefix = `${currentDomain}/bitrix/mobileapp/tasksmobile/extensions/tasks/dashboard/images/more-menu-`;
	const Icons = {
		[TaskFilter.counterType.expired]: `${iconPrefix}expired.png`,
		[TaskFilter.counterType.newComments]: `${iconPrefix}new-comments.png`,
		sortByActivity: `${iconPrefix}sort-by-activity.png`,
		sortByDeadline: `${iconPrefix}sort-by-deadline.png`,
		readAll: `${iconPrefix}read-all.png`,
	};

	class MoreMenu
	{
		constructor(
			counters,
			selectedCounter,
			selectedSorting,
			callbacks = {},
		)
		{
			this.counters = counters;
			this.selectedCounter = selectedCounter;
			this.selectedSorting = selectedSorting;

			this.menu = null;

			this.openMoreMenu = this.openMoreMenu.bind(this);

			this.onCounterClick = callbacks.onCounterClick;
			this.onSortingClick = callbacks.onSortingClick;
			this.onReadAllClick = callbacks.onReadAllClick;

			setTimeout(() => this.prefetchAssets(), 1000);
		}

		prefetchAssets()
		{
			const icons = Object.values(Icons).filter((icon) => icon !== null);

			void downloadImages(icons);
		}

		/**
		 * @public
		 * @returns {{svg: {content: string}, callback: ((function(): void)|*), type: string}}
		 */
		getMenuButton()
		{
			return {
				type: 'more',
				callback: this.openMoreMenu,
				svg: {
					content: moreWithDot(AppTheme.colors.base4, this.getMenuBackgroundColor(), this.getMenuDotColor()),
				},
			};
		}

		/**
		 * @private
		 * @returns {string|null}
		 */
		getMenuBackgroundColor()
		{
			const isCounterSelected = (
				this.selectedCounter === TaskFilter.counterType.expired
				|| this.selectedCounter === TaskFilter.counterType.newComments
			);

			return (isCounterSelected ? AppTheme.colors.accentBrandBlue : null);
		}

		/**
		 * @private
		 * @returns {string|null}
		 */
		getMenuDotColor()
		{
			const hasCountersValue = (
				this.counters[TaskFilter.counterType.expired] + this.counters[TaskFilter.counterType.newComments] > 0
			);

			return (hasCountersValue ? AppTheme.colors.accentMainAlert : null);
		}

		/**
		 * @private
		 */
		openMoreMenu()
		{
			let menuItems = [
				{
					id: TaskFilter.counterType.expired,
					title: Loc.getMessage('TASKSMOBILE_TASK_VIEW_ROUTER_MORE_MENU_EXPIRED'),
					iconUrl: Icons[TaskFilter.counterType.expired],
					sectionCode: 'default',
					checked: (this.selectedCounter === TaskFilter.counterType.expired),
					showCheckedIcon: false,
					counterValue: this.counters[TaskFilter.counterType.expired],
					counterStyle: {
						backgroundColor: AppTheme.colors.accentMainAlert,
					},
					onItemSelected: this.onMenuItemSelected.bind(this),
				},
				{
					id: TaskFilter.counterType.newComments,
					title: Loc.getMessage('TASKSMOBILE_TASK_VIEW_ROUTER_MORE_MENU_NEW_COMMENTS'),
					iconUrl: Icons[TaskFilter.counterType.newComments],
					sectionCode: 'default',
					checked: (this.selectedCounter === TaskFilter.counterType.newComments),
					showCheckedIcon: false,
					counterValue: this.counters[TaskFilter.counterType.newComments],
					counterStyle: {
						backgroundColor: AppTheme.colors.accentMainSuccess,
					},
					showTopSeparator: false,
					onItemSelected: this.onMenuItemSelected.bind(this),
				},
				{
					id: 'sortByActivity',
					title: Loc.getMessage('TASKSMOBILE_TASK_VIEW_ROUTER_MORE_MENU_SORT_ACTIVITY_MSGVER_1'),
					iconUrl: Icons.sortByActivity,
					sectionCode: 'default',
					checked: (this.selectedSorting === Sorting.type.ACTIVITY),
					showCheckedIcon: false,
					showTopSeparator: true,
					onItemSelected: this.onMenuItemSelected.bind(this),
				},
				{
					id: 'sortByDeadline',
					title: Loc.getMessage('TASKSMOBILE_TASK_VIEW_ROUTER_MORE_MENU_SORT_DEADLINE'),
					iconUrl: Icons.sortByDeadline,
					sectionCode: 'default',
					checked: (this.selectedSorting === Sorting.type.DEADLINE),
					showCheckedIcon: false,
					showTopSeparator: false,
					onItemSelected: this.onMenuItemSelected.bind(this),
				},
				{
					id: 'readAll',
					title: Loc.getMessage('TASKSMOBILE_TASK_VIEW_ROUTER_MORE_MENU_READ_ALL'),
					iconUrl: Icons.readAll,
					iconName: 'read',
					sectionCode: 'default',
					showTopSeparator: true,
					onItemSelected: this.onMenuItemSelected.bind(this),
				},
			];

			this.menu = new UI.Menu(menuItems);
			this.menu.show();
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
				case TaskFilter.counterType.expired:
				case TaskFilter.counterType.newComments:
					this.onCounterClick(item.id);
					break;

				case 'sortByActivity':
					this.onSortingClick(Sorting.type.ACTIVITY);
					break;

				case 'sortByDeadline':
					this.onSortingClick(Sorting.type.DEADLINE);
					break;

				case 'readAll':
					this.onReadAllClick();
					break;

				default:
					break;
			}
		}

		/**
		 * @public
		 * @param counters
		 */
		setCounters(counters)
		{
			this.counters = counters;
		}

		/**
		 * @public
		 * @param counter
		 */
		setSelectedCounter(counter)
		{
			this.selectedCounter = counter;
		}

		/**
		 * @public
		 * @param sorting
		 */
		setSelectedSorting(sorting)
		{
			this.selectedSorting = sorting;
		}
	}

	module.exports = { MoreMenu };
});
