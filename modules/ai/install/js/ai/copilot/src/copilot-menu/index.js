import { KeyboardMenu, KeyboardMenuEvents } from './keyboard-menu';
import { CopilotMenu, CopilotMenuEvents, CopilotMenuItem as cmi, type CopilotMenuOptions } from './copilot-menu';
import { CopilotMenuCommand } from './copilot-menu-command';
import { BaseMenuItem } from './copilot-menu-item';

export type CopilotMenuItem = cmi;

export {
	KeyboardMenu,
	KeyboardMenuEvents,
	CopilotMenu,
	CopilotMenuCommand,
	CopilotMenuEvents,
	CopilotMenuOptions,
	BaseMenuItem,
};
