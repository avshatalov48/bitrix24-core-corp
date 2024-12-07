import type { ConflictField } from './types';
import { EntityInfo } from './types';

export default {
	isLoading(state): boolean {
		return state.isLoading;
	},
	conflictFields(state): string[] {
		return state.conflictFields.sort((a, b) => a.order - b.order);
	},
	mergeUuid(state): string {
		return state.mergeUuid;
	},
	activityId(state): number {
		return state.activityId;
	},
	activityDirection(state): string {
		return state.activityDirection;
	},
	getEntityInfo(state): ?EntityInfo {
		return state.entityInfo;
	},
	isEntityEditorLoaded(state): boolean {
		return state.isEntityEditorLoaded;
	},
	eeControlPosition: (state) => (fieldId: string) => {
		return state.eeControlPositions.get(fieldId, 0);
	},
	eeControlPositions: (state) => {
		return state.eeControlPositions;
	},
	getexpandedConflictControls: (state) => {
		return state.expandedConflictControls;
	},
	getNotVisibleUnresolvedCount: (state) => {
		return state.notVisibleUnresolvedCount;
	},
	getMainLayoutScrollPosition: (state) => {
		return state.mainLayoutScrollPosition;
	},
	getMainLayoutContainerHeight: (state) => {
		return state.mainLayoutContainerHeight;
	},
	getMainLayoutScrollHeight: (state) => {
		return state.mainLayoutScrollHeight;
	},
	getFirstUnseenFieldPosition: (state) => {
		const position = state.mainLayoutScrollPosition;

		let lowerField = null;
		let min = Infinity;
		for (const [fieldName, value] of state.eeControlPositions)
		{
			const field: ConflictField = state.conflictFields.find((f) => f.name === fieldName);

			if (!field || field.isAiValuesUsed || position + 120 > value)
			{
				continue;
			}

			if (value < min)
			{
				min = value;
				lowerField = fieldName;
			}
		}

		if (!lowerField)
		{
			return null;
		}

		return state.eeControlPositions.get(lowerField);
	},
	isFieldsTouched: (state): boolean => {
		return state.isFieldsTouched;
	},
	aiValuesAppliedCount: (state): number => {
		return state.aiValuesAppliedCount;
	},
	isFooterHiddenAndSaveDisabled(state): boolean {
		return state.aiValuesAppliedCount === 0;
	},
	isSliderConfirmPopupShown: (state): boolean => {
		return state.isSliderConfirmPopupShown;
	},
	isNeededShowCloseConfirm: (state): boolean => {
		return state.isNeededShowCloseConfirm;
	},
	isFeedbackMessageShown: (state): boolean => {
		return state.aiFeedback.isMessageComponentShown;
	},
	isAiFeedbackShowBeforeClose(state): boolean {
		return state.aiFeedback.showBeforeClose;
	},
	aiFeedback(state) {
		return state.aiFeedback;
	},
};
