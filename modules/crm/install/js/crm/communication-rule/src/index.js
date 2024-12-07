import { Type } from 'main.core';
import { BitrixVue, VueCreateAppResult } from 'ui.vue3';
import { CommunicationRule as CommunicationRuleComponent } from './components/communication-rule';
import './communication-rule.css';

type CommunicationRuleParams = {
	rule: Object,
	channels: Object[],
	entities: Object[],
	searchTargetEntities: Object[],
	selectedTargetEntitySectionId?: string,
	selectedTargetEntityTypeIds?: Array<Object>,
}

export class CommunicationRule
{
	#container: HTMLElement;
	#app: ?VueCreateAppResult = null;
	#channels: Object[];
	#entities: Object[];
	#rule: Object;
	#searchTargetEntities: Object[];
	#selectedTargetEntitySectionId: ?string;
	#selectedTargetEntityTypeIds: ?Object[];

	constructor(containerId: string, params: CommunicationRuleParams)
	{
		this.#channels = params.channels;
		this.#entities = params.entities;
		this.#rule = params.rule;
		this.#searchTargetEntities = params.searchTargetEntities;

		this.#selectedTargetEntitySectionId = params.selectedTargetEntitySectionId ?? null;
		this.#selectedTargetEntityTypeIds = params.searchTargetEntities ?? [];

		this.#container = document.getElementById(containerId);

		if (!Type.isDomNode(this.#container))
		{
			throw new Error('container not found');
		}

		this.#app = BitrixVue.createApp(
			CommunicationRuleComponent,
			{
				rule: this.#rule,
				channels: this.#channels,
				entities: this.#entities,
				searchTargetEntities: this.#searchTargetEntities,
				selectedTargetEntitySectionId: this.#selectedTargetEntitySectionId,
				selectedTargetEntityTypeIds: this.#selectedTargetEntityTypeIds,
			},
		);

		this.#app.mount(this.#container);
	}
}
