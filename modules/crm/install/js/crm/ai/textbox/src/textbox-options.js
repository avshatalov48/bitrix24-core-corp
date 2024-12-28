import { Attention } from 'crm.ai.textbox';

export type TextboxOptions = {
	text: string,
	title: string,
	enableSearch?: boolean,
	enableCollapse?: boolean,
	isCollapsed?: boolean,
	previousTextContent?: HTMLElement,
	attentions?: Array<Attention>,
};
