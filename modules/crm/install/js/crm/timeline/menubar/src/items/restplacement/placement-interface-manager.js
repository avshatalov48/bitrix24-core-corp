import { Type } from 'main.core';

export default class PlacementInterfaceManager
{
	#placementCode: string = null;
	#methodsList: Array = [];
	#handlers: Object = {};
	constructor(placementCode: String, methodsList: Array)
	{
		this.#placementCode = placementCode;
		this.#methodsList = methodsList;
		this.#initializeInterface();
	}

	static Instances = {};
	static getInstance(placementCode: String, methodsList: Array): PlacementInterfaceManager
	{
		if (!Object.hasOwn(PlacementInterfaceManager.Instances, placementCode))
		{
			PlacementInterfaceManager.Instances[placementCode] = new PlacementInterfaceManager(placementCode, methodsList);
		}

		return PlacementInterfaceManager.Instances[placementCode];
	}

	registerHandlers(placementId: string, handlers: Object): void
	{
		this.#handlers[placementId] = handlers;
	}

	#initializeInterface(): void
	{
		const PlacementInterface = BX.rest.AppLayout.initializePlacement(this.#placementCode);
		this.#methodsList.forEach((methodName) => {
			PlacementInterface.prototype[methodName] = this.#interfaceCallback.bind(this, methodName);
		});
	}

	#interfaceCallback(): void
	{
		const methodName = arguments[0] ?? null;
		const placementId = arguments[3]?.params?.placementId ?? null;
		if (!methodName || !placementId)
		{
			return;
		}
		const placementHandlers = this.#handlers[placementId] ?? {};
		if (Type.isFunction(placementHandlers[methodName]))
		{
			placementHandlers[methodName](arguments[1] ?? null, arguments[2] ?? null);
		}
	}
}
