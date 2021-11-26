import {Event, Type} from 'main.core';

export class Culture
{
	constructor(parent)
	{
		this.cultureList = parent.cultureList;
		this.selectorNode = document.querySelector("[data-role='culture-selector']");
		this.shortDateNode = document.querySelector("[data-role='culture-short-date-format']");
		this.longDateNode = document.querySelector("[data-role='culture-long-date-format']");

		if (Type.isDomNode(this.selectorNode))
		{
			Event.bind(this.selectorNode, 'change', () => {
				this.changeFormatExample(this.selectorNode.value);
			});
		}
	}

	changeFormatExample(cultureId)
	{
		if (!Type.isDomNode(this.shortDateNode) || !Type.isDomNode(this.longDateNode))
		{
			return;
		}

		this.shortDateNode.textContent = this.cultureList[cultureId].SHORT_DATE_FORMAT;
		this.longDateNode.textContent = this.cultureList[cultureId].LONG_DATE_FORMAT;
	}
}