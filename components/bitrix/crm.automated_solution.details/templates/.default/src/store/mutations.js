/* eslint-disable no-param-reassign */
import { Text } from 'main.core';
import { Error } from './index';
import {
	isValidError,
	normalizeDynamicTypesTitles,
	normalizeErrors,
	normalizeId,
	normalizeTitle,
	normalizeTypeId,
	normalizeTypesIds,
} from './validation';
import type { StateShape } from './index';

export default {
	/**
	 * Sets new initial state for the store. Modification flag is reset
	 */
	setState: (state: StateShape, stateToSet: StateShape): void => {
		state.automatedSolution.id = normalizeId(stateToSet.automatedSolution?.id);

		state.automatedSolution.title = normalizeTitle(stateToSet.automatedSolution?.title);

		const typeIds = normalizeTypesIds(stateToSet.automatedSolution?.typeIds);
		state.automatedSolution.typeIds = [...typeIds];
		state.automatedSolutionOrigTypeIds = [...typeIds];

		state.permissions.canMoveSmartProcessFromCrm = Text.toBoolean(
			stateToSet.permissions?.canMoveSmartProcessFromCrm ?? false,
		);
		state.permissions.canMoveSmartProcessFromAnotherAutomatedSolution = Text.toBoolean(
			stateToSet.permissions?.canMoveSmartProcessFromAnotherAutomatedSolution ?? false,
		);

		state.dynamicTypesTitles = normalizeDynamicTypesTitles(stateToSet.dynamicTypesTitles);

		state.errors = normalizeErrors(stateToSet.errors);

		state.isModified = false;
		state.isPermissionsLayoutV2Enabled = Text.toBoolean(
			stateToSet.isPermissionsLayoutV2Enabled ?? false,
		);
	},

	setErrors: (state: StateShape, errors: Error[]): void => {
		state.errors = normalizeErrors(errors);
	},
	removeError: (state: StateShape, error: Error): void => {
		if (!isValidError(error))
		{
			return;
		}

		state.errors = state.errors.filter((x) => x !== error);
	},

	setTitle: (state: StateShape, title: string): void => {
		const newTitle = normalizeTitle(title);

		if (newTitle !== state.automatedSolution.title)
		{
			state.isModified = true;
		}

		state.automatedSolution.title = newTitle;
	},

	addTypeId: (state: StateShape, typeId: number): void => {
		const normalizedTypeId = normalizeTypeId(typeId);
		if (normalizedTypeId <= 0)
		{
			return;
		}

		if (state.automatedSolution.typeIds.includes(normalizedTypeId))
		{
			return;
		}

		state.automatedSolution.typeIds.push(normalizedTypeId);
		state.isModified = true;
	},

	removeTypeId: (state: StateShape, typeId: number): void => {
		const normalizedTypeId = normalizeTypeId(typeId);
		if (normalizedTypeId <= 0)
		{
			return;
		}

		const newTypeIds = state.automatedSolution.typeIds.filter((id) => id !== normalizedTypeId);

		if (newTypeIds.length !== state.automatedSolution.typeIds.length)
		{
			state.isModified = true;
		}

		state.automatedSolution.typeIds = newTypeIds;
	},
};
