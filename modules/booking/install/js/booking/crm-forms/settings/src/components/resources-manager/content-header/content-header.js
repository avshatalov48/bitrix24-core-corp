import { BaseHeader } from 'ui.entity-selector';

export class ContentHeader extends BaseHeader
{
	constructor(...props)
	{
		super(...props);

		this.getContainer();
	}

	render(): HTMLElement
	{
		return this.options.content;
	}
}
