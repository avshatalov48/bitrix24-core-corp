import getters from './getters';
import mutations from './mutations';
import actions from './actions';

export default () => {
	return {
		state: {
			mergeUuid: null,
			isLoading: true,
			conflictFields: [],
			isEditMode: false,
			isEntityEditorLoaded: false,
			entityInfo: null,
			eeControlPositions: new Map(),
			expandedConflictControls: new Map(),
			mainLayoutScrollPosition: null,
			mainLayoutContainerHeight: null,
			mainLayoutScrollHeight: null,
			notVisibleUnresolvedCount: 0,
			isFieldsTouched: false,
			aiValuesAppliedCount: 0,
			isSliderConfirmPopupShown: false,
			isNeededShowCloseConfirm: false,
			aiFeedback: {
				feedbackWasSent: false,
				isShownByReturnBtn: false,
				isMessageComponentShown: false,
				lastTriggeredBy: null,
				showBeforeClose: true,
				checkFeedbackBeforeSend: false, // Send check request before sending
			},
		},
		getters,
		mutations,
		actions,
	};
};

// export default store;
