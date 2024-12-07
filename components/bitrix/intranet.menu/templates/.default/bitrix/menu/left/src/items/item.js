import {Type} from 'main.core';

export default class Item
{
	parentContainer: Element;
	container: Element;

	constructor(parentContainer, container)
	{
		this.parentContainer = parentContainer;
		this.container = container;
		this.init();
	}

	init(): void
	{
		this.makeTextIcons();
	}

	getId(): string
	{
		return this.container.dataset.id;
	}

	getCode(): string
	{
		return this.constructor.code;
	}

	getName(): string
	{
		return this.container.querySelector("[data-role='item-text']").textContent;
	}

	static detect(node: Element)
	{
		return node.getAttribute("data-role") !== 'group' &&
			node.getAttribute("data-type") === this.code;
	}

	makeTextIcons(): void
	{
		if (!this.container.classList.contains("menu-item-no-icon-state"))
		{
			return;
		}

		const icon = this.container.querySelector(".menu-item-icon");
		const text = this.container.querySelector(".menu-item-link-text");

		if (icon && text)
		{
			icon.textContent = this.getShortName(text.textContent);
		}
	}

	getCounterValue(): ?number
	{
		const node = this.container.querySelector('[data-role="counter"]');
		if (!node)
		{
			return null;
		}
		return parseInt(node.dataset.counterValue);
	}

	updateCounter(counterValue): undefined|Object
	{
		const node = this.container.querySelector('[data-role="counter"]');
		if (!node)
		{
			return;
		}
		const oldValue = parseInt(node.dataset.counterValue) || 0;
		node.dataset.counterValue = counterValue;

		if (counterValue > 0)
		{
			node.innerHTML = (counterValue > 99 ? '99+' : counterValue);
			this.container.classList.add('intranet__desktop-menu_item_counters');
		}
		else
		{
			node.innerHTML = '';
			this.container.classList.remove('menu-item-with-index');
		}

		return {oldValue, newValue: counterValue};
	}

	getShortName(name): string
	{
		if (!Type.isStringFilled(name))
		{
			return "...";
		}

		name = name.replace(/['`".,:;~|{}*^$#@&+\-=?!()[\]<>\n\r]+/g, "").trim();
		if (name.length <= 0)
		{
			return '...';
		}

		let shortName;
		let words = name.split(/[\s,]+/);
		if (words.length <= 1)
		{
			shortName = name.substring(0, 1);
		}
		else if (words.length === 2)
		{
			shortName = words[0].substring(0, 1) + words[1].substring(0, 1);
		}
		else
		{
			let firstWord = words[0];
			let secondWord = words[1];

			for (let i = 1; i < words.length; i++)
			{
				if (words[i].length > 3)
				{
					secondWord = words[i];
					break;
				}
			}

			shortName = firstWord.substring(0, 1) + secondWord.substring(0, 1);
		}

		return shortName.toUpperCase();
	}
}