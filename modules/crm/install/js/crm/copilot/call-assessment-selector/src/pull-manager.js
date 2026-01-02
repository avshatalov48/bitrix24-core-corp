import { PULL } from 'pull.client';

const TAG = 'CRM_COPILOT_CALL_ASSESSMENT_SCRIPT_SELECTOR';
const COMMAND_UPDATE = 'update_call_assessment';
const COMMAND_SELECT = 'select_call_assessment';

declare type ListenerOptions = {
	onUpdate: Function,
	onSelect: Function,
};

export class PullManager
{
	#unsubscribeUpdate: Function;
	#unsubscribeSelect: Function;

	constructor(options: ListenerOptions)
	{
		this.#unsubscribeUpdate = this.#subscribe(COMMAND_UPDATE, options.onUpdate);
		this.#unsubscribeSelect = this.#subscribe(COMMAND_SELECT, options.onSelect);

		this.#extendsWatch();
	}

	#subscribe(command: string, callback: Function): Function
	{
		if (!PULL)
		{
			return () => {};
		}

		return PULL.subscribe({
			moduleId: 'crm',
			command,
			callback,
		});
	}

	#extendsWatch(): void
	{
		if (PULL)
		{
			PULL.extendWatch(TAG);
		}
	}

	#clearWatch(): void
	{
		if (PULL)
		{
			PULL.clearWatch(TAG);
		}
	}

	unsubscribe(): void
	{
		this.#unsubscribeUpdate();
		this.#unsubscribeSelect();

		this.#clearWatch();
	}
}
