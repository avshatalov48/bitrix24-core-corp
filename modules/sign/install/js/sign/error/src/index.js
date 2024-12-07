type ErrorItem = {
	code: string,
	message: string
};

export class Error
{
	static instance;

	errors = [];
	callbacks = [];

	/**
	 * Returns Error instance.
	 * @return {Error}
	 */
	static getInstance(): Error
	{
		if (!Error.instance)
		{
			Error.instance = new Error();
		}

		return Error.instance;
	}

	constructor()
	{
	}

	/**
	 * Adds new error message.
	 * @param {ErrorItem} error
	 */
	addError(error: ErrorItem)
	{
		this.errors.push(error);
		this.#triggerError();
	}

	/**
	 * Returns errors collection.
	 * @return {Array<ErrorItem>}
	 */
	getErrors(): Array<ErrorItem>
	{
		return this.errors;
	}

	/**
	 * Adds new callback to handle errors.
	 * @param {() => {}} callback
	 */
	onError(callback: () => {})
	{
		this.callbacks.push(callback);
	}

	/**
	 * Executes all error handlers set through onError.
	 */
	#triggerError()
	{
		this.callbacks.map(callback => {
			callback(this.errors);
		});
	}
}
