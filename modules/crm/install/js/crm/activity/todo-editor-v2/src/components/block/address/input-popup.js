import { Address, ControlMode } from 'location.core';
import { Factory } from 'location.widget';
import { Tag, Text, Type } from 'main.core';
import { Popup } from 'main.popup';

export class InputPopup
{
	#popup: Popup = null;
	#bindElement: HTMLElement = null;
	#title: string = null;
	#buttonTitle: string = null;
	#onSubmit: Function = null;
	#address: Object = null;
	#addressFormatted: string = '';
	#addressWidget: Object = null;
	#searchInput: HTMLElement = null;
	#detailsContainer: HTMLElement = null;
	#addressContainer: HTMLElement = null;

	constructor(params: Object)
	{
		this.#bindElement = params.bindElement;
		this.#title = params.title;
		this.#buttonTitle = params.buttonTitle || 'OK';
		this.#address = Type.isStringFilled(params.addressJson) ? new Address(JSON.parse(params.addressJson)) : null;
		this.#addressFormatted = Type.isStringFilled(params.addressFormatted) ? params.addressFormatted : null;

		this.#onSubmit = params.onSubmit;
		this.onClickHandler = this.onClickHandler.bind(this);
		this.onKeyUpHandler = this.onKeyUpHandler.bind(this);
	}

	show(): void
	{
		this.#getPopup().show();

		setTimeout(() => {
			this.#searchInput.focus();
		});
	}

	destroy(): void
	{
		if (this.#popup)
		{
			this.#popup.destroy();
		}
	}

	#getPopup(): Popup
	{
		if (!this.#popup)
		{
			this.#popup = new Popup({
				id: `crm-todo-address-input-popup-${Text.getRandom()}`,
				bindElement: this.#bindElement,
				content: this.#getContent(),
				closeByEsc: false,
				closeIcon: false,
				draggable: false,
				width: 466,
				padding: 0,
				events: {
					onFirstShow: () => {
						this.initAddress();
					},
				},
			});
		}

		return this.#popup;
	}

	#getContent(): HTMLElement
	{
		this.#searchInput = Tag.render`
			<input 
				type="text" 
				class="ui-ctl-element ui-ctl-textbox crm-activity__todo-editor-v2_block-popup-address-input-container" 
				value="${this.#addressFormatted}" 
			>
		`;
		this.#detailsContainer = Tag.render`<div class="location-fields-control-block"></div>`;
		this.#addressContainer = Tag.render`<div></div>`;

		return Tag.render`
			<div>
				<div class="crm-activity__todo-editor-v2_block-popup-wrapper --address">
					<div class="crm-activity__todo-editor-v2_block-popup-title">
						${Text.encode(this.#title)}
					</div>
					<div class="crm-activity__todo-editor-v2_block-popup-content">
						${this.#searchInput}
						<button 
							onclick="${this.onClickHandler}" 
							class="ui-btn ui-btn-primary"
						>
							${Text.encode(this.#buttonTitle)}
						</button>
					</div>
					${this.#detailsContainer}
					${this.#addressContainer}
				</div>
			</div>
		`;
	}

	initAddress()
	{
		const widgetFactory = new Factory();

		this.#addressWidget = widgetFactory.createAddressWidget({
			address: this.#address,
			mode: ControlMode.edit,
		});

		this.#addressWidget.subscribeOnAddressChangedEvent((event) => {
			const data = event.getData();

			this.#address = Type.isObject(data.address) ? data.address : null;
		});

		const addressWidgetParams = {
			mode: ControlMode.edit,
			inputNode: this.#searchInput,
			mapBindElement: this.#searchInput,
			fieldsContainer: this.#detailsContainer,
			controlWrapper: this.#addressContainer,
		};

		this.#addressWidget.render(addressWidgetParams);
		this.#setFocus();
	}

	#setFocus(): void
	{
		this.#searchInput.focus();
	}

	onClickHandler(): void
	{
		this.#getPopup().close();
		this.#onSubmit(this.#address);
	}

	onKeyUpHandler(event): void
	{
		if (event.keyCode === 13)
		{
			this.onClickHandler();
		}
	}
}
