import {BaseEvent, EventEmitter} from 'main.core.events';

export default class Queue extends EventEmitter
{
	constructor()
	{
		super('CRM:Calltracker:');
		this.setEventNamespace('CRM:Calltracker:');
		this.queue = [];
		this.erroredQueue = [];
		this.next = this.next.bind(this);
	}

	send(entity: Entity)
	{
		this.queue.push(entity);
		this.check();
	}

	check()
	{
		if (this.queue.length > 0)
		{
			return this.execute(this.queue[0]);
		}

		return this.finish();
	}

	next({data:{entity}}: BaseEvent)
	{
		if (entity.isFailed())
		{
			this.erroredQueue.push(entity);
		}

		if (this.queue[0] === entity)
		{
			this.queue.shift();
		}
		else
		{
			let index = 0;
			this.queue.forEach((ent, ind) => {
				if (ent === entity)
				{
					index = ind;
				}
			});
			this.queue.splice(index, 1);
		}
		this.check();
	}

	execute(entity: Entity)
	{
		if (entity.isReady())
		{
			entity.subscribe('finish', this.next);
			entity.execute();
		}
	}

	finish()
	{
		if (this.erroredQueue.length > 0)
		{
			this.emit('error');
		}
		else
		{
			this.emit('success');
		}
		this.emit('finish');
	}
}