/**
 * @module im/messenger/lib/element/dialog/message/element/attach/attach
 */
jn.define('im/messenger/lib/element/dialog/message/element/attach/attach', (require, exports, module) => {
	const { Type } = require('type');
	const { Loc } = require('loc');
	const { clone } = require('utils/object');
	const {
		AttachType,
		AttachColorToken,
		AttachGridItemDisplay,
	} = require('im/messenger/const');
	const { parser } = require('im/messenger/lib/parser/parser');
	const { formatFileSize } = require('im/messenger/lib/helper');
	const { LoggerManager } = require('im/messenger/lib/logger');
	const logger = LoggerManager.getInstance().getLogger('element--message-attach');

	class Attach
	{
		/**
		 * @param {AttachModelItem} modelAttach
		 */
		static createByMessagesModelAttach(modelAttach)
		{
			return new this(modelAttach);
		}

		/**
		 * @param {AttachModelItem} modelAttach
		 */
		constructor(modelAttach)
		{
			/**
			 * @type AttachModelItem
			 */
			this.modelAttach = [];
			if (Type.isArrayFilled(modelAttach))
			{
				this.modelAttach = modelAttach;
			}
		}

		/**
		 * @return {AttachConfig[]}
		 */
		toMessageFormat()
		{
			const messageAttach = clone(this.modelAttach).map((item) => {
				if (!Type.isStringFilled(item.colorToken))
				{
					// eslint-disable-next-line no-param-reassign
					item.colorToken = AttachColorToken.primary;
				}

				const blocks = [];

				item.blocks.forEach((block) => {
					const messageBlocks = this.#toMessageBlocks(block);
					blocks.push(...messageBlocks);
				});

				return {
					...item,
					blocks,
				};
			});

			logger.log(`${this.constructor.name}.toMessageFormat: `, this.modelAttach, messageAttach);

			return messageAttach;
		}

		/**
		 * @param block
		 * @return {Object[]}
		 */
		#toMessageBlocks(block)
		{
			if (block[AttachType.grid])
			{
				return this.#toMessageGridBlocks(block[AttachType.grid]);
			}

			if (block[AttachType.file])
			{
				return [
					this.#toMessageFileBlock(block[AttachType.file]),
				];
			}

			if (block[AttachType.rich])
			{
				return [
					this.#toMessageRichLinkBlock(block[AttachType.rich]),
				];
			}

			if (block[AttachType.link])
			{
				return [
					this.#toMessageLinkBlock(block[AttachType.link]),
				];
			}

			if (block[AttachType.message])
			{
				return [
					this.#toMessageMessageBlock(block[AttachType.message]),
				];
			}

			return [block];
		}

		/**
		 * @param {AttachGridItemConfig[]} grid
		 * @return {Object[]}
		 */
		#toMessageGridBlocks(grid)
		{
			const result = [];
			let tempGrid = [];
			let lastWasLine = false;

			for (let itemIndex = 0; itemIndex < grid.length; itemIndex++)
			{
				const item = grid[itemIndex];
				item.value = parser.decodeTextForAttachBlock(item.value);
				if (Type.isStringFilled(item.link))
				{
					item.value = `[url=${item.link}]${item.value}[/url]`;
				}

				if (!Type.isStringFilled(item.colorToken))
				{
					// eslint-disable-next-line no-param-reassign
					item.colorToken = AttachColorToken.base;
				}

				if (item.display === AttachGridItemDisplay.line)
				{
					if (tempGrid.length > 0)
					{
						result.push({ grid: tempGrid });
						tempGrid = [];
					}

					result.push(this.#createGridWithDisplayBlock(item));
					lastWasLine = true;

					continue;
				}

				if (lastWasLine)
				{
					result.push(this.#createBaseDelimiter());
					lastWasLine = false;
				}

				tempGrid.push({
					...item,
					display: AttachGridItemDisplay.block,
				});

				if (
					itemIndex === grid.length - 1
					|| (
						grid[itemIndex + 1].display !== AttachGridItemDisplay.row
						&& grid[itemIndex + 1].display !== AttachGridItemDisplay.block
					)
				)
				{
					result.push({ grid: tempGrid });
					tempGrid = [];
				}
			}

			if (tempGrid.length > 0)
			{
				result.push({ grid: tempGrid });
			}

			return result;
		}

		/**
		 * @param {AttachFileItemConfig[]} files
		 * @return {MobileAttachFileConfig}
		 */
		#toMessageFileBlock(files)
		{
			const attachFiles = files.map((file) => {
				const attachFile = file;
				if (Type.isNumber(file.size))
				{
					attachFile.displayedSize = formatFileSize(file.size);
				}

				attachFile.downloadText = Loc.getMessage('IMMOBILE_ELEMENT_DIALOG_MESSAGE_ATTACH_FILE_DOWNLOAD');

				return attachFile;
			});

			return {
				[AttachType.file]: attachFiles,
			};
		}

		/**
		 * @param {AttachRichItemConfig[]} richItems
		 * @return {MobileAttachFileConfig}
		 */
		#toMessageRichLinkBlock(richItems)
		{
			const rich = richItems.map((richLink) => {
				let previewUrl = richLink.preview ?? null;
				if (Type.isString(previewUrl) && !previewUrl.startsWith('http'))
				{
					previewUrl = currentDomain + previewUrl;
				}

				return {
					link: richLink.link ?? '',
					desc: richLink.desc ?? '',
					name: richLink.name ?? '',
					previewUrl,
					previewSize: {
						height: richLink?.previewSize?.height ?? 0,
						width: richLink?.previewSize?.width ?? 0,
					},
				};
			});

			return {
				[AttachType.rich]: rich,
			};
		}

		/**
		 * @param {AttachLinkItemConfig[]} link
		 * @return {AttachLinkConfig}
		 */
		#toMessageLinkBlock(link)
		{
			const linkConfig = link[0];

			return {
				[AttachType.link]: [{
					preview: linkConfig.preview,
					width: linkConfig.width,
					height: linkConfig.height,
					name: linkConfig.name,
					desc: parser.decodeTextForAttachBlock(linkConfig.desc),
					link: linkConfig.link,
				}],
			};
		}

		/**
		 * @param {AttachMessageConfig['message']} message
		 * @return {AttachMessageConfig}
		 */
		#toMessageMessageBlock(message)
		{
			return {
				[AttachType.message]: parser.decodeTextForAttachBlock(message),
			};
		}

		/**
		 * @param {AttachGridItemConfig} grid
		 * @return AttachGridConfig
		 */
		#createGridWithDisplayBlock(grid)
		{
			return {
				[AttachType.grid]: [{
					...grid,
					display: AttachGridItemDisplay.block,
				}],
			};
		}

		/**
		 * @return AttachDelimiterConfig
		 */
		#createBaseDelimiter()
		{
			return {
				[AttachType.delimiter]: {
					// fake size and color, chat.dialog widget will ignore it
					size: 200,
					color: '#c6c6c6',
				},
			};
		}
	}

	module.exports = { Attach };
});
