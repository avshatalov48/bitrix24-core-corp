import { Tag } from 'main.core';
import { Popup, PopupManager } from 'main.popup';
import { EventEmitter, BaseEvent } from 'main.core.events';
import { ProcessingStep } from './step/processing-step';
import { ResultStep } from './step/result-step';
import { StartStep } from './step/start-step';
import 'ui.fonts.inter';
import './style.css';

export const PopupState = Object.freeze({
	START: 'start',
	PROCESSING: 'processing',
	RESULT: 'result',
});

export type DepartmentType = {
	id: string,
	name: string,
	accessCode: string,
}

export type TransferToIntranetPopupType = {
	userType: string,
	userName: string,
	userPhoto: string,
	componentName: string,
	signedParameters: string,
	rootDepartment: DepartmentType,
}

export class TransferToIntranetPopup extends EventEmitter
{
	#popup: Popup = null;
	#popupState: PopupState;
	#startStep: StartStep;
	#processingStep: ProcessingStep;
	#resultStep: ResultStep;
	#options: TransferToIntranetPopupType;
	#departmentIds: Array;
	#success: boolean;

	constructor(options: TransferToIntranetPopupType)
	{
		super();

		this.setEventNamespace('BX.Intranet.TransferToIntranet');
		this.#popupState = PopupState.START;
		this.#options = options;

		this.subscribe('closePopup', this.#onClosePopup.bind(this));
		this.subscribe('changeState', this.#onChangeState.bind(this));
	}

	#onClosePopup(): void
	{
		this.#clearPopupData();
		this.#popup.destroy();
	}

	#clearPopupData(): void
	{
		this.#startStep = null;
		this.#processingStep = null;
		this.#resultStep = null;
		this.unsubscribe('closePopup', this.#onClosePopup.bind(this));
		this.unsubscribe('changeState', this.#onChangeState.bind(this));
	}

	destroy(): void
	{
		this.#popup.destroy();
		this.#clearPopupData();
	}

	#onChangeState(event: BaseEvent): void
	{
		let height = 357;
		switch (this.#popupState)
		{
			case PopupState.START:
				height = 245;
				if ('departmentValues' in event.getData())
				{
					this.#departmentIds = event.getData().departmentValues;
				}
				this.#popupState = PopupState.PROCESSING;
				break;
			case PopupState.PROCESSING:
				height = 307;
				this.#popupState = PopupState.RESULT;
				if (event.getData()?.success)
				{
					this.#success = event.getData().success;
				}
				break;
			case PopupState.RESULT:
				this.#popupState = PopupState.START;
				this.#onClosePopup();
				if (this.#success)
				{
					location.reload();
				}
				return;
			default:
				throw new Error('Popup state is not defined');
		}

		if (this.#popupState !== PopupState.START)
		{
			this.#popup.setHeight(height);
			this.#popup.setContent(this.#getPopupContent());
		}
	}

	show(): void
	{
		this.#popup = this.#createPopup();
		this.#popup.show();
	}

	#createPopup(): Popup
	{
		const popup = PopupManager.create({
			id: 'extranet-to-intranet-popup',
			className: 'extranet-to-intranet-container',
			closeIcon: false,
			autoHide: false,
			closeByEsc: true,
			padding: 0,
			width: 542,
			height: 357,
			events: {
				onClose: () => {
					this.#onClosePopup();
				},
			},
		});

		popup.setContent(this.#getPopupContent());

		return popup;
	}

	#getPopupContent(): HTMLElement
	{
		let content = '';
		switch (this.#popupState)
		{
			case PopupState.START:
				content = this.#getStartStep().render();
				break;
			case PopupState.PROCESSING:
				this.#getProcessingStep().send();
				content = this.#getProcessingStep().render();
				break;
			case PopupState.RESULT:
				content = this.#getResultStep().render();
				break;
			default:
				throw new Error('Popup state is not defined');
		}

		return Tag.render`
			<div class="extranet-to-intranet-wrapper">
				${this.#renderCloseIcon()}
				${content}
			</div>
		`;
	}

	#renderCloseIcon(): HTMLElement
	{
		if (this.#popupState !== PopupState.START)
		{
			return null;
		}
		const onClick = () => {
			this.#popup.close();
		};

		return Tag.render`
			<div class="extranet-to-intranet__close-icon ui-icon-set --cross-30" onclick="${onClick}"></div>
		`;
	}

	#getStartStep(): StartStep
	{
		if (!this.#startStep)
		{
			this.#startStep = new StartStep(this.#options, this);
		}

		return this.#startStep;
	}

	#getProcessingStep(): ProcessingStep
	{
		if (!this.#processingStep)
		{
			this.#processingStep = new ProcessingStep(this.#options, this.#departmentIds, this);
		}

		return this.#processingStep;
	}

	#getResultStep(): ResultStep
	{
		if (!this.#resultStep)
		{
			this.#resultStep = new ResultStep(this.#options, this.#success, this);
		}

		return this.#resultStep;
	}
}
