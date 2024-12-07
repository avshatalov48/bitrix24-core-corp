import { Tag, Type, Dom } from 'main.core';
import { Icon, Set } from 'ui.icon-set.api.core';

declare type AttentionPreset = {
	className: string,
	iconOptions: {
		icon: string,
		color ?: string,
		size ?: number,
	},
};

export const AttentionPresets = {
	DEFAULT: {
		className: '--crm-textbox-attention-default',
		iconOptions: {
			icon: Set.INFO_1,
			color: '#BDC1C6',
			size: 16,
		},
	},
	COPILOT: {
		className: '--crm-textbox-attention-copilot',
		iconOptions: {
			icon: Set.EARTH,
			color: '#B6AAC8',
			size: 16,
		},
	},
};

declare type AttentionOptions = {
	content: HTMLElement | string,
	preset ?: AttentionPreset,
};

export class Attention
{
	content: HTMLElement | string;
	preset: AttentionPreset;

	constructor(options: AttentionOptions)
	{
		this.setContent(options.content);
		this.setPreset(options.preset ?? AttentionPresets.DEFAULT);
	}

	setContent(content: HTMLElement | string): void
	{
		if (Type.isElementNode(content))
		{
			this.content = content;

			return;
		}

		this.content = Tag.render`<span>${content}</span>`;
	}

	setPreset(preset: AttentionPreset): void
	{
		this.preset = preset;
	}

	render(): HTMLElement
	{
		this.getContainer().innerHTML = '';

		Dom.append(this.#getIconNode(), this.getContainer());
		Dom.append(this.#getContentNode(), this.getContainer());

		return this.getContainer();
	}

	getContainer(): HTMLElement
	{
		if (!this.container)
		{
			this.container = Tag.render`<div class="crm-textbox-attention ${this.preset.className}"></div>`;
		}

		return this.container;
	}

	#getIconNode(): HTMLElement
	{
		const iconNode = Tag.render`<span class="crm-textbox-attention__icon"></span>`;
		const icon = new Icon(this.preset.iconOptions);

		Dom.append(icon.render(), iconNode);

		return iconNode;
	}

	#getContentNode(): HTMLElement
	{
		const contentNode = Tag.render`<span class="crm-textbox-attention__content"></span>`;
		Dom.append(this.content, contentNode);

		return contentNode;
	}
}
