import { Runtime } from 'main.core';
import type { CyclePopupOpener, CardId } from 'booking.component.cycle-popup';

export function showCyclePopup(status: string): void
{
	void Runtime.loadExtension('booking.component.cycle-popup')
		.then((CyclePopup: { CardId: CardId, cyclePopupOpener: CyclePopupOpener }): void => {
			const scrollToCard = {
				not_confirmed: CyclePopup.CardId.Unconfirmed,
				confirmed: CyclePopup.CardId.Confirmed,
				success: CyclePopup.CardId.Confirmed,
				late: CyclePopup.CardId.Late,
				failed: CyclePopup.CardId.Late,
				waitlist: CyclePopup.CardId.Waitlist,
				overbooking: CyclePopup.CardId.Overbooking,
			}[status];

			CyclePopup.cyclePopupOpener.show({ context: 'crm', scrollToCard });
		})
	;
}
