import type {Options} from "./design";

const States = {
	Disabled: 'disabled',
	Sent: 'sent',
	Loading: 'loading',
	Error: 'error',
	Idle: 'idle',
};

class State
{
	value: String = States.Wait;
	message: String = null;

	setDisabled()
	{
		this.value = States.Disabled;
	}

	setSent()
	{
		this.value = States.Sent;
	}

	setLoading()
	{
		this.value = States.Loading;
	}

	setError(message)
	{
		this.value = States.Error;
		this.message = message;
	}

	setIdle()
	{
		this.value = States.Sent;
	}

	getMessage()
	{
		return this.message;
	}

	isDisabled()
	{
		return this.value === States.Disabled;
	}

	isSent()
	{
		return this.value === States.Sent;
	}

	isError()
	{
		return this.value === States.Error;
	}

	isWait()
	{
		return this.value === States.Wait;
	}

	isIdle()
	{
		return this.value === States.Idle;
	}
}

export {
	State
}