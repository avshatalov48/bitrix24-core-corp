import 'date';
import { Loc, Tag, Dom } from 'main.core';

import Dummy from './dummy';

export default class Date extends Dummy
{
	#calendar: BX.JCCalendar;
	#calendarOpened: boolean = false;
	#date: string;

	/**
	 * Returns initial dimension of block.
	 * @return {width: number, height: number}
	 */
	getInitDimension(): {width: number, height: number}
	{
		return {
			width: 105,
			height: 28
		};
	}

	/**
	 * Sets new data.
	 * @param {any} data
	 */
	setData(data: any)
	{
		this.data = data ? data : {};
		this.#date = this.data.text;
	}

	/**
	 * Calls when action button was clicked.
	 */
	onActionClick()
	{
		this.#calendar = BX.calendar({
			node: this.block.getLayout(),
			field: this.getViewContent(),
			value: this.#date,
			bTime: false,
			callback_after: (date) => {
				this.setText(
					BX.date.format(BX.date.convertBitrixFormat(BX.message('FORMAT_DATE')), date, null)
				);
				this.block.renderView();
			}
		});

		this.#calendarOpened = true;
		const targetContainer = document.querySelector('.sign-editor__content');
		const { popup } = this.#calendar;
		popup.targetContainer = targetContainer;
		if (popup.popupContainer.parentElement !== targetContainer)
		{
			Dom.append(popup.popupContainer, targetContainer);
			popup.adjustPosition();
		}
	}

	/**
	 * Returns action button for edit content.
	 * @return {HTMLElement}
	 */
	getActionButton(): HTMLElement
	{
		return Tag.render`
			<div class="sign-document__block-style-btn --funnel">
				<button onclick="${this.onActionClick.bind(this)}" data-role="action">
					${Loc.getMessage('SIGN_JS_DOCUMENT_DATE_ACTION_BUTTON')}
				</button>
			</div>
		`;
	}

	/**
	 * Closes calendar if open.
	 */
	#closeCalendar()
	{
		if (this.#calendar)
		{
			this.#calendar.Close();
			this.#calendarOpened = false;
		}
	}

	/**
	 * Calls when block starts being resized or moved.
	 */
	onStartChangePosition()
	{
		this.#closeCalendar();
	}

	/**
	 * Calls when block saved.
	 */
	onSave()
	{
		this.#closeCalendar();
	}

	/**
	 * Calls when block removed.
	 */
	onRemove()
	{
		this.#closeCalendar();
	}

	/**
	 * Calls when click was out the block.
	 */
	onClickOut()
	{
		if (!this.#calendarOpened)
		{
			this.block.forceSave();
		}

		this.#calendarOpened = false;
	}

	getStyles(): { [p: string]: string }
	{
		return { ...super.getStyles(), ...Date.defaultTextBlockPaddingStyles };
	}
}
