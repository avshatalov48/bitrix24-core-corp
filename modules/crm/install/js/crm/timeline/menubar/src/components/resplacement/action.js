import { Dom, Type } from 'main.core';
import EventType from './enums/event-type';
import ActionType from './enums/action-type';
import { ITEM_ACTION_EVENT } from './layout';

export class Action
{
	#type: string = null;
	#value: string | Object = null;
	#sliderParams: ?Object = null;

	constructor(params)
	{
		this.#type = params.type;
		this.#value = params.value ?? null;
		this.#sliderParams = params.sliderParams ?? null;
	}

	execute(vueComponent): Promise
	{
		return new Promise((resolve, reject) => {
			if (this.isLayoutJsEvent())
			{
				vueComponent.$Bitrix.eventEmitter.emit(ITEM_ACTION_EVENT, {
					event: EventType.LAYOUT_EVENT,
					value: {
						id: vueComponent.$parent?.getIdByComponentInstance
							? vueComponent.$parent?.getIdByComponentInstance(vueComponent)
							: null,
						value: this.#value,
					},
				});

				resolve(true);
			}

			else if (this.isOpenRestApp())
			{
				const params = {
					...(
						Type.isPlainObject(this.#value)
							? this.#value
							: { value: `${this.#value}` }
					),
				};
				const appId = vueComponent.$root.getAppId();

				if (Type.isStringFilled(this.#sliderParams?.title ?? null))
				{
					params.bx24_title = this.#sliderParams.title;
				}

				if (Type.isNumber(this.#sliderParams?.width ?? null))
				{
					params.bx24_width = this.#sliderParams.width;
				}

				if (Type.isNumber(this.#sliderParams?.leftBoundary ?? null))
				{
					params.bx24_leftBoundary = this.#sliderParams.leftBoundary;
				}

				const labelParams = {};
				if (Type.isStringFilled(this.#sliderParams?.labelBgColor ?? null))
				{
					labelParams.bgColor = this.#sliderParams.labelBgColor;
				}

				if (Type.isStringFilled(this.#sliderParams?.labelColor ?? null))
				{
					labelParams.color = this.#sliderParams.labelColor;
				}

				if (Type.isStringFilled(this.#sliderParams?.labelText ?? null))
				{
					labelParams.text = this.#sliderParams.labelText;
				}

				if (Object.keys(labelParams).length > 0)
				{
					params.bx24_label = labelParams;
				}

				if (BX.rest && BX.rest.AppLayout)
				{
					BX.rest.AppLayout.openApplication(appId, params);
				}
			}
			else if (this.isRedirect())
			{
				const linkAttrs = {
					href: this.#value,
				};

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

				resolve(this.#value);
			}
			else if (this.isFooterButtonClick())
			{
				vueComponent.$Bitrix.eventEmitter.emit(ITEM_ACTION_EVENT, {
					event: EventType.FOOTER_BUTTON_CLICK,
					value: this.#value,
				});

				resolve(true);
			}
			else
			{
				reject(false);
			}
		});
	}

	isFooterButtonClick(): boolean
	{
		return (this.#type === ActionType.FOOTER_BUTTON_CLICK);
	}

	isLayoutJsEvent(): boolean
	{
		return (this.#type === ActionType.LAYOUT_JS_EVENT);
	}

	isOpenRestApp(): boolean
	{
		return (this.#type === ActionType.OPEN_REST_APP);
	}

	isRedirect(): boolean
	{
		return (this.#type === ActionType.REDIRECT);
	}

	getValue(): string | Object
	{
		return this.#value;
	}
}
