import {Dom, Type, ajax as Ajax, Event} from "main.core";
import {BaseEvent} from "main.core.events";
import {ComponentOptions} from "./types";

export default class Component
{
	options: ComponentOptions;

	constructor(options: ComponentOptions)
	{
		this.options = options;

		this.bindEvents();
	}

	bindEvents()
	{
		const buttonClass = this.options.buttonClass;
		document.querySelectorAll(`.${buttonClass}`).forEach(button => {
			Event.bind(button, 'click', this.handleClickOnTemplate.bind(this));
		});
	}

	handleClickOnTemplate(event: BaseEvent)
	{
		const templateId = event.currentTarget.dataset.templateId;
		Dom.clean(this.options.container);

		top.window.postMessage({
			type: 'selectedTemplate',
		}, '*');

		const loader = new BX.Loader({
			target: this.options.container,
		});
		loader.show();

		Ajax.runAction('disk.api.integration.messengerCall.createResumeByTemplate', {
			data: {
				callId: this.options.call.id,
				templateId: templateId,
			}
		}).then((response) => {
			if (response.data.document.urlToEdit)
			{
				document.location = response.data.document.urlToEdit;
			}
		});
	}
}