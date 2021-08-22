import {DefaultFooter, Dialog} from 'ui.entity-selector';
import {Loc, Tag} from 'main.core';

export default class Footer extends DefaultFooter
{
	constructor(dialog: Dialog, options: {[option: string]: any})
	{
		super(dialog, options);
	}

	getContent(): HTMLElement | HTMLElement[] | string | null
	{
		return this.cache.remember('content', () => {
			return Tag.render`
                <div class="my-module-footer-class">
                    ${Loc.getMessage('TASKS_ENTITY_SELECTOR_TAG_FOOTER_CREATE_NEW')}
                </div>
            `;
		});
	}
}