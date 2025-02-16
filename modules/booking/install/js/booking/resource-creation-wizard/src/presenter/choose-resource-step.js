import { Event, Loc } from 'main.core';
import { EventName } from 'booking.const';
import { Step } from './step';

export class ChooseResourceStep extends Step
{
	constructor()
	{
		super(true);
	}

	get labelBack(): string
	{
		return Loc.getMessage('BRCW_BUTTON_CANCEL');
	}

	next(): Promise<void>
	{
		return super.next();
	}

	back()
	{
		Event.EventEmitter.emit(EventName.CloseWizard);
	}
}
