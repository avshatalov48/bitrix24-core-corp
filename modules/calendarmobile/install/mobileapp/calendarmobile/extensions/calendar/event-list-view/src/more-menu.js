/**
 * @module calendar/event-list-view/more-menu
 */
jn.define('calendar/event-list-view/more-menu', (require, exports, module) => {
	const { Color } = require('tokens');
	const { Loc } = require('loc');
	const { Icon } = require('ui-system/blocks/icon');

	const { SettingsPage } = require('calendar/event-list-view/settings-page');
	const { SyncManager } = require('calendar/data-managers/sync-manager');
	const { State } = require('calendar/event-list-view/state');
	const { CalendarType, Counters } = require('calendar/enums');

	class MoreMenu
	{
		constructor(props)
		{
			this.props = props;
			this.counters = props.counters;
			this.menu = null;
			this.settingsPage = null;
		}

		showMenu = () => {
			if (!this.menu)
			{
				this.menu = dialogs.createPopupMenu();
			}

			this.menu.setData(this.getItems(), this.getSections(), (event, item) => {
				if (event === 'onItemSelected')
				{
					this.onItemSelected(item);
				}
			});

			this.menu.show();
		};

		getItems()
		{
			const items = [];

			if (!env.extranet && State.calType === CalendarType.USER)
			{
				items.push(this.getSyncItem());
			}

			items.push(this.getSettingsItem());

			return items;
		}

		getSyncItem()
		{
			return {
				id: itemTypes.sync,
				sectionCode: sectionTypes.base,
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
				sectionCode: sectionTypes.base,
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

		getSections()
		{
			return [
				{
					id: sectionTypes.base,
					title: '',
				},
			];
		}

		onItemSelected(item)
		{
			const menuItemId = item.id;

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
				testId: 'calendar-more',
				callback: this.showMenu,
				dot: this.hasCountersValue(),
			};
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
	}

	const itemTypes = {
		sync: 'sync',
		settings: 'settings',
	};

	const sectionTypes = {
		base: 'base',
	};

	module.exports = { MoreMenu };
});
