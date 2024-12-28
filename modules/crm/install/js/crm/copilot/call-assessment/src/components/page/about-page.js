import { Loc, Type } from 'main.core';
import { TextEditor, TextEditorComponent } from 'ui.text-editor';
import { TextEditorWrapperComponent } from '../common/text-editor-wrapper-component';
import { BasePage } from './base-page';
import { PromptLength as PromptLengthValidator } from './../../validator/prompt-length';
import { BBCodeParser } from 'ui.bbcode.parser';

export const AboutPage = {
	extends: BasePage,

	components: {
		TextEditorComponent,
		TextEditorWrapperComponent,
	},

	props: {
		textEditor: TextEditor,
	},

	data(): Object
	{
		return {
			id: 'about',
			parser: new BBCodeParser(),
			promptLengthValidator: new PromptLengthValidator(),
			promptLengthError: null,
		};
	},

	methods: {
		getData(): Object
		{
			return {
				prompt: this.textEditor.getText(),
			};
		},
		isReadyToMoveOn(): boolean
		{
			return Type.isStringFilled(this.textEditor.getText());
		},
		validate(): boolean
		{
			return this.promptLengthValidator.validate(this.getPlainPromptText());
		},
		onValidationFailed(): void
		{
			this.promptLengthError = this.promptLengthValidator.getError();
		},
		onChange(): void
		{
			this.promptLengthError = null;
			BasePage.methods.onChange.call(this);
		},
		getPlainPromptText(): number
		{
			return this.parser.parse(this.textEditor.getText()).toPlainText().trim();
		},
	},

	computed: {
		pageTitle(): string
		{
			return Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_ABOUT_TITLE');
		},
		pageDescription(): string
		{
			return Loc.getMessage('CRM_COPILOT_CALL_ASSESSMENT_PAGE_ABOUT_DESCRIPTION');
		},
	},

	template: `
		<div v-if="isActive">
			<div class="crm-copilot__call-assessment_page-section">
				<Breadcrumbs :active-tab-id="id" />
				<header class="crm-copilot__call-assessment_page-section-header">
					<div class="crm-copilot__call-assessment_page-section-header-title">
						{{ pageTitle }}
					</div>
					<div class="crm-copilot__call-assessment_page-section-header-description">
						{{ pageDescription }}
					</div>
				</header>
				<div class="crm-copilot__call-assessment_page-section-body">
					<div class="crm-copilot__call-assessment_page-section-body-field">
						<TextEditorWrapperComponent
							:textEditor="textEditor"
							@change="onChange"
						/>
						<div v-if="promptLengthError" class="crm-copilot__call-assessment_page-section-body-field-error">
							{{ promptLengthError }}
						</div>
					</div>
				</div>
			</div>
		</div>
	`,
};
