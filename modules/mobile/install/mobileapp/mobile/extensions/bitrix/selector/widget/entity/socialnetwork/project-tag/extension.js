(() => {
	/**
	 * @class ProjectTagSelector
	 */
	class ProjectTagSelector extends BaseSelectorEntity
	{
		static getEntityId()
		{
			return 'project-tag';
		}

		static getContext()
		{
			return 'PROJECT_TAG';
		}

		static getStartTypingText()
		{
			return BX.message('SELECTOR_COMPONENT_START_TYPING_TO_SEARCH_PROJECT_TAG');
		}

		static isCreationEnabled()
		{
			return true;
		}

		static getCreateText()
		{
			return BX.message('SELECTOR_COMPONENT_CREATE_PROJECT_TAG');
		}

		static getCreatingText()
		{
			return BX.message('SELECTOR_COMPONENT_CREATING_PROJECT_TAG');
		}

		static getCreateEntityHandler(providerOptions)
		{
			return (text) => {
				return new Promise((resolve) => {
					resolve({
						id: text.toLowerCase(),
						entityId: this.getEntityId(),
						title: text.toLowerCase()
					});
				});
			};
		}

		static getTitle()
		{
			return BX.message('SELECTOR_COMPONENT_PICK_PROJECT_TAG_2');
		}
	}
	
	this.ProjectTagSelector = ProjectTagSelector;
})();
