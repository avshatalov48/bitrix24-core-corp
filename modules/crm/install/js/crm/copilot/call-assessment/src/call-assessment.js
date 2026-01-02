import { Loc, Reflection, Type } from 'main.core';
import { BasicEditor } from 'ui.text-editor';
import { BitrixVue, VueCreateAppResult } from 'ui.vue3';
import { CallAssessment as CallAssessmentComponent } from './components/call-assessment';
import 'ui.design-tokens';
import './call-assessment.css';

type CallAssessmentParams = {
	data: Object,
	config?: Object,
	events?: Object,
}

export class CallAssessment
{
	#container: HTMLElement;
	#app: ?VueCreateAppResult = null;
	#layoutComponent: ?Object = null;
	#title: string = null;

	#textEditor: BasicEditor = null;
	#isReadOnly: boolean = true;
	#isEnabled: boolean = true;

	constructor(containerId: string, params: CallAssessmentParams = {})
	{
		this.#container = document.getElementById(containerId);

		if (!Type.isDomNode(this.#container))
		{
			throw new Error('container not found');
		}

		this.#isReadOnly = params.config?.readOnly ?? true;
		this.#isEnabled = params.config?.isEnabled ?? true;

		this.#initTitleInlineEditing(params);

		this.#app = BitrixVue.createApp(CallAssessmentComponent, {
			textEditor: this.#getTextEditor(params.data, params.config ?? {}),
			settings: {
				readOnly: this.#isReadOnly,
				isEnabled: this.#isEnabled,
				isCopy: params.config?.isCopy ?? false,
				baas: params.config?.baasSettings ?? false,
			},
			params: {
				data: params.data,
				events: params.events,
			},
		});

		this.#layoutComponent = this.#app.mount(this.#container);
	}

	#initTitleInlineEditing(params: CallAssessmentParams): void
	{
		if (this.#isReadOnly)
		{
			return;
		}

		const ToolbarManager = Reflection.getClass('BX.UI.ToolbarManager');
		const toolbar = ToolbarManager?.getDefaultToolbar();
		if (toolbar)
		{
			const ToolbarEvents = Reflection.getClass('BX.UI.ToolbarEvents');
			toolbar.subscribe(ToolbarEvents.finishEditing, ({ data }) => {
				const { updatedTitle } = data;

				this.#title = updatedTitle;
				this.#layoutComponent.setTitle(this.#title);
			});
		}

		const { data } = params;

		if (Type.isString(data?.title))
		{
			this.#title = data.title;
		}
	}

	#getTextEditor({ prompt: content }, { copilotSettings }): BasicEditor
	{
		if (this.#textEditor !== null)
		{
			return this.#textEditor;
		}

		const toolbar = (
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
			placeholder: Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PLACEHOLDER'),
			paragraphPlaceholder: Loc.getMessage(
				Type.isPlainObject(copilotSettings)
					? 'CRM_COPILOT_CALL_ASSESSMENT_PLACEHOLDER_WITH_COPILOT'
					: null,
			),
			toolbar,
			floatingToolbar: [],
			collapsingMode: false,
			copilot: {
				copilotOptions: Type.isPlainObject(copilotSettings) ? copilotSettings : null,
				triggerBySpace: true,
			},
		});

		return this.#textEditor;
	}
}
