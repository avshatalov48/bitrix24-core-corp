import { EventEmitter } from 'main.core.events';

type Props = {
	filterId: string,
};

export class Filter
{
	#props: Props;
	#filter: BX.Main.Filter;
	#popupManager: BX.Main.PopupManager;
	#fields: any;

	constructor(props: Props)
	{
		this.#props = props;
		this.#init();
	}

	#init()
	{
		this.#filter = BX.Main.filterManager.getById(this.#props.filterId);
		this.#popupManager = BX.Main.PopupManager;
		this.#updateFields();
		this.#subscribeToEvents();
	}

	#updateFields()
	{
		this.#fields = this.#filter.getFilterFieldsValues();
	}

	#unSubscribeToEvents()
	{
		EventEmitter.unsubscribe('BX.Main.Filter:apply', this.#applyFilterHandler.bind(this));
		EventEmitter.unsubscribe('BX.Main.Filter:onGetStopBlur', this.#onGetStopBlur.bind(this));
	}

	#subscribeToEvents()
	{
		EventEmitter.subscribe('BX.Main.Filter:apply', this.#applyFilterHandler.bind(this));
		EventEmitter.subscribe('BX.Main.Filter:onGetStopBlur', this.#onGetStopBlur.bind(this));
	}

	#applyFilterHandler()
	{
		this.#updateFields();
	}

	#onGetStopBlur(event)
	{
		const { target, path } = event.getCompatData()[0];
		const popupWindow = path.find(el => {
			return el.classList?.contains('popup-window') || el.classList?.contains('main-ui-square')
		});
		const popups = [
			this.#filter.popup.popupContainer,
			this.#filter.getFieldsPopup().popupContainer
		];

		if (
			this.#filter.popup.isShown()
			&& !popups.some(el => el.contains(target))
			&& !popupWindow
		)
		{
			this.#filter.closePopup();
		}
	}

	#resetFilter()
	{
		this.#filter.resetFilter();
	}

	destroyPopups()
	{
		this.#filter.popup.destroy();
		this.#popupManager.getPopupById(this.#props.filterId + '_fields_popup')?.destroy();
		this.#popupManager.getPopupById(this.#props.filterId + '-grid-settings-window')?.destroy();
	}

	release()
	{
		this.#resetFilter();
		this.#unSubscribeToEvents();
		this.destroyPopups();
	}
}