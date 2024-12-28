import { Tag, Event, Loc } from 'main.core';
import { DefaultFooter } from 'ui.entity-selector';

export class Footer extends DefaultFooter
{
	render(): HTMLElement
	{
		const element = Tag.render`
			<span class="ui-selector-footer-link ui-selector-footer-link-add">
				${Loc.getMessage('TASKS_FLOW_ENTITY_SELECTOR_FOOTER_CREATE_FLOW')}
			</span>
		`;

		Event.bind(
			element,
			'click',
			this.#onCreateFlowButtonClicked.bind(this),
		);

		return element;
	}

	#onCreateFlowButtonClicked(): Promise
	{
		return new Promise((resolve) => {
			// eslint-disable-next-line promise/catch-or-return
			top.BX.Runtime.loadExtension('tasks.flow.edit-form')
				.then(async (exports) => {
					const editForm = await exports.EditForm.createInstance({
						flowName: '',
					});
					editForm.subscribe('afterSave', (baseEvent) => {
						resolve(baseEvent.getData());
					});
					editForm.subscribe('afterClose', (baseEvent) => {
						resolve();
					});
				})
			;
		}).then((createdFlowData) => {
			if (createdFlowData)
			{
				this.#onFlowCreated(createdFlowData);
			}
		});
	}

	#onFlowCreated(createdFlowData): void
	{
		const item = this.getDialog().addItem({
			tabs: 'recents',
			id: createdFlowData.id,
			entityId: 'flow',
			title: createdFlowData.name,
			customData: {
				groupId: createdFlowData.groupId,
				templateId: createdFlowData.templateId,
			},
		});

		item.select();
	}
}
