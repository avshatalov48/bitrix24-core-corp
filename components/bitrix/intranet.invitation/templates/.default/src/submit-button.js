import { Type, Dom, Event } from 'main.core';
import { EventEmitter } from 'main.core.events';

export class SubmitButton extends EventEmitter
{
	static ENABLED_STATE = 'enabled';
	static DISABLED_STATE = 'disabled';

	#container: HTMLElement;

	constructor(options)
	{
		super();

		this.#container = options.node;
		this.setEventNamespace('BX.Intranet.Invitation.Submit');
		Event.unbindAll(this.#container);
		if (Type.isFunction(options.events?.click))
		{
			Event.bind(this.#container, 'click', options.events.click);
		}
	}

	#disableSubmitButton(isDisable)
	{
		if (!Type.isDomNode(this.#container) || !Type.isBoolean(isDisable))
		{
			return;
		}

		if (isDisable)
		{
			Dom.addClass(this.#container, ['ui-btn-wait', 'invite-cursor-auto']);
		}
		else
		{
			Dom.removeClass(this.#container, ['ui-btn-wait', 'invite-cursor-auto']);
		}
	}

	wait(): void
	{
		this.#disableSubmitButton(true);
	}

	isWaiting(): boolean
	{
		return Dom.hasClass(this.#container, 'ui-btn-wait');
	}

	ready(): void
	{
		this.#disableSubmitButton(false);
	}

	disable(): void
	{
		Dom.addClass(this.#container, 'ui-btn-disabled');
	}

	enable(): void
	{
		Dom.removeClass(this.#container, 'ui-btn-disabled');
	}

	isEnabled(): boolean
	{
		return !Dom.hasClass(this.#container, 'ui-btn-disabled');
	}

	setLabel(lable: string): void
	{
		this.#container.innerText = lable;
	}

	sendSuccessEvent(users)
	{
		BX.SidePanel.Instance.postMessageAll(window, 'BX.Intranet.Invitation:onAdd', { users });
	}
}
