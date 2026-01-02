import { ActivityEditorService } from './service/activity-editor-service';
import { CommunicationService } from './service/communication-service';
import { EventService } from './service/event-service';

export class ServiceLocator
{
	static #instance = null;

	#activityEditorService = null;
	#communicationService = null;
	#eventService = null;

	static getInstance(): ServiceLocator
	{
		this.#instance = new ServiceLocator();

		return this.#instance;
	}

	getActivityEditorService(): ActivityEditorService
	{
		this.#activityEditorService ??= new ActivityEditorService();

		return this.#activityEditorService;
	}

	getCommunicationService(): CommunicationService
	{
		this.#communicationService ??= new CommunicationService();

		return this.#communicationService;
	}

	getEventService(): EventService
	{
		this.#eventService ??= new EventService();

		return this.#eventService;
	}
}
