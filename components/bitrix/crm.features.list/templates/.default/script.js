BX.ready(() => {
	const switcherNodes = document.querySelectorAll('.crm-features-list [data-role="feature-switcher"]');
	[...switcherNodes].forEach((node) => {
		node.innerHTML = '';
		const switcher = new BX.UI.Switcher({
			id: `crm-feature-list-item-${node.dataset.id}`,
			checked: (node.dataset.checked === 'Y'),
			handlers: {
				toggled: () => {
					switcher.setLoading(true);
					BX.ajax.runComponentAction(
						'bitrix:crm.features.list',
						switcher.isChecked() ? 'enableFeature' : 'disableFeature',
						{
							mode: 'class',
							data: {
								featureId: node.dataset.id,
							},
						},
					).then((response) => {
						switcher.setLoading(false);
						if (response.errors && response.errors.length > 0)
						{
							BX.UI.Notification.Center.notify({
								content: response.errors[0]?.message,
							});
							console.error(response);
						}
					}, (response) => {
						switcher.setLoading(false);
						if (response.errors && response.errors.length > 0)
						{
							BX.UI.Notification.Center.notify({
								content: response.errors[0]?.message,
							});
							console.error(response);
						}
					});
				},
			},
		});
		switcher.renderTo(node);
	});

	const copyNodes = document.querySelectorAll('.crm-features-list-item-copy[data-url]');
	[...copyNodes].forEach((node) => {
		BX.Event.bind(node, 'click', (e) => {
			const url = node.dataset.url;
			BX.clipboard.copy(url);
			BX.UI.Notification.Center.notify({
				content: BX.message('crmFeatureListCopiedToClipboard'),
			});
		});
	});
});
