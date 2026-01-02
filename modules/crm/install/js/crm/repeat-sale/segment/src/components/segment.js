import { Builder } from 'crm.integration.analytics';

import { ajax as Ajax, Text, Type } from 'main.core';
import { sendData } from 'ui.analytics';
import { BBCodeParser } from 'ui.bbcode.parser';
import { InfoHelper } from 'ui.info-helper';
import { UI } from 'ui.notification';
import { TextEditor } from 'ui.text-editor';

import { AdditionalInfoComponent } from './common/additional-info-component';
import { AiSwitcherComponent } from './common/ai-switcher-component';
import { TextEditorWrapperComponent } from './common/text-editor-wrapper-component';
import { Button, ButtonEvents } from './navigation/button';
import { CallAssessmentSelector } from './selector/call-assessment-selector';
import { CategorySelector } from './selector/category-selector';
import { StageSelector } from './selector/stage-selector';
import { UserSelector } from './selector/user-selector';

export const Segment = {
	components: {
		Button,
		AdditionalInfoComponent,
		AiSwitcherComponent,
		TextEditorWrapperComponent,
		CallAssessmentSelector,
		CategorySelector,
		StageSelector,
		UserSelector,
	},

	props: {
		settings: {
			type: Object,
			default: {},
		},
		segment: {
			type: Object,
			required: true,
		},
		categories: {
			type: Object,
			required: true,
		},
		callAssessments: {
			type: Object,
			required: true,
		},
		events: {
			type: Object,
			default: {},
		},
		analytics: {
			type: Object,
			default: {},
		},
		textEditor: TextEditor,
	},

	data(): Object
	{
		const { segment, textEditor, categories, settings } = this;
		const id = segment?.id ?? null;
		const isEnabled = segment?.isEnabled ?? null;

		const firstCategory = categories[0];

		let isAiEnabled = false;
		if (
			settings.ai?.isAvailable
			&& (settings.ai?.isSponsored || settings.baas?.hasPackage)
		)
		{
			isAiEnabled = segment.isAiEnabled ?? true;
		}

		return {
			id,
			isEnabled,
			text: textEditor.getText(),
			parser: new BBCodeParser(),

			currentCategoryId: segment.entityCategoryId ?? firstCategory.id,
			currentStageId: segment.entityStageId ?? this.getFirstAvailableCategoryStageId(firstCategory),
			assignmentUserIds: new Set(segment.assignmentUserIds ?? []),
			currentEntityTitlePattern: segment.entityTitlePattern ?? null,
			currentCallAssessmentId: segment.callAssessmentId ?? null,
			currentIsAiEnabled: isAiEnabled,
		};
	},

	mounted(): void
	{
		this.$Bitrix.eventEmitter.subscribe(ButtonEvents.click, this.onNavigationButtonClick);

		this.sendViewAnalytics();
	},

	beforeUnmount(): void
	{
		this.$Bitrix.eventEmitter.unsubscribe(ButtonEvents.click, this.onNavigationButtonClick);
	},

	methods: {
		onSaveCallback(): void
		{
			if (Type.isFunction(this.events?.onSave))
			{
				this.events.onSave();
			}
		},
		onNavigationButtonClick({ data }): void
		{
			const { id } = data;
			if (id === 'cancel' || id === 'close')
			{
				this.sendCancelAnalytics();
				this.closeSlider();

				return;
			}

			this.sendData();
		},
		sendData(): void
		{
			const data = {
				entityTypeId: 2, // temporary only deal
				entityCategoryId: this.currentCategoryId,
				entityStageId: this.currentStageId,
				assignmentUserIds: [...this.assignmentUserIds.values()],
				entityTitlePattern: this.currentEntityTitlePattern,
				callAssessmentId: this.currentCallAssessmentId,
				isAiEnabled: this.currentIsAiEnabled,
			};

			if (!this.currentIsAiEnabled)
			{
				data.prompt = this.textEditor.getText();
			}

			if (!this.validate(data))
			{
				return;
			}

			const dataParams = {
				id: this.id,
				data,
			};

			top.BX.Event.EventEmitter.emit('crm:repeatSale:segment:beforeSave', dataParams);

			Ajax
				.runAction('crm.repeatsale.segment.save', { json: dataParams })
				.then(
					(response) => {
						top.BX.Event.EventEmitter.emit('crm:repeatSale.segment:save', {
							...dataParams,
							status: response?.status,
						});

						if (response?.status !== 'success')
						{
							UI.Notification.Center.notify({
								content: Text.encode(response.errors[0].message),
								autoHideDelay: 6000,
							});

							return;
						}

						this.onSaveCallback();

						this.sendEditAnalytics();

						this.closeSlider();
					},
					(response) => {
						const messageCode = 'CRM_REPEAT_SALE_SEGMENT_SAVE_ERROR';

						UI.Notification.Center.notify({
							content: this.$Bitrix.Loc.getMessage(messageCode),
							autoHideDelay: 6000,
						});
					},
				)
				.catch((response) => {
					UI.Notification.Center.notify({
						content: Text.encode(response.errors[0].message),
						autoHideDelay: 6000,
					});

					throw response;
				})
			;
		},
		validate(data: Object): boolean
		{
			if (!Type.isArrayFilled(data.assignmentUserIds))
			{
				UI.Notification.Center.notify({
					content: this.$Bitrix.Loc.getMessage('CRM_REPEAT_SALE_SEGMENT_VALIDATE_ASSIGNMENT_USERS_ERROR'),
					autoHideDelay: 6000,
				});

				return false;
			}

			if (!this.currentIsAiEnabled && !Type.isStringFilled(this.getPlainText()))
			{
				UI.Notification.Center.notify({
					content: this.$Bitrix.Loc.getMessage('CRM_REPEAT_SALE_SEGMENT_VALIDATE_TEXT_ERROR'),
					autoHideDelay: 6000,
				});

				return false;
			}

			return true;
		},
		closeSlider(): void
		{
			top.BX.SidePanel.Instance.getSliderByWindow(window).close();
		},
		getPlainText(): number
		{
			return this.parser.parse(this.textEditor.getText()).toPlainText().trim();
		},
		onSelectCategory(category: Object): void
		{
			if (this.currentCategoryId === category.id)
			{
				return;
			}

			this.$refs.stageSelector.destroy();
			this.currentCategoryId = category.id;

			void this.$nextTick(() => {
				const currentCategory = this.getCategoryById(this.currentCategoryId);
				this.currentStageId = this.getFirstAvailableCategoryStageId(currentCategory);
			});
		},
		onSelectStage(stage: Object): void
		{
			this.currentStageId = stage.id;
		},
		getCategoryById(id: number): Object
		{
			return this.categories.find((category) => category.id === id);
		},
		getFirstAvailableCategoryStageId(category: Object): string
		{
			return category.items[0].id;
		},
		onSelectAssignmentUser(user: Object): void
		{
			this.assignmentUserIds.add(user.id);
		},
		onDeselectAssignmentUser(user: Object): void
		{
			this.assignmentUserIds.delete(user.id);
		},
		setCurrentCallAssessmentId(id: number): void
		{
			this.currentCallAssessmentId = id;
		},
		getMessageByCode(code: string): string
		{
			return this.$Bitrix.Loc.getMessage(code);
		},
		setCurrentIsAiEnabled(value: boolean): void
		{
			this.currentIsAiEnabled = value;
		},
		sendViewAnalytics(): void
		{
			const section = this.analytics.section ?? '';
			const viewEvent = Builder.RepeatSale.Segment.ViewEvent.createDefault(section);
			sendData(viewEvent.buildData());
		},
		sendCancelAnalytics(): void
		{
			const section = this.analytics.section ?? '';
			const viewEvent = Builder.RepeatSale.Segment.CancelEvent.createDefault(section);
			sendData(viewEvent.buildData());
		},
		sendEditAnalytics(): void
		{
			const section = this.analytics.section ?? '';
			const editEvent = Builder.RepeatSale.Segment.EditEvent.createDefault(section);

			if (
				!this.currentIsAiEnabled
				&& this.getPlainPromptText() !== this.getPlainSegmentText()
			)
			{
				editEvent.setIsActivityTextChanged(true);
			}

			if (this.segment.entityTitlePattern !== this.currentEntityTitlePattern)
			{
				editEvent.setIsEntityTitlePatternChanged(true);
			}

			if (this.segment.isAiEnabled !== this.currentIsAiEnabled)
			{
				editEvent.setIsCopilotEnabled(this.currentIsAiEnabled);
			}

			editEvent.setSegmentCode(this.segment.code);

			sendData(editEvent.buildData());
		},
		getPlainPromptText(): string
		{
			return this.parseText(this.textEditor.getText());
		},
		getPlainSegmentText(): string
		{
			return this.parseText(this.segment.prompt);
		},
		parseText(text: string): string
		{
			return this.parser.parse(text).toPlainText().trim();
		},
	},

	computed: {
		readOnly(): boolean
		{
			return this.settings.isReadOnly;
		},
		isSponsored(): boolean
		{
			return this.settings.ai?.isSponsored ?? false;
		},
		isAiAvailable(): boolean
		{
			return this.settings.ai?.isAvailable ?? false;
		},
		aiDisabledSliderCode(): ?string
		{
			return this.settings.ai?.aiDisabledSliderCode ?? null;
		},
		isBaasAvailable(): boolean
		{
			return this.settings.baas?.isAvailable ?? false;
		},
		isBaasHasPackage(): boolean
		{
			return this.settings.baas?.hasPackage ?? false;
		},
		packageEmptySliderCode(): ?string
		{
			return this.settings.baas?.aiPackagesEmptySliderCode ?? null;
		},
		aiCallEnabled(): boolean
		{
			return this.settings.isAiCallEnabled;
		},
		repeatSaleSegmentSection(): Array
		{
			return [
				'crm-repeat-sale__segment-section',
			];
		},
		title(): string
		{
			const code = this.readOnly ? 'CRM_REPEAT_SALE_SEGMENT_TITLE_READ_ONLY' : 'CRM_REPEAT_SALE_SEGMENT_TITLE';

			return this.$Bitrix.Loc.getMessage(code);
		},
		currentCategory(): ?Object
		{
			return this.categories.find((category) => category.id === this.currentCategoryId) ?? null;
		},
		messages(): Object
		{
			return {
				textAreaTitle: this.getMessageByCode('CRM_REPEAT_SALE_SEGMENT_MANUAL_TEXTAREA_TITLE'),
				dealHelp: this.getMessageByCode('CRM_REPEAT_SALE_SEGMENT_DEAL_HELP'),
				sectionTitle: this.getMessageByCode('CRM_REPEAT_SALE_SEGMENT_MANUAL_SECTION_TITLE'),
				stageTitle: this.getMessageByCode('CRM_REPEAT_SALE_SEGMENT_MANUAL_STAGE_TITLE'),
				dealAssignedTitle: this.getMessageByCode('CRM_REPEAT_SALE_SEGMENT_DEAL_ASSIGNED_TITLE'),
				dealTitlePattern: this.getMessageByCode('CRM_REPEAT_SALE_SEGMENT_DEAL_NAME_PATTERN_TITLE'),
				assessmentTitle: this.getMessageByCode('CRM_REPEAT_SALE_SEGMENT_CALL_ASSESSMENT_TITLE'),
				assessmentDescription: this.getMessageByCode('CRM_REPEAT_SALE_SEGMENT_CALL_ASSESSMENT_DESCRIPTION'),
			};
		},
	},

	watch: {
		currentIsAiEnabled(value: boolean): void
		{
			if (
				this.isAiAvailable
				&& (
					this.isSponsored
					|| this.isBaasHasPackage
				)
			)
			{
				this.currentIsAiEnabled = value;

				return;
			}

			if (value === true)
			{
				if (!this.isAiAvailable && this.aiDisabledSliderCode)
				{
					InfoHelper.show(this.aiDisabledSliderCode);
				}
				else if (!this.isBaasHasPackage && this.packageEmptySliderCode)
				{
					InfoHelper.show(this.packageEmptySliderCode);
				}

				void this.$nextTick(() => {
					this.currentIsAiEnabled = false;
				});
			}
		},
	},

	// language=Vue
	template: `
		<div class="crm-repeat-sale__segment_container">
			<div class="crm-repeat-sale__segment-wrapper">
				<header class="crm-repeat-sale__segment-section-header">
					<div class="crm-repeat-sale__segment-section-header-title">
						<span>{{title}}</span>
					</div>
				</header>
				<div class="crm-repeat-sale__segment-section-body">
					<section class="crm-repeat-sale__segment-section --main --active">
						<h1 class="crm-repeat-sale__segment-section-title">
							{{segment.title}}
						</h1>
						<div class="crm-repeat-sale__segment-section-description">
							{{segment.description}}
						</div>
						<AdditionalInfoComponent
							:title="messages.dealHelp"
						/>
					</section>
					
					
					<section class="crm-repeat-sale__segment-section --active">
						<div
							class="crm-repeat-sale__segment-fields-row"
							v-if="isBaasAvailable || isSponsored"
						>
							<div class="crm-repeat-sale__segment-field">
								<AiSwitcherComponent
									ref="aiSwitcher"
									:checked="currentIsAiEnabled"
									:read-only="readOnly"
									@change="setCurrentIsAiEnabled"
								/>
							</div>
						</div>
						
						<div
							v-if="!currentIsAiEnabled"
							class="crm-repeat-sale__segment-fields-row"
						>
							<div class="crm-repeat-sale__segment-field">
								<div class="crm-repeat-sale__segment-field-title">
									{{messages.textAreaTitle}}
								</div>
								<TextEditorWrapperComponent
									:textEditor="textEditor"
								/>
							</div>
						</div>
						
						<div class="crm-repeat-sale__segment-fields-row">
							<div class="crm-repeat-sale__segment-field">
								<div class="crm-repeat-sale__segment-field-title">
									{{messages.sectionTitle}}
								</div>
								<CategorySelector 
									:current-category-id="currentCategoryId"
									:categories="categories"
									:read-only="readOnly"
									@onSelectItem="onSelectCategory"
								/>
							</div>
							<div class="crm-repeat-sale__segment-field">
								<div class="crm-repeat-sale__segment-field-title">
									{{messages.stageTitle}}
								</div>
								<StageSelector 
									ref="stageSelector"
									:current-stage-id="currentStageId"
									:category="currentCategory"
									:read-only="readOnly"
									@onSelectItem="onSelectStage"
								/>
							</div>
						</div>

						<div class="crm-repeat-sale__segment-fields-row">
							<div class="crm-repeat-sale__segment-field">
								<div class="crm-repeat-sale__segment-field-title">
									{{messages.dealAssignedTitle}}
								</div>
								<UserSelector
									:user-ids="[...assignmentUserIds.values()]"
									:read-only="readOnly"
									@onSelectItem="onSelectAssignmentUser"
									@onDeselectItem="onDeselectAssignmentUser"
								/>
							</div>
						</div>
						
						<div class="crm-repeat-sale__segment-fields-row">
							<div class="crm-repeat-sale__segment-field">
								<div class="crm-repeat-sale__segment-field-title">
									{{messages.dealTitlePattern}}
								</div>
								<input
									class="ui-ctl-element"
									type="text"
									v-model="currentEntityTitlePattern"
									:readonly="readOnly"
								>
							</div>
						</div>
					</section>

					<section 
						:class="repeatSaleSegmentSection"
						v-if="aiCallEnabled && false"
					>
						<h1 class="crm-repeat-sale__segment-section-title --level2">
							{{messages.assessmentTitle}}
						</h1>
						<div class="crm-repeat-sale__segment-section-description">
							{{messages.assessmentDescription}}
						</div>

						<div class="crm-repeat-sale__segment-field">
							<CallAssessmentSelector 
								:call-assessments="callAssessments"
								:current-call-assessment-id="currentCallAssessmentId"
								:read-only="readOnly"
								@onSelectItem="setCurrentCallAssessmentId"
							/>
							{{currentCallAssessmentId}}
						</div>
					</section>
				</div>
			</div>
			<div class="crm-repeat-sale__segment_navigation-container">
				<div class="crm-repeat-sale__segment_navigation-buttons-wrapper">
					<Button v-if="!readOnly" id="update" />
					<Button v-if="!readOnly" id="cancel" />
					<Button v-if="readOnly" id="close" />
				</div>
			</div>
		</div>
	`,
};
