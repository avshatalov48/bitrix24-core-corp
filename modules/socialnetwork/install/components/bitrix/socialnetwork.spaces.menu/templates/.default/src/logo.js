import { Tag } from 'main.core';

export type LogoData = {
	type: string,
	id: string,
}

export class Logo
{
	#logo: LogoData;

	constructor(logo: LogoData)
	{
		this.#logo = logo;
	}

	render(): HTMLElement
	{
		const iconClass = this.#getIconClass();
		const iconStyle = this.#getIconStyle();

		return Tag.render`<i class="${iconClass}" style="${iconStyle}"></i>`;
	}

	getClass(): string
	{
		let result = '';

		if (this.#logo.type === 'icon')
		{
			if (this.#logo.id.length > 0)
			{
				result = `sonet-common-workgroup-avatar --${this.#logo.id}`;
			}
			else
			{
				result = 'ui-icon-common-user-group ui-icon';
			}
		}

		return result;
	}

	#getIconStyle(): string
	{
		let result = '';
		if (this.#logo.type === 'image')
		{
			result = `background-image: url(${this.#logo.id}); background-size: cover`;
		}

		return result;
	}

	#getIconClass(): string
	{
		return this.#logo.type === 'image' ? 'sn-spaces__list-item_img' : '';
	}
}