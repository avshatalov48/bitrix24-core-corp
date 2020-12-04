import {Text} from 'main.core';

type Sprint = {
	name: string,
}

type CurrentSprint = {
	sprintId: number,
	name: string
}

type Params = {
	completedSprint: Sprint
}

export class CompletedSprint
{
	constructor(params: Params)
	{
		this.completedSprint = params.completedSprint;

		this.bindHandlers();
		this.createTitle();
	}

	bindHandlers()
	{
		/* eslint-disable */
		BX.addCustomEvent('onTasksGroupSelectorChange', this.onSprintSelectorChange.bind(this));
		/* eslint-enable */
	}

	createTitle()
	{
		this.titleContainer = document.getElementById('tasks-scrum-completed-sprint-title');
		this.titleContainer.textContent = Text.encode(this.completedSprint.getName());
	}

	onSprintSelectorChange(currentSprint: CurrentSprint)
	{
		this.titleContainer.textContent = Text.encode(currentSprint.name);
	}
}