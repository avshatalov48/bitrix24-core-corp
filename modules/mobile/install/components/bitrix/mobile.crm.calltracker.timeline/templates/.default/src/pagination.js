import Backend from './backend';
import {Tag, Loc, Cache} from 'main.core';
import {Loader} from 'main.loader';

export class Pagination
{
	constructor(itemId, callback) {
		this.itemId = itemId;
		this.callback = callback;
		this.pointer = 1;
		this.busy = false;
		this.cache = new Cache.MemoryCache();
	}
	getNode()
	{
		return this.cache.remember('mainNode', () => {
			return Tag.render`<div class="crm-phonetracker-detail-comments-btn-container" onclick="${this.sendPagination.bind(this)}">
				<div class="crm-phonetracker-detail-comments-btn">${Loc.getMessage('MPT_PREVIOUS_COMMENTS')}</div>
			</div>`;
		});
	}
	getLoader(): Loader
	{
		if (!this.cache.has('PaginationLoader'))
		{
			const target = this.getNode().appendChild(Tag.render`<div class="loader"></div>`);
			this.cache.set('PaginationLoader', new Loader({target: target, size: 20}));
		}
		return this.cache.get('PaginationLoader');
	}
	sendPagination(): boolean
	{
		if (this.busy === true)
		{
			return false;
		}
		this.getLoader().show();

		this.busy = true;

		Backend.getItemsFromPage(this.itemId, ++this.pointer)
			.then(({data: {items, paginationHasMore}, errors}) => {
				this.getLoader().hide();
				this.callback.call(this, items)

				if (paginationHasMore !== true)
				{
					this.destroy();
				}
				if (errors.length > 0)
				{
					this.showErrors(errors);
				}
			}, () => {
				this.showErrors(arguments);
			})
			.finally(() => {
				this.busy = false;
			});
	}

	destroy()
	{
		if (this.getNode().parentNode)
		{
			this.getNode().parentNode.removeChild(this.getNode());
		}
		this.getNode().style.display = 'none';
		this.cache.keys().forEach((key) => {
			this.cache.delete(key);
		});
	}
	showErrors(errors)
	{
		console.log('Pagination errors: ', errors);
	}
}