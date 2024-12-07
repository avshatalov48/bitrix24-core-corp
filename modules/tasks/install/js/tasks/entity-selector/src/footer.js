import { DefaultFooter, Dialog } from 'ui.entity-selector';
import { Loc, Tag } from 'main.core';

export default class Footer extends DefaultFooter
{
	constructor(dialog: Dialog, options: { [option: string]: any })
	{
		super(dialog, options);

		this.userId = options.userId ? options.userId.toString() : BX.message('USER_ID');
		this.taskId = options.taskId ? options.taskId.toString() : 0;
		this.groupId = options.groupId ? options.groupId.toString() : 0;
	}

	getContent(): HTMLElement | HTMLElement[] | string | null
	{
		if (this.#isTaskTemplateFooter())
		{
			return this.#renderTasksTemplateFooter();
		}

		return this.#renderTasksTagFooter();
	}

	#isTaskTemplateFooter(): boolean
	{
		return [...this.dialog.entities.keys()][0] === 'task-template';
	}

	#renderTasksTagFooter(): HTMLElement
	{
		let url = '/company/personal/user/' + this.userId + '/tasks/tags/';
		const task = this.taskId;
		const group = this.groupId;
		if (group !== 0)
		{
			url = '/company/personal/user/' + this.userId + '/tasks/tags/?GROUP_ID=' + group;
		}
		return this.cache.remember('content', () => {
			return Tag.render`
				<div class="tags-widget-custom-footer">
					<a class="ui-selector-footer-link ui-selector-footer-link-add"  
						id="tags-widget-custom-footer-add-new" hidden="true">
							${Loc.getMessage('TASKS_ENTITY_SELECTOR_TAG_FOOTER_CREATE')}
					</a>
					<span class="ui-selector-footer-conjunction" 
						id="tags-widget-custom-footer-conjunction" hidden="true">
							${Loc.getMessage('TASKS_ENTITY_SELECTOR_TAG_FOOTER_OR')}
					</span>
					<a class="ui-selector-footer-link" 
						onclick="BX.SidePanel.Instance.open(\'${url}\', {
									width: 1000,
									requestMethod: 'post',
									requestParams: {
										taskId: ${task},
									},
								})
						">
							${Loc.getMessage('TASKS_ENTITY_SELECTOR_TAG_FOOTER_GET_TAG_SLIDER')}
					</a>
				</div>
			`;
		});
	}

	#renderTasksTemplateFooter(): HTMLElement | null
	{
		if (!this.options.canCreateTemplate)
		{
			return null;
		}

		return Tag.render`
			<a class="ui-selector-footer-link ui-selector-footer-link-add" href="${this.options.templateAddUrl}">
				${Loc.getMessage('TASKS_ENTITY_SELECTOR_TEMPLATE_FOOTER_CREATE_TEMPLATE')}
			</a>
		`;
	}
}