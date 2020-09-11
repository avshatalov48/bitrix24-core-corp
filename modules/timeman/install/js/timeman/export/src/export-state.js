import {Event} from "main.core";

export class ExportState extends Event.EventEmitter
{
	constructor()
	{
		super();

		this.states = {
			intermediate: 0,
			running: 1,
			completed: 2,
			stopped: 3,
			error: 4
		};
	}

	isRunning()
	{
		return this.state === this.states.running;
	}

	setRunning()
	{
		this.state = this.states.running;

		this.emit('running');
	}

	setIntermediate()
	{
		this.state = this.states.intermediate;

		this.emit('intermediate');
	}

	setStopped()
	{
		this.state = this.states.stopped;

		this.emit('stopped');
	}

	setCompleted()
	{
		this.state = this.states.completed;

		this.emit('completed');
	}

	setError()
	{
		this.state = this.states.error;

		this.emit('error');
	}
}