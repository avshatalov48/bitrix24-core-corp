/**
 * @module im/messenger/model/dialogues/collab/validators
 */

jn.define('im/messenger/model/dialogues/collab/validators', (require, exports, module) => {
	const { Type } = require('type');
	const { CollabEntity } = require('im/messenger/const');

	const defaultEntity = {
		counter: 0,
		url: '',
	};

	/**
	 * @param {CollabEntities} entities
	 */
	function validateEntities(entities)
	{
		const validEntities = {};
		Object.entries(entities).forEach(([key, entity]) => {
			if (validateEntityKey(key))
			{
				validEntities[key] = {
					...defaultEntity,
					...validateEntity(entity),
				};
			}
		});

		return { ...validEntities };
	}

	/**
	 * @param {CollabEntity} fields
	 */
	function validateEntity(fields)
	{
		const result = {};

		if (Type.isNumber(fields.counter))
		{
			result.counter = fields.counter;
		}

		if (Type.isString(fields.url))
		{
			result.url = fields.url;
		}

		return result;
	}

	/**
	 * @param {string} key
	 */
	function validateEntityKey(key)
	{
		return Boolean(CollabEntity[key]);
	}

	/**
	 * @param {CollabSetEntityCounterData} payload
	 */
	function validateSetCounterPayload(payload)
	{
		const {
			dialogId,
			entity,
			counter,
		} = payload;

		const isValidDialogId = Type.isString(dialogId);
		const isValidCounter = Type.isNumber(counter);
		const isValidDEntity = validateEntityKey(entity);

		return isValidDialogId && isValidCounter && isValidDEntity;
	}

	/**
	 * @param {CollabSetGuestCountData} payload
	 */
	function validateSetGuestCountPayload(payload)
	{
		const {
			dialogId,
			guestCount,
		} = payload;

		const isValidDialogId = Type.isString(dialogId);
		const isValidGuestCount = Type.isNumber(guestCount);

		return isValidDialogId && isValidGuestCount;
	}

	module.exports = {
		validateSetGuestCountPayload,
		validateSetCounterPayload,
		validateEntities,
	};
});
