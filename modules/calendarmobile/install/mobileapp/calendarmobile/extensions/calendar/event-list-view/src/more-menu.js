/**
 * @module calendar/event-list-view/more-menu
 */
jn.define('calendar/event-list-view/more-menu', (require, exports, module) => {
	const { Color } = require('tokens');
	const { Loc } = require('loc');
	const { Icon } = require('ui-system/blocks/icon');

	const { SectionListPage } = require('calendar/event-list-view/section-list/page');
	const { SettingsPage } = require('calendar/event-list-view/settings-page');
	const { SyncManager } = require('calendar/data-managers/sync-manager');
	const { State } = require('calendar/event-list-view/state');
	const { CalendarType, Counters } = require('calendar/enums');
	const { BaseMenu, baseSectionType } = require('calendar/base-menu');

	class MoreMenu extends BaseMenu
	{
		constructor(props)
		{
			super(props);

			this.counters = props.counters;
			this.settingsPage = null;
			this.sectionListPage = null;
		}

		getItems()
		{
			const items = [];

			if (this.shouldShowSyncItem())
			{
				items.push(this.getSyncItem());
			}

			items.push(this.getSectionListItem());

			if (this.shouldShowSettingsItem())
			{
				items.push(this.getSettingsItem());
			}

			return items;
		}

		getSectionListItem()
		{
			return {
				id: itemTypes.sectionList,
				sectionCode: baseSectionType,
				testId: 'calendar-more-menu-calendar-list',
				title: Loc.getMessage('M_CALENDAR_EVENT_LIST_SECTION_LIST_TITLE'),
				iconName: Icon.CALENDAR_WITH_CHECKS.getIconName(),
				styles: {
					icon: {
						color: Color.base3.toHex(),
					},
				},
			};
		}

		getSyncItem()
		{
			return {
				id: itemTypes.sync,
				sectionCode: baseSectionType,
				testId: 'calendar-more-menu-sync',
				title: Loc.getMessage('M_CALENDAR_EVENT_LIST_MORE_MENU_SYNC'),
				counterValue: this.counters[Counters.SYNC_ERRORS],
				counterStyle: { backgroundColor: Color.accentMainAlert.toHex() },
				iconName: Icon.REFRESH.getIconName(),
				styles: {
					icon: {
						color: SyncManager.getSyncItemIconColor(),
					},
				},
			};
		}

		getSettingsItem()
		{
			return {
				id: itemTypes.settings,
				sectionCode: baseSectionType,
				testId: 'calendar-more-menu-settings',
				title: Loc.getMessage('M_CALENDAR_EVENT_LIST_MORE_MENU_SETTINGS'),
				iconName: Icon.SETTINGS.getIconName(),
				styles: {
					icon: {
						color: Color.base3.toHex(),
					},
				},
			};
		}

		onItemSelected(item)
		{
			const menuItemId = item.id;

			if (menuItemId === itemTypes.sectionList)
			{
				return this.openSectionListPage();
			}

			if (menuItemId === itemTypes.sync)
			{
				return SyncManager.openSyncPage();
			}

			if (menuItemId === itemTypes.settings)
			{
				return this.openSettingsPage();
			}

			return false;
		}

		getMenuButton()
		{
			return {
				type: Icon.MORE.getIconName(),
				id: 'calendar-more',
				badgeCode: 'calendar-more',
				testId: 'calendar-more',
				callback: () => this.showMenu(),
				dot: this.hasCountersValue(),
			};
		}

		showMenu()
		{
			this.show(null);
		}

		openSectionListPage()
		{
			this.sectionListPage = new SectionListPage();

			this.sectionListPage.show(this.props.layout);
		}

		openSettingsPage()
		{
			this.settingsPage ??= new SettingsPage();

			this.settingsPage.show(this.props.layout);
		}

		hasCountersValue()
		{
			return Boolean(this.counters[Counters.SYNC_ERRORS]);
		}

		setCounters(counters)
		{
			this.counters = counters;
		}

		shouldShowSyncItem()
		{
			return !env.extranet
				&& State.calType === CalendarType.USER
				&& State.ownerId === Number(env.userId)
			;
		}

		shouldShowSettingsItem()
		{
			return State.calType === CalendarType.USER
				&& State.ownerId === Number(env.userId)
			;
		}
	}

	const itemTypes = {
		sync: 'sync',
		sectionList: 'section-list',
		settings: 'settings',
	};

	module.exports = { MoreMenu };
});
