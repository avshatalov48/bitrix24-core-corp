import { CallAssessmentSelector } from 'crm.copilot.call-assessment-selector';
import { DatetimeConverter } from 'crm.timeline.tools';
import { Dom, Loc } from 'main.core';
import { BaseEvent } from 'main.core.events';
import { HtmlFormatter } from 'ui.bbcode.formatter.html-formatter';
import { SidePanel } from 'ui.sidepanel';
import { ScriptSelectorDisplayStrategy } from '../script-selector-display-strategy';
import { ViewMode } from './view-mode';

const ARTICLE_CODE = '23240682';

export const ScriptSelector = {
	props: {
		assessmentSettingsId: {
			type: Number,
			required: true,
		},
		assessmentSettingsTitle: {
			type: String,
			required: true,
		},
		isPromptChanged: {
			type: Boolean,
			required: true,
		},
		promptUpdatedAt: {
			type: String,
			required: true,
		},
		prompt: {
			type: String,
			required: true,
		},
		viewMode: {
			type: String,
			default: '',
		},
	},

	data(): Object
	{
		return {
			callAssessmentSelector: this.getCallAssessmentSelector(),
			htmlFormatter: new HtmlFormatter(),
		};
	},

	methods: {
		getCallAssessmentSelector(): CallAssessmentSelector
		{
			return new CallAssessmentSelector({
				currentCallAssessment: {
					id: this.assessmentSettingsId,
					title: (
						this.assessmentSettingsId > 0
							? this.assessmentSettingsTitle
							: Loc.getMessage('CRM_COPILOT_CALL_QUALITY_EMPTY_SCRIPT_LIST_SCRIPT_TITLE')
					),
				},
				emptyScriptListTitle: Loc.getMessage('CRM_COPILOT_CALL_QUALITY_EMPTY_SCRIPT_LIST_SCRIPT_TITLE'),
				displayStrategy: new ScriptSelectorDisplayStrategy(),
				additionalSelectorOptions: {
					dialog: {
						events: {
							'Item:onBeforeSelect': this.onBeforeSelect.bind(this),
						},
					},
					popup: {
						events: {
							onShow: (event) => {
								// @todo maybe there is a better solution?
								const zIndex = SidePanel.Instance.getTopSlider().getZindex() + 1;
								event.target.getZIndexComponent().setZIndex(zIndex);
							},
						},
					},
				},
			});
		},
		onBeforeSelect(event: BaseEvent)
		{
			this.$emit('onBeforeSelect', this.callAssessmentSelector.getCurrentCallAssessmentItem()?.id);
		},
		onEditCallAssessmentSettings({ target }): void
		{
			top.BX.UI.Hint.popupParameters = {
				closeByEsc: true,
				autoHide: true,
				angle: false,
			};

			top.BX.UI.Hint.show(
				target,
				Loc.getMessage('CRM_COPILOT_CALL_QUALITY_SCRIPT_EDIT_HINT'),
				false,
			);

			// temporary disabled
			// Router.openSlider(
			// 	`/crm/copilot-call-assessment/details/${this.#itemIdentifier.id}/`,
			// 	{
			// 		width: 700,
			// 		cacheable: false,
			// 	},
			// );
		},
		onShowActualPrompt(): void
		{
			this.$emit('onShowActualPrompt');
		},
		showArticle(): void
		{
			window.top.BX?.Helper?.show(`redirect=detail&code=${ARTICLE_CODE}`);
		},
		formatHtml(source: string): string
		{
			return this.htmlFormatter.format({ source });
		},
		close(): void
		{
			this.callAssessmentSelector?.close();
		},
		disable(): void
		{
			this.callAssessmentSelector?.disable();
		},
		enable(): void
		{
			this.callAssessmentSelector?.enable();
		},
		doAssessment(): void
		{
			this.$emit('doAssessment');
		},
	},

	mounted()
	{
		Dom.append(this.callAssessmentSelector.getContainer(), this.$refs.container);

		if (this.$refs.prompt)
		{
			Dom.append(this.formattedPrompt, this.$refs.prompt);
		}
	},

	computed: {
		scriptUpdatedAt(): string
		{
			const date = new Date(this.promptUpdatedAt);
			const datetimeConverter = new DatetimeConverter(date);

			const dateString = datetimeConverter.toDatetimeString({
				withDayOfWeek: false,
				delimiter: ', ',
			});

			return Loc.getMessage(
				'CRM_COPILOT_CALL_QUALITY_SCRIPT_INFO_UPDATED',
				{
					'#UPDATED_AT#': dateString,
				},
			);
		},
		formattedPrompt(): string
		{
			return this.formatHtml(this.prompt);
		},
		isShowFooterButtons(): boolean
		{
			return (
				this.viewMode !== ViewMode.usedOtherVersionOfScript
				&& this.viewMode !== ViewMode.usedNotAssessmentScript
				&& this.viewMode !== ViewMode.pending
				&& this.assessmentSettingsId > 0
			);
		},
		footerButtonClassList(): Object
		{
			const isErrorViewMode = (this.viewMode === ViewMode.error);

			return {
				'ui-btn': true,
				'ui-btn-xs': true,
				'ui-btn-no-caps': true,
				'ui-btn-light-border': !isErrorViewMode,
				'ui-btn-round': true,
				'ui-btn-active': !isErrorViewMode,
				'ui-btn-success': isErrorViewMode,
			};
		},
		isEmptyScriptListViewMode(): boolean
		{
			return this.viewMode === ViewMode.emptyScriptList;
		},
	},

	watch: {
		prompt(): void
		{
			Dom.clean(this.$refs.prompt);
			Dom.append(this.formattedPrompt, this.$refs.prompt);
		},
	},

	template: `
		<div>
			<div class="call-quality__script-selector__container">
				<div class="call-quality__script-selector__title">
					<div>
						{{ $Bitrix.Loc.getMessage('CRM_COPILOT_CALL_QUALITY_SCRIPT_SELECTOR_TITLE') }}
					</div>
					<div class="call-quality__script-selector__selector-container" ref="container"></div>
					<div 
						class="call-quality__script-selector__article ui-icon-set --help"
						@click="showArticle"
					></div>
				</div>
			</div>
			<div
				v-if="this.isPromptChanged && isShowFooterButtons"
				class="call-quality__script-info__container"
			>
				<span>{{scriptUpdatedAt}}</span>
				<button
					class="ui-btn ui-btn-xs ui-btn-no-caps ui-btn-round ui-btn-link ui-btn-active"
					@click="onShowActualPrompt"
				>
					{{ $Bitrix.Loc.getMessage('CRM_COPILOT_CALL_QUALITY_SCRIPT_INFO_SHOW_NEW_PROMPT') }}
				</button>
			</div>
			<div class="call-quality__script-container">
				<div
					v-if="isEmptyScriptListViewMode"
					class="call-quality__script-text"
				>
					{{ $Bitrix.Loc.getMessage('CRM_COPILOT_CALL_QUALITY_EMPTY_SCRIPT_LIST_PROMPT_TEXT') }}
				</div>
				<div v-else class="call-quality__script-text" ref="prompt">
				</div>
				
				<div
					v-if="isShowFooterButtons"
					class="call-quality__script-footer"
				>
					<button 
						:class="footerButtonClassList"
						@click="doAssessment"
					>
						{{ $Bitrix.Loc.getMessage('CRM_COPILOT_CALL_QUALITY_SCRIPT_ASSESSMENT_REPLY') }}
					</button>
					<button 
						class="ui-btn ui-btn-xs ui-btn-no-caps ui-btn-round ui-btn-light ui-btn-active edit-button"
						@click="onEditCallAssessmentSettings"
					>
						{{ $Bitrix.Loc.getMessage('CRM_COPILOT_CALL_QUALITY_SCRIPT_EDIT') }}
					</button>
				</div>
			</div>
		</div>
	`,
};
