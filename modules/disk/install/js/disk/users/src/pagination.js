import {EventEmitter} from "main.core.events";

export default class Pagination extends EventEmitter
{
	busy: boolean = false;
	finished: boolean = true;
	pageNumber: Number = 1;
	callback: ?Function;

	constructor(callback: ?Function)
	{
		super('disk.users');
		if (callback instanceof Function)
		{
			this.finished = false;
			this.callback = callback;
		}
	}

	isFinished()
	{
		return this.finished === true;
	}

	getNext(): boolean
	{
		if (this.busy === true || this.finished === true)
		{
			return false;
		}

		this.busy = true;
		this.callback(++this.pageNumber)
			.then(({data: {
				data,
				getPageCount,
				getCurrentPage
			}, errors}) => {
				this.emit('onGetPage', {
					data,
					getPageCount,
					getCurrentPage
				});
				if (getCurrentPage >= getPageCount)
				{
					this.finished = true;
					this.emit('onEndPage', {
						getPageCount,
						getCurrentPage
					});
				}
				this.busy = false;
			}, () => {
				this.emit('onError');
				this.busy = false;

			});
	}

}