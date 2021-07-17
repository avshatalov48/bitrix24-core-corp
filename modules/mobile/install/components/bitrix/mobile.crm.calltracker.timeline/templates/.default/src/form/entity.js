import {EventEmitter} from 'main.core.events';

export default class Entity extends EventEmitter
{
	constructor()
	{
		super();
		this.setEventNamespace('CRM:Calltracker:');
		this.status = 'ready';
		this.error = null;
	}

	isReady()
	{
		return this.status === 'ready';
	}

	isFailed()
	{
		return this.error !== null;
	}

	execute()
	{
		this.status = 'busy';

		this.emit('start');

		this
			.prepare()
			.then(this.submit.bind(this))
			.then(this.succeed.bind(this))
			.then(this.finalise.bind(this))
			.catch((err) => {
				this.fail(err);
				this.finalise();
			})
		;
	}

	prepare()
	{
		return Promise.resolve();
	}

	submit()
	{
		return Promise.resolve();
	}

	succeed({data})
	{
		this.emit('success', {entity: this, data: data});
	}

	fail(error: Error)
	{
		this.error = error;
		this.emit('error', {entity: this, error: error});
	}

	finalise()
	{
		this.status = 'finished';
		this.emit('finish', {entity: this});
	}
}