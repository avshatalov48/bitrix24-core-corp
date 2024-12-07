import { Loc } from '../loc';
import { EventEmitter } from 'main.core.events';

export class Base extends EventEmitter
{
	props;
	#loc: Loc;

	constructor(props)
	{
		super(props);
		this.props = props;
		this.#loc = Loc.getInstance();

		this.setEventNamespace('AI:Picker:UI');
	}

	getMessage(code: string): string
	{
		return this.#loc.getMessage(code);
	}

	render(): HTMLElement | null
	{
		return null;
	}
}
