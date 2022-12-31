import {Uri, ajax} from 'main.core';
import {BaseEvent, EventEmitter} from 'main.core.events';
import {Guide, Step} from 'ui.tour';

import type {PopupData} from './tour';

type Params = {
	targetNodeId: string,
	popupData: Array<PopupData>,
}

export class FirstScrum extends EventEmitter
{
	constructor(params: Params)
	{
		super(params);

		this.setEventNamespace('BX.Tasks.Tour.FirstScrum');

		this.popupData = params.popupData;

		this.targetNode = document.getElementById(params.targetNodeId);

		this.guide = new Guide({
			steps: [
				{
					target: this.targetNode,
					title: this.popupData[0].title,
					text: this.popupData[0].text,
					article: null
				},
			],
			onEvents: true
		});

		this.bindEvents();
	}

	bindEvents()
	{
		EventEmitter.subscribe('UI.Tour.Guide:onFinish', this.onGuideFinish.bind(this));
		EventEmitter.subscribe('SidePanel.Slider:onMessage', this.onProjectSliderMessage.bind(this));
	}

	onGuideFinish(event: BaseEvent)
	{
		const {guide} = event.getData();

		if (guide === this.guide)
		{
			this.targetNode.href = Uri.removeParam(this.targetNode.href, ['PROJECT_OPTIONS']);
		}
	}

	onProjectSliderMessage(event: BaseEvent)
	{
		const [sliderEvent] = event.getData();
		if (sliderEvent.getEventId() !== 'sonetGroupEvent')
		{
			return;
		}

		const sliderEventData = sliderEvent.getData();
		if (
			sliderEventData.code !== 'afterCreate'
			|| sliderEventData.data.projectOptions.tourId !== this.guide.getId()
		)
		{
			return;
		}

		const projectId = sliderEventData.data.group.ID;

		this.emit('afterProjectCreated', projectId);
	}

	showFinalStep(target: HTMLElement)
	{
		this.guide.steps.push(new Step({
			target,
			cursorMode: true,
			targetEvent: () => {
				BX.SidePanel.Instance.open(target.href);
				setTimeout(() => this.guide.close(), 1000);
			},
		}));
		this.finish();
		this.showNextStep();
	}

	start()
	{
		this.targetNode.href = Uri.addParam(this.targetNode.href, {
			PROJECT_OPTIONS: {
				tourId: this.guide.getId(),
			},
		});
		this.showNextStep();
	}

	finish()
	{
		ajax.runAction('tasks.tourguide.firstscrumcreation.finish');
	}

	showNextStep()
	{
		setTimeout(() => this.guide.showNextStep(), 1000);
	}
}