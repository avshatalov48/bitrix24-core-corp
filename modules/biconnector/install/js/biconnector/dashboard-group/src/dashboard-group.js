import { EventEmitter } from 'main.core.events';
import { Popup } from 'main.popup';
import { Loc } from 'main.core';
import { BitrixVue } from 'ui.vue3';
import { Store } from './store';
import { App } from './app';
import { type Group, GroupAppParams, GroupAppState, GroupType } from './type';

export class DashboardGroup
{
	static open(appParams: GroupAppParams = {})
	{
		const app = this.getApp(appParams);

		const popup = new Popup({
			id: 'biconnector-dashboard-group',
			width: 800,
			height: 560,
			overlay: true,
			closeIcon: false,
			autoHide: true,
			padding: 0,
			cacheable: false,
			content: '<div id="app"></div>',
			events: {
				onPopupClose: () => {
					EventEmitter.emit('BIConnector.Internal.GroupPopup:onPopupClose');

					app.unmount();
				},
			},
		});
		popup.show();
		app.mount('#app');
	}

	static getApp(appParams: GroupAppParams = {})
	{
		let group: Group = appParams.groups.find((item: Group) => item.id === appParams.groupId);
		if (!group)
		{
			group = {
				id: 'new_G0',
				name: Loc.getMessage('BI_GROUP_NAME_NEW'),
				type: GroupType.custom,
				scopes: [],
				dashboardIds: [],
			};
		}
		const state: GroupAppState = {
			group,
			otherGroups: appParams.groups.filter((item: Group) => item.id !== appParams.groupId),
			dashboards: appParams.dashboards,
			saveEnabled: appParams.saveEnabled,
			isLoading: false,
			user: appParams.user,
		};

		const app = BitrixVue.createApp({
			name: 'GroupPopupApp',
			data()
			{
				return {};
			},
			components: {
				App,
			},
			computed: {
				componentName()
				{
					return App;
				},
			},
			// language=Vue
			template: `
				<App/>
			`,
		});

		const store = Store.buildStore(state);
		app.use(store);

		return app;
	}
}
