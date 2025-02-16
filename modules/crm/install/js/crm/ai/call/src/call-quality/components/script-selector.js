import { CallAssessmentSelector } from 'crm.copilot.call-assessment-selector';
import { Router } from 'crm.router';
import { DatetimeConverter } from 'crm.timeline.tools';
import { Dom, Loc, Runtime } from 'main.core';
import { BaseEvent } from 'main.core.events';
import { HtmlFormatter } from 'ui.bbcode.formatter.html-formatter';
import { SidePanel } from 'ui.sidepanel';
import { ScriptSelectorDisplayStrategy } from '../script-selector-display-strategy';
import { ViewMode } from './common/view-mode';

const ARTICLE_CODE = '23240682';
const SUCCESS_STATUS = 'SUCCESS';
const PENDING_STATUS = 'PENDING';

export const ScriptSelector = {
	props: {
		assessmentSettingsId: {
			type: Number,
			required: true,
		},
		assessmentSettingsStatus: {
			type: String,
			default: SUCCESS_STATUS,
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
			if (this.assessmentSettingsStatus === PENDING_STATUS)
			{
				this.showDisabledButtonHint(target);

				return;
			}

			Router.openSlider(
				`/crm/copilot-call-assessment/details/${this.assessmentSettingsId}/`,
				{
					width: 700,
					cacheable: false,
				},
			);
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
		doAssessment({ target }): void
		{
			if (this.assessmentSettingsStatus === PENDING_STATUS)
			{
				this.showDisabledButtonHint(target);

				return;
			}

			this.$emit('doAssessment');
		},
		showDisabledButtonHint(target: HTMLElement): void
		{
			top.BX.UI.Hint.popupParameters = {
				closeByEsc: true,
				autoHide: true,
				angle: null,
				events: {},
			};

			Runtime.debounce(
				() => {
					top.BX.UI.Hint.show(
						target,
						Loc.getMessage('CRM_COPILOT_CALL_QUALITY_SCRIPT_DISABLED_DO_ASSESSMENT_HINT'),
						true,
					);
				},
				150,
				this,
			)();
		},
		isDisabledAssessmentButton(): boolean
		{
			return (this.assessmentSettingsStatus !== SUCCESS_STATUS);
		},
		isDisabledEditButton(): boolean
		{
			return (this.assessmentSettingsStatus === PENDING_STATUS);
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
		footerButtonClassList(): Array
		{
			return [
				'ui-btn',
				'ui-btn-xs',
				'ui-btn-no-caps',
				'ui-btn-light-border',
				'ui-btn-round',
				{ 'ui-btn-disabled': this.isDisabledAssessmentButton() },
			];
		},
		footerEditButtonClassList(): Array
		{
			return [
				'ui-btn',
				'ui-btn-xs',
				'ui-btn-no-caps',
				'ui-btn-round',
				'ui-btn-light',
				'edit-button',
				{ 'ui-btn-disabled': this.isDisabledEditButton() },
			];
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
						:class="footerEditButtonClassList"
						@click="onEditCallAssessmentSettings"
					>
						{{ $Bitrix.Loc.getMessage('CRM_COPILOT_CALL_QUALITY_SCRIPT_EDIT') }}
					</button>
				</div>
			</div>
		</div>
	`,
};
