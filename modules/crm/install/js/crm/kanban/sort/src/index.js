import { Reflection } from "main.core";

import { Sorter } from "./sorter";
import { Settings } from "./settings";
import { SettingsController } from "./settings-controller";
import { Type } from "./type";
import type { SortParams } from "./sort-params";

const namespace = Reflection.namespace('BX.CRM.Kanban.Sort');
namespace.Sorter = Sorter;
namespace.Settings = Settings;
namespace.SettingsController = SettingsController;
namespace.Type = Type;

export {
	Sorter,
	Settings,
	SettingsController,
	Type,
};

export type {
	SortParams,
};
