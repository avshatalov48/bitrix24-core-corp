import {Uri, Type} from 'main.core';
import {EventEmitter} from 'main.core.event';
import ItemGroup from './item-group';

export default class ItemGroupSystem extends ItemGroup
{
	constructor() {
		super(...arguments);

		this.container
			.querySelector('[data-role="item-edit-control"]')
			.style.display = 'none';
	}
	static code = 'system_group';
}
