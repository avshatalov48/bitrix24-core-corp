import {Type} from 'main.core';

export class StoryPoints
{
	constructor()
	{
		this.clearPoints();

		this.differencePoints = 0;
	}

	setPoints(storyPoints: string)
	{
		if (Type.isUndefined(storyPoints))
		{
			return;
		}

		this.saveDifferencePoints((this.storyPoints ? this.storyPoints : 0), storyPoints);

		this.storyPoints = String(storyPoints);
	}

	getPoints(): string
	{
		return String(this.storyPoints);
	}

	addPoints(storyPoints: string)
	{
		if (Type.isUndefined(storyPoints) || isNaN(parseFloat(storyPoints)))
		{
			return;
		}

		const currentStoryPoints = (this.storyPoints !== '' ? parseFloat(this.storyPoints) : 0);
		const inputStoryPoints = parseFloat(storyPoints);

		let result = (currentStoryPoints + inputStoryPoints);

		if (Type.isFloat(result))
		{
			result = result.toFixed(1);
		}

		this.storyPoints = String(result);
	}

	subtractPoints(storyPoints: string)
	{
		if (Type.isUndefined(storyPoints) || isNaN(parseFloat(storyPoints)))
		{
			return;
		}

		const currentStoryPoints = (this.storyPoints !== '' ? parseFloat(this.storyPoints) : 0);
		const inputStoryPoints = parseFloat(storyPoints);

		let result = (currentStoryPoints - inputStoryPoints);

		if (Type.isFloat(result))
		{
			result = result.toFixed(1);
		}

		this.storyPoints = String(result);
	}

	clearPoints()
	{
		this.storyPoints = '';
	}

	saveDifferencePoints(firstPoints: string, secondPoints: string)
	{
		this.differencePoints = 0;

		if (Type.isUndefined(firstPoints) || isNaN(parseFloat(firstPoints)))
		{
			return;
		}

		if (Type.isUndefined(secondPoints) || isNaN(parseFloat(secondPoints)))
		{
			return;
		}

		firstPoints = parseFloat(firstPoints);
		secondPoints = parseFloat(secondPoints);

		this.differencePoints = (secondPoints - firstPoints);
	}

	getDifferencePoints(): number
	{
		return this.differencePoints;
	}
}