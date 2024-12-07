import { sendFeedback, wasFeedbackSent } from 'crm.ai.feedback';
import { Builder, Dictionary } from 'crm.integration.analytics';
import { Loc, onCustomEvent } from 'main.core';
import { sendData } from 'ui.analytics';
import { UI } from 'ui.notification';
import { copilotSliderInstance, sliderButtonsAdapter } from '../ai-form-fill-app';
import { entityEditorProxy } from '../app';
import { ControlValue } from '../services/entity-editor-proxy';
import { EntityEditorRender } from '../services/entity-editor-render';
import { timeout } from '../services/utils';
import type { EntityInfo } from './types';
import {
	ConflictField,
	EditorControlsParams,
	FEEDBACK_TRIGGER_APP_CLOSE,
	FEEDBACK_TRIGGER_CONTROL,
	FormFieldsToMergeResult,
} from './types';

export default {
	async initialize({ dispatch, getters }) {
		await dispatch('fetchFormFieldsToMerge');
		await dispatch('createEntityEditor');
		await dispatch('collectFieldDataFromEntityEditor');
		await dispatch('updateControlPositionInfo');
	},
	async fetchFormFieldsToMerge({ commit, getters }) {
		const data: FormFieldsToMergeResult = await fetchMergeFields(getters.mergeUuid);

		const fields: ConflictField[] = data.fields.map((field): ConflictField => {
			return {
				name: field.name,
				type: field.type,
				title: field.title,
				aiModel: field.aiModel,
				isMultiple: field.isMultiple,
				isUserField: field.isUserField,
				aiValue: field.aiModel.VALUE,
				originalValue: null,
				originalModel: null,
				isAiValuesUsed: false,
			};
		});

		commit('setConflictFields', fields);
		commit('setEditMode', data.editMode);
		commit('setEntityInfo', data.target);
		commit('setAiFeedbackWasSent', data.target.feedbackWasSent);
		commit('setAiFeedbackShowBeforeClose', !data.target.feedbackWasSent);
	},
	async saveFormFieldsToMerge({ getters, commit, dispatch }) {
		const fieldNamesToApply = getters.conflictFields
			.filter((field: ConflictField) => field.isAiValuesUsed)
			.map((field: ConflictField) => field.name);

		const mergeUuid = getters.mergeUuid;

		const response = await BX.ajax.runAction('crm.timeline.ai.applyMerge', {
			method: 'GET',
			getParameters: { mergeUuid, fieldNamesToApply },
		});
		commit('setAiFeedbackShowBeforeClose', false);

		if (response.status === 'success')
		{
			dispatch('closeFormWithoutConfirm');
		}
		else
		{
			UI.Notification.Center.notify({
				content: Loc.getMessage('CRM_AI_FORM_FILL_MERGER_SAVE_ERROR'),
				autoHideDelay: 5000,
			});
		}
	},
	showFeedbackMessageBeforeClose({ getters, commit }) {
		commit('showFeedbackMessageIfNeeded', FEEDBACK_TRIGGER_APP_CLOSE);
		commit('setAiFeedbackShowBeforeClose', false);
	},
	closeFeedbackMessage({ getters, commit, dispatch }, sendFeedback: boolean = false) {
		if (sendFeedback)
		{
			dispatch('sendFeedBack');
			commit('setAiFeedbackShowBeforeClose', false);
		}
		commit('hideFeedbackMessage');
		if (getters.aiFeedback.lastTriggeredBy === FEEDBACK_TRIGGER_APP_CLOSE)
		{
			dispatch('closeFormWithoutConfirm');
		}
	},
	closeFormWithoutConfirm({ getters, commit }) {
		commit('setNeededShowCloseConfirm', false);
		commit('setIsConfirmPopupShow', false);
		const mergeUuid = getters.mergeUuid;
		onCustomEvent(window, 'BX.Crm.AiFormFill:CloseSlider', { mergeUuid });
	},
	async setEditorFieldValue({ dispatch, getters, commit }, conflictField)
	{
		const fieldName = conflictField.name;
		const isSetAiValue = !conflictField.isAiValuesUsed;

		const value = isSetAiValue ? conflictField.aiValue : conflictField.originalValue;
		const model = isSetAiValue ? conflictField.aiModel : conflictField.originalModel;

		if (!isSetAiValue)
		{
			setTimeout(() => {
				commit('showFeedbackMessageIfNeeded', FEEDBACK_TRIGGER_CONTROL);
			}, 300);
		}

		const controlValue: ControlValue = { value, model };

		await entityEditorProxy.setFieldValue(fieldName, controlValue);
		await entityEditorProxy.setControlAiClass(fieldName, isSetAiValue);

		commit('setIsFieldsTouched', true);
		commit('updateConflictField', {
			name: fieldName,
			field: {
				isAiValuesUsed: !conflictField.isAiValuesUsed,
			},
		});
	},
	async createEntityEditor({ getters, commit, dispatch }) {
		const getEntityInfo: EntityInfo = getters.getEntityInfo;
		const entityEditorRender = new EntityEditorRender({
			entityId: getEntityInfo.entityId,
			configId: getEntityInfo.editorId,
			entityTypeName: getEntityInfo.entityTypeName,
			domContainerId: `crm-ai-merge-fields__container__${getters.mergeUuid}`,
		});

		const editor = await entityEditorRender.render();

		await entityEditorProxy.init(editor);

		entityEditorProxy.setOnUserFieldDeployedCb(async () => {
			const scrollPositionThreshold = 40;
			const scrollPosY = Math.floor(
				getters.getMainLayoutScrollPosition + getters.getMainLayoutContainerHeight,
			);

			const scrollHeight = getters.getMainLayoutScrollHeight || 0;

			let waitMs = 0;
			if (scrollHeight - scrollPosY < scrollPositionThreshold)
			{
				waitMs = 400;
			}
			// at the scroll bottom position entity editor will shake and resize, to prevent it do some timeout before
			// update control positions info
			await timeout(waitMs);
			dispatch('updateControlPositionInfo');
		});
	},

	async collectFieldDataFromEntityEditor({ getters, commit, dispatch }) {
		const conflictFields = getters.conflictFields;

		const fieldsIds: Set<string> = new Set(conflictFields.map((field) => field.name));

		const fieldParams: EditorControlsParams[] = await entityEditorProxy.getEditorControlsParams(fieldsIds);
		if (fieldParams.length === 0)
		{
			return;
		}

		for (const param of fieldParams)
		{
			commit('updateConflictField', {
				name: param.fieldId,
				field: {
					originalValue: param.originalValue,
					originalModel: param.originalModel,
					order: param.order,
				},
			});

			commit('setEeControlPositions', {
				fieldId: param.fieldId,
				topPosition: param.relatedFieldOffsetY,
			});
		}
		commit('setIsEntityEditorLoaded', true);
	},

	async updateControlPositionInfo({ getters, commit }, { updateOnlyFrom } = {}) {
		const conflictFields: ConflictField[] = getters.conflictFields;

		if (conflictFields.length === 0)
		{
			return;
		}

		const fieldsIds: Set<string> = new Set(conflictFields.map((field) => field.name));

		const positions = await entityEditorProxy.getEditorControlsPositions(fieldsIds);

		const scrollPosition = getters.getMainLayoutScrollPosition || 0;

		for (const [fieldId, topPosition] of positions)
		{
			positions.set(fieldId, scrollPosition + topPosition);
		}

		for (const field of conflictFields)
		{
			const fieldId = field.name;
			if (!updateOnlyFrom && updateOnlyFrom > field.order)
			{
				continue;
			}

			if (!positions.has(fieldId))
			{
				continue;
			}

			commit('setEeControlPositions', {
				fieldId,
				topPosition: positions.get(fieldId),
			});
		}
	},

	async applyAllAiFields({ dispatch, getters }) {
		for (const field: ConflictField of getters.conflictFields)
		{
			if (field.isAiValuesUsed)
			{
				continue;
			}
			dispatch('setEditorFieldValue', field);
		}
	},

	revertAllAiFields({ dispatch, getters }) {
		for (const field: ConflictField of getters.conflictFields)
		{
			if (!field.isAiValuesUsed)
			{
				continue;
			}
			dispatch('setEditorFieldValue', field);
		}
	},
	showEntityEditorControlOutline(store, { fieldName, isShow }) {
		entityEditorProxy.setControlOutline(fieldName, isShow);
	},
	updateSliderFooter({ getters }) {
		const disable = getters.isFooterHiddenAndSaveDisabled;
		sliderButtonsAdapter.saveButton.setDisabled(disable);

		copilotSliderInstance?.footerDisplay(!disable);
	},
	async sendFeedBack({ commit, getters }) {
		const mergeUuid = getters.mergeUuid;

		if (getters.aiFeedback.checkFeedbackBeforeSend)
		{
			const checkResult = await checkIsFeedbackAlreadySend(mergeUuid);
			if (checkResult)
			{
				commit('setAiFeedbackWasSent', true);

				return;
			}
		}

		const getEntityInfo: EntityInfo = getters.getEntityInfo;
		const ownerType: string = getEntityInfo.entityTypeName;
		sendFeedback(mergeUuid, ownerType, getters.activityId);
		commit('setAiFeedbackWasSent', true);
	},

	sendAiCallParsingData({ getters }, element: string): void
	{
		const getEntityInfo: EntityInfo = getters.getEntityInfo;
		const ownerType: string = getEntityInfo.entityTypeName;
		const activityId: number = getters.activityId;
		const activityDirection: string = getters.activityDirection;

		sendData(
			Builder.AI.CallParsingEvent.createDefault(ownerType, activityId, Dictionary.STATUS_SUCCESS)
				.setElement(element)
				.setActivityDirection(activityDirection)
				.buildData(),
		);

		sendData(
			Builder.AI.CallParsingEvent.createDefault(ownerType, activityId, Dictionary.STATUS_SUCCESS)
				.setTool(Dictionary.TOOL_CRM)
				.setCategory(Dictionary.CATEGORY_AI_OPERATIONS)
				.setElement(element)
				.setActivityDirection(activityDirection)
				.buildData(),
		);
	},
};

const checkIsFeedbackAlreadySend = async (mergeUuid: string) => {
	return wasFeedbackSent(mergeUuid);
};

const fetchMergeFields = async (mergeUuid: string) => {
	const response = await BX.ajax.runAction('crm.timeline.ai.mergeFields', {
		method: 'GET',
		getParameters: { mergeUuid },
	});

	return response.data;
};
