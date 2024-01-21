import { Event } from 'main.core';
import { SwitchboardAction } from './switchboard-action';

/**
 * A utility for communications between an iframe and its parent, used by the Expertbridge embedded SDK.
 * This builds useful patterns on top of the basic functionality offered by MessageChannel.
 *
 * Both windows instantiate a Switchboard, passing in their MessagePorts.
 * Calling methods on the switchboard causes messages to be sent through the channel.
 */
export class Switchboard
{
	port = null;
	name = '';
	methods = [];
	incrementor = 1;
	debugMode = false;
	constructor({ port, name = 'switchboard', debug = false })
	{
		this.port = port;
		this.name = name;
		this.debugMode = debug;

		Event.bind('message', async (event) => {
			this.log('message received', event);
			const message = event.data;
			console.log(message);
			if (this.isGet(message))
			{
				// find the method, call it, and reply with the result
				this.port.postMessage(await this.getMethodResult(message));
			}
			else if (this.isEmit(message))
			{
				const { method, args } = message;
				const executor = this.methods[method];
				if (executor)
				{
					executor(args);
				}
			}
		});
	}

	async getMethodResult({
		messageId,
		method,
		args,
	})
	{
		const executor = this.methods[method];
		if (executor == null)
		{
			return {
				switchboardAction: SwitchboardAction.ERROR,
				messageId,
				error: `[${this.name}] Method "${method}" is not defined`,
			};
		}

		try
		{
			const result = await executor(args);

			return {
				switchboardAction: SwitchboardAction.REPLY,
				messageId,
				result,
			};
		}
		catch (err)
		{
			this.logError(err);

			return {
				switchboardAction: SwitchboardAction.ERROR,
				messageId,
				error: `[${this.name}] Method "${method}" threw an error`,
			};
		}
	}

	/**
	 * Defines a method that can be "called" from the other side by sending an event.
	 */
	defineMethod(methodName, executor) {
		this.methods[methodName] = executor;
	}

	/**
	 * Calls a method registered on the other side, and returns the result.
	 *
	 * How this is accomplished:
	 * This switchboard sends a "get" message over the channel describing which method to call with which arguments.
	 * The other side's switchboard finds a method with that name, and calls it with the arguments.
	 * It then packages up the returned value into a "reply" message, sending it back to us across the channel.
	 * This switchboard has attached a listener on the channel, which will resolve with the result
	 * when a reply is detected.
	 *
	 * Instead of an arguments list, arguments are supplied as a map.
	 *
	 * @param method the name of the method to call
	 * @param args arguments that will be supplied. Must be serializable, no functions or other nonense.
	 * @returns whatever is returned from the method
	 */
	get(method, args) {
		return new Promise((resolve, reject) => {
			// In order to "call a method" on the other side of the port,
			// we will send a message with a unique id
			const messageId = this.getNewMessageId();
			// attach a new listener to our port, and remove it when we get a response
			const listener = (event) => {
				const message = event.data;
				if (message.messageId !== messageId)
				{
					return;
				}
				Event.unbind(this.port, 'message', listener);
				if (this.isReply(message))
				{
					resolve(message.result);
				}
				else
				{
					const errStr = this.isError(message)
						? message.error
						: 'Unexpected response message'
					;
					reject(new Error(errStr));
				}
			};
			Event.bind(this.port, 'message', listener);
			this.port.start();
			const message = {
				switchboardAction: SwitchboardAction.GET,
				method,
				messageId,
				args,
			};

			this.port.postMessage(message);
		});
	}

	/**
	 * Emit calls a method on the other side just like get does.
	 * But emit doesn't wait for a response, it just sends and forgets.
	 *
	 * @param method
	 * @param args
	 */
	emit(method, args) {
		const message = {
			switchboardAction: SwitchboardAction.EMIT,
			method,
			args,
		};

		this.port.postMessage(message);
	}

	start(): void
	{
		this.port.start();
	}

	log(...args): void
	{
		if (this.debugMode)
		{
			console.debug(`[${this.name}]`, ...args);
		}
	}

	logError(...args): void
	{
		console.error(`[${this.name}]`, ...args);
	}

	getNewMessageId(): string
	{
		// eslint-disable-next-line no-plusplus
		return `m_${this.name}_${this.incrementor++}`;
	} // @ts-ignore

	isGet(message): boolean
	{
		return message.switchboardAction === SwitchboardAction.GET;
	}

	isReply(message): boolean
	{
		return message.switchboardAction === SwitchboardAction.REPLY;
	}

	isEmit(message): boolean
	{
		return message.switchboardAction === SwitchboardAction.EMIT;
	}

	isError(message): boolean
	{
		return message.switchboardAction === SwitchboardAction.ERROR;
	}
}

// Each message we send on the channel specifies an action we want the other side to cooperate with.
// var Actions;

// helper types/functions for making sure wires don't get crossed
// (function(Actions) { Actions.GET = 'get'; Actions.REPLY = 'reply'; Actions.EMIT = 'emit'; Actions.ERROR = 'error'; })(Actions || (Actions = {}));


// function isError(message)
// {
// 	return message.switchboardAction === Actions.ERROR;
// }

// (function() { var reactHotLoader = typeof reactHotLoaderGlobal === 'undefined' ? undefined : reactHotLoaderGlobal.default; if (!reactHotLoader)
//
//
// //   { return;
// // }reactHotLoader.register(Switchboard, 'Switchboard', '/Users/ville/src/expertbridge-release/expertbridge-frontend/packages/expertbridge-ui-switchboard/src/switchboard.ts'); reactHotLoader.register(isGet, 'isGet', '/Users/ville/src/expertbridge-release/expertbridge-frontend/packages/expertbridge-ui-switchboard/src/switchboard.ts'); reactHotLoader.register(isReply, 'isReply', '/Users/ville/src/expertbridge-release/expertbridge-frontend/packages/expertbridge-ui-switchboard/src/switchboard.ts'); reactHotLoader.register(isEmit, 'isEmit', '/Users/ville/src/expertbridge-release/expertbridge-frontend/packages/expertbridge-ui-switchboard/src/switchboard.ts'); reactHotLoader.register(isError, 'isError', '/Users/ville/src/expertbridge-release/expertbridge-frontend/packages/expertbridge-ui-switchboard/src/switchboard.ts'); })();
//
// (function() { var leaveModule = typeof reactHotLoaderGlobal === 'undefined' ? undefined : reactHotLoaderGlobal.leaveModule; leaveModule && leaveModule(module); })();
