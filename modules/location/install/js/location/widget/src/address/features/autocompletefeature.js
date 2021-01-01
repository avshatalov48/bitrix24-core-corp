import {Address, ControlMode} from 'location.core';
import BaseFeature from './basefeature';
import Autocomplete from '../../autocomplete/autocomplete';

/**
 * Complex address widget
 */
export default class AutocompleteFeature extends BaseFeature
{
	static searchStartedEvent = 'searchStarted';
	static searchCompletedEvent = 'searchCompleted';

	#autocomplete;
	#addressWidget = null;

	constructor(props)
	{
		super();

		if(!(props.autocomplete instanceof Autocomplete))
		{
			BX.debug('props.autocomplete  must be instance of Autocomplete');
		}

		this.#autocomplete = props.autocomplete;

		this.#autocomplete.onAddressChangedEventSubscribe(
			(event) =>
			{
				const data = event.getData();
				this.#addressWidget.setAddressByFeature(data.address, this);
			});

		this.#autocomplete.onStateChangedEventSubscribe(
			(event) =>
			{
				const data = event.getData();
				this.#addressWidget.setStateByFeature(data.state);
			});

		this.#autocomplete.onSearchStartedEventSubscribe(
			(event) =>
			{
				const data = event.getData();
				this.#addressWidget.emitFeatureEvent(
					{
						feature: this,
						eventCode: AutocompleteFeature.searchStartedEvent,
						payload: data
					}
				);
			});

		this.#autocomplete.onSearchCompletedEventSubscribe(
			(event) =>
			{
				const data = event.getData();
				this.#addressWidget.emitFeatureEvent(
					{
						feature: this,
						eventCode: AutocompleteFeature.searchCompletedEvent,
						payload: data
					}
				);
			});
	}

	resetView(): void
	{
		this.#autocomplete.closePrompt();
	}

	render(props): void
	{
		if(this.#addressWidget.mode === ControlMode.edit)
		{
			this.#autocomplete.render({
				inputNode: this.#addressWidget.inputNode,
				address: this.#addressWidget.address,
				mode: this.#addressWidget.mode,
			});
		}
	}

	setAddress(address: ?Address): void
	{
		this.#autocomplete.address = address;
	}

	setAddressWidget(addressWidget)
	{
		this.#addressWidget = addressWidget;
	}

	get autocomplete()
	{
		return this.#autocomplete;
	}

	destroy()
	{
		this.#autocomplete.destroy();
		this.#autocomplete = null;
	}
}