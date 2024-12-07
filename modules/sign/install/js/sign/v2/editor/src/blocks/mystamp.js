import { Loc, Tag, Text } from 'main.core';
import { Api } from 'sign.v2.api';
import { Uploader } from 'ui.stamp.uploader';
import Dummy from './dummy';
import Block from './block';

export default class MyStamp extends Dummy
{
	static currentBlock: Block;

	dataSrc: string;
	#uploader: Uploader;
	#api: Api;

	constructor(block: Block)
	{
		super(block);

		this.#uploader = new Uploader({
			controller: {
				upload: 'sign.upload.stampUploadController',
			},
			contact: {
				id: 0,
				label: 'Admin'
			},
		});
		this.#uploader.subscribe('onSaveAsync', async (event) => {
			MyStamp.currentBlock.await(true);
			const eventData = event.getData();
			if (!eventData?.file?.serverId)
			{
				return;
			}

			this.dataSrc = eventData.file.serverPreviewUrl;
			this.#saveStamp(MyStamp.currentBlock, eventData.file.serverId);
		});
		this.#api = new Api();
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
		const title = this.data.__view?.base64 ?
			Loc.getMessage('SIGN_EDITOR_BLOCK_CHANGE_STAMP') :
			Loc.getMessage('SIGN_EDITOR_BLOCK_LOAD_STAMP');

		return Tag.render`
			<div class="sign-document__block-style-btn --funnel">
				<button onclick="${this.#onActionClick.bind(this)}" data-role="action">
					${title}
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
	async #saveStamp(block: Block, fileId: string)
	{
		const memberPart = block.getMemberPart();
		const member = block.blocksManager.getMemberByPart(memberPart);
		await this.#api.saveStamp(member.uid, fileId);
		block.assign();
		const layout = MyStamp.currentBlock.getLayout();
		const button = layout.querySelector('button[data-role="action"]');
		button.textContent = Loc.getMessage('SIGN_EDITOR_BLOCK_CHANGE_STAMP');
	}

	/**
	 * Returns type's content in view mode.
	 * @return {HTMLElement | string}
	 */
	getViewContent(): HTMLElement | string
	{
		let src = null;

		if (this.data.__view?.base64)
		{
			src = 'data:image;base64,' + this.data.__view.base64;
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
