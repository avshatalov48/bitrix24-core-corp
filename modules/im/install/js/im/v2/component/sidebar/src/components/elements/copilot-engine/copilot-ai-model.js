import { Core } from 'im.v2.application.core';
import { Feature, FeatureManager } from 'im.v2.lib.feature';

import './css/copilot-ai-model.css';

import type { ImModelCopilotAIModel } from 'im.v2.model';
import type { JsonObject } from 'main.core';

// @vue/component
export const CopilotAIModel = {
	name: 'CopilotAIModel',
	props:
	{
		dialogId: {
			type: String,
			required: true,
		},
	},
	data(): JsonObject
	{
		return {
			showAIModelsDialog: false,
		};
	},
	computed:
	{
		aiModels(): ImModelCopilotAIModel[]
		{
			return Core.getStore().getters['copilot/getAIModels'];
		},
		currentAIModelCode(): ImModelCopilotAIModel
		{
			return this.$store.getters['copilot/chats/getAIModel'](this.dialogId);
		},
		currentAIModelName(): string
		{
			return this.aiModels.find((item) => item.code === this.currentAIModelCode.code).name;
		},
		isAIModelChangeAllowed(): boolean
		{
			return FeatureManager.isFeatureAvailable(Feature.isAIModelChangeAllowed);
		},
	},
	methods:
	{
		toggleAIModelList()
		{
			this.showAIModelsDialog = !this.showAIModelsDialog;
		},
	},
	template: `
		<div v-if="isAIModelChangeAllowed" class="bx-im-sidebar-copilot-ai-model__container" @click="toggleAIModelList" ref="change-ai-model">
			<div class="bx-im-sidebar-copilot-ai-model__title">
				{{ currentAIModelName }}
			</div>
			<div class="bx-im-sidebar-copilot-ai-model__arrow-icon"></div>
		</div>
		<!--CopilotAIModelListPopup
			v-if="showAIModelsDialog"
			:bindElement="$refs['change-ai-model']"
			:dialogId="dialogId"
			@close="closeAIModelsListPopup"
		/-->
	`,
};
