/**
 * @module statemanager/redux/optimistic-ui/src/change-registry
 */
jn.define('statemanager/redux/optimistic-ui/src/change-registry', (require, exports, module) => {
	const ChangeStatus = {
		PENDING: 'pending',
		SUCCESS: 'success',
		ERROR: 'error',
	};

	class ReduxSliceChangeRegistry
	{
		constructor()
		{
			this.fieldsRegistry = new Map();
		}

		updateRequestStatus(requestId, status)
		{
			const request = this.fieldsRegistry.get(requestId);
			if (!request)
			{
				return;
			}

			this.fieldsRegistry.set(requestId, {
				...request,
				status,
			});
		}

		isEntityReadyToUpdateInStore(entityId)
		{
			let result = true;
			for (const entry of this.fieldsRegistry)
			{
				const [, operation] = entry;
				if ((operation.create.has(entityId)
						|| operation.update.has(entityId)
						|| operation.delete.has(entityId))
					&& operation.status === ChangeStatus.PENDING)
				{
					result = false;
					break;
				}
			}

			return result;
		}

		isCustomDataChangesReadyToUpdateInStore()
		{
			let result = true;
			for (const entry of this.fieldsRegistry)
			{
				const { customSliceDataChanges, status } = entry[1];
				if (customSliceDataChanges && status === ChangeStatus.PENDING)
				{
					result = false;
					break;
				}
			}

			return result;
		}

		getAllEntitiesIds()
		{
			const result = [];

			const forEachHandler = (entity, entityId) => {
				if (!result.includes(entityId))
				{
					result.push(entityId);
				}
			};

			this.fieldsRegistry.forEach((request) => {
				request.create.forEach((entity, entityId) => forEachHandler(entity, entityId));
				request.update.forEach((entity, entityId) => forEachHandler(entity, entityId));
				request.delete.forEach((entity, entityId) => forEachHandler(entity, entityId));
			});

			return result;
		}

		getAllReadyToUpdateEntitiesIds()
		{
			return this.getAllEntitiesIds().filter((entityId) => this.isEntityReadyToUpdateInStore(entityId));
		}

		getCustomDataFinalStateFromRegistry()
		{
			const finalState = {};
			this.fieldsRegistry.forEach((request) => {
				const { customSliceDataChanges } = request;
				if (request.status !== ChangeStatus.PENDING && customSliceDataChanges)
				{
					if (request.status === ChangeStatus.SUCCESS)
					{
						Object.assign(finalState, customSliceDataChanges.newFields);
					}
					else
					{
						Object.assign(finalState, customSliceDataChanges.oldFields);
					}
				}
			});

			return finalState;
		}

		getEntityFinalStateFromRegistry(entityId) {
			const finalState = {};
			const currentNewFieldsFromSuccessRequests = {};

			this.fieldsRegistry.forEach((request) => {
				const { create, update, delete: del, status, thunkSettings, errors } = request;
				const { toUpdateEntityWithErrors, errorsFieldName } = thunkSettings;
				const errorsObject = toUpdateEntityWithErrors && errors ? { [errorsFieldName]: errors } : {};

				const operation = create.get(entityId) || update.get(entityId) || del.get(entityId);

				if (!operation)
				{
					return;
				}

				if (status === ChangeStatus.SUCCESS)
				{
					Object.assign(finalState, operation.newFields, operation.fieldsFromServer ?? {});
					Object.assign(currentNewFieldsFromSuccessRequests, operation.newFields, operation.fieldsFromServer ?? {});
				}
				else
					if (status === ChangeStatus.ERROR)
					{
						Object.assign(finalState, operation.oldFields, currentNewFieldsFromSuccessRequests, errorsObject);
					}
			});

			return finalState;
		}

		isRequestProcessFinished(request)
		{
			if (!request)
			{
				return true;
			}

			return request.create.size === 0
				&& request.update.size === 0
				&& request.delete.size === 0
				&& request.customSliceDataChanges === null;
		}

		removeEntityFromRequest(requestId, entityId)
		{
			const request = this.fieldsRegistry.get(requestId);
			if (!request)
			{
				return;
			}

			request.create.delete(entityId);
			request.update.delete(entityId);
			request.delete.delete(entityId);

			const isRequestProcessFinished = this.isRequestProcessFinished(request);

			if (isRequestProcessFinished)
			{
				this.fieldsRegistry.delete(requestId);
			}
		}

		removeCustomSliceDataChangesFromRequest(requestId)
		{
			const request = this.fieldsRegistry.get(requestId);
			if (!request)
			{
				return;
			}

			request.customSliceDataChanges = null;
			const isRequestProcessFinished = this.isRequestProcessFinished(request);
			if (isRequestProcessFinished)
			{
				this.fieldsRegistry.delete(requestId);
			}
		}

		getAllReadyRequestsWithCustomDataChangesIds()
		{
			const result = [];
			this.fieldsRegistry.forEach((request, requestId) => {
				if (request.customSliceDataChanges && request.status !== ChangeStatus.PENDING)
				{
					result.push(requestId);
				}
			});

			return result;
		}

		removeAllReadyEntitiesFromRegistry() {
			const readyEntities = this.getAllReadyToUpdateEntitiesIds();
			readyEntities.forEach((entityId) => {
				this.fieldsRegistry.forEach((request, requestId) => {
					this.removeEntityFromRequest(requestId, entityId);
				});
			});
		}

		getSliceStateWithoutAdapterEntities(state, changedFieldsKeys)
		{
			const result = {};
			changedFieldsKeys.forEach((key) => {
				if (!this.isAdapterEntityKey(key))
				{
					result[key] = state[key];
				}
			});

			return result;
		}

		isAdapterEntityKey(key)
		{
			return key === 'ids' || key === 'entities';
		}

		getChangedCustomFieldsKeys(prevState, nextState)
		{
			const result = [];
			Object.keys(prevState).forEach((key) => {
				if (!this.isAdapterEntityKey(key) && prevState[key] !== nextState[key])
				{
					result.push(key);
				}
			});

			return result;
		}

		getChanges(prevState, nextState, thunkSettings)
		{
			const result = {
				status: ChangeStatus.PENDING,
				create: new Map(),
				update: new Map(),
				delete: new Map(),
				customSliceDataChanges: null,
				thunkSettings,
			};

			let hasChanges = false;
			const changedCustomFieldsKeys = this.getChangedCustomFieldsKeys(prevState, nextState);
			if (changedCustomFieldsKeys.length > 0)
			{
				hasChanges = true;
				const oldFields = this.getSliceStateWithoutAdapterEntities(prevState, changedCustomFieldsKeys);
				const newFields = this.getSliceStateWithoutAdapterEntities(nextState, changedCustomFieldsKeys);
				result.customSliceDataChanges = {
					oldFields,
					newFields,
				};
			}

			if (!prevState.ids || !nextState.ids)
			{
				return hasChanges ? result : null;
			}

			prevState.ids.forEach((id) => {
				if (nextState.ids.includes(id))
				{
					const prevEntity = prevState.entities[id];
					const nextEntity = nextState.entities[id];
					let entityWasUpdated = false;
					const oldFields = {};
					const newFields = {};

					Object.keys(prevEntity).forEach((key) => {
						if (prevEntity[key] !== nextEntity[key])
						{
							entityWasUpdated = true;
							oldFields[key] = prevEntity[key];
							newFields[key] = nextEntity[key];
						}
					});

					if (entityWasUpdated)
					{
						result.update.set(id, { oldFields, newFields });
						hasChanges = true;
					}
				}
				else
				{
					result.delete.set(id, { oldFields: prevState.entities[id] });
					hasChanges = true;
				}
			});

			nextState.ids.forEach((id) => {
				if (!prevState.ids.includes(id))
				{
					result.create.set(id, { newFields: nextState.entities[id] });
					hasChanges = true;
				}
			});

			return hasChanges ? result : null;
		}

		registerChanges(requestId, oldSliceState, newSliceState, thunkSettings)
		{
			const changes = this.getChanges(oldSliceState, newSliceState, thunkSettings);
			if (changes)
			{
				this.fieldsRegistry.set(requestId, changes);
			}
		}

		hasChanges(requestId)
		{
			return this.fieldsRegistry.has(requestId);
		}

		updateEntityWithFieldsFromServer(requestId, entityId, fieldsFromServer)
		{
			const request = this.fieldsRegistry.get(requestId);
			if (!request)
			{
				return;
			}

			let updated = false;
			if (request.create.has(entityId))
			{
				request.create.set(entityId, {
					...request.create.get(entityId),
					fieldsFromServer,
				});
				updated = true;
			}

			if (!updated && request.update.has(entityId))
			{
				request.update.set(entityId, {
					...request.update.get(entityId),
					fieldsFromServer,
				});
				updated = true;
			}

			if (!updated && request.delete.has(entityId))
			{
				request.delete.set(entityId, {
					...request.delete.get(entityId),
					fieldsFromServer,
				});
				updated = true;
			}

			if (updated)
			{
				this.fieldsRegistry.set(requestId, request);
			}
		}

		updateEntitiesWithErrorsFromServer(requestId, errors)
		{
			const request = this.fieldsRegistry.get(requestId);
			if (!request)
			{
				return;
			}

			request.errors = errors;
		}
	}

	module.exports = {
		ReduxSliceChangeRegistry,
		ChangeStatus,
	};
});
