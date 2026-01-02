import { Loc, Type } from 'main.core';
import { BasicEditor } from 'ui.text-editor';
import { BitrixVue, VueCreateAppResult } from 'ui.vue3';
import { Segment as SegmentComponent } from './components/segment';
import 'ui.design-tokens';
import 'ui.design-tokens.air';
import './segment.css';

type RepeatSaleSegmentParams = {
	data: Object,
	config?: Object,
	events?: Object,
	analytics?: Object,
}

export class Segment
{
	#container: HTMLElement;
	#app: ?VueCreateAppResult = null;

	#textEditor: BasicEditor = null;
	#isReadOnly: boolean = true;

	constructor(containerId: string, params: RepeatSaleSegmentParams = {})
	{
		this.#container = document.getElementById(containerId);

		if (!Type.isDomNode(this.#container))
		{
			throw new Error('container not found');
		}

		this.#isReadOnly = params.config?.readOnly ?? true;

		this.#app = BitrixVue.createApp(SegmentComponent, {
			textEditor: this.#getTextEditor(params.data.segment, params.config ?? {}),
			title: params.config?.title ?? '',
			settings: {
				isReadOnly: this.#isReadOnly,
				isCopy: params.config?.isCopy ?? false,
				ai: params.data.aiSettings,
				baas: params.data.baasSettings,
				isAiCallEnabled: params.data.isAiCallEnabled,
			},
			segment: params.data.segment,
			categories: params.data.categories,
			callAssessments: params.data.callAssessments,
			events: params.events,
			analytics: params.analytics,
		});

		this.#app.mount(this.#container);
	}

	#getTextEditor({ prompt: content }, { copilotSettings }): BasicEditor
	{
		if (this.#textEditor !== null)
		{
			return this.#textEditor;
		}

		const floatingToolbar = (
			this.#isReadOnly
				? []
				: [
					'bold', 'italic', 'underline', 'strikethrough',
					'|',
					'numbered-list', 'bulleted-list',
					'copilot',
				]
		);

		this.#textEditor = new BasicEditor({
			editable: !this.#isReadOnly,
			removePlugins: ['BlockToolbar'],
			minHeight: 250,
			maxHeight: 400,
			content,
			placeholder: Loc.getMessage('CRM_REPEAT_SALE_SEGMENT_PLACEHOLDER'),
			paragraphPlaceholder: Loc.getMessage(
				Type.isPlainObject(copilotSettings)
					? 'CRM_REPEAT_SALE_SEGMENT_PLACEHOLDER_WITH_COPILOT'
					: null,
			),
			toolbar: [],
			floatingToolbar,
			collapsingMode: false,
			copilot: {
				copilotOptions: Type.isPlainObject(copilotSettings) ? copilotSettings : null,
				triggerBySpace: true,
			},
		});

		return this.#textEditor;
	}
}
