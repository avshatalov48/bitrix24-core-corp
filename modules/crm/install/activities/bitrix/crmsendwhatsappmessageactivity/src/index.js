import { Reflection, Type, Event, ajax, Text, Dom } from 'main.core';
import { Editor, FilledPlaceholder } from 'crm.template.editor';
import { Designer, tryGetGlobalContext, Template, SelectorItemsManager, enrichFieldsWithModifiers } from 'bizproc.automation';

const namespace = Reflection.namespace('BX.Crm.Activity');

import './css/style.css';

class CrmSendWhatsAllMessageActivity
{
	#isRobot: boolean;
	#documentType: [];
	#formName: string;
	#editorWrapper: HTMLDivElement;
	#templates: Map = new Map();
	#placeholders: Map = new Map();
	#templateId: number;

	#dialogItems: [] = [];
	#items: Object<string, { title: string, parentTitle: string }> = {};

	constructor(options: {
		isRobot: boolean,
		documentType: [],
		formName: string,
		editorWrapper: HTMLDivElement,
		currentTemplateId: ?string,
		currentTemplate: ?{},
		currentPlaceholders: ?{},
	})
	{
		this.#isRobot = Type.isBoolean(options.isRobot) ? options.isRobot : true;

		if (!Type.isArrayFilled(options.documentType))
		{
			throw new Error('documentType must be filled array');
		}
		this.#documentType = options.documentType;

		if (!Type.isElementNode(options.editorWrapper))
		{
			throw new Error('editorWrapper must be HTMLDivElement');
		}
		this.#editorWrapper = options.editorWrapper;

		if (!Type.isStringFilled(options.formName))
		{
			throw new Error('formName must be filled string');
		}
		this.#formName = options.formName;

		const form = document.forms[this.#formName];
		if (!form || !form.template_id)
		{
			throw new Error('form must have template_id element');
		}
		Event.bind(form.template_id, 'change', this.#onChangeTemplate.bind(this));

		this.#setOnBeforeSaveSettingsCallback();

		this.#fillDialogItems();
		if (Type.isPlainObject(options.currentTemplate) && (Text.toInteger(options.currentTemplateId) > 0))
		{
			this.#setCurrentTemplate(
				Text.toInteger(options.currentTemplateId),
				options.currentTemplate,
				options.currentPlaceholders,
			);
		}
	}

	#onChangeTemplate(event): void
	{
		const target: ?HTMLSelectElement = event.target;
		if (!target)
		{
			return;
		}

		const selectedOptions = target.selectedOptions;
		const templateId = selectedOptions.item(0) ? Text.toInteger(selectedOptions.item(0).value) : 0;

		this.#templateId = templateId;

		if (templateId <= 0)
		{
			this.#removeTemplateEditor();

			return;
		}

		if (this.#templates.has(templateId))
		{
			this.#insertTemplateEditor(templateId);

			return;
		}

		this.#loadTemplate(templateId)
			.then(({ data }) => {
				if (Type.isPlainObject(data))
				{
					this.#addTemplate(templateId, data);
					this.#insertTemplateEditor(templateId);
				}
			})
			.catch((response) => console.error(response.errors))
		;
	}

	#setOnBeforeSaveSettingsCallback(): void
	{
		if (!this.#isRobot)
		{
			return;
		}

		const designer = Designer.getInstance();
		const dialog = designer ? designer.getRobotSettingsDialog() : null;
		if (dialog?.robot)
		{
			dialog.robot.setOnBeforeSaveRobotSettings(this.#onBeforeSaveRobotSettings.bind(this));
		}
	}

	#setCurrentTemplate(templateId: number, template: {}, placeholders): void
	{
		this.#templateId = templateId;
		this.#addTemplate(templateId, template);
		if (Type.isPlainObject(placeholders))
		{
			const templatePlaceholders: Map = this.#placeholders.get(templateId);
			Object.entries(placeholders).forEach(([key: string, value: string]) => {
				if (Object.hasOwn(this.#items, value))
				{
					templatePlaceholders.set(
						key,
						{ value, parentTitle: this.#items[value].parentTitle, title: this.#items[value].title },
					);
				}
			});
		}

		this.#insertTemplateEditor(templateId);
	}

	#addTemplate(templateId: number, data: {}): void
	{
		const content = Type.isString(data.content) ? data.content : '';

		this.#templates.set(
			templateId,
			{
				content: Text.encode(content).replaceAll('\n', '<br>'),
				placeholders: Type.isPlainObject(data.placeholders) ? data.placeholders : {},
			},
		);
		this.#placeholders.set(templateId, new Map());
	}

	#loadTemplate(templateId: number): Promise
	{
		return ajax.runAction(
			'bizproc.activity.request',
			{
				data: {
					documentType: this.#documentType,
					activity: 'CrmSendWhatsAppMessageActivity',
					params: {
						template_id: templateId,
						form_name: this.#formName,
					},
				},
			},
		);
	}

	#insertTemplateEditor(templateId: number): void
	{
		const data = this.#templates.get(templateId);

		Dom.addClass(this.#editorWrapper, 'bizproc-automation-whats-app-message-activity-editor');
		Dom.removeClass(
			this.#isRobot ? this.#editorWrapper.parentElement : this.#editorWrapper.parentElement?.parentElement,
			'--hidden',
		);

		const editor = new Editor({
			target: this.#editorWrapper,
			onSelect: ({ id, value, parentTitle, title }) => {
				const templatePlaceholders: Map = this.#placeholders.get(templateId);
				templatePlaceholders.set(id, { value, parentTitle, title });
			},
			dialogOptions: {
				items: this.#dialogItems,
				entities: [],
			},
			usePlaceholderProvider: false,
			canUseFieldsDialog: true,
			canUseFieldValueInput: false,
		});

		editor
			.setPlaceholders(data.placeholders)
			.setFilledPlaceholders(this.#prepareFilledPlaceholders(templateId))
			.setBody(data.content)
		;
	}

	#fillDialogItems(): void
	{
		if (!this.#isRobot)
		{
			return;
		}

		const context = tryGetGlobalContext();
		if (!context)
		{
			return;
		}

		const designer = Designer.getInstance();
		const component = designer ? designer.component : null;
		const dialog = designer ? designer.getRobotSettingsDialog() : null;
		const template: Template = dialog ? dialog.template : null;
		const triggerManager = component ? component.triggerManager : null;

		const robotsWithReturnFields = template ? template.getRobotsWithReturnFields(dialog.robot) : [];

		const manager = new SelectorItemsManager({
			documentFields: enrichFieldsWithModifiers(context.document.getFields(), 'Document'),
			documentTitle: context.document.title,
			globalVariables: context.automationGlobals.globalVariables,
			variables: template ? template.getVariables() : null,
			globalConstants: context.automationGlobals.globalConstants,
			constants: template ? template.getConstants() : null,
			activityResultFields: (
				robotsWithReturnFields.map((robot) => {
					return {
						id: robot.getId(),
						title: robot.getTitle(),
						fields: enrichFieldsWithModifiers(
							robot.getReturnFieldsDescription(),
							robot.getId(),
							{
								friendly: false,
								printable: false,
								server: false,
								responsible: false,
								shortLink: true,
							},
						),
					};
				})
			),
			triggerResultFields: (
				triggerManager && template ? triggerManager.getReturnProperties(template.getStatusId()) : null
			),
			useModifier: true,
		});

		this.#dialogItems = manager.groupsWithChildren;
		manager.items.forEach((field) => {
			this.#items[field.id] = {
				title: field.title,
				parentTitle: field.supertitle,
			};
		});
	}

	#prepareFilledPlaceholders(templateId: number): FilledPlaceholder[]
	{
		const placeholders = [];

		const templatePlaceholders: Map = this.#placeholders.get(templateId);
		templatePlaceholders.forEach((data, key) => {
			placeholders.push({
				PLACEHOLDER_ID: key,
				FIELD_NAME: data.value,
				FIELD_ENTITY_TYPE: 'bp',
				TITLE: data.title,
				PARENT_TITLE: data.parentTitle,
			});
		});

		return placeholders;
	}

	#removeTemplateEditor(): void
	{
		Dom.removeClass(this.#editorWrapper, 'bizproc-automation-whats-app-message-activity-editor');
		Dom.clean(this.#editorWrapper);
		Dom.addClass(
			this.#isRobot ? this.#editorWrapper.parentElement : this.#editorWrapper.parentElement?.parentElement,
			'--hidden',
		);
	}

	#onBeforeSaveRobotSettings(): Object
	{
		if (this.#templateId > 0)
		{
			const placeholders = {};
			this.#placeholders.get(this.#templateId).forEach(({ value }, key) => {
				placeholders[key] = value;
			});

			return { placeholders };
		}

		return {};
	}
}

namespace.CrmSendWhatsAllMessageActivity = CrmSendWhatsAllMessageActivity;
