import { Tag, Type, Dom } from 'main.core';
import { EventEmitter } from 'main.core.events';

export default class ReleaseEar extends EventEmitter
{
	container: HTMLElement = null;

	constructor(options)
	{
		super();
		this.setEventNamespace('BX.Intranet.Bitrix24.ReleaseEar');

		options = Type.isPlainObject(options) ? options : {};
		this.zone = Type.isStringFilled(options.zone) ? options.zone : 'en';
		this.subscribeFromOptions(options.events);
	}

	show(animate = false)
	{
		if (animate)
		{
			Dom.removeClass(this.getContainer(), '--hidden');
			requestAnimationFrame(() => {
				requestAnimationFrame(() => {
					Dom.removeClass(this.getContainer(), '--hidden');
				});
			});
		}
		else
		{
			Dom.removeClass(this.getContainer(), '--hidden');
		}
	}

	hide()
	{
		Dom.addClass(this.getContainer(), '--hidden');
	}

	getContainer(): HTMLElement
	{
		if (this.container === null)
		{
			this.container = Tag.render`
				<div class="intranet-release-ear" onclick="${this.handleClick.bind(this)}">
					<div class="intranet-release-button"><i></i></div>
					<div class="intranet-release-logo --${this.zone}"></div>
				</div>
			`;

			Dom.append(this.container, document.body);
		}

		return this.container;
	}

	handleClick()
	{
		this.emit('onClick');
	}
}
