/**
 * @module statemanager/redux/optimistic-ui/src/utils
 */
jn.define('statemanager/redux/optimistic-ui/src/utils', (require, exports, module) => {
	const OPTIMISTIC_UI_CODE = 'optimistic-ui';

	const isOptimisticUiAction = (action) => {
		const splitted = action.type.split('/');

		return splitted?.length === 4 && splitted[2].toLowerCase() === OPTIMISTIC_UI_CODE;
	};

	const getSliceName = (action) => {
		return action.type.split('/')?.[0] ?? null;
	};

	const isPendingAction = (action) => {
		return action.type.endsWith('/pending');
	};

	const isFulfilledAction = (action) => {
		return action.type.endsWith('/fulfilled');
	};

	const isRejectedAction = (action) => {
		return action.type.endsWith('/rejected');
	};

	const isThunkAction = (action) => {
		return isPendingAction(action) || isFulfilledAction(action) || isRejectedAction(action);
	};

	/**
	 * @param {Object} [props]
	 * @param {boolean} [props.disable = false]
	 * @param {string} [props.errorsFieldName = 'errors']
	 */
	const createThunkSettings = (props = {}) => {
		return {
			optUiSettings: {
				disable: false,
				toUpdateEntityWithErrors: true,
				errorsFieldName: 'errors',
				...props,
			},
		};
	};

	const getThunkSettingsFromAction = (action) => {
		return action.meta?.optUiSettings ?? createThunkSettings().optUiSettings;
	};

	module.exports = {
		isOptimisticUiAction,
		getSliceName,
		isPendingAction,
		isFulfilledAction,
		isRejectedAction,
		isThunkAction,
		createThunkSettings,
		OPTIMISTIC_UI_CODE,
		getThunkSettingsFromAction,
	};
});
