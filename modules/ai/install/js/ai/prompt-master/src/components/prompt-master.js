import { Loc, ajax, Event, Reflection, Extension } from 'main.core';
import { BIcon } from 'ui.icon-set.api.vue';
import { Actions, Main as MainIconSet } from 'ui.icon-set.api.core';
import { Button } from 'ui.buttons';
import { hint } from 'ui.vue3.directives.hint';
import { Loader } from 'main.loader';

import { PromptMasterPromptTypes, promptTypes } from './prompt-master-prompt-types';
import { PromptMasterStep } from './prompt-master-step';
import { PromptMasterEditorStep } from './prompt-master-editor-step';
import { PromptMasterAccessStep } from './prompt-master-access-step';
import { PromptMasterBtn } from './prompt-master-btn';
import { PromptMasterBackBtn } from './prompt-master-back-btn';
import { PromptMasterProgress } from './prompt-master-progress';
import { PromptMasterAlertMessage } from './prompt-master-alert-message';
import { PromptMasterSaveErrorScreen } from './prompt-master-save-error-screen';

const promptTypeName = {
	DEFAULT: Loc.getMessage('PROMPT_MASTER_PROMPT_TYPE_FIRST_NAME'),
	SIMPLE_TEMPLATE: Loc.getMessage('PROMPT_MASTER_PROMPT_TYPE_SECOND_NAME'),
};

type PromptMasterData = {
	promptType: string;
};

const currentUserId = Extension.getSettings('ai.prompt-master').get('userId');

