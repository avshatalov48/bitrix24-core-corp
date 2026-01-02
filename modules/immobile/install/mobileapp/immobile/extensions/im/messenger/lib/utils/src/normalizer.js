/**
 * @module im/messenger/lib/utils/normalizer
 */
jn.define('im/messenger/lib/utils/normalizer', (require, exports, module) => {
	const { Type } = require('type');
	const { clone } = require('utils/object');
	const { ObjectUtils } = require('im/messenger/lib/utils/object');
	const { Logger } = require('im/messenger/lib/logger');

	const attachDefaultObject = {
		id: 0,
		blocks: [],
		description: '',
		color: '',
		colorToken: '',
	};

	class Normalizer
	{
		/**
		 * @param {*} attachData
		 * @return {Array<AttachConfig>}
		 */
		static normalizeMessageAttach(attachData)
		{
			if (Type.isNil(attachData))
			{
				return [];
			}

			if (Type.isArray(attachData) && !Type.isArrayFilled(attachData))
			{
				return attachData;
			}

			let attachDataArray = attachData;
			if (!Type.isArray(attachData))
			{
				attachDataArray = [attachData];
			}

			return attachDataArray.map((item) => {
				try
				{
					if (Type.isNil(item))
					{
						return { ...attachDefaultObject };
					}

					const preparedKeysItem = ObjectUtils.convertKeysToCamelCase(clone(item), true);

					return {
						id: Type.isNumber(preparedKeysItem.id) || Type.isStringFilled(preparedKeysItem.id)
							? Number(preparedKeysItem.id) : attachDefaultObject.id,
						blocks: Type.isArray(preparedKeysItem.blocks)
							? preparedKeysItem.blocks : attachDefaultObject.blocks,
						description: Type.isString(preparedKeysItem.description)
							? preparedKeysItem.description : attachDefaultObject.description,
						color: Type.isString(preparedKeysItem.color)
							? preparedKeysItem.color : attachDefaultObject.color,
						colorToken: Type.isString(preparedKeysItem.colorToken)
							? preparedKeysItem.colorToken : attachDefaultObject.colorToken,
					};
				}
				catch (error)
				{
					Logger.error('Normalizer.normalizeMessageAttach catch:', error);

					return { ...attachDefaultObject };
				}
			});
		}
	}

	module.exports = {
		Normalizer,
	};
});
