(() => {
	/**
	 * @class IblockElementSelector
	 */
	class IblockElementSelector extends BaseSelectorEntity
	{
		static getEntityId()
		{
			return 'iblock-property-element';
		}

		static getContext()
		{
			return 'IBLOCK_ELEMENT';
		}

		static getStartTypingText()
		{
			return BX.message('SELECTOR_COMPONENT_IBLOCK_ELEMENT_START_TYPING_TEXT');
		}

		static getTitle()
		{
			return BX.message('SELECTOR_COMPONENT_IBLOCK_ELEMENT_TITLE');
		}

		static isCreationEnabled()
		{
			return false;
		}
	}

	this.IblockElementSelector = IblockElementSelector;
})();
