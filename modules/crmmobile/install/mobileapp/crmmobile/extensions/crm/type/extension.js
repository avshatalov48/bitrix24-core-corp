/**
 * @module crm/type
 */
jn.define('crm/type', (require, exports, module) => {
	const { stringify, camelize } = require('utils/string');
	const { TypeId, DynamicTypeId } = require('crm/type/id');
	const { TypeName, DynamicTypeName } = require('crm/type/name');

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

	const SUPPORTED_ENTITIES = new Set([
		TypeId.Contact,
		TypeId.Company,
		TypeId.Lead,
		TypeId.Deal,
		TypeId.Quote,
		TypeId.SmartInvoice,
	]);

	/**
	 * @class Type
	 */
	class Type
	{
		/**
		 * @param {Number} id
		 * @return {Boolean}
		 */
		static isEntitySupportedById(id)
		{
			id = Type.prepareEntityId(id);
			if (!Number.isInteger(id))
			{
				return false;
			}

			return SUPPORTED_ENTITIES.has(id) || Type.isDynamicTypeById(id);
		}

		/**
		 * @param {Number} id
		 * @return {Boolean}
		 */
		static existsById(id)
		{
			id = Type.prepareEntityId(id);
			if (!Number.isInteger(id))
			{
				return false;
			}

			if (nameByIdMap.has(id))
			{
				return true;
			}

			return Type.isDynamicTypeById(id);
		}

		/**
		 * @param {Number} id
		 * @return {null|string}
		 */
		static resolveNameById(id)
		{
			id = Type.prepareEntityId(id);
			if (!Number.isInteger(id))
			{
				return null;
			}

			if (nameByIdMap.has(id))
			{
				return nameByIdMap.get(id);
			}

			if (Type.isDynamicTypeById(id))
			{
				return Type.getDynamicTypeNameById(id);
			}

			return null;
		}

		static getDynamicTypeNameById(id)
		{
			id = Type.prepareEntityId(id);
			if (!Number.isInteger(id))
			{
				return null;
			}

			return DynamicTypeName.Prefix + id;
		}

		/**
		 * @param {Number} id
		 * @return {Boolean}
		 */
		static isDynamicTypeById(id)
		{
			id = Type.prepareEntityId(id);
			if (!Number.isInteger(id))
			{
				return false;
			}

			if (id >= DynamicTypeId.UnlimitedTypeStart)
			{
				return id % 2 === 0;
			}

			return id >= DynamicTypeId.Start && id < DynamicTypeId.End;
		}

		/**
		 * @param {Number} id
		 * @return {Number}
		 */
		static prepareEntityId(id)
		{
			return Number(id);
		}

		/**
		 * @param {String} name
		 * @return {Boolean}
		 */
		static isEntitySupportedByName(name)
		{
			return Type.isEntitySupportedById(Type.resolveIdByName(name));
		}

		/**
		 * @param {String} name
		 * @return {Boolean}
		 */
		static existsByName(name)
		{
			name = Type.prepareEntityName(name);
			if (name === '')
			{
				return false;
			}

			if (idByNameMap.has(name))
			{
				return true;
			}

			return Type.isDynamicTypeByName(name);
		}

		/**
		 * @param {String} name
		 * @return {null|Number}
		 */
		static resolveIdByName(name)
		{
			name = Type.prepareEntityName(name);
			if (name === '')
			{
				return null;
			}

			if (idByNameMap.has(name))
			{
				return idByNameMap.get(name);
			}

			if (Type.isDynamicTypeByName(name))
			{
				return Type.getDynamicTypeIdByName(name);
			}

			return null;
		}

		/**
		 * @param {String} name
		 * @return {Boolean}
		 */
		static isDynamicTypeByName(name)
		{
			const dynamicTypeId = Type.getDynamicTypeIdByName(name);

			return Type.isDynamicTypeById(dynamicTypeId);
		}

		/**
		 * @param {String} name
		 * @return {Number}
		 */
		static getDynamicTypeIdByName(name)
		{
			name = Type.prepareEntityName(name);
			if (name === '')
			{
				return 0;
			}

			if (name.indexOf(DynamicTypeName.Prefix) === 0)
			{
				return parseInt(name.replace(DynamicTypeName.Prefix, '')) || 0;
			}

			return 0;
		}

		/**
		 * @param {String} name
		 * @return {String}
		 */
		static prepareEntityName(name)
		{
			name = stringify(name);

			return name.toUpperCase().trim();
		}

		static getCommonEntityTypeName(entityType)
		{
			if (Type.existsById(entityType))
			{
				entityType = Type.resolveNameById(entityType);
			}

			if (Type.existsByName(entityType))
			{
				return Type.isDynamicTypeByName(entityType) ? TypeName.CommonDynamic : entityType.toUpperCase();
			}

			return null;
		}

		static getTypeForAnalytics(entityType)
		{
			const analyticsType = Type.getCommonEntityTypeName(entityType);
			if (analyticsType)
			{
				return analyticsType.toLowerCase();
			}

			return null;
		}

		static getCamelizedEntityTypeName(entityType)
		{
			entityType = Type.getCommonEntityTypeName(entityType);
			if (entityType)
			{
				return camelize(entityType.toLowerCase());
			}

			return null;
		}

		static getSupportedEntities()
		{
			const idsAndNames = {};
			SUPPORTED_ENTITIES.forEach((entityId) => {
				idsAndNames[entityId] = Type.resolveNameById(entityId);
			});

			return idsAndNames;
		}
	}

	module.exports = { Type, TypeId, TypeName };
});
