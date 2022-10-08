import {ajax} from 'main.core';
import {UI} from 'ui.notification';
import {Menu} from "./components/layout/menu";

export class Action
{
	#type: string = null;
	#value: string | Object  = null;
	#actionParams: ?Object = null;

	constructor(params)
	{
		this.#type = params.type;
		this.#value = params.value;
		this.#actionParams = params.actionParams;
	}

	execute(vueComponent): void
	{
		if (this.isJsEvent())
		{
			vueComponent.$Bitrix.eventEmitter.emit('crm:timeline:item:action:' + this.#value, this.#actionParams);
			vueComponent.$Bitrix.eventEmitter.emit('crm:timeline:item:action', {
				action: this.#value,
				actionParams: this.#actionParams,
			});
		}
		if (this.isJsCode())
		{
			eval(this.#value);
		}
		if (this.isAjaxAction())
		{
			ajax.runAction(
				this.#value,
				{
					data: this.#actionParams,
				}
			).then(() =>
			{
				// do something
			}, (response) =>
			{
				UI.Notification.Center.notify({
					content: response.errors[0].message,
					autoHideDelay: 5000,
				});
			});
		}
		if (this.isRedirect())
		{
			location.href = this.#value;
		}
		if (this.isShowMenu())
		{
			Menu.showMenu(
				vueComponent,
				this.#prepareMenuItems(this.#value.items, vueComponent),
				{
					id: 'actionMenu',
					bindElement: vueComponent.$el,
				}
			);
		}
	}

	isJsEvent(): boolean
	{
		return (this.#type === 'jsEvent');
	}

	isJsCode(): boolean
	{
		return (this.#type === 'jsCode');
	}

	isAjaxAction(): boolean
	{
		return (this.#type === 'runAjaxAction');
	}

	isRedirect(): boolean
	{
		return (this.#type === 'redirect');
	}

	isShowMenu(): boolean
	{
		return (this.#type === 'showMenu');
	}

	getValue(): string | Object
	{
		return this.#value;
	}

	#prepareMenuItems(items: Object, vueComponent: Object): Array
	{
		return Object.values(items)
			.filter((item) => (item.state !== 'hidden' && item.scope !== 'mobile' && (!vueComponent.isReadOnly || !item.hideIfReadonly)))
			.sort((a, b) => (a.sort - b.sort))
		;
	}
}
