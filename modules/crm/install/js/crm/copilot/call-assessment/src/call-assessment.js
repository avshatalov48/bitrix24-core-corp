import { Dom, Event, Loc, Tag, Text, Type } from 'main.core';
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

	#titleId: ?string = null;
	#titleEditButtonId: ?string = null;

	#titleInputContainer = null;
	#titleNode: ?HTMLElement = null;

	#titleInput = null;
	#inputEditButton = null;
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

		this.onTitleInlineEditingStart = this.onTitleInlineEditingStart.bind(this);
		this.onTitleInlineEditingFinish = this.onTitleInlineEditingFinish.bind(this);
		this.onTitleInputEnterPressed = this.onTitleInputEnterPressed.bind(this);
		this.onTitleInputBlur = this.onTitleInputBlur.bind(this);

		const { config, data } = params;

		const { titleId, titleEditButtonId } = config ?? {};

		if (!Type.isStringFilled(titleId) || !Type.isStringFilled(titleEditButtonId))
		{
			return;
		}

		this.#titleEditButtonId = titleEditButtonId;
		this.#titleId = titleId;

		if (Type.isString(data?.title))
		{
			this.#title = data.title;
		}

		this.#bindTitleInlineEditingStart();
	}

	#bindTitleInlineEditingStart(): void
	{
		Event.bind(this.#getInputEditButton(), 'click', this.onTitleInlineEditingStart);
	}

	onTitleInlineEditingStart(): void
	{
		if (this.#titleInputContainer !== null)
		{
			return;
		}

		this.#appendTitleInput();
		this.#hideTitle();

		this.#bindTitleInlineEditingFinish();
	}

	#appendTitleInput(): void
	{
		this.#titleInput = Tag.render`
			<input value="${Text.encode(this.#title)}" type="text" class="crm-copilot__call-assessment_title-item" />
		`;

		this.#titleInputContainer = Tag.render`
			<div class="crm-copilot__call-assessment_title-wrapper">
				${this.#titleInput}
			</div>
		`;

		Dom.append(this.#titleInputContainer, this.#getTitleNode().parentNode);

		this.#titleInput.focus();

		const length = this.#titleInput.value.length;
		this.#titleInput.selectionStart = length;
		this.#titleInput.selectionEnd = length;

		Dom.addClass(
			document.querySelector('.copilot-call-assessment-pagetitle-description'),
			'--title-edit',
		);
	}

	#hideTitle(): void
	{
		Dom.style(this.#getTitleNode(), { display: 'none' });
		Dom.style(this.#getInputEditButton(), { display: 'none' });
	}

	#bindTitleInlineEditingFinish(): void
	{
		Event.bind(document, 'mousedown', this.onTitleInlineEditingFinish);
		Event.bind(this.#titleInput, 'keyup', this.onTitleInputEnterPressed);
		Event.bind(this.#titleInput, 'blur', this.onTitleInputBlur);
	}

	onTitleInputBlur(event: KeyboardEvent): void
	{
		this.onTitleInlineEditingFinish(event, false);
	}

	onTitleInputEnterPressed(event: KeyboardEvent): void
	{
		if (event.key === 'Enter')
		{
			this.onTitleInlineEditingFinish(event, false);
		}
	}

	onTitleInlineEditingFinish(event: InputEvent, checkTarget: boolean = true): void
	{
		if (this.#titleInputContainer === null || this.#titleInput === null)
		{
			return;
		}

		if (
			checkTarget
			&& (event.target === this.#titleInput || event.target === this.#getInputEditButton())
		)
		{
			return;
		}

		const title = this.#titleInput.value;
		if (!Type.isStringFilled(title))
		{
			this.#titleInput.focus();

			return;
		}

		this.#title = title;
		this.#layoutComponent.setTitle(this.#title);

		Event.unbind(document, 'mousedown', this.onTitleInlineEditingFinish);
		Event.unbind(this.#titleInput, 'keyup', this.onTitleInputEnterPressed);
		Event.unbind(this.#titleInput, 'blur', this.onTitleInputBlur);

		this.#hideInputAndShowTitle();
	}

	#getInputEditButton(): HTMLElement
	{
		if (this.#inputEditButton === null)
		{
			this.#inputEditButton = document.getElementById(this.#titleEditButtonId);
		}

		return this.#inputEditButton;
	}

	#hideInputAndShowTitle(): void
	{
		const titleNode = this.#getTitleNode();
		titleNode.innerText = this.#title;

		Dom.style(titleNode, { display: 'inline-block' });
		Dom.style(this.#getInputEditButton(), { display: 'inline-block' });
		Dom.remove(this.#titleInputContainer);

		this.#titleInputContainer = null;

		Dom.removeClass(
			document.querySelector('.copilot-call-assessment-pagetitle-description'),
			'--title-edit',
		);
	}

	#getTitleNode(): HTMLElement
	{
		if (this.#titleNode === null)
		{
			this.#titleNode = document.getElementById(this.#titleId);
		}

		return this.#titleNode;
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
