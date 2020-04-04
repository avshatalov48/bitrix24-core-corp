import {Event} from '../../src/core';

describe('Event.EventEmitter', () => {
	it('Should be exported as function', () => {
		assert(typeof Event.EventEmitter === 'function');
	});

	it('Should implement public interface', () => {
		const emitter = new Event.EventEmitter();

		assert(typeof emitter.subscribe === 'function');
		assert(typeof emitter.subscribeOnce === 'function');
		assert(typeof emitter.emit === 'function');
		assert(typeof emitter.unsubscribe === 'function');
		assert(typeof emitter.getMaxListeners === 'function');
		assert(typeof emitter.setMaxListeners === 'function');
		assert(typeof emitter.getListeners === 'function');
	});

	describe('subscribe', () => {
		it('Should add event listener', () => {
			const emitter = new Event.EventEmitter();
			const event = 'test:event';
			const listener1 = () => {};
			const listener2 = () => {};
			const listener3 = () => {};

			emitter.subscribe(event, listener1);
			emitter.subscribe(event, listener2);
			emitter.subscribe(event, listener3);

			assert(emitter.getListeners(event).size === 3);
		});

		it('Should add unique listeners only', () => {
			const emitter = new Event.EventEmitter();
			const event = 'test:event';
			const listener = () => {};

			emitter.subscribe(event, listener);
			emitter.subscribe(event, listener);
			emitter.subscribe(event, listener);
			emitter.subscribe(event, listener);

			assert(emitter.getListeners(event).size === 1);
		});
	});

	describe('unsubscribe', () => {
		it('Should remove specified event listener', () => {
			const emitter = new Event.EventEmitter();
			const event = 'test:event';
			const listener1 = () => {};
			const listener2 = () => {};
			const listener3 = () => {};

			emitter.subscribe(event, listener1);
			emitter.subscribe(event, listener2);
			emitter.subscribe(event, listener3);

			emitter.unsubscribe(event, listener1);

			assert(emitter.getListeners(event).size === 2);
			assert(emitter.getListeners(event).has(listener1) === false);
			assert(emitter.getListeners(event).has(listener2) === true);
			assert(emitter.getListeners(event).has(listener3) === true);
		});
	});

	describe('emit', () => {
		it('Should call all event listeners', () => {
			const emitter = new Event.EventEmitter();
			const event = 'test:event';
			const listener1 = sinon.stub();
			const listener2 = sinon.stub();
			const listener3 = sinon.stub();

			emitter.subscribe(event, listener1);
			emitter.subscribe(event, listener2);
			emitter.subscribe(event, listener3);

			emitter.emit(event);

			assert(listener1.calledOnce);
			assert(listener2.calledOnce);
			assert(listener3.calledOnce);
		});

		it('Should call event listeners after each emit call', () => {
			const emitter = new Event.EventEmitter();
			const event = 'test:event';
			const listener1 = sinon.stub();
			const listener2 = sinon.stub();
			const listener3 = sinon.stub();

			emitter.subscribe(event, listener1);
			emitter.subscribe(event, listener2);
			emitter.subscribe(event, listener3);

			emitter.emit(event);

			assert(listener1.callCount === 1);
			assert(listener2.callCount === 1);
			assert(listener3.callCount === 1);

			emitter.emit(event);

			assert(listener1.callCount === 2);
			assert(listener2.callCount === 2);
			assert(listener3.callCount === 2);

			emitter.emit(event);
			emitter.emit(event);
			emitter.emit(event);

			assert(listener1.callCount === 5);
			assert(listener2.callCount === 5);
			assert(listener3.callCount === 5);
		});

		it('Should not call deleted listeners', () => {
			const emitter = new Event.EventEmitter();
			const event = 'test:event';
			const listener1 = sinon.stub();
			const listener2 = sinon.stub();
			const listener3 = sinon.stub();

			emitter.subscribe(event, listener1);
			emitter.subscribe(event, listener2);
			emitter.subscribe(event, listener3);

			emitter.emit(event);

			assert(listener1.callCount === 1);
			assert(listener2.callCount === 1);
			assert(listener3.callCount === 1);

			emitter.unsubscribe(event, listener1);
			emitter.emit(event);

			assert(listener1.callCount === 1);
			assert(listener2.callCount === 2);
			assert(listener3.callCount === 2);
		});

		it('Should call listener with valid Event object anyway', async () => {
			const emitter = new Event.EventEmitter();
			const eventName = "Test:event";

			await new Promise((resolve) => {
				emitter.subscribe(eventName, (event) => {
					assert(event instanceof Event.BaseEvent);
					assert(event.isTrusted === true);
					assert(event.type === eventName);
					assert(!!event.data && typeof event.data === 'object');
					assert(event.defaultPrevented === false);
					assert(event.immediatePropagationStopped === false);
					assert(typeof event.preventDefault === 'function');
					assert(typeof event.stopImmediatePropagation === 'function');
					assert(typeof event.isImmediatePropagationStopped === 'function');
					resolve();
				});
				emitter.emit(eventName);
			});
		});

		it('Should assign props to data if passed plain object', async () => {
			const emitter = new Event.EventEmitter();
			const eventName = "Test:event";

			await new Promise((resolve) => {
				emitter.subscribe(eventName, (event) => {
					assert(event.data.test1 === 1);
					assert(event.data.test2 === 2);
					resolve();
				});
				emitter.emit(eventName, {test1: 1, test2: 2});
			});
		});

		it('Should add event value to data.event.value if passed not event object and not plain object', async () => {
			const emitter = new Event.EventEmitter();
			const eventName = "Test:event";

			await new Promise((resolve) => {
				emitter.subscribe(eventName, (event) => {
					assert(Array.isArray(event.data.value));
					assert(event.data.value[0] === 1);
					assert(event.data.value[1] === 2);
					resolve();
				});
				emitter.emit(eventName, [1, 2]);
			});

			await new Promise((resolve) => {
				emitter.subscribe(`${eventName}2`, (event) => {
					assert(typeof event.data.value === 'string');
					assert(event.data.value === 'test');
					resolve();
				});
				emitter.emit(`${eventName}2`, 'test');
			});

			await new Promise((resolve) => {
				emitter.subscribe(`${eventName}3`, (event) => {
					assert(typeof event.data.value === 'boolean');
					assert(event.data.value === true);
					resolve();
				});
				emitter.emit(`${eventName}3`, true);
			});
		});

		it('Should set event.isTrusted = true if event emitted with instance method', async () => {
			class Emitter extends Event.EventEmitter {}
			const emitter = new Emitter();

			await new Promise((resolve) => {
				emitter.subscribe("test", (event) => {
					assert(event.isTrusted === true);
					resolve();
				});
				emitter.emit("test");
			});
		});

		it('Should set event.isTrusted = false if event emitted with static method', async () => {
			class Emitter extends Event.EventEmitter {}
			const emitter = new Emitter();

			await new Promise((resolve) => {
				emitter.subscribe("test2", (event) => {
					assert(event.isTrusted === false);
					resolve();
				});
				Event.EventEmitter.emit("test2");
			});

			await new Promise((resolve) => {
				emitter.subscribe("test3", (event) => {
					assert(event.isTrusted === false);
					resolve();
				});
				Emitter.emit("test3");
			});
		});

		it('Should set defaultPrevented = true called .preventDefault() in listener', async () => {
			const emitter = new Event.EventEmitter();

			emitter.subscribe('test4', (event) => {
				event.preventDefault();
			});

			const event = new Event.BaseEvent();

			emitter.emit('test4', event);

			assert(event.isDefaultPrevented() === true);
			assert(event.defaultPrevented === true);
		});
	});

	describe('emitAsync', () => {
		it('Should emit event and return promise', () => {
			const emitter = new Event.EventEmitter();
			const resultPromise = emitter.emitAsync('test');

			assert.ok(resultPromise instanceof Promise);
		});

		it('Should resolve returned promise with values that returned from listeners', () => {
			const emitter = new Event.EventEmitter();

			emitter.subscribe('test', () => {
				return 'result-1';
			});

			emitter.subscribe('test', () => {
				return true;
			});

			emitter.subscribe('test', () => {
				return 'test-result-3';
			});

			return emitter
				.emitAsync('test')
				.then((results) => {
					assert.ok(results[0] === 'result-1');
					assert.ok(results[1] === true);
					assert.ok(results[2] === 'test-result-3');
				});
		});

		it('Promise should be resolved, when resolved all promises returned from listeners', () => {
			const emitter = new Event.EventEmitter();

			emitter.subscribe('test', () => {
				return new Promise((resolve) => {
					setTimeout(() => {
						resolve('value1');
					}, 500);
				});
			});

			emitter.subscribe('test', () => {
				return new Promise((resolve) => {
					setTimeout(() => {
						resolve('value2');
					}, 700);
				});
			});

			emitter.subscribe('test', () => {
				return new Promise((resolve) => {
					setTimeout(() => {
						resolve('value3');
					}, 900);
				});
			});

			return emitter
				.emitAsync('test')
				.then((results) => {
					assert.ok(results[0] === 'value1');
					assert.ok(results[1] === 'value2');
					assert.ok(results[2] === 'value3');
				});
		});

		it('Should reject returned promise if listener throw error', () => {
			const emitter = new Event.EventEmitter();

			emitter.subscribe('test', () => {
				return Promise.reject(new Error());
			});

			emitter
				.emitAsync('test')
				.then(() => {})
				.catch((err) => {
					assert.ok(err instanceof Error);
				});
		});
	});

	describe('static emitAsync', () => {
		it('Should emit event and return promise', () => {
			const resultPromise = Event.EventEmitter.emitAsync('test-event--1');
			assert.ok(resultPromise instanceof Promise);
		});

		it('Should resolve returned promise with values that returned from listeners', () => {
			const emitter = new Event.EventEmitter();

			emitter.subscribe('test-event-1', () => {
				return 'result-1';
			});

			emitter.subscribe('test-event-1', () => {
				return true;
			});

			emitter.subscribe('test-event-1', () => {
				return 'test-result-3';
			});

			return Event.EventEmitter
				.emitAsync('test-event-1')
				.then((results) => {
					assert.ok(results[0] === 'result-1');
					assert.ok(results[1] === true);
					assert.ok(results[2] === 'test-result-3');
				});
		});

		it('Promise should be resolved, when resolved all promises returned from listeners', () => {
			const emitter = new Event.EventEmitter();

			emitter.subscribe('test-event-2', () => {
				return new Promise((resolve) => {
					setTimeout(() => {
						resolve('value1');
					}, 500);
				});
			});

			emitter.subscribe('test-event-2', () => {
				return new Promise((resolve) => {
					setTimeout(() => {
						resolve('value2');
					}, 700);
				});
			});

			emitter.subscribe('test-event-2', () => {
				return new Promise((resolve) => {
					setTimeout(() => {
						resolve('value3');
					}, 900);
				});
			});

			return Event.EventEmitter
				.emitAsync('test-event-2')
				.then((results) => {
					assert.ok(results[0] === 'value1');
					assert.ok(results[1] === 'value2');
					assert.ok(results[2] === 'value3');
				});
		});

		it('Should reject returned promise if listener throw error', () => {
			const emitter = new Event.EventEmitter();

			emitter.subscribe('test-event-3', () => {
				return Promise.reject(new Error());
			});

			return Event.EventEmitter
				.emitAsync('test-event-3')
				.then(() => {})
				.catch((err) => {
					assert.ok(err instanceof Error);
				});
		});
	});

	describe('subscribeOnce', () => {
		it('Should call listener only once', () => {
			const emitter = new Event.EventEmitter();
			const event = 'test:event';
			const listener = sinon.stub();

			emitter.subscribeOnce(event, listener);
			emitter.emit(event);
			emitter.emit(event);
			emitter.emit(event);
			emitter.emit(event);

			assert(listener.calledOnce);
		});

		it('Should add only unique listeners', () => {
			const emitter = new Event.EventEmitter();
			const event = 'test:event';
			const listener = sinon.stub();

			emitter.subscribeOnce(event, listener);
			emitter.subscribeOnce(event, listener);
			emitter.subscribeOnce(event, listener);
			emitter.subscribeOnce(event, listener);

			emitter.emit(event);
			emitter.emit(event);
			emitter.emit(event);
			emitter.emit(event);

			assert(listener.calledOnce);
		});
	});

	describe('setMaxEventListeners', () => {
		it('Should set max allowed listeners count', () => {
			const emitter = new Event.EventEmitter();
			const maxListenersCount = 3;

			emitter.setMaxListeners(maxListenersCount);

			assert(emitter.getMaxListeners() === maxListenersCount);
		});
	});

	describe('getMaxListeners', () => {
		it('Should return max listeners count for each event', () => {
			const emitter = new Event.EventEmitter();
			const defaultMaxListenersCount = 10;

			assert(emitter.getMaxListeners() === defaultMaxListenersCount);
		});
	});

	describe('static', () => {
		it('Should implement public static interface', () => {
			assert(typeof Event.EventEmitter.subscribe === 'function');
			assert(typeof Event.EventEmitter.subscribeOnce === 'function');
			assert(typeof Event.EventEmitter.emit === 'function');
			assert(typeof Event.EventEmitter.unsubscribe === 'function');
			assert(typeof Event.EventEmitter.getMaxListeners === 'function');
			assert(typeof Event.EventEmitter.setMaxListeners === 'function');
			assert(typeof Event.EventEmitter.getListeners === 'function');
		});

		it('Should add global event listener', () => {
			const emitter = new Event.EventEmitter();
			const eventName = 'test:event';
			const listener = sinon.stub();

			Event.EventEmitter.subscribe(eventName, listener);

			emitter.emit(eventName);

			assert(listener.callCount === 1);

			emitter.emit(eventName);
			emitter.emit(eventName);

			assert(listener.callCount === 3);
		});
	});
});