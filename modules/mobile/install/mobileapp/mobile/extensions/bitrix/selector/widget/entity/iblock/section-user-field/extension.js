(() => {
	/**
	 * @class IblockSectionUserFieldSelector
	 */
	class IblockSectionUserFieldSelector extends BaseSelectorEntity
	{
		static getEntityId()
		{
			return 'iblock-section-user-field';
		}

		static getContext()
		{
			return 'USER_FIELD';
		}

		static getStartTypingText()
		{
			return BX.message('SELECTOR_COMPONENT_START_TYPING_TO_SEARCH_IBLOCK_SECTION');
		}

		static getTitle()
		{
			return BX.message('SELECTOR_COMPONENT_PICK_IBLOCK_SECTION');
		}

		static isCreationEnabled()
		{
			return false;
		}
	}

	this.IblockSectionUserFieldSelector = IblockSectionUserFieldSelector;
})();
