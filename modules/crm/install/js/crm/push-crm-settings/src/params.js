import { Menu } from "main.popup";
import { SettingsController } from "crm.kanban.sort";
import { Restriction } from "crm.kanban.restriction";

export type Params = {
	entityTypeId: number,
	rootMenu: Menu,
	todoCreateNotificationSkipPeriod: ?string,
	targetItemId: ?string,
	controller: ?SettingsController,
	restriction: ?Restriction,
	grid: ?BX.Main.grid,
};
