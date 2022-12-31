/**
 * @module crm/type
 */
jn.define('crm/type', (require, exports, module) => {
	const { stringify } = require('utils/string');
	const { TypeId } = require('crm/type/id');
	const { TypeName } = require('crm/type/name');

	const idByNameMap = new Map();
	Object.keys(TypeName).forEach((name) => {
		if (TypeId.hasOwnProperty(name))
		{
			idByNameMap.set(TypeName[name], TypeId[name]);
		}
	});

	const nameByIdMap = new Map();
	Object.keys(TypeId).forEach((name) => {
		if (TypeName.hasOwnProperty(name))
		{
			nameByIdMap.set(TypeId[name], TypeName[name]);
		}
	});

	/**
	 * @class Type
	 */
	class Type
	{
		/**
		 * @param {String} name
		 * @return {null|Number}
		 */
		static resolveIdByName(name)
		{
			name = stringify(name);
			name = name.toUpperCase().trim();
			if (name === '')
			{
				return null;
			}

			if (idByNameMap.has(name))
			{
				return idByNameMap.get(name);
			}

			return null;
		}

		/**
		 * @param {Number} id
		 * @return {null|string}
		 */
		static resolveNameById(id)
		{
			id = Number(id);
			if (!Number.isInteger(id))
			{
				return null;
			}

			if (nameByIdMap.has(id))
			{
				return nameByIdMap.get(id);
			}

			return null;
		}

		/**
		 * @param {Number} id
		 * @return {boolean}
		 */
		static existsById(id)
		{
			id = Number(id);
			if (!Number.isInteger(id))
			{
				return false;
			}

			return nameByIdMap.has(id);
		}

		/**
		 * @param {String} name
		 * @return {boolean}
		 */
		static existsByName(name = '')
		{
			name = stringify(name);
			name = name.toUpperCase().trim();
			if (name === '')
			{
				return false;
			}

			return idByNameMap.has(name);
		}
	}

	module.exports = { Type, TypeId, TypeName };
});
