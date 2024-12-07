import { Loc } from 'main.core';
import { Spot } from './spot';

export class MyTasks extends Spot
{
	getIconSrc(): string
	{
		return `${Spot.PATH_TO_IMAGES}my-tasks.svg`;
	}

	getTitle(): string
	{
		return Loc.getMessage('TASKS_CLUE_FLASH_MY_TASKS_TITLE');
	}

	getText(): string
	{
		return Loc.getMessage('TASKS_CLUE_FLASH_MY_TASKS_TEXT');
	}
}
