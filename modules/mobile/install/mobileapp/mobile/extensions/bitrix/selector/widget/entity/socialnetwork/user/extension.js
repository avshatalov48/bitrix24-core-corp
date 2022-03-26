(() => {
	/**
	 * @class SocialNetworkUserSelector
	 */
	class SocialNetworkUserSelector extends BaseSelectorEntity
	{
		static getEntityId()
		{
			return 'user';
		}

		static getContext()
		{
			return 'mobile-user';
		}

		static getStartTypingText()
		{
			return BX.message('SELECTOR_COMPONENT_START_TYPING_TO_SEARCH_USER');
		}

		static getTitle()
		{
			return BX.message('SELECTOR_COMPONENT_PICK_USER_2');
		}
	}

	this.SocialNetworkUserSelector = SocialNetworkUserSelector;
})();
