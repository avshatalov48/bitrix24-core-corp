import { BitrixVue } from 'ui.vue3';
import type { Dashboard, Group, PermissionAppState } from './type';
import { PermissionsApp } from './permissions-app';
import { Store } from './store';

type initPermissionParams = {
	newGroupPermissions: Object,
	dashboardGroups: Group[],
	dashboards: Map<number, Dashboard>,
	appGuid: string,
}

export class Permissions
{
	static init(params: initPermissionParams)
	{
		const {
			newGroupPermissions,
			dashboardGroups,
			dashboards,
			user,
			appGuid,
		} = params;
		const state: PermissionAppState = {
			groups: dashboardGroups,
			dashboards: new Map(Object.entries(dashboards).map(([key, value]) => [Number(key), value])),
			newGroupPermissions,
			appGuid,
			user,
		};

		const permissionsApp = BitrixVue.createApp({
			name: 'BIConnectorRights',
			data()
			{
				return {};
			},
			components: {
				PermissionsApp,
			},
			computed: {
				componentName()
				{
					return PermissionsApp;
				},
			},
			template: `
				<PermissionsApp/>
			`,
		});

		const store = Store.buildStore(state);
		permissionsApp.use(store);
		permissionsApp.mount('#bx-biconnector-role-main2');
	}
}
