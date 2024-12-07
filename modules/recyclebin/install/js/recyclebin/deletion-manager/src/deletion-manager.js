import { ajax as Ajax, Cache, Dom, Loc, Type } from 'main.core';
import { MemoryCache } from 'main.core.cache';
import { EventEmitter } from 'main.core.events';
import { Panel } from './panel';
import { Progress } from './progress';
import { State } from './state';
import './css/style.css';

type Settings = {
	moduleId: string;
	containerId: string;
}

export type Messages = {
	textBefore: string;
	textAfter: string;
	successCount: string;
	failedCount: string;
}

/**
 * @memberOf BX.Recyclebin
 */
export class DeletionManager
{
	static items: MemoryCache = new Cache.MemoryCache();
	id: string = null;
	#isRunning: boolean = false;
	#entityIds: number[] = [];
	#hasLayout: boolean;
	#progress: Progress;
	#panel: Panel = null;
	#settings: Settings;
	#operationHash: ?string;
	#messages: Messages;
	#action: string;
	emitter: EventEmitter;

	static getInstance(id: string, settings: Settings): DeletionManager
	{
		if (!DeletionManager.items.has(id))
		{
			const instance = new DeletionManager(id, settings);
			DeletionManager.items.set(id, instance);
		}

		return DeletionManager.items.get(id);
	}

	constructor(id: string, settings)
	{
		this.id = id;
		this.#settings = settings;

		this.emitter = new EventEmitter();
		this.emitter.setEventNamespace('Recyclebin.DeletionManager');

		this.progressChangeHandler = this.progressChangeHandler.bind(this);
	}

	setMessages(messages: Messages): void
	{
		this.#messages = messages;
	}

	#setAction(action: string): void
	{
		this.#action = action;
	}

	getId(): string
	{
		return this.id;
	}

	setEntityIds(entityIds: number[]): DeletionManager
	{
		this.#entityIds = entityIds;

		return this;
	}

	executeRestore(): void
	{
		if (this.isRunning())
		{
			return;
		}

		this.#setAction('restore');

		this.layout();
		this.run();
	}

	executeDelete(): void
	{
		if (this.isRunning())
		{
			return;
		}

		BX.Recyclebin
			.confirm(
				Loc.getMessage('RECYCLEBIN_DM_CONFIRM_REMOVE_TITLE'),
				null,
				{
					buttonSet: [
						{
							text: Loc.getMessage('RECYCLEBIN_DM_CONFIRM_REMOVE_YES'),
							type: 'green',
							code: 'continue',
							default: true,
						},
					],
				},
			)
			.then(() => {
				this.#setAction('delete');
				this.layout();
				this.run();
			})
		;
	}

	isRunning(): boolean
	{
		return this.#isRunning;
	}

	layout(): void
	{
		if (this.#hasLayout)
		{
			this.clearLayout();
		}

		this.#getPanel().render();

		this.#hasLayout = true;
	}

	run(): void
	{
		if (this.#isRunning)
		{
			return;
		}

		this.#progress?.reset();

		this.#isRunning = true;

		this.enableGridFilter(false);

		const params = {
			gridId: this.id,
		};

		if (Type.isArrayFilled(this.#entityIds))
		{
			params.entityIds = this.#entityIds;
		}

		Ajax.runAction(
			this.#getPrepareActionPath(),
			{
				data: {
					params,
				},
			},
		).then((response) => {
			const { data: { hash } } = response;

			if (!Type.isStringFilled(hash))
			{
				this.reset();

				return;
			}

			this.#operationHash = hash;
			this.#getProgress()
				.setParams({ hash })
				.run()
			;

			this.emitter.subscribe('ON_AUTORUN_PROCESS_STATE_CHANGE', this.progressChangeHandler);
		}).catch((error) => {
			console.error(error);
		});
	}

	#getPrepareActionPath(): string
	{
		const path = 'recyclebin.api.DeletionManager';
		const action = this.#action;

		if (action === 'delete')
		{
			return `${path}.prepareDeletion`;
		}

		if (action === 'restore')
		{
			return `${path}.prepareRestore`;
		}

		throw new Error(`Unknown action: ${action}`);
	}

	#getProcessActionPath(): string
	{
		const path = 'recyclebin.api.DeletionManager';
		const action = this.#action;

		if (action === 'delete')
		{
			return `${path}.processDeletion`;
		}

		if (action === 'restore')
		{
			return `${path}.processRestore`;
		}

		throw new Error(`Unknown action: ${action}`);
	}

	getCancelActionPath(): string
	{
		const path = 'recyclebin.api.DeletionManager';
		const action = this.#action;

		if (action === 'delete')
		{
			return `${path}.cancelDeletion`;
		}

		if (action === 'restore')
		{
			return `${path}.cancelRestore`;
		}

		throw new Error(`Unknown action: ${action}`);
	}

	getOperationHash(): ?string
	{
		return this.#operationHash;
	}

	reset(clearLayout: boolean = true): void
	{
		this.#operationHash = null;
		this.#isRunning = false;

		this.emitter.unsubscribe('ON_AUTORUN_PROCESS_STATE_CHANGE', this.progressChangeHandler);

		if (this.#hasLayout && clearLayout)
		{
			window.setTimeout(this.clearLayout.bind(this), 2000);
		}

		this.enableGridFilter(true);
		BX.Main.gridManager.reload(this.getId());
	}

	clearLayout(): void
	{
		this.#panel.close(true);

		this.#panel = null;
		this.#progress = null;

		this.#hasLayout = false;
	}

	cancel(): void
	{
		const hash = this.getOperationHash();

		Ajax.runAction(
			this.getCancelActionPath(),
			{
				data: {
					params: {
						hash,
					},
				},
			},
		).then(() => {
			this.clearLayout();
		}).catch((error) => {
			console.error(error);
		});

		this.#getPanel().hide();
	}

	progressChangeHandler({ data })
	{
		const { state, errors } = data;

		if (state === State.completed || state === State.stopped)
		{
			this.reset(!Type.isArrayFilled(errors));
		}
	}

	#getProgress(): Progress
	{
		if (!this.#progress)
		{
			this.#progress = new Progress(this.id, {
				action: this.#getProcessActionPath(),
				panel: this.#getPanel(),
				emitter: this.emitter,
				params: {
					moduleId: this.#settings.moduleId,
				},
			});
		}

		return this.#progress;
	}

	#getPanel(): Panel
	{
		if (!this.#panel)
		{
			const params = {
				entityIds: this.#entityIds,
				containerId: this.#settings.containerId,
				onAfterTextClick: this.cancel.bind(this),
				messages: this.#messages ?? [],
			};
			this.#panel = new Panel(params);
		}

		return this.#panel;
	}

	resetEntityIds(): void
	{
		this.#entityIds = [];
	}

	enableGridFilter(enable: boolean): void
	{
		const container = document.getElementById(`${this.id}_search_container`);
		if (!container)
		{
			return;
		}

		const className = 'main-ui-disable';
		if (enable)
		{
			Dom.removeClass(container, className);
		}
		else
		{
			Dom.addClass(container, className);
		}
	}
}
