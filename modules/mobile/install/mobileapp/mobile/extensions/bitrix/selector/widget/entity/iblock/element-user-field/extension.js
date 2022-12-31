(() => {
	/**
	 * @class IblockElementUserFieldSelector
	 */
	class IblockElementUserFieldSelector extends BaseSelectorEntity
	{
		static getEntityId()
		{
			return 'iblock-element-user-field';
		}

		static getContext()
		{
			return 'USER_FIELD';
		}

		static getStartTypingText()
		{
			return BX.message('SELECTOR_COMPONENT_START_TYPING_TO_SEARCH_IBLOCK_ELEMENT');
		}

		static getTitle()
		{
			return BX.message('SELECTOR_COMPONENT_PICK_IBLOCK_ELEMENT');
		}

		static isCreationEnabled()
		{
			return false;
		}
	}

	this.IblockElementUserFieldSelector = IblockElementUserFieldSelector;
})();
