import * as Util from '../../util/registry';
import Event from '../../util/event';

type Options = {
	value: ?string;
	label: ?string;
	selected: ?boolean;
};


class Item extends Event
{
	events: Object = {
		changeSelected: 'change:selected',
	};
	value: ?string = '';
	label: string = '';
	_selectedInternal: boolean = false;

	constructor(options: Options)
	{
		super(options);
		this._selectedInternal = !!options.selected;
		if (Util.Type.defined(options.label))
		{
			this.label = options.label;
		}
		if (Util.Type.defined(options.value))
		{
			this.value = options.value;
		}
	}

	get selected()
	{
		return this._selectedInternal;
	}

	set selected(value)
	{
		this._selectedInternal = this.onSelect(value);
		this.emit(this.events.changeSelected);
	}

	onSelect(value)
	{
		return value;
	}

	getComparableValue(): ?string
	{
		return this.value;
	}
}

export {Item, Options}