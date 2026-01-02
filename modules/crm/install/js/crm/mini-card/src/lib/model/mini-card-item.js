import { Type, Text } from 'main.core';

import { type ComponentOptions, Component } from './component';

export type MiniCardItemOptions = {
	id?: string,
	title: string,
	avatar: ComponentOptions,
	controls: ComponentOptions[],
	fields: ComponentOptions[],
	footerNotes: ComponentOptions[],
};

export class MiniCardItem
{
	#id: string;
	#title: string;
	#avatar: Component;
	#controls: Component[];
	#fields: Component[];
	#footerNotes: Component[];

	constructor(options: MiniCardItemOptions)
	{
		this.#id = Type.isStringFilled(options.id) ? options.id : Text.getRandom(16);

		if (!Type.isStringFilled(options.title))
		{
			throw new RangeError('BX.Crm.MiniCard.MiniCardItem: options.title must be a string filled');
		}

		this.#title = options.title;
		this.#avatar = new Component(options.avatar);

		this.#controls = [];
		options.controls.forEach((controlOptions: ComponentOptions) => {
			this.#controls.push(new Component(controlOptions));
		});

		this.#fields = [];
		options.fields.forEach((fieldOptions: ComponentOptions) => {
			this.#fields.push(new Component(fieldOptions));
		});

		this.#footerNotes = [];
		options.footerNotes.forEach((footerNoteOptions: ComponentOptions) => {
			this.#footerNotes.push(new Component(footerNoteOptions));
		});
	}

	id(): string
	{
		return this.#id;
	}

	title(): string
	{
		return this.#title;
	}

	avatar(): Component
	{
		return this.#avatar;
	}

	controls(): Component[]
	{
		return this.#controls;
	}

	fields(): Component[]
	{
		return this.#fields;
	}

	footerNotes(): Component[]
	{
		return this.#footerNotes;
	}
}
