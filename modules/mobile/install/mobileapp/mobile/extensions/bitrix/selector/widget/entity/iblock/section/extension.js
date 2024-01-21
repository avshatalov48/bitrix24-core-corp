(() => {
	/**
	 * @class IblockSectionSelector
	 */

	class IblockSectionSelector extends BaseSelectorEntity
	{
		static getEntityId()
		{
			return 'iblock-property-section';
		}

		static getContext()
		{
			return 'IBLOCK_SECTION';
		}

		static getStartTypingText()
		{
			return BX.message('SELECTOR_COMPONENT_IBLOCK_SECTION_START_TYPING_TEXT');
		}

		static getTitle()
		{
			return BX.message('SELECTOR_COMPONENT_IBLOCK_SECTION_TITLE');
		}

		static isCreationEnabled()
		{
			return false;
		}
	}

	this.IblockSectionSelector = IblockSectionSelector;
})();
