import { ajax, Type } from 'main.core';
import { UI } from 'ui.notification';
import { Menu } from "./components/layout/menu";
import { DateTimeFormat } from "main.date";
import { DatetimeConverter } from "crm.timeline.tools";

declare type AnimationParams = {
	target: string,
	type: string,
	forever: boolean,
}

const AnimationTarget = {
	block: 'block',
	item: 'item',
};

const AnimationType = {
	disable: 'disable',
	loader: 'loader',
};

const ActionType = {
	JS_EVENT: 'jsEvent',
	AJAX_ACTION: {
		STARTED: 'ajaxActionStarted',
		FINISHED: 'ajaxActionFinished',
		FAILED: 'ajaxActionFailed',
	},

	isJsEvent(type: string): boolean
	{
		return (type === this.JS_EVENT);
	},

	isAjaxAction(type: string): boolean
	{
		return (
			type === this.AJAX_ACTION.STARTED
			|| type === this.AJAX_ACTION.FINISHED
			|| type === this.AJAX_ACTION.FAILED
		);
	}
};

Object.freeze(ActionType.AJAX_ACTION);
Object.freeze(ActionType);

export { ActionType };

export class Action
{
	#type: string = null;
	#value: string | Object  = null;
	#actionParams: ?Object = null;
	#animation: ?AnimationParams = null;

	constructor(params)
	{
		this.#type = params.type;
		this.#value = params.value;
		this.#actionParams = params.actionParams;
		this.#animation = Type.isPlainObject(params.animation) ? params.animation : null;
	}

	execute(vueComponent): void
	{
		return new Promise((resolve, reject) => {
			if (this.isJsEvent())
			{
				vueComponent.$Bitrix.eventEmitter.emit('crm:timeline:item:action', {
					action: this.#value,
					actionType: ActionType.JS_EVENT,
					actionData: this.#actionParams,
					animationCallbacks: {
						onStart: this.#startAnimation.bind(this, vueComponent),
						onStop: this.#stopAnimation.bind(this, vueComponent),
					}
				});

				resolve(true);
			}
			else if (this.isJsCode())
			{
				this.#startAnimation(vueComponent);
				eval(this.#value);
				this.#stopAnimation(vueComponent);
				resolve(true);
			}
			else if (this.isAjaxAction())
			{
				this.#startAnimation(vueComponent);
				vueComponent.$Bitrix.eventEmitter.emit('crm:timeline:item:action', {
					action: this.#value,
					actionType: ActionType.AJAX_ACTION.STARTED,
					actionData: this.#actionParams,
				});
				ajax.runAction(
					this.#value,
					{
						data: this.#prepareRunActionParams(this.#actionParams),
					}
				).then(
					(response) =>
					{
						this.#stopAnimation(vueComponent);
						vueComponent.$Bitrix.eventEmitter.emit('crm:timeline:item:action', {
							action: this.#value,
							actionType: ActionType.AJAX_ACTION.FINISHED,
							actionData: this.#actionParams,
							response,
						});
						resolve(response);
					},
					(response) =>
					{
						this.#stopAnimation(vueComponent);
						UI.Notification.Center.notify({
							content: response.errors[0].message,
							autoHideDelay: 5000,
						});
						vueComponent.$Bitrix.eventEmitter.emit('crm:timeline:item:action', {
							action: this.#value,
							actionType: ActionType.AJAX_ACTION.FAILED,
							actionParams: this.#actionParams,
							response,
						});

						reject(response);
					}
				);
			}
			else if (this.isRedirect())
			{
				this.#startAnimation(vueComponent);
				location.href = this.#value;
				resolve(this.#value);
			}
			else if (this.isShowMenu())
			{
				Menu.showMenu(
					vueComponent,
					this.#prepareMenuItems(this.#value.items, vueComponent),
					{
						id: 'actionMenu',
						bindElement: vueComponent.$el,
						minWidth: vueComponent.$el.offsetWidth,
					}
				);

				resolve(true);
			}
			else {
				reject(false);
			}
		});
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

	#prepareRunActionParams(params): Object
	{
		const result = {};

		if (Type.isUndefined(params))
		{
			return result;
		}
		for (const paramName in params)
		{
			const paramValue = params[paramName];
			if (Type.isDate(paramValue))
			{
				result[paramName] = DateTimeFormat.format(DatetimeConverter.getSiteDateTimeFormat(), paramValue);
			}
			else if (Type.isPlainObject(paramValue))
			{
				result[paramName] = this.#prepareRunActionParams(paramValue);
			}
			else
			{
				result[paramName] = paramValue;
			}
		}

		return result;
	}

	#prepareMenuItems(items: Object, vueComponent: Object): Array
	{
		return Object.values(items)
			.filter((item) => (item.state !== 'hidden' && item.scope !== 'mobile' && (!vueComponent.isReadOnly || !item.hideIfReadonly)))
			.sort((a, b) => (a.sort - b.sort))
		;
	}

	#startAnimation(vueComponent)
	{
		if (!this.#isAnimationValid())
		{
			return;
		}
		if (this.#animation.target === AnimationTarget.item)
		{
			if (this.#animation.type === AnimationType.disable)
			{
				vueComponent.$root.setFaded(true);
			}
			if (this.#animation.type === AnimationType.loader)
			{
				vueComponent.$root.showLoader(true);
			}
		}
		if (this.#animation.target === AnimationTarget.block)
		{
			if (this.#animation.type === AnimationType.disable)
			{
				if (Type.isFunction(vueComponent.setDisabled))
				{
					vueComponent.setDisabled(true);
				}
			}
			if (this.#animation.type === AnimationType.loader)
			{
				if (Type.isFunction(vueComponent.setLoading))
				{
					vueComponent.setLoading(true);
				}
			}
		}
	}

	#stopAnimation(vueComponent)
	{
		if (!this.#isAnimationValid())
		{
			return;
		}
		if (this.#animation.forever)
		{
			return; // should not be stopped
		}

		if (this.#animation.target === AnimationTarget.item)
		{
			if (this.#animation.type === AnimationType.disable)
			{
				vueComponent.$root.setFaded(false);
			}
			if (this.#animation.type === AnimationType.loader)
			{
				vueComponent.$root.showLoader(false);
			}
		}
		if (this.#animation.target === AnimationTarget.block)
		{
			if (this.#animation.type === AnimationType.disable)
			{
				if (Type.isFunction(vueComponent.setDisabled))
				{
					vueComponent.setDisabled(false);
				}
			}
			if (this.#animation.type === AnimationType.loader)
			{
				if (Type.isFunction(vueComponent.setLoading))
				{
					vueComponent.setLoading(false);
				}
			}
		}
	}

	#isAnimationValid(): boolean
	{
		if (!this.#animation)
		{
			return false;
		}
		if (!AnimationTarget.hasOwnProperty(this.#animation.target))
		{
			return false;
		}
		if (!AnimationType.hasOwnProperty(this.#animation.type))
		{
			return false;
		}

		return true;
	}
}
