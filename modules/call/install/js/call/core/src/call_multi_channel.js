export class CallMultiChannel
{
	constructor(name) {
		this.channel = new BroadcastChannel(name);
		this.senderId = Math.random().toString(36).slice(2);
		this.requests = new Map();

		this.channel.onmessage = (event) => {
			const { type, requestId, senderId, payload } = event.data;

			if (type === 'response' && this.requests.has(requestId)) {
				const req = this.requests.get(requestId);

				if (!req.responders.has(senderId)) {
					req.responders.add(senderId);
					req.responses.push(payload);

					if (Date.now() > req.deadline) {
						req.resolve(req.responses);
						this.requests.delete(requestId);
					}
				}
			}

			if (type === 'request' && senderId !== this.senderId) {
				const result = this.handle ? this.handle(payload) : undefined;

				if (result !== undefined) {
					this.channel.postMessage({
						type: 'response',
						requestId,
						senderId: this.senderId,
						payload: result
					});
				}
			}
		};
	}

	executer(handler) {
		this.handle = handler;
	}

	async broadcastRequest(payload, { timeout = 100 } = {}) {
		const requestId = Math.random().toString(36).slice(2);

		return new Promise((resolve) => {
			this.requests.set(requestId, {
				resolve,
				responses: [],
				responders: new Set(),
				deadline: Date.now() + timeout,
			});

			this.channel.postMessage({
				type: 'request',
				requestId,
				senderId: this.senderId,
				payload,
			});

			setTimeout(() => {
				if (this.requests.has(requestId)) {
					resolve(this.requests.get(requestId).responses);
					this.requests.delete(requestId);
				}
			}, timeout);
		});
	}

	destroy() {
		this.channel.close();
		this.requests.clear();
	}
}