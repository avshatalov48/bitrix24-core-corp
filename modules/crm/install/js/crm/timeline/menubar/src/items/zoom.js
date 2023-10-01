import Item from '../item';
import { Loc, Tag } from 'main.core';
import { Zoom as ZoomEditor } from 'crm.zoom';

export default class Zoom extends Item
{
	#editor: ZoomEditor = null;

	showSlider(): void
	{
		if (this.getSetting('isAvailable'))
		{
			BX.Crm.Zoom.onNotConnectedHandler(Loc.getMessage('USER_ID'));
		}
		else // not available
		{
			BX.Crm.Zoom.onNotAvailableHandler();
		}
	}

	supportsLayout(): Boolean
	{
		return this.getSetting('isConnected') && this.getSetting('isAvailable');
	}

	createLayout(): HTMLElement
	{
		return Tag.render`<div class="crm-entity-stream-content-new-detail ui-timeline-zoom-editor --focus --hidden"></div>`;
	}

	onFocus(e)
	{
		this.setFocused(true);
	}

	onShow()
	{
		if (!this.#editor)
		{
			this.#createEditor();
		}
	}

	#createEditor(): void
	{
		this.#editor = new ZoomEditor({
			ownerTypeId: this.getEntityTypeId(),
			ownerId: this.getEntityId(),
			container: this.getContainer(),
			onFinishEdit: this.#onFinishEdit.bind(this),
		});
	}

	#onFinishEdit(): void
	{
		this.emitFinishEditEvent();
	}
}
