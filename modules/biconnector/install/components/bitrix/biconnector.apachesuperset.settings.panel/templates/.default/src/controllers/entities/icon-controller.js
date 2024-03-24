/* eslint-disable no-underscore-dangle */
import { Tag, Dom } from 'main.core';
import { EventEmitter } from 'main.core.events';
import 'ui.icon-set.main';
import 'ui.design-tokens';

export class IconController extends BX.UI.EntityEditorController
{
	constructor(id, settings)
	{
		super();

		this.initialize(id, settings);
		this.#subscribeOnEvents();
	}

	#subscribeOnEvents(): void
	{
		EventEmitter.subscribeOnce('BX.UI.EntityEditor:onInit', (event) => {
			const [editor] = event.getData();

			const control = editor?._controls?.[0];

			if (control?._sections && control._sections.length > 0)
			{
				this.#fillSectionIcons(control._sections);
			}
		});
	}

	#fillSectionIcons(sectionList: Array<BX.UI.EntityEditorSection>): void
	{
		for (const section of sectionList)
		{
			if (section.getTitle() !== '')
			{
				this.#setSectionIcon(section);
			}
		}
	}

	#setSectionIcon(section: BX.UI.EntityEditorSection): void
	{
		const container = section._headerContainer;
		if (container === null)
		{
			return;
		}

		const data = section.getData();

		const headerTitle = container.querySelector('.ui-entity-editor-header-title');
		if (headerTitle && data.iconClass)
		{
			const icon = Tag.render`
					<span class="
						superset-settings-section-icon
						ui-icon-set 
						${data.iconClass}
					"></span>
			`;

			Dom.insertBefore(icon, headerTitle);
		}
	}
}
