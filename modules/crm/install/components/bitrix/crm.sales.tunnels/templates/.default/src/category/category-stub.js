import {Dom} from 'main.core';
import {Category} from './category';

export default class CategoryStub extends Category
{
	constructor(options)
	{
		super(options);
		Dom.addClass(this.getContainer(), 'crm-st-category-stub');
		Dom.removeClass(this.getContainer(), 'crm-st-category-automation-disabled');

		this.getAllColumns()
			.forEach((column) => {
				column.marker.disable();
			});
	}
}