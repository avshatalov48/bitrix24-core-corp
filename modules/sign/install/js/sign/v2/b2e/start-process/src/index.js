import { Loc, Tag, Type } from 'main.core';
import { MemoryCache } from 'main.core.cache';
import { Loader } from 'main.loader';
import type { Template } from 'sign.v2.api';
import { Api } from 'sign.v2.api';
import { type ItemOptions, SignDropdown } from 'sign.v2.b2e.sign-dropdown';

const dropdownTemplateEntityId = 'sign-b2e-start-process-type';
const dropdownProcessTabId = 'sign-b2e-start-process-types';

export class StartProcess
{
	#cache: MemoryCache<any> = new MemoryCache();
	#api: Api = new Api();

	#templatesList: Promise<Template[]> = this.#api.template.getList();

	constructor()
	{
		void this.#getProcessTypeLayoutLoader().show();
		void this.#templatesList.then((data: Template[]) => this.#onProcessTypesLoaded(data));
	}

	getLayout(): HTMLElement
	{
		return this.#cache.remember('layout', () => {
			return Tag.render`
				<div>
					<h1 class="sign-b2e-settings__header">${Loc.getMessage('SIGN_START_PROCESS_HEAD')}</h1>
					<div class="sign-b2e-settings__item">
						<p class="sign-b2e-settings__item_title">
							${Loc.getMessage('SIGN_START_PROCESS_TYPE')}
						</p>
						${this.#getProcessTypeDropdown().getLayout()}
					</div>
				</div>
			`;
		});
	}

	getSelectedTemplateUid(): string
	{
		return this.#getProcessTypeDropdown().getSelectedId();
	}

	getTemplates(): Promise<Template[]>
	{
		return this.#templatesList;
	}

	#getProcessTypeLayoutLoader(): Loader
	{
		return this.#cache.remember(
			'processTypeLayoutLoader',
			() => new Loader({ target: this.#getProcessTypeDropdown().getLayout() }),
		);
	}

	#getProcessTypeDropdown(): SignDropdown
	{
		return this.#cache.remember(
			'processTypeDropdown',
			() => new SignDropdown({
				tabs: [{ id: dropdownProcessTabId, title: ' ' }],
				entities: [
					{
						id: dropdownTemplateEntityId,
					},
				],
				items: [],
			}),
		);
	}

	#onProcessTypesLoaded(templates: Template[]): void
	{
		const dropdownItems = templates
			.map((template) => this.#createProcessTypeDropdownItemByTemplate(template))
		;
		const processTypeDropdown = this.#getProcessTypeDropdown();
		dropdownItems.forEach((item) => processTypeDropdown.addItem(item));

		void this.#getProcessTypeLayoutLoader().hide();

		const firstDropdownItemId = dropdownItems.at(0)?.id;
		if (!Type.isNil(firstDropdownItemId))
		{
			processTypeDropdown.selectItem(firstDropdownItemId);
		}
	}

	#createProcessTypeDropdownItemByTemplate(template: Template): ItemOptions
	{
		return {
			id: template.uid,
			title: template.title,
			entityId: dropdownTemplateEntityId,
			tabs: dropdownProcessTabId,
			deselectable: false,
		};
	}
}
