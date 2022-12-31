import { Type, Runtime } from 'main.core';

export default class Scrum
{
	constructor(params)
	{
		this.scrumMeetings = null;
		this.scrumMethodology = null;

		this.init(params);
	}

	init(params)
	{
		this.groupId = !Type.isUndefined(params.groupId) ? Number(params.groupId) : 0;
		this.urls = Type.isPlainObject(params.urls) ? params.urls : {};

		const scrumMeetingsButton = document.getElementById('tasks-scrum-meetings-button');
		if (scrumMeetingsButton)
		{
			scrumMeetingsButton.addEventListener('click', this.showScrumMeetings.bind(this));
		}

		const scrumMethodologyButton = document.getElementById('tasks-scrum-methodology-button');
		if (scrumMethodologyButton)
		{
			scrumMethodologyButton.addEventListener('click', this.showScrumMethodology.bind(this));
		}
	}

	showScrumMeetings(event)
	{
		event.target.classList.add('ui-btn-wait');

		Runtime.loadExtension('tasks.scrum.meetings').then(exports => {
			const { Meetings } = exports;

			if (this.scrumMeetings === null)
			{
				this.scrumMeetings = new Meetings({
					groupId: this.groupId,
				});
			}

			this.scrumMeetings.showMenu(event.target);
			event.target.classList.remove('ui-btn-wait');
		});

		event.preventDefault();
	}

	showScrumMethodology(event)
	{
		event.target.classList.add('ui-btn-wait');

		Runtime.loadExtension('tasks.scrum.methodology').then(exports => {
			const { Methodology } = exports;

			if (this.scrumMethodology === null)
			{
				this.scrumMethodology = new Methodology({
					groupId: this.groupId,
					teamSpeedPath: this.urls.ScrumTeamSpeed,
					burnDownPath: this.urls.ScrumBurnDown,
					pathToTask: this.urls.TasksTask,
				});
			}

			this.scrumMethodology.showMenu(event.target);
			event.target.classList.remove('ui-btn-wait');
		});

		event.preventDefault();
	}


}
