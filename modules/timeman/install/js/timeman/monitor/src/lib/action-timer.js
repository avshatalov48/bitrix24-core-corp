class ActionTimer
{
	constructor()
	{
		this.actionsCollection = {};
	}

	start(key)
	{
		this.actionsCollection[key] = {};
		this.actionsCollection[key].start = Date.now();
	}

	finish(key)
	{
		if (
			!this.actionsCollection[key]
			|| !this.actionsCollection[key].start
			|| this.actionsCollection[key].finish
		)
		{
			return;
		}

		this.actionsCollection[key].finish = Date.now();
	}

	getDuration(key)
	{
		if (
			!this.actionsCollection[key]
			|| !this.actionsCollection[key].start
			|| !this.actionsCollection[key].finish
		)
		{
			return;
		}

		const timeInSeconds = (this.actionsCollection[key].finish - this.actionsCollection[key].start) / 1000;

		return `ACTION: ${key}, TIME: ${timeInSeconds.toFixed(2)}s`;
	}
}

let actionTimer = new ActionTimer();

export {actionTimer as ActionTimer};