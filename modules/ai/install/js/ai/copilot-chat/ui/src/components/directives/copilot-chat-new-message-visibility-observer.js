export const CopilotChatNewMessageVisibilityObserver = {
	mounted(element, binding) {
		const isMessageViewed = binding.value;

		if (isMessageViewed === false)
		{
			binding.instance.observer.observe(element);
		}
	},
	beforeUnmount(element, binding) {
		binding.instance.observer.unobserve(element);
	},
};
