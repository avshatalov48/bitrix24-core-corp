import { Runtime, Text, Type } from 'main.core';
import type { AnalyticsOptions } from 'ui.analytics';

const Categories = Object.freeze({
	text: 'text_operations',
	image: 'image_operations',
	readonly: 'read_operations',
	promptSaving: 'prompt_saving',
});

const Types = Object.freeze({
	textNew: 'create_new',
	textReply: 'reply_context',
	textEdit: 'edit_context',
	imageNew: 'create_image',
});

const ContextSubSections = Object.freeze({
	fromText: 'from_text',
	fromAudio: 'audio_used',
	fromTextAndAudio: 'from_text+audio_used',
});

const ContextElements = Object.freeze({
	editorButton: 'editor_button',
	spaceButton: 'space_button',
	popupButton: 'popup_button',
	readonlyCommon: 'common',
	readonlyQuote: 'quote',
});

const Events = Object.freeze({
	open: 'open',
	generate: 'generate',
	success: 'success',
	error: 'error',
	saveResult: 'save',
	cancelResult: 'cancel',
	editResult: 'edit',
	copyResult: 'copy_text',
	openPromptsLibrary: 'open_list',
});

type AnalyticsParam = {
	name: string;
	value: string;
}

export class CopilotAnalytics
{
	#tool: string;
	#category: string;
	#event: string;
	#type: string;
	#c_section: ?string;
	#c_sub_section: ?string;
	#c_element: ?string;
	#params: ?AnalyticsParam[] = [];

	constructor()
	{
		this.#tool = 'AI';
		this.#category = '';
	}

	getCategory(): string
	{
		return this.#category;
	}

	getType(): string
	{
		return this.#type;
	}

	getCSection(): ?string
	{
		return this.#c_section;
	}

	getCSubSection(): ?string
	{
		return this.#c_sub_section;
	}

	getCElement(): ?string
	{
		return this.#c_element;
	}

	// region Set category
	setCategoryText(): CopilotAnalytics
	{
		return this.#setCategory(Categories.text);
	}

	setCategoryReadonly(): CopilotAnalytics
	{
		return this.#setCategory(Categories.readonly);
	}

	setCategoryImage(): CopilotAnalytics
	{
		return this.#setCategory(Categories.image);
	}

	setCategoryPromptSaving(): CopilotAnalytics
	{
		return this.#setCategory(Categories.promptSaving);
	}

	#setCategory(category: string): CopilotAnalytics
	{
		if (Object.values(Categories).includes(category))
		{
			this.#category = category;
		}

		return this;
	}
	// endregion

	// region Set type
	setTypeTextNew(): CopilotAnalytics
	{
		return this.#setType(Types.textNew);
	}

	setTypeTextReply(): CopilotAnalytics
	{
		return this.#setType(Types.textReply);
	}

	setTypeTextEdit(): CopilotAnalytics
	{
		return this.#setType(Types.textEdit);
	}

	setTypeImageNew(): CopilotAnalytics
	{
		return this.#setType(Types.imageNew);
	}

	#setType(type: string): CopilotAnalytics
	{
		if (Object.values(Types).includes(type))
		{
			this.#type = type;
		}

		return this;
	}
	// endregion

	// region Set c_section
	setContextSection(cSection: string): CopilotAnalytics
	{
		if (cSection.length > 0)
		{
			this.#c_section = cSection;
		}

		return this;
	}
	// endregion

	// region Set c_sub_section
	setContextTypeFromText(): CopilotAnalytics
	{
		return this.#setContextSubSection(ContextSubSections.fromText);
	}

	setContextTypeFromAudio(): CopilotAnalytics
	{
		return this.#setContextSubSection(ContextSubSections.fromAudio);
	}

	setContextTypeFromTextAndAudio(): CopilotAnalytics
	{
		return this.#setContextSubSection(ContextSubSections.fromTextAndAudio);
	}

	#setContextSubSection(subSection: string): CopilotAnalytics
	{
		if (Object.values(ContextSubSections).includes(subSection))
		{
			this.#c_sub_section = subSection;
		}

		return this;
	}
	// endregion

