BX.ready(function() {
	const emptyStateContainer = document.querySelector('[data-role="tasks-interface__emptystate"]');
	const targetArea = document.getElementById('workarea-content');

	if (emptyStateContainer && targetArea)
	{
		const rect = targetArea.getBoundingClientRect();

		if (targetArea.classList.contains('ui-side-panel-wrap-workarea'))
		{
			emptyStateContainer.style.minHeight = 'calc(100vh - ' + (rect.top + rect.x + 70) + 'px)';
		}
		else
		{
			emptyStateContainer.style.minHeight = rect.height + 'px';
		}
	}
});