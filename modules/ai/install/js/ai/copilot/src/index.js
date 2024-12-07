import { Copilot, CopilotEvents, type CopilotMenuOption as cmo, type CopilotOptions as co, CopilotMode } from './copilot.js';
import { CopilotInput, CopilotInputEvents } from './copilot-input-field/src/copilot-input';
import { type CopilotMenuItem as cmi, CopilotMenu, CopilotMenuEvents, CopilotMenuOptions } from './copilot-menu/index.js';
import { CopilotMenuCommand } from './copilot-menu/index';
import { CopilotResult } from './copilot-result';
import { CopilotContextMenu, type CopilotContextMenuOptions as cro } from './copilot-context-menu/copilot-context-menu';

export type CopilotMenuOption = cmo;
export type CopilotMenuItem = cmi;
export type CopilotOptions = co;
export type CopilotContextMenuOptions = cro;

export {
	Copilot,
	CopilotMode,
	CopilotEvents,
	CopilotInput,
	CopilotInputEvents,
	CopilotMenu,
	CopilotMenuEvents,
	CopilotMenuCommand,
	CopilotResult,
	CopilotContextMenu,
};
