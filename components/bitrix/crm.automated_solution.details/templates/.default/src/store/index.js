import actions from './actions';
import mutations from './mutations';

type StateShape = {
	automatedSolution: {
		id?: ?number,
		title?: ?string,
		typeIds?: number[],
	},

	permissions: {
		canMoveSmartProcessFromCrm: boolean,
		canMoveSmartProcessFromAnotherAutomatedSolution: boolean,
	},

	dynamicTypesTitles: {[key: number]: string},

	errors: Error[],
	isModified: boolean,
};

export type Error = {
	message: string,
	code: string | number,
	customData: ?Object,
};

export const store = {
	strict: true,
	state(): StateShape
	{
		return {
			automatedSolution: {
				id: null,
				title: null,
				typeIds: [],
			},

			permissions: {
				canMoveSmartProcessFromCrm: false,
				canMoveSmartProcessFromAnotherAutomatedSolution: false,
			},

			dynamicTypesTitles: {},

			errors: [],

			isModified: false,
		};
	},
	getters: {
		isNew: (state: StateShape) => {
			return state.automatedSolution.id <= 0;
		},
		isSaved: (state: StateShape, getters) => {
			return !state.isModified && !getters.isNew;
		},
	},
	actions,
	mutations,
};
