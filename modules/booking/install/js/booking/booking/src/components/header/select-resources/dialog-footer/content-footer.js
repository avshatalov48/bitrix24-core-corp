import { BaseFooter } from 'ui.entity-selector';
import './dialog-footer.css';

export class ContentFooter extends BaseFooter
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