	// region Set c_element
	setContextElementEditorButton(): CopilotAnalytics
	{
		return this.#setContextElement(ContextElements.editorButton);
	}

	setContextElementSpaceButton(): CopilotAnalytics
	{
		return this.#setContextElement(ContextElements.spaceButton);
	}

	setContextElementReadonlyCommon(): CopilotAnalytics
	{
		return this.#setContextElement(ContextElements.readonlyCommon);
	}

	setContextElementReadonlyQuote(): CopilotAnalytics
	{
		return this.#setContextElement(ContextElements.readonlyQuote);
	}

	setContextElementPopupButton(): CopilotAnalytics
	{
		return this.#setContextElement(ContextElements.popupButton);
	}

	#setContextElement(cElement: string): CopilotAnalytics
	{
		if (Object.values(ContextElements).includes(cElement))
		{
			this.#c_element = cElement;
		}

		return this;
	}
	// endregion

	// region Set params
	setP1(name: string, value: string): CopilotAnalytics
	{
		this.#params[0] = {
			name,
			value,
		};

		return this;
	}

	setP2(name: string, value: string): CopilotAnalytics
	{
		this.#params[1] = {
			name,
			value,
		};

		return this;
	}

	setP3(name: string, value: string): CopilotAnalytics
	{
		this.#params[2] = {
			name,
			value,
		};

		return this;
	}

	setP4(name: string, value: string): CopilotAnalytics
	{
		this.#params[3] = {
			name,
			value,
		};

		return this;
	}

	setP5(name: string, value: string): CopilotAnalytics
	{
		this.#params[4] = {
			name,
			value,
		};

		return this;
	}

	// endregion

	// region Set event and Send
	sendEventOpen(status: string): void
	{
		this.#event = Events.open;

		return this.#sendData(status);
	}

	sendEventGenerate(): void
	{
		this.#event = Events.generate;

		return this.#sendData();
	}

	sendEventSuccess(): void
	{
		this.#event = Events.success;

		return this.#sendData();
	}

	sendEventError(): void
	{
		this.#event = Events.error;

		return this.#sendData();
	}

	sendEventSave(): void
	{
		this.#event = Events.saveResult;

		return this.#sendData();
	}

	sendEventCancel(): void
	{
		this.#event = Events.cancelResult;

		return this.#sendData();
	}

	sendEventOpenPromptLibrary(): void
	{
		this.#event = Events.openPromptsLibrary;

		return this.#sendData();
	}

	sendEventCopyResult(): void
	{
		this.#event = Events.copyResult;

		return this.#sendData();
	}

	sendEventEditResult(): void
	{
		this.#event = Events.editResult;

		return this.#sendData();
	}

	#sendData(status: string): void
	{
		let data = this.#getData();

		if (!data)
		{
			return;
		}

		if (status)
		{
			data = { ...data, status };
		}

		Runtime.loadExtension('ui.analytics')
			.then(({ sendData }) => {
				sendData(data);
			})
			.catch(() => {
				console.error("AI: Copilot: can't load ui.analytics");
			});
	}

	#getData(): ?AnalyticsOptions
	{
		if (
			!this.#tool
			|| !this.#category
			|| !this.#event
		)
		{
			return null;
		}

		const data = {
			tool: this.#tool,
			category: this.#category,
			event: this.#event,
		};

		if (this.#type)
		{
			data.type = this.#type;
		}

		// non required
		if (this.#c_section)
		{
			data.c_section = this.#c_section;
		}

		if (this.#c_sub_section)
		{
			data.c_sub_section = this.#c_sub_section;
		}

		if (this.#c_element)
		{
			data.c_element = this.#c_element;
		}

		// params
		if (this.#params && Type.isArray(this.#params))
		{
			this.#params.forEach((param, index) => {
				if (!param?.value || !param?.name)
				{
					return;
				}

				const { name, value } = param;

				const key = `p${index + 1}`;
				data[key] = `${Text.toCamelCase(name)}_${Text.toCamelCase(value)}`;
			});
		}

		return data;
	}
	// endregion
}