export const PromptMaster = {
	props: {
		code: {
			type: String,
			required: false,
			default: '',
		},
		type: String,
		title: String,
		text: String,
		icon: String,
		categories: Array,
		accessCodes: Array,
		analyticCategory: String,
		authorId: String,
	},
	directives: {
		hint,
	},
	components: {
		PromptMasterStep,
		PromptMasterPromptTypes,
		PromptMasterEditorStep,
		PromptMasterAccessStep,
		PromptMasterBtn,
		PromptMasterBackBtn,
		PromptMasterProgress,
		PromptMasterAlertMessage,
		PromptMasterSaveErrorScreen,
		BIcon,
	},
	data(): PromptMasterData {
		return {
			currentStepIndex: 0,
			promptType: this.type || '',
			promptTitle: this.title || '',
			promptText: this.text || '',
			promptIcon: this.icon || MainIconSet.ROCKET,
			selectedItems: this.accessCodes || [['user', currentUserId]],
			promptCategories: this.categories || [],
			saveButtonState: '',
			isPromptSaving: false,
			isPromptSaved: false,
			isPromptSavedError: false,
		};
	},
	computed: {
		isPromptEditing(): boolean {
			return !this.isPromptSaving && !this.isPromptSavedError && !this.isPromptSaved;
		},
		isSecondStepEnabled(): boolean {
			return Boolean(this.promptType);
		},
		promptTypeName(): string {
			return promptTypeName[this.promptType];
		},
		chevronLeftIcon(): string {
			return Actions.CHEVRON_LEFT;
		},
		checkIcon(): string {
			return MainIconSet.CHECK;
		},
		isEditPromptMaster(): boolean
		{
			return Boolean(this.code);
		},
		alertMessage(): string
		{
			if (this.isEditPromptMaster && this.accessCodes.length > 1)
			{
				return this.$Bitrix.Loc.getMessage('PROMPT_MASTER_TYPE_ALERT');
			}

			return '';
		},
		useClarificationInEditor(): boolean
		{
			return this.promptType === promptTypes[1].id;
		},
		maxSymbolsCount(): number {
			return 2500;
		},
		isPromptCanBeSave(): boolean {
			const isTitleValid = this.promptTitle.length > 1 && this.promptTitle.length <= 70;
			const isPromptCategoriesValid = this.promptCategories.length > 0;
			const isAccessPointsValid = this.selectedItems.length > 0;

			return isTitleValid && isPromptCategoriesValid && isAccessPointsValid && !this.isPromptSaving;
		},
		closeMasterBtnColor(): string {
			return Button.Color.LIGHT_BORDER;
		},
		promptEditorStepTitle(): string {
			if (this.promptType === 'DEFAULT')
			{
				return this.text
					? this.$Bitrix.Loc.getMessage('PROMPT_MASTER_PROMPT_STEP_TITLE_FOR_EDIT_SIMPLE_PROMPT')
					: this.$Bitrix.Loc.getMessage('PROMPT_MASTER_PROMPT_STEP_TITLE_FOR_ADD_SIMPLE_PROMPT')
				;
			}

			return this.$Bitrix.Loc.getMessage('PROMPT_MASTER_PROMPT_STEP_TITLE');
		},
	},
	methods: {
		handlePromptTypeSelect(selectedPromptType: string): void {
			this.promptType = selectedPromptType;
			// this.currentStepIndex += 1;
		},
		handlePromptIconSelect(selectedIcon: string): void {
			this.promptIcon = selectedIcon;
		},
		async savePrompt(): void {
			if (this.isPromptCanBeSave === false)
			{
				return;
			}

			let isLoadFinished = false;
			try
			{
				setTimeout(() => {
					if (isLoadFinished === false)
					{
						this.isPromptSavedError = false;
						this.saveButtonState = Button.State.WAITING;
						this.isPromptSaving = true;
					}
				}, 100);

				const action = this.code ? 'change' : 'create';

				const data = {
					promptCode: this.code,
					analyticCategory: this.analyticCategory,
					promptType: this.promptType,
					promptTitle: this.promptTitle,
					promptDescription: this.promptText,
					promptIcon: this.promptIcon,
					accessCodes: this.selectedItems,
					categoriesForSave: this.promptCategories,
				};

				await ajax.runAction(`ai.prompt.${action}`, {
					data,
				});

				Event.EventEmitter.emit('AI.prompt-master-app:save-success', data);
				this.isPromptSaved = true;
			}
			catch (e)
			{
				console.error(e);
				this.isPromptSavedError = true;
				Event.EventEmitter.emit('AI.prompt-master-app:save-failed');
			}
			finally
			{
				this.isPromptSaving = false;
				isLoadFinished = true;
				this.saveButtonState = '';
			}
		},
		selectItem(user: Object): void {
			this.selectedItems.push([
				user.entityId,
				user.id,
			]);
		},
		deselectItem(item: Object): void {
			const removingUserIndex = this.selectedItems.findIndex((currentItem) => {
				return currentItem[0] === item.entityId && String(currentItem[1]) === String(item.id);
			});

			if (removingUserIndex > -1)
			{
				this.selectedItems.splice(removingUserIndex, 1);
			}
		},
		handlePromptNameInput(name: string): void {
			this.promptTitle = name;
		},
		handlePromptTextInput(text: string): void {
			this.promptText = text;
		},
		selectCategory(categoryId: string) {
			this.promptCategories.push(categoryId);
		},
		deselectCategory(categoryId: string) {
			const removingCategoryIndex = this.promptCategories.indexOf(categoryId);

			this.promptCategories.splice(removingCategoryIndex, 1);
		},
		handleBackBtnClick() {
			this.isPromptSavedError = false;
		},
		handleRepeatRequestBtnClick() {
			this.savePrompt();
		},
		openArticleAboutPromptMaster(): void {
			const articleCode = '21979776';

			const Helper = Reflection.getClass('top.BX.Helper');

			if (Helper)
			{
				Helper.show(`redirect=detail&code=${articleCode}`);
			}
		},
		emitCloseMasterEvent(): void {
			Event.EventEmitter.emit('AI.prompt-master-app:close-master');
		},
	},
	watch: {
		isPromptSaving(isSaving) {
			if (isSaving === false)
			{
				return;
			}

			const copilotPrimaryColor = getComputedStyle(document.body).getPropertyValue('--ui-color-copilot-primary');

			const loader = new Loader({
				size: 80,
				strokeWidth: 4,
				target: this.$refs.loaderScreen,
				color: copilotPrimaryColor,
			});

			loader.show(this.$refs.loaderScreen);
		},
	},
	template: `
		<div class="ai__prompt-master">
			<transition-group>
				<PromptMasterStep
					v-show="currentStepIndex === 0 && isPromptEditing"
					:suptitle="$Bitrix.Loc.getMessage('PROMPT_MASTER_SELECT_TYPE_STEP_SUPTITLE')"
					:title="$Bitrix.Loc.getMessage('PROMPT_MASTER_SELECT_TYPE_STEP_TITLE')"
					:steps-count="3"
					:step-index="1"
					:alert-message="alertMessage"
				>
					<template #content>
						<PromptMasterPromptTypes @select="handlePromptTypeSelect" :active-prompt-type="promptType"/>
					</template>
					<template #footer>
						<div class="ai__prompt-master_navigation">
							<div class="ai__prompt-master_prompt-types-step-more">
									<span
										@click="openArticleAboutPromptMaster"
										class="ai__prompt-master_more-details"
									>
										{{ $Bitrix.Loc.getMessage('PROMPT_MASTER_SELECT_TYPE_STEP_MORE') }}
									</span>
							</div>
							<PromptMasterBtn
								@click="currentStepIndex += 1"
								:disabled="isSecondStepEnabled === false"
								:text="$Bitrix.Loc.getMessage('PROMPT_MASTER_BTN_NEXT')"
							/>
						</div>
					</template>
				</PromptMasterStep>
				<PromptMasterStep
					v-show="currentStepIndex === 1 && isPromptEditing"
					:suptitle="promptTypeName"
					:title="promptEditorStepTitle"
					:steps-count="3"
					:step-index="2"
				>
					<template #content>
						<PromptMasterEditorStep
							:is-shown="currentStepIndex === 1 && isPromptEditing"
							:use-clarification="useClarificationInEditor"
							:prompt-text="promptText"
							:max-symbols-count="maxSymbolsCount"
							@input-text="handlePromptTextInput"
						/>
					</template>
					<template #footer>
						<div class="ai__prompt-master_navigation">
							<PromptMasterBackBtn @click="currentStepIndex -= 1"></PromptMasterBackBtn>
							<PromptMasterBtn
								:disabled="promptText.length > maxSymbolsCount || promptText.length < 6"
								@click="currentStepIndex += 1"
								:text="$Bitrix.Loc.getMessage('PROMPT_MASTER_BTN_NEXT')"
							/>
						</div>
					</template>
				</PromptMasterStep>
				<PromptMasterStep
					v-show="currentStepIndex === 2 && isPromptEditing"
					:suptitle="promptTypeName"
					:title="$Bitrix.Loc.getMessage('PROMPT_MASTER_ACCESS_STEP_TITLE')"
					:steps-count="3"
					:step-index="3"
				>
					<template #content>
						<PromptMasterAccessStep
							:is-shown="currentStepIndex === 2 && isPromptEditing"
							:prompt-icon="promptIcon"
							:prompt-title="promptTitle"
							:selected-items="selectedItems"
							:selected-categories="promptCategories"
							:prompt-author-id="authorId"
							@select-icon="handlePromptIconSelect"
							@input-name="handlePromptNameInput"
							@select-item="selectItem"
							@deselect-item="deselectItem"
							@select-category="selectCategory"
							@deselect-category="deselectCategory"
						/>
					</template>
					<template #footer>
						<div class="ai__prompt-master_navigation">
							<PromptMasterBackBtn @click="currentStepIndex -= 1"></PromptMasterBackBtn>
							<PromptMasterBtn
								@click="savePrompt"
								:text="$Bitrix.Loc.getMessage('PROMPT_MASTER_BTN_SAVE')"
								:disabled="isPromptCanBeSave === false"
								:state="saveButtonState"
							/>
						</div>
					</template>
				</PromptMasterStep>
				<div v-show="isPromptSaving" class="ai__prompt-master_loader">
					<div ref="loaderScreen" class="ai__prompt-master_loader-loader"></div>
					<div class="ai__prompt-master_loader-text">
						{{ $Bitrix.Loc.getMessage('PROMPT_MASTER_SAVE_IN_PROCESS') }}
					</div>
				</div>
				<div v-if="isPromptSaved" class="ai__prompt-master_success">
					<div class="ai__prompt-master_success-icon">
						<BIcon :name="checkIcon" size="58" color="#8E52EC"></BIcon>
					</div>
					<div class="ai__prompt-master_success-text">
						{{ $Bitrix.Loc.getMessage('PROMPT_MASTER_SAVE_SUCCESS') }}
					</div>
					<div class="ai__prompt-master_success-close-btn">
						<PromptMasterBtn
							@click="emitCloseMasterEvent"
							:color="closeMasterBtnColor"
							:text="$Bitrix.Loc.getMessage('PROMPT_MASTER_SAVE_CLOSE_BTN')"
						/>
					</div>
				</div>
				<div v-if="isPromptSavedError" class="ai__prompt-master_error">
					<PromptMasterSaveErrorScreen
						@click-repeat-btn="handleRepeatRequestBtnClick"
						@click-back-btn="handleBackBtnClick"
					/>
				</div>
			</transition-group>
		</div>
	`,
};
