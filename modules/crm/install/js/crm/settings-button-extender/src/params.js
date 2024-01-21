import { Restriction } from 'crm.kanban.restriction';
import { SettingsController } from 'crm.kanban.sort';
import { Menu } from 'main.popup';

export type Params = {
	entityTypeId: number,
	categoryId: ?number,
	aiAutostartSettings: ?string, // json
	pingSettings: Object,
	rootMenu: Menu,
	todoCreateNotificationSkipPeriod: ?string,
	targetItemId: ?string,
	controller: ?SettingsController,
	restriction: ?Restriction,
	grid: ?BX.Main.grid,
};
