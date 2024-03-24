import { DatetimeConverter } from 'crm.timeline.tools';
import { ajax as Ajax, Dom, Type } from 'main.core';
import { DateTimeFormat } from 'main.date';
import { rest as Rest } from 'rest.client';
import { UI } from 'ui.notification';
import { Menu } from './components/layout/menu';
import { sendData } from 'ui.analytics';

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
	#analytics: ?Object = null;

	constructor(params)
	{
		this.#type = params.type;
		this.#value = params.value;
		this.#actionParams = params.actionParams;
		this.#animation = Type.isPlainObject(params.animation) ? params.animation : null;
		this.#analytics = Type.isPlainObject(params.analytics) ? params.analytics : null;
	}

	execute(vueComponent): Promise
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

				this.#sendAnalytics();

				resolve(true);
			}
			else if (this.isJsCode())
			{
				this.#startAnimation(vueComponent);
				eval(this.#value);
				this.#stopAnimation(vueComponent);
				this.#sendAnalytics();
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

				const ajaxConfig = {
					data: this.#prepareRunActionParams(this.#actionParams),
				};
				if (this.#analytics)
				{
					ajaxConfig.analytics = this.#analytics;
				}

				Ajax.runAction(
					this.#value,
					ajaxConfig
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
						this.#stopAnimation(vueComponent, true);
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
			else if (this.isCallRestBatch())
			{
				this.#startAnimation(vueComponent);
				vueComponent.$Bitrix.eventEmitter.emit('crm:timeline:item:action', {
					action: this.#value,
					actionType: 'ajaxActionStarted',
					actionData: this.#actionParams,
				});
				Rest.callBatch(
					this.#prepareCallBatchParams(this.#actionParams),
					(restResult) => {
						for (const result in restResult)
						{
							const response = restResult[result].answer;
							if (response.error)
							{
								this.#stopAnimation(vueComponent);
								UI.Notification.Center.notify({
									content: response.error.error_description,
									autoHideDelay: 5000,
								});
								vueComponent.$Bitrix.eventEmitter.emit('crm:timeline:item:action', {
									action: this.#value,
									actionType: 'ajaxActionFailed',
									actionParams: this.#actionParams,
								});
								reject(restResult);

								return;
							}
						}

						this.#stopAnimation(vueComponent);
						vueComponent.$Bitrix.eventEmitter.emit('crm:timeline:item:action', {
							action: this.#value,
							actionType: 'ajaxActionFinished',
							actionData: this.#actionParams,
						});
						resolve(restResult);
					},
					true
				);
			}
			else if (this.isRedirect())
			{
				this.#startAnimation(vueComponent);

				const linkAttrs = {
					href: this.#value,
				};
				if (this.#actionParams && this.#actionParams.target)
				{
					linkAttrs.target = this.#actionParams.target;
				}
				// this magic allows auto opening internal links in slider if possible:
				const link = Dom.create('a', {
					attrs: linkAttrs,
					text: '',
					style: {
						display: 'none',
					},
				});
				Dom.append(link, document.body);
				link.click();
				setTimeout(() => Dom.remove(link), 10);

				this.#sendAnalytics();

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

				this.#sendAnalytics();

				resolve(true);
			}
			else if (this.isShowInfoHelper())
			{
				BX.UI.InfoHelper?.show(this.#value);

				this.#sendAnalytics();

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

	isCallRestBatch(): boolean
	{
		return (this.#type === 'callRestBatch');
	}

	isRedirect(): boolean
	{
		return (this.#type === 'redirect');
	}

	isShowInfoHelper(): boolean
	{
		return (this.#type === 'showInfoHelper');
	}

	isShowMenu(): boolean
	{
		return (this.#type === 'showMenu');
	}

	getValue(): string | Object
	{
		return this.#value;
	}

	getActionParam(param: string)
	{
		return this.#actionParams && this.#actionParams.hasOwnProperty(param)
			? this.#actionParams[param]
			: null
		;
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

	#prepareCallBatchParams(params): Object
	{
		const result = {};

		if (Type.isUndefined(params))
		{
			return result;
		}

		for (const paramName in params)
		{
			result[paramName] = {
				method: params[paramName].method,
				params: this.#prepareRunActionParams(params[paramName].params)
			};
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

	#stopAnimation(vueComponent, force = false)
	{
		if (!this.#isAnimationValid())
		{
			return;
		}
		if (this.#animation.forever && !force)
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

		return AnimationType.hasOwnProperty(this.#animation.type);
	}

	#sendAnalytics()
	{
		if (this.#analytics && this.#analytics.hit)
		{
			const clonedAnalytics = {...this.#analytics};
			delete clonedAnalytics.hit;

			sendData(clonedAnalytics);
		}
	}
}
