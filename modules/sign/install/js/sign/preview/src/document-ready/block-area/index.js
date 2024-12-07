import { Tag, Dom, Type, Text } from 'main.core';

import './style.css';

export class BlockArea
{
	#style: Array;
	#data: String;
	#layout: Array

	constructor({ id, style, data })
	{
		this.id = id ? id : null;
		this.#style = style ? style : {};
		this.#data = data ? data : null;
		this.#layout = {
			container: null
		};
	}

	#getNodeContainer()
	{
		if (!this.#layout.container)
		{
			
			this.#layout.container = Tag.render`
				<div class="sign-preview__block-area --empty"></div>
			`;
			
			if (Type.isString(this.#data.text))
			{
				let preContent = Text.encode(this.#data.text);
				let content = preContent.replaceAll('[br]', '<br>');
				this.#layout.container = Tag.render`
					<div class="sign-preview__block-area${this.#data.text === '' ? ' --empty' : ''}">${content}</div>
				`;
			}

			if (this.#data.base64)
			{
				const src = 'data:image;base64,' + this.#data.base64;
				this.#layout.container = Tag.render`
					<div class="sign-preview__block-area --image">
						<img src="${src}" alt="">
					</div>
				`;
			}

			for (let key in this.#style)
			{
				this.#layout.container.style.setProperty(key, this.#style[key]);
			}

		}

		return this.#layout.container;
	}

	render()
	{
		return this.#getNodeContainer();
	}
}