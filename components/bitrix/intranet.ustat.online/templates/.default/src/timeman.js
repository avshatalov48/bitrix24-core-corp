import {Type, Dom} from 'main.core';
import {PULL} from "pull.client";

export class Timeman
{
	constructor(parent)
	{
		this.parent = parent;
		this.signedParameters = this.parent.signedParameters;
		this.componentName = this.parent.componentName;
		this.isTimemanAvailable = this.parent.isTimemanAvailable;
		this.timemanNode = this.parent.timemanNode;
		this.containerNode = this.parent.ustatOnlineContainerNode;

		if (this.isTimemanAvailable && Type.isDomNode(this.timemanNode))
		{
			this.timemanValueNodes = this.timemanNode.querySelectorAll('.intranet-ustat-online-value');
			this.timemanTextNodes =  this.timemanNode.querySelectorAll('.js-ustat-online-timeman-text');

			this.resizeTimemanText();
			this.subscribePullEvent();
		}
	}

	resizeTimemanText()
	{
		if (!Type.isDomNode(this.timemanNode))
		{
			return;
		}

		let textSum = 0;
		let valueSum = 0;

		if (Type.isArrayLike(this.timemanTextNodes))
		{
			for (let text of this.timemanTextNodes)
			{
				let textItems = text.textContent.length;
				textSum += textItems;
			}
		}

		if (Type.isArrayLike(this.timemanValueNodes))
		{
			for (let value of this.timemanValueNodes)
			{
				let valueItems = value.textContent.length;
				valueSum += valueItems;
			}
		}

		if (textSum >= 17 && valueSum >= 6 || textSum >= 19 && valueSum >= 4)
		{
			Dom.addClass(this.timemanNode, 'intranet-ustat-online-info-text-resize');
		}
		else
		{
			Dom.removeClass(this.timemanNode, 'intranet-ustat-online-info-text-resize');
		}
	}

	redrawTimeman(data)
	{
		if (data.hasOwnProperty("OPENED"))
		{
			let openedNode = this.containerNode.querySelector('.js-ustat-online-timeman-opened');
			if (Type.isDomNode(openedNode))
			{
				openedNode.innerHTML = data["OPENED"];
			}
		}

		if (data.hasOwnProperty("CLOSED"))
		{
			let closedNode = this.containerNode.querySelector('.js-ustat-online-timeman-closed');
			if (Type.isDomNode(closedNode))
			{
				closedNode.innerHTML = data["CLOSED"];
			}
		}

		this.resizeTimemanText();
	}

	checkTimeman()
	{
		BX.ajax.runComponentAction(this.componentName, "checkTimeman", {
			signedParameters: this.signedParameters,
			mode: 'class'
		}).then((response) => {
			if (response.data)
			{
				this.redrawTimeman(response.data);
			}
		});
	}

	subscribePullEvent()
	{
		PULL.subscribe({
			moduleId: 'intranet',
			command: 'timemanDayInfo',
			callback: (data) =>
			{
				this.redrawTimeman(data);
			}
		});
	}
}