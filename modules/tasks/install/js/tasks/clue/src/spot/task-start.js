import { Loc } from 'main.core';
import { Spot } from './spot';

export class TaskStart extends Spot
{
	getIconSrc(): string
	{
		return `${Spot.PATH_TO_IMAGES}task-start.svg`;
	}

	getTitle(): string
	{
		return Loc.getMessage('TASKS_CLUE_FLASH_TASK_START_TITLE');
	}

	getText(): string
	{
		return Loc.getMessage('TASKS_CLUE_FLASH_TASK_START_TEXT');
	}
}
