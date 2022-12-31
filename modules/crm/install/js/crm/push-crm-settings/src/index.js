import { Reflection } from "main.core";

import { PushCrmSettings } from "./push-crm-settings";

const namespace = Reflection.namespace('BX.Crm');
namespace.PushCrmSettings = PushCrmSettings;

export {
	PushCrmSettings,
};
