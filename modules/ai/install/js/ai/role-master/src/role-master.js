import { Tag, Type } from 'main.core';
import { EventEmitter } from 'main.core.events';
import { BitrixVue, VueCreateAppResult } from 'ui.vue3';
import { type RoleMasterOptions } from './types';
import { RoleMaster as RoleMasterRootComponent } from './components/role-master';

import './css/role-master.css';

export const RoleMasterEvents = {
	CLOSE: 'close',
	SAVE_SUCCESS: 'save-success',
};

export class RoleMaster extends EventEmitter
{
	#roleMasterOptions: RoleMasterOptions;
	#app: VueCreateAppResult;

	constructor(options: RoleMasterOptions)
	{
		super(options);

		this.setEventNamespace('AI.RoleMaster');

		this.#roleMasterOptions = options;
	}

	render(): HTMLElement
	{
		EventEmitter.subscribe('AI.RoleMasterApp:Close', () => {
			this.emit(RoleMasterEvents.CLOSE);
		});
		EventEmitter.subscribe('AI.RoleMasterApp:Save-success', (event) => {
			this.emit(RoleMasterEvents.SAVE_SUCCESS, event.getData());
		});

		const appContainer = Tag.render`<div class="ai__role-master-app-container"></div>`;
		this.#app = BitrixVue.createApp(RoleMasterRootComponent, {
			id: this.#roleMasterOptions.id ?? '',
			authorId: this.#roleMasterOptions.authorId ?? '',
			name: this.#roleMasterOptions.name ?? '',
			text: this.#roleMasterOptions.text ?? '',
			description: this.#roleMasterOptions?.description ?? '',
			avatar: this.#roleMasterOptions?.avatar ?? '',
			avatarUrl: this.#roleMasterOptions?.avatarUrl ?? '',
			itemsWithAccess: this.#roleMasterOptions?.itemsWithAccess ?? [],
		});

		this.#app.mount(appContainer);

		return appContainer;
	}

	destroy(): void
	{
		this.#app?.unmount();
	}

	static validateOptions(options: RoleMasterOptions)
	{
		if (!options)
		{
			return;
		}

		if (options && Type.isObject(options) === false)
		{
			throw new Error('AI.RoleMaster: options must be the object');
		}

		if (options.id && Type.isStringFilled(options.id) === false)
		{
			throw new Error('AI.RoleMaster: id option must be the filled string');
		}

		if (options.text && Type.isStringFilled(options.text) === false)
		{
			throw new Error('AI.RoleMaster: roleText option must be the filled string');
		}

		if (options.avatar && Type.isStringFilled(options.avatar) === false)
		{
			throw new Error('AI.RoleMaster: avatar option must be the url string');
		}

		if (options.avatarUrl && Type.isStringFilled(options.avatar) === false)
		{
			throw new Error('AI.RoleMaster: avatar option must be the url string');
		}

		if (options.description && Type.isStringFilled(options.description) === false)
		{
			throw new Error('AI.RoleMaster: description option must be the filled string');
		}

		if (options.name && Type.isStringFilled(options.name) === false)
		{
			throw new Error('AI.RoleMaster: name option must be the filled string');
		}

		if (options.itemsWithAccess && Type.isArrayFilled(options.itemsWithAccess) === false)
		{
			throw new Error('AI.RoleMaster: users option must be the array.');
		}
	}
}
