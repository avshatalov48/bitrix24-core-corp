import { Loc, Tag, Text as TextFormat } from 'main.core';
import { Api } from 'sign.v2.api';

import Dummy from './dummy';

export default class Number extends Dummy
{
	static sliderOnMessageBind: boolean = false;

	/**
	 * Returns true if block is in singleton mode.
	 * @return {boolean}
	 */
	isSingleton(): boolean
	{
		return true;
	}

	/**
	 * Returns initial dimension of block.
	 * @return {width: number, height: number}
	 */
	getInitDimension(): {width: number, height: number}
	{
		return {
			width: 100,
			height: 28
		};
	}

	/**
	 * Calls when action button was clicked.
	 * @param {PointerEvent} event
	 */
	#onActionClick(event: PointerEvent)
	{
		event.stopPropagation();
		if (!Number.sliderOnMessageBind)
		{
			Number.sliderOnMessageBind = true;

			BX.addCustomEvent(window, 'SidePanel.Slider:onMessage', async (event) =>
			{
				const isEditor = event.slider?.url === 'editor';
				if (isEditor && event.getEventId() === 'numerator-saved-event')
				{
					const numeratorData = event.getData();
					const currentBlock = event?.sender?.options?.data?.block ?? this.block;

					if (numeratorData.type === 'CRM_SMART_DOCUMENT')
					{
						currentBlock.await(true);
						const api = new Api();
						const uid = currentBlock.blocksManager.getDocumentUid();
						await api.refreshEntityNumber(uid);
						currentBlock.assign();
					}
				}
			});
		}

		const { crmNumeratorUrl } = this.data.__view;
		BX.SidePanel.Instance.open(crmNumeratorUrl, {
			width: 480,
			cacheable: false,
			data: {
				block: this.block
			}
		});
	}

	/**
	 * Returns action button for edit content.
	 * @return {HTMLElement | null}
	 */
	getActionButton(): ?HTMLElement
	{
		return Tag.render`
			<div class="sign-document__block-style-btn --funnel">
				<button onclick="${this.#onActionClick.bind(this)}" data-role="action">
					${Loc.getMessage('SIGN_JS_DOCUMENT_NUMBER_ACTION_EDIT')}
				</button>
			</div>
		`;
	}

	/**
	 * Returns type's content in view mode.
	 * @return {HTMLElement | string}
	 */
	getViewContent(): HTMLElement | string
	{
		return Tag.render`
			<div class="sign-document__block-number">
				${TextFormat.encode(this.data.text || '').toString()}
			</div>
		`;
	}

	getStyles(): { [p: string]: string }
	{
		return { ...super.getStyles(), ...Number.defaultTextBlockPaddingStyles };
	}
}
