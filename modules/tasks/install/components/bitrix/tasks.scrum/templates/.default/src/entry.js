import {ActiveSprint} from './view/active.sprint';
import {Plan} from './view/plan';
import {CompletedSprint} from './view/completed.sprint';
import {Sprint} from './entity/sprint/sprint';

type View = {
	name: string,
	url: string
}

type Responsible = {
	name: string,
	pathToUser: string,
	photo: {
		src: string
	}
}

type Params = {
	signedParameters: string,
	debugMode: string,
	views: {
		plan: {
			name: string,
			url: string,
			active: boolean
		},
		activeSprint: {
			name: string,
			url: string,
			active: boolean
		},
		completedSprint: {
			name: string,
			url: string,
			active: boolean
		}
	},
	activeView: string,
	activeSprintId: number,
	filterId: string,
	sprints: Array, // todo
	pathToTask?: string,
	backlog?: Object, // todo
	activeSprintData?: Object, //todo
	tags?: Object, //todo split to epic object and tags array
	defaultSprintDuration?: number,
	completedSprint?: Object, //todo
	defaultResponsible: Responsible
}

export class Entry
{
	constructor(params: Params)
	{
		this.activeView = params.activeView;

		this.scrumView = null;

		switch (this.activeView)
		{
			case 'plan':
				this.scrumView = new Plan(params);
				break;
			case 'activeSprint':
				this.scrumView = new ActiveSprint(params);
				break;
			case 'completedSprint':
				this.scrumView = new CompletedSprint({
					completedSprint: new Sprint(params.completedSprint)
				});
				break;
		}
	}

	renderTo(container: HTMLElement)
	{
		this.scrumView.renderTo(container);
	}

	openEpicEditForm(epicId: number)
	{
		this.scrumView.openEpicEditForm(epicId);
	}

	openEpicViewForm(epicId: number)
	{
		this.scrumView.openEpicViewForm(epicId);
	}

	removeEpic(epicId: number)
	{
		this.scrumView.removeEpic(epicId);
	}
}