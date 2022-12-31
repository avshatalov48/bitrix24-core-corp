/**
 * @module selector/widget/entity/socialnetwork/project
 */
jn.define('selector/widget/entity/socialnetwork/project', (require, exports, module) => {
	class ProjectSelector extends BaseSelectorEntity
	{
		static getEntityId()
		{
			return 'project';
		}

		static getContext()
		{
			return 'mobile-project';
		}

		static getStartTypingText()
		{
			return BX.message('SELECTOR_COMPONENT_START_TYPING_TO_SEARCH_PROJECT');
		}

		static getTitle()
		{
			return BX.message('SELECTOR_COMPONENT_PICK_PROJECT');
		}
	}

	module.exports = {ProjectSelector};
});