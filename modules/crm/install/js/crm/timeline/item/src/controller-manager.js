import ConfigurableItem from "./configurable-item";

export default class ControllerManager
{
	#id: ?string = null;

	constructor(id)
	{
		this.#id = id;
	}

	getItemControllers(item: ConfigurableItem): Array
	{
		const foundControllers = [];
		for (const controller of ControllerManager.getRegisteredControllers())
		{
			if (controller.isItemSupported(item))
			{
				const controllerInstance = new controller();
				controllerInstance.onInitialize(item);
				foundControllers.push(controllerInstance);
			}
		}

		return foundControllers;
	}

	static getInstance(timelineId): ControllerManager
	{
		if (!this.#instances.hasOwnProperty(timelineId))
		{
			this.#instances[timelineId] = new ControllerManager(timelineId);
		}

		return this.#instances[timelineId];
	}

	static registerController(controller): void
	{
		this.#availableControllers.push(controller);
	}

	static getRegisteredControllers(): Array
	{
		return this.#availableControllers;
	}

	static #instances = {};
	static #availableControllers = [];
}
