/**
 * @module statemanager/redux/optimistic-ui
 */
jn.define('statemanager/redux/optimistic-ui', (require, exports, module) => {
	const { ReduxSliceChangeRegistry, ChangeStatus } = require('statemanager/redux/optimistic-ui/src/change-registry');
	const {
		isOptimisticUiAction,
		isPendingAction,
		isFulfilledAction,
		isRejectedAction,
		isThunkAction,
		getSliceName,
		OPTIMISTIC_UI_CODE,
		createThunkSettings,
		getThunkSettingsFromAction,
	} = require('statemanager/redux/optimistic-ui/src/utils');
	const { createNextState, createEntityAdapter } = require('statemanager/redux/toolkit');
	const { Type } = require('type');

	class ReduxOptimisticUi
	{
		constructor({ sliceName })
		{
			this.sliceName = sliceName;
			this.sliceChangeRegistry = new ReduxSliceChangeRegistry();
			this.adapter = createEntityAdapter();
		}

		#updateAllReadySliceDataItemsInStore = (state) => {
			const toUpdateInStoreEntitiesIds = this.sliceChangeRegistry.getAllReadyToUpdateEntitiesIds();
			if (Type.isArrayFilled(toUpdateInStoreEntitiesIds))
			{
				toUpdateInStoreEntitiesIds.forEach((id) => {
					const entityFinalState = this.sliceChangeRegistry.getEntityFinalStateFromRegistry(id);
					if (entityFinalState)
					{
						if (!entityFinalState.id || id === entityFinalState.id)
						{
							if (state.ids.includes(id))
							{
								this.adapter.updateOne(state, {
									id,
									changes: {
										...state.entities[id],
										...entityFinalState,
									},
								});
							}
							else
							{
								this.adapter.addOne(state, entityFinalState);
							}

							return;
						}

						this.adapter.removeOne(state, id);
						this.adapter.upsertOne(state, entityFinalState);
					}
					else
					{
						this.adapter.removeOne(state, id);
					}
				});
				this.sliceChangeRegistry.removeAllReadyEntitiesFromRegistry();
			}
		};

		#updateAllReadySliceCustomDataInStore = (state) => {
			if (!this.sliceChangeRegistry.isCustomDataChangesReadyToUpdateInStore())
			{
				return;
			}
			const customDataFinalState = this.sliceChangeRegistry.getCustomDataFinalStateFromRegistry();
			Object.assign(state, customDataFinalState);
			this.sliceChangeRegistry.getAllReadyRequestsWithCustomDataChangesIds().forEach((requestId) => {
				this.sliceChangeRegistry.removeCustomSliceDataChangesFromRequest(requestId);
			});
		};

		handlePending = (requestId, oldSliceState, newSliceState, thunkSettings) => {
			this.sliceChangeRegistry.registerChanges(requestId, oldSliceState, newSliceState, thunkSettings);
		};

		fulfilledReducer = (state, action) => {
			return createNextState(state, (draftState) => {
				const { requestId } = action.meta;
				const { entitiesFromServer } = action.payload;
				if (this.sliceChangeRegistry.hasChanges(requestId))
				{
					this.sliceChangeRegistry.updateRequestStatus(requestId, ChangeStatus.SUCCESS);
					if (Type.isArrayFilled(entitiesFromServer))
					{
						entitiesFromServer.forEach((entity) => {
							const { id, changes } = entity;
							this.sliceChangeRegistry.updateEntityWithFieldsFromServer(requestId, id, changes);
						});
					}
					this.#updateAllReadySliceCustomDataInStore(draftState);
					this.#updateAllReadySliceDataItemsInStore(draftState);
				}
			});
		};

		rejectedReducer = (state, action) => {
			return createNextState(state, (draftState) => {
				const { requestId } = action.meta;
				const { errors } = action.payload;
				this.sliceChangeRegistry.updateRequestStatus(requestId, ChangeStatus.ERROR);
				if (Type.isArrayFilled(errors))
				{
					this.sliceChangeRegistry.updateEntitiesWithErrorsFromServer(requestId, errors);
				}
				this.#updateAllReadySliceCustomDataInStore(draftState);
				this.#updateAllReadySliceDataItemsInStore(draftState);
			});
		};
	}

	const createOptimisticUiSliceReducer = (sliceName, sliceReducer) => {
		const reduxOptimisticUi = new ReduxOptimisticUi({ sliceName });

		return (state, action) => {
			if (!isThunkAction(action))
			{
				return sliceReducer(state, action);
			}

			if (isPendingAction(action))
			{
				const thunkSettings = getThunkSettingsFromAction(action);
				if (thunkSettings.disable)
				{
					return sliceReducer(state, action);
				}

				const nextState = sliceReducer(state, action);
				reduxOptimisticUi.handlePending(action.meta.requestId, state, nextState, thunkSettings);

				return nextState;
			}

			if (isFulfilledAction(action))
			{
				return sliceReducer(reduxOptimisticUi.fulfilledReducer(state, action), action);
			}

			if (isRejectedAction(action))
			{
				return sliceReducer(reduxOptimisticUi.rejectedReducer(state, action), action);
			}

			return sliceReducer(state, action);
		};
	};

	module.exports = {
		ReduxOptimisticUi,
		ChangeStatus,
		createOptimisticUiSliceReducer,
		isOptimisticUiAction,
		getSliceName,
		isPendingAction,
		isFulfilledAction,
		isRejectedAction,
		OPTIMISTIC_UI_CODE,
		createThunkSettings,
	};
});
