import {Type} from 'main.core';

export class StoryPointsStorage
{
	constructor()
	{
		this.storyPoints = '';
	}

	setPoints(storyPoints: string)
	{
		if (storyPoints === '')
		{
			this.storyPoints = '';

			return;
		}

		if (
			Type.isUndefined(storyPoints)
			|| (Type.isFloat(storyPoints) && isNaN(parseFloat(storyPoints)))
		)
		{
			return;
		}

		if (Type.isFloat(storyPoints))
		{
			storyPoints = parseFloat(storyPoints).toFixed(1);
		}

		this.storyPoints = String(storyPoints);
	}

	getPoints(): string
	{
		return this.storyPoints;
	}

	clearPoints()
	{
		this.storyPoints = '';
	}

	isEmpty(): boolean
	{
		return this.storyPoints === '';
	}
}