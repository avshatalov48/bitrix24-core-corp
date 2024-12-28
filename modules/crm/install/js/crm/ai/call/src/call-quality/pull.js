import { PULL } from 'pull.client';

const CALL_SCORING_ADD_COMMAND = 'call_scoring_add';

export class Pull
{
	#callback: Function;
	#unsubscribe: ?Function = null;

	constructor(callback: Function)
	{
		this.#callback = callback;
	}

	init(): void
	{
		if (!PULL)
		{
			console.error('pull is not initialized');

			return;
		}

		this.#unsubscribe = PULL.subscribe({
			moduleId: 'crm',
			command: CALL_SCORING_ADD_COMMAND,
			callback: (params) => {
				this.#callback(params);
			},
		});

		PULL.extendWatch(CALL_SCORING_ADD_COMMAND);
	}

	unsubscribe(): void
	{
		this.#unsubscribe();
	}
}
