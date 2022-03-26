import {BaseEvent, EventEmitter} from 'main.core.events';
import {Grid} from '../grid';
import {Guide, Step} from 'ui.tour';
import {Loc, Uri} from 'main.core';

export class FirstScrumCreationTourGuide
{
	constructor(options)
	{
		this.grid = new Grid(options);
		this.signedParameters = options.signedParameters;
		this.popupData = options.tours.firstScrumCreation.popupData;

		this.projectAddButton = BX('projectAddButton');
		this.guide = new Guide({
			steps: [
				{
					target: this.projectAddButton,
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
			this.projectAddButton.href = Uri.removeParam(this.projectAddButton.href, ['PROJECT_OPTIONS']);
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

		if (this.grid.isRowExist(projectId))
		{
			this.showFinalStep(projectId);
		}
		else
		{
			EventEmitter.subscribe('Tasks.Projects.Grid:RowAdd', this.onProjectRowAdded.bind(this, projectId));
		}
	}

	onProjectRowAdded(projectId, event: BaseEvent)
	{
		const {id} = event.getData();

		if (Number(id) === Number(projectId))
		{
			this.showFinalStep(projectId);
		}
	}

	showFinalStep(projectId)
	{
		const target = this.grid.getRowNodeById(projectId).querySelector('.tasks-projects-text');

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
		this.projectAddButton.href = Uri.addParam(this.projectAddButton.href, {
			PROJECT_OPTIONS: {
				tourId: this.guide.getId(),
			},
		});
		this.showNextStep();
	}

	finish()
	{
		BX.ajax.runAction('tasks.tourguide.firstscrumcreation.finish');
	}

	showNextStep()
	{
		setTimeout(() => this.guide.showNextStep(), 1000);
	}
}