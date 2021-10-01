import {Tag, Cache} from 'main.core';
import {EventEmitter} from 'main.core.events';

export default class ItemMoreButton
{
	hiddenFilesCount: number = 0;
	stepNumber: number = 0;
	cache = new Cache.MemoryCache();
	stepValue = 5;

	constructor()
	{
	}

	getContainer(): Element
	{
		return this.cache.remember('container', () => {
			return Tag.render`
		<div class="disk-file-thumb disk-file-thumb-file" onclick="${this.onClick.bind(this)}">
			<div class="ui-icon ui-icon-more disk-file-thumb-icon">
				<i></i>
			</div>
			<div class="disk-file-thumb-text">${this.getValueContainer()}</div>
		</div>`;
		});
	}

	getValueContainer(): Element
	{
		return this.cache.remember('valueContainer', () => {
			return document.createElement('DIV');
		});
	}

	reset()
	{
		this.hiddenFilesCount = 1;
		this.stepNumber = 0;
		this.getValueContainer().innerHTML = this.hiddenFilesCount;
		this.show();
		return this;
	}

	increment()
	{
		this.hiddenFilesCount++;
		this.getValueContainer().innerHTML = this.hiddenFilesCount;
		this.show();
	}

	decrement(step: number = 1)
	{
		this.hiddenFilesCount -= step;
		this.getValueContainer().innerHTML = this.hiddenFilesCount;
		if (this.hiddenFilesCount <= 0)
		{
			this.hide();
		}
	}

	onClick()
	{
		this.stepNumber++;
		const itemsCount = Math.min(30, this.stepValue * this.stepNumber);
		this.decrement(itemsCount);
		EventEmitter.emit(this, 'onGetMore', {itemsCount: itemsCount});
	}

	show()
	{
		delete this.getContainer().style.display;
	}

	hide()
	{
		this.getContainer().style.display = 'none';
	}
}
