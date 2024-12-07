import { BitrixVue } from 'ui.vue3';
import { createStore } from 'ui.vue3.vuex';
import { Main } from './components/main';
import store from './store';

interface EditOptions {
	containerId: string;
}

export interface AppData {
	role: {
		id: number;
		name: string;
	},
	availablePermissions: Object;
	permissionEntities: Object;
	roleAssignedPermissions: Object;
	restriction: {
		hasPermission: boolean;
		restrictionScript: ?string;
	};
	roleAssignedSettings: Object;
}

export class EditApp
{
	#options: EditOptions;

	#application = null;

	constructor(options: EditOptions)
	{
		this.#options = options;
	}

	start(data: AppData) {
		const storage = createStore(store());
		this.#application = BitrixVue.createApp({
			name: 'CrmConfigPermsRoleEdit',
			components: { Main },
			template: `
				<Main/>
			`,
		});

		storage.commit('setInitData', data);
		this.#application.use(storage);

		const rootNode = document.getElementById(this.#options.containerId);
		this.#application.mount(rootNode);
	}
}
