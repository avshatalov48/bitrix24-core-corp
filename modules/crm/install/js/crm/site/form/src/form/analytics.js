import * as Type from "./types";
import {Controller} from './controller';

class Analytics
{
	#form: Controller;
	#isStartSent: boolean = false;
	#filledFields: Array = [];

	constructor(form: Controller)
	{
		this.#form = form;
		this.#form.subscribeAll(this.onFormEvent.bind(this));
	}

	onFormEvent(data: any, obj: Object, type: string)
	{
		if (this.#form.disabled)
		{
			return;
		}

		if (this.#form.editMode)
		{
			return; // don't handle analytics in form editor mode
		}

		switch (type)
		{
			case Type.EventTypes.showFirst:
				this.send('view');
				break;
			case Type.EventTypes.view: // form
				this.#form.analyticsHandler('view', this.#form.identification.id);
				break;
			case Type.EventTypes.fieldFocus:
				if (!this.#isStartSent)
				{
					this.#isStartSent = true;
					this.send('start');
					this.#form.analyticsHandler('start', this.#form.identification.id);
				}
				break;
			case Type.EventTypes.fieldBlur:
				const field = data.field;
				if (this.#filledFields.indexOf(field.name) < 0 && field.hasValidValue())
				{
					this.#filledFields.push(field.name);
					this.send('field', [
						{from: '%name%', to: field.label},
						{from: '%code%', to: field.name},
					]);
				}
				break;
			case Type.EventTypes.sendSuccess:
				this.send('end');
				break;
		}
	}

	send(type, replace: Array = [])
	{
		if (!b24form.common || !type)
		{
			return;
		}

		/**	@var Object[Type.Analytics] opt */
		const opt = b24form.common.properties.analytics;
		if(!opt || !opt[type])
		{
			return;
		}

		let action = opt[type].name;
		let page = opt[type].code;
		replace.forEach(item => {
			action = action.replace(item.from, item.to);
			page = page.replace(item.from, item.to);
		});

		//////////// google
		let gaEventCategory = opt.category
			.replace('%name%', this.#form.title)
			.replace('%form_id%', this.#form.identification.id);
		let gaEventAction = opt.template.name
			.replace('%name%', action)
			.replace('%form_id%', this.#form.identification.id);
		b24form.util.analytics.trackGa('event', gaEventCategory, gaEventAction);

		if (page)
		{
			const gaPageName = opt.template.code
				.replace('%code%', page)
				.replace('%form_id%', this.#form.identification.id);
			b24form.util.analytics.trackGa('pageview', gaPageName);
		}

		//////////// yandex
		const yaEventName = opt.eventTemplate.code
			.replace('%code%', page)
			.replace('%form_id%', this.#form.identification.id);
		b24form.util.analytics.trackYa(yaEventName);
	}
}

export default Analytics