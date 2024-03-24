import { Type, Tag, Dom, Runtime, ajax } from 'main.core';
import { EventEmitter } from 'main.core.events';
import { BitrixVue } from 'ui.vue3';
import ButtonType from '../../components/resplacement/enums/button-type';
import { Layout } from '../../components/resplacement/layout';
import BlocksCollection from '../../components/resplacement/layout/blocks-collection';
import Base from './base';
import { LayoutValidator } from './layoutvalidator';
import EventType from '../../components/resplacement/enums/event-type';
import './restplacement.css';
import PlacementInterfaceManager from './placement-interface-manager';

const LAYOUT_EVENT_NAME = 'LayoutEvent';
const PRIMARY_BTN_CLICK_EVENT_NAME = 'PrimaryButtonClickEvent';
const SECONDARY_BTN_CLICK_EVENT_NAME = 'SecondaryButtonClickEvent';
const VALUE_CHANGE_EVENT_NAME = 'ValueChangeEvent';
const ENTITY_UPDATE_EVENT_NAME = 'entityUpdateEvent';

export default class WithLayout extends Base
{
	#layoutComponent: ?Object = null;
	#layoutApp: ?Object = null;
	#activated: Boolean = false;
	#eventEmitter: EventEmitter = null;

	constructor()
	{
		super();
		this.#eventEmitter = new EventEmitter();
		this.#eventEmitter.setEventNamespace('RestPlacement');
		EventEmitter.subscribe('onCrmEntityUpdate', () => {
			this.#eventEmitter.emit(ENTITY_UPDATE_EVENT_NAME, {});
		});
	}

	createLayout(): HTMLElement
	{
		return Tag.render`<div class="crm-entity-stream-content-new-detail --hidden"></div>`;
	}

