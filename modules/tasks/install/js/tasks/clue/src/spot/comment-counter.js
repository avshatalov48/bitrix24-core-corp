import { Loc } from 'main.core';
import { Spot } from './spot';

export class CommentCounter extends Spot
{
	getTitle(): string
	{
		return Loc.getMessage('TASKS_CLUE_FLASH_COMMENT_COUNTER_TITLE');
	}

	getText(): string
	{
		return Loc.getMessage('TASKS_CLUE_FLASH_COMMENT_COUNTER_TEXT');
	}
}
