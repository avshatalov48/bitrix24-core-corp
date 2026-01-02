/**
 * @module entity-ready
 */
jn.define('entity-ready', (require, exports, module) => {
	class EntityReady
	{
		constructor()
		{
			this.readyEntitiesCollection = new Set();
			this.eventHandlers = new Map();
			this.checkReadyHandlers = new Map();
			this.listenUnreadyEvent();
			this.listenReadyEvent();
		}

		addCondition(entityId, condition)
		{
			if (typeof condition !== 'function')
			{
				// eslint-disable-next-line no-param-reassign
				condition = () => condition === true;
			}

			const checkReadyHandler = () => {
				if (condition())
				{
					BX.postComponentEvent('EntityReady::ready', [entityId]);
				}
			};

			this.checkReadyHandlers.set(entityId, checkReadyHandler);
			BX.addCustomEvent('EntityReady::checkReady', checkReadyHandler);
		}

		wait(entityId)
		{
			return new Promise((resolve) => {
				if (this.readyEntitiesCollection.has(entityId))
				{
					resolve();

					return;
				}

				const readyHandler = (readyEntityId) => {
					this.readyEntitiesCollection.add(readyEntityId);

					if (readyEntityId === entityId)
					{
						resolve();

						BX.removeCustomEvent('EntityReady::ready', this.eventHandlers.get(entityId));
						this.eventHandlers.delete(entityId);

						const checkReadyHandler = this.checkReadyHandlers.get(entityId);
						if (checkReadyHandler)
						{
							BX.removeCustomEvent('EntityReady::checkReady', checkReadyHandler);
							this.checkReadyHandlers.delete(entityId);
						}
					}
				};

				this.eventHandlers.set(entityId, readyHandler);

				BX.addCustomEvent('EntityReady::ready', readyHandler);
				BX.postComponentEvent('EntityReady::checkReady', []);
			});
		}

		ready(entityId)
		{
			BX.postComponentEvent('EntityReady::ready', [entityId]);
		}

		unready(entityId)
		{
			BX.postComponentEvent('EntityReady::unready', [entityId]);
		}

		isReady(entityId)
		{
			return this.readyEntitiesCollection.has(entityId);
		}

		listenUnreadyEvent()
		{
			BX.addCustomEvent('EntityReady::unready', (entityId) => {
				this.readyEntitiesCollection.delete(entityId);
			});
		}

		listenReadyEvent()
		{
			BX.addCustomEvent('EntityReady::ready', (entityId) => {
				this.readyEntitiesCollection.add(entityId);
			});
		}
	}

	module.exports = {
		EntityReady: new EntityReady(),
	};
});
