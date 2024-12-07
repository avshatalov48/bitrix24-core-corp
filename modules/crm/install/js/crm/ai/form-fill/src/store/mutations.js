import type { ConflictField } from './types';
import { EntityInfo, FEEDBACK_TRIGGER_CONTROL } from './types';

/* eslint no-param-reassign: off */
export default {
	setMergeUUID: (state, val) => {
		state.mergeUuid = val;
	},
	setActivityId: (state, val) => {
		state.activityId = val;
	},
	setActivityDirection: (state, val) => {
		state.activityDirection = val;
	},
	startLoading: (state) => {
		state.isLoading = true;
	},
	stopLoading: (state) => {
		state.isLoading = false;
	},
	setEntityInfo: (state, entityInfo: EntityInfo) => {
		state.entityInfo = entityInfo;
	},
	setConflictFields: (state, conflictFields) => {
		state.conflictFields = conflictFields;
	},
	setEditMode: (state, isEditMode) => {
		state.isEditMode = isEditMode;
	},
	setIsEntityEditorLoaded(state, isEntityEditorLoaded) {
		state.isEntityEditorLoaded = isEntityEditorLoaded;
	},
	updateConflictField: (state, { name, field }) => {
		state.conflictFields = state.conflictFields.map((f) => {
			if (f.name === name)
			{
				return {
					...f,
					...field,
				};
			}

			return f;
		});
		const aiAppliedCount = state.conflictFields.filter((f) => f.isAiValuesUsed).length;
		state.aiValuesAppliedCount = aiAppliedCount;
		state.isNeededShowCloseConfirm = aiAppliedCount > 0;
	},
	setEeControlPositions: (state, { fieldId, topPosition }) => {
		state.eeControlPositions.set(fieldId, topPosition);
	},
	toggleExpandedConflictControls: (state, { fieldId, size, isExpanded }) => {
		if (isExpanded)
		{
			state.expandedConflictControls.set(fieldId, size);
		}
		else
		{
			state.expandedConflictControls.delete(fieldId);
		}
	},
	changeMainLayoutScrollPosition: (state, { scrollTop, containerHeight }) => {
		const containerBottomPosition = scrollTop + containerHeight;
		state.mainLayoutScrollPosition = scrollTop;
		state.mainLayoutContainerHeight = containerHeight;

		const hidden = [];
		const controlHeight = 30;

		for (const [key, value] of state.eeControlPositions)
		{
			if (containerBottomPosition < value + controlHeight)
			{
				hidden.push(key);
			}
		}

		if (hidden.length === 0)
		{
			state.notVisibleUnresolvedCount = 0;

			return;
		}

		let counter = 0;
		for (const hideName of hidden)
		{
			const field: ConflictField = state.conflictFields.find((f) => f.name === hideName);

			if (!field || field.isAiValuesUsed)
			{
				continue;
			}
			counter++;
		}
		state.notVisibleUnresolvedCount = counter;
	},
	setIsFieldsTouched(state, isFieldsTouched) {
		state.isFieldsTouched = isFieldsTouched;
	},
	setIsConfirmPopupShow(state, isSliderConfirmPopupShown) {
		state.isSliderConfirmPopupShown = isSliderConfirmPopupShown;
	},
	setNeededShowCloseConfirm(state, isNeededShowCloseConfirm) {
		state.isNeededShowCloseConfirm = isNeededShowCloseConfirm;
	},
	showFeedbackMessageIfNeeded(state, source) {
		if (
			state.aiFeedback.feedbackWasSent
			|| (source === FEEDBACK_TRIGGER_CONTROL && state.aiFeedback.isShownByReturnBtn)
		)
		{
			return;
		}

		state.aiFeedback.lastTriggeredBy = source;
		if (source === FEEDBACK_TRIGGER_CONTROL)
		{
			state.aiFeedback.isShownByReturnBtn = true;
		}
		state.aiFeedback.isMessageComponentShown = true;
	},
	hideFeedbackMessage(state) {
		state.aiFeedback.isMessageComponentShown = false;
	},
	setAiFeedbackWasSent(state, isFeedbackWasSent) {
		state.aiFeedback.feedbackWasSent = isFeedbackWasSent;
	},
	setAiFeedbackShowBeforeClose(state, showBeforeClose) {
		state.aiFeedback.showBeforeClose = showBeforeClose;
	},
	setMainLayoutScrollHeight(state, height) {
		state.mainLayoutScrollHeight = height;
	},
};
/* eslint no-param-reassign: 2 */
