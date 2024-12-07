import { Loc, Tag, Text } from 'main.core';
import { Backend } from 'sign.backend';
import { Uploader } from 'ui.stamp.uploader';

import Dummy from './dummy';
import Block from '../block';

export default class MyStamp extends Dummy
{
	static currentBlock: Block;

	dataSrc: string;
	#uploader: Uploader;

	constructor(block: Block)
	{
		super(block);

		this.#uploader = new Uploader({
			controller: {
				upload: 'sign.upload.stampUploadController',
			},
			contact: {
				id: 0,
				label: Text.encode(this.block.getDocument().getInitiatorName()),
			},
		});

		this.#uploader.subscribe('onSaveAsync', (event) => {

			MyStamp.currentBlock.await(true);

			return new Promise((resolve, reject) => {

				const eventData = event.getData();

				if (!eventData?.file?.serverId)
				{
					reject();
					return;
				}

				this.dataSrc = eventData.file.serverPreviewUrl;

				this.#saveStamp(MyStamp.currentBlock, eventData.file.serverId).then(() => {
					resolve();
				}).catch((error) => {
					reject(error);
				});
			});
		});
	}

	/**
	 * Returns true if block is in singleton mode.
	 * @return {boolean}
	 */
	isSingleton(): boolean
	{
		return true;
	}

	/**
	 * Returns true if style panel mast be showed.
	 * @return {boolean}
	 */
	isStyleAllowed(): boolean
	{
		return false;
	}

	/**
	 * Returns initial dimension of block.
	 * @return {width: number, height: number}
	 */
	getInitDimension(): {width: number, height: number}
	{
		return {
			width: 151,
			height: 151
		};
	}

	/**
	 * Returns action button for edit content.
	 * @return {HTMLElement}
	 */
	getActionButton(): HTMLElement
	{
		return Tag.render`
			<div class="sign-document__block-style-btn --funnel">
				<button onclick="${this.#onActionClick.bind(this)}" data-role="action">
					${Loc.getMessage('SIGN_JS_DOCUMENT_STAMP_ACTION_BUTTON')}
				</button>
			</div>
		`;
	}

	/**
	 * Calls when action button was clicked.
	 * @param {PointerEvent} event
	 */
	#onActionClick(event: PointerEvent)
	{
		MyStamp.currentBlock = this.block;
		this.#uploader.show();
	}

	/**
	 * Saves stamp to backend.
	 * @param {Block} block
	 * @param {string} fileId
	 */
	#saveStamp(block: Block, fileId: string): Promise
	{
		return Backend.controller({
				command: 'integration.crm.saveCompanyStamp',
				postData: {
					documentId: block.getDocument().getId(),
					companyId: block.getDocument().getCompanyId(),
					fileId: fileId
				}
			})
			.then(result => {
				block.assign();
			})
			.catch(() => {});
	}

	/**
	 * Returns type's content in view mode.
	 * @return {HTMLElement | string}
	 */
	getViewContent(): HTMLElement | string
	{
		let src = null;

		if (this.dataSrc)
		{
			src = this.dataSrc;
		}
		else if (this.data.base64)
		{
			src = 'data:image;base64,' + this.data.base64;
		}

		if (src)
		{
			return Tag.render`
				<div class="sign-document__block-content_stamp" style="background-image: url(${src})"></div>
			`;
		}
		else
		{
			return Tag.render`
				<div class="sign-document__block-content_member-nodata --stamp"></div>
			`;
		}
	}
}
