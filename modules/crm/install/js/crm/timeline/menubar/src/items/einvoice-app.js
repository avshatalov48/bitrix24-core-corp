import { SidePanel } from 'ui.sidepanel';
import Item from '../item';

export default class EInvoiceApp extends Item
{
	#einvoiceUrl: string;

	showSlider(): void
	{
		SidePanel.Instance.open(
			this.#einvoiceUrl,
			{
				width: 575,
				allowChangeHistory: false,
			},
		);
	}

	supportsLayout(): Boolean
	{
		return false;
	}

	initializeSettings(): void
	{
		this.#einvoiceUrl = this.getSetting('einvoiceUrl');
	}
}
