import {BaseEvent, EventEmitter} from 'main.core.events';

import {FirstProject} from './first.project';
import {FirstScrum} from './first.scrum';

export type PopupData = {
	article: number,
	text: string,
	title: string,
}

type TourData = {
	targetNodeId: string,
	popupData: Array<PopupData>,
	show: boolean,
}

type Params = {
	tours: {
		firstProjectCreation?: TourData,
		firstScrumCreation?: TourData,
	}
}

export class Tour extends EventEmitter
{
	constructor(params: Params)
	{
		super(params);

		this.setEventNamespace('BX.Tasks.Tour');

		const tours = params.tours;

		const firstProjectData = tours.firstProjectCreation ?? {};
		const firstScrumData = tours.firstScrumCreation ?? {};

		if (firstProjectData.show)
		{
			this.firstProject = new FirstProject({
				targetNodeId: firstProjectData.targetNodeId,
				popupData: firstProjectData.popupData
			});

			this.firstProject.subscribe('afterProjectCreated', (baseEvent: BaseEvent) => {
				this.emit('FirstProject:afterProjectCreated', baseEvent.getData());
			});

			this.firstProject.start();
		}

		if (firstScrumData.show)
		{
			this.firstScrum = new FirstScrum({
				targetNodeId: firstScrumData.targetNodeId,
				popupData: firstScrumData.popupData
			});

			this.firstScrum.subscribe('afterProjectCreated', (baseEvent: BaseEvent) => {
				this.emit('FirstScrum:afterScrumCreated', baseEvent.getData());
			});

			this.firstScrum.start();
		}
	}

	showFinalStep(target: HTMLElement)
	{
		if (this.firstProject)
		{
			this.firstProject.showFinalStep(target);
		}

		if (this.firstScrum)
		{
			this.firstScrum.showFinalStep(target);
		}
	}
}