/**
 * @module crm/duplicates
 */
jn.define('crm/duplicates', (require, exports, module) => {
	const { findDuplicates } = require('crm/duplicates/finder');
	const { DuplicatesContent } = require('crm/duplicates/content');
	const { get } = require('utils/object');

	/**
	 * @class Duplicates
	 */
	class Duplicates
	{
		/**
		 * @param {String} entityTypeName
		 * @return {Boolean}
		 */
		static checkEnableForEntityType(entityTypeName)
		{
			const duplicateControlEnableFor = get(
				jnExtensionData.get('crm:duplicates'),
				['duplicateControlEnableFor', 'enableFor'],
				{},
			);
			const duplicateControlEnableForEntityType = duplicateControlEnableFor[entityTypeName.toUpperCase()];

			return !duplicateControlEnableForEntityType || duplicateControlEnableForEntityType === 'Y';
		}

		/**
		 * @param {Number} props.entityId
		 * @param {String} props.entityTypeName
		 * @param {Object[]} props.values
		 * @param {Object} props.duplicateControl
		 * @returns {Promise}
		 */
		static find(props)
		{
			return findDuplicates(props);
		}

		/**
		 * @param {Object} props
		 * @param {Object[]} props.duplicates
		 * @param {String} props.fieldType
		 * @param {String} props.entityTypeName
		 * @param {String} props.color
		 * @param {String} props.parentUid
		 * @param {Object} props.style
		 * @returns {DuplicatesContent}
		 */
		static getTooltipContent(props)
		{
			return new DuplicatesContent(props);
		}
	}

	module.exports = { Duplicates };
});