	initializeLayout(): void
	{
		super.initializeLayout();

		this.#layoutApp = BitrixVue.createApp(
			Layout,
			{
				id: String(this.getSetting('placementId', '')),
				appId: this.getSetting('appId', ''),
				onAction: this.#onLayoutAppAction.bind(this),
			},
		);
		this.#layoutApp.component('BlocksCollection', BlocksCollection);
		this.#layoutComponent = this.#layoutApp.mount(this.getContainer());
	}

	activate(): void
	{
		super.activate();
		if (!this.#activated)
		{
			this.#activated = true;
			this.#initializeInterface();
			this.#loadApp();
		}
	}

	#initializeInterface(): void
	{
		const placementInterfaceManager = PlacementInterfaceManager.getInstance(
			this.getSetting('placement', ''),
			[
				'setLayout',
				'setLayoutItemState',
				'bindLayoutEventCallback',
				'bindValueChangeCallback',
				'setPrimaryButtonState',
				'setSecondaryButtonState',
				'bindPrimaryButtonClickCallback',
				'bindSecondaryButtonClickCallback',
				'bindEntityUpdateCallback',
				'finish',
				'lock',
				'unlock',
			],
		);

		placementInterfaceManager.registerHandlers(
			this.getSetting('placementId', ''),
			{
				setLayout: this.#setLayout.bind(this),
				setLayoutItemState: this.#setLayoutItemState.bind(this),
				bindLayoutEventCallback: this.#bindEventCallback.bind(this, LAYOUT_EVENT_NAME),
				bindValueChangeCallback: this.#bindEventCallback.bind(this, VALUE_CHANGE_EVENT_NAME),

				setPrimaryButtonState: this.#setButtonState.bind(this, ButtonType.PRIMARY),
				setSecondaryButtonState: this.#setButtonState.bind(this, ButtonType.SECONDARY),
				bindPrimaryButtonClickCallback: this.#bindEventCallback.bind(this, PRIMARY_BTN_CLICK_EVENT_NAME),
				bindSecondaryButtonClickCallback: this.#bindEventCallback.bind(this, SECONDARY_BTN_CLICK_EVENT_NAME),
				bindEntityUpdateCallback: this.#bindEventCallback.bind(this, ENTITY_UPDATE_EVENT_NAME),
				finish: this.#finish.bind(this),
				lock: this.setLocked.bind(this, true),
				unlock: this.setLocked.bind(this, false),
			},
		);
	}

	#setLayout(layout: Object, callback): void
	{
		const validator = new LayoutValidator();
		const errors = validator.validate(layout);

		if (errors.length > 0)
		{
			this.#executeCallback(callback, { result: 'error', errors });
		}
		else
		{
			this.#layoutComponent.showLoader(false);
			this.#layoutComponent.setLayout(layout);
			this.#executeCallback(callback, { result: 'success' });
		}
	}

	#setLayoutItemState(params: Object, callback): void
	{
		const id = params.id ?? null;
		let properties = params.properties ?? null;
		let visible = params.visible ?? null;

		if (!Type.isStringFilled(id))
		{
			this.#executeCallback(callback, { result: 'error', errors: ['Wrong id'] });

			return;
		}

		const isCorrectVisible = Type.isBoolean(visible);
		const isCorrectProps = Type.isPlainObject(properties);

		if (!isCorrectProps && !isCorrectVisible)
		{
			this.#executeCallback(callback, { result: 'error', errors: ['Wrong state'] });

			return;
		}

		if (!isCorrectVisible)
		{
			visible = null;
		}

		if (!isCorrectProps)
		{
			properties = null;
		}

		this.#layoutComponent.setLayoutItemState(id, visible, properties, (result) => this.#executeCallback(callback, result));
	}

	#setButtonState(buttonId: string, params: ?Object, callback): void
	{
		if (!Type.isPlainObject(params) && !(Type.isArray(params) && params.length === 0) && !Type.isNull(params))
		{
			this.#executeCallback(callback, { result: 'error', errors: ['Wrong params'] });

			return;
		}
		let state = params;
		if (Type.isArray(params) && params.length === 0)
		{
			state = null;
		}

		this.#layoutComponent.setButtonState(buttonId, state, (result) => this.#executeCallback(callback, result));
	}

	#bindEventCallback(eventName: string, params, callback): void
	{
		this.#eventEmitter.subscribe(eventName, this.#executeEventCallback.bind(this, params, callback));
	}

	#finish(): void
	{
		this.emitFinishEditEvent();
	}

	#executeEventCallback(params, callback, eventData: BaseEvent): void
	{
		const data = eventData.getData();
		if (Type.isStringFilled(params)) // if need to call callback only for definite id
		{
			if ((data.id ?? '') === params)
			{
				this.#executeCallback(callback, data);
			}

			return;
		}

		this.#executeCallback(callback, data);
	}

	#onLayoutAppAction(eventData: ?Object)
	{
		const event = eventData.event ?? null;
		const value = eventData.value ?? null;
		if (event === EventType.FOOTER_BUTTON_CLICK && value === ButtonType.PRIMARY)
		{
			this.#eventEmitter.emit(PRIMARY_BTN_CLICK_EVENT_NAME, {});
		}

		if (event === EventType.FOOTER_BUTTON_CLICK && value === ButtonType.SECONDARY)
		{
			this.#eventEmitter.emit(SECONDARY_BTN_CLICK_EVENT_NAME, {});
		}

		if (event === EventType.LAYOUT_EVENT)
		{
			this.#eventEmitter.emit(LAYOUT_EVENT_NAME, value);
		}

		if (event === EventType.VALUE_CHANGED_EVENT)
		{
			this.#eventEmitter.emit(VALUE_CHANGE_EVENT_NAME, value);
		}
	}

	#executeCallback(callback, data): void
	{
		if (Type.isFunction(callback))
		{
			callback(data);
		}
	}

	#loadApp(): void
	{
		ajax.runComponentAction(
			'bitrix:app.layout',
			'getComponent',
			{
				data: {
					placementId: this.getSetting('placementId', ''),
					placementOptions: {
						entityTypeId: this.getEntityTypeId(),
						entityId: this.getEntityId(),
						useBuiltInInterface: 'Y',
					},
				},
			},
		).then((response) => {
			if (!(response && response.data && response.data.componentResult))
			{
				return;
			}

			const componentResult = response.data.componentResult;

			this.appSid = componentResult.APP_SID;

			const iframeNode = Tag.render`<div style="display: none; overflow: hidden;"></div>`;
			Dom.append(iframeNode, document.body);
			Runtime.html(iframeNode, response.data.html);
		});
	}
}
