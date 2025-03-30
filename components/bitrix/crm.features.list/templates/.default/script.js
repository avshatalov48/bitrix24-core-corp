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

	const enableToursNode = document.querySelector('.crm-features-list-item [data-role="tour-switcher"]');
	if (enableToursNode)
	{
		const toursSwitcher = new BX.UI.Switcher({
			id: 'crm-feature-tours-switcher',
			checked: (enableToursNode.dataset.checked === 'Y'),
			handlers: {
				toggled: () => {
					toursSwitcher.setLoading(true);
					BX.ajax.runComponentAction(
						'bitrix:crm.features.list',
						toursSwitcher.isChecked() ? 'enableTours' : 'disableTours',
						{
							mode: 'class',
						},
					).then((response) => {
						toursSwitcher.setLoading(false);
						if (response.errors && response.errors.length > 0)
						{
							BX.UI.Notification.Center.notify({
								content: response.errors[0]?.message,
							});
							console.error(response);
						}
					}, (response) => {
						toursSwitcher.setLoading(false);
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
		toursSwitcher.renderTo(enableToursNode);
	}

	const tourBtnNodes = document.querySelectorAll('.crm-features-list [data-role="tour-reset"]');
	[...tourBtnNodes].forEach((node) => {
		BX.bind(node, 'click', (event) => {
			const tourId = node.dataset.tourId;
			if (BX.Dom.hasClass(node, 'ui-btn-clock'))
			{
				return;
			}
			BX.Dom.addClass(node, 'ui-btn-clock');

			BX.ajax.runComponentAction(
				'bitrix:crm.features.list',
				'resetTour',
				{
					mode: 'class',
					data: {
						tourId,
					},
				},
			).then((response) => {
				BX.Dom.removeClass(node, 'ui-btn-clock');
				if (response.errors && response.errors.length > 0)
				{
					BX.UI.Notification.Center.notify({
						content: response.errors[0]?.message,
					});
					console.error(response);
				}
				else
				{
					BX.Dom.addClass(node, 'ui-btn-icon-success');
					setTimeout(() => {
						BX.Dom.removeClass(node, 'ui-btn-icon-success');
					}, 3000);
				}
			}, (response) => {
				BX.Dom.removeClass(node, 'ui-btn-clock');
				if (response.errors && response.errors.length > 0)
				{
					BX.UI.Notification.Center.notify({
						content: response.errors[0]?.message,
					});
					console.error(response);
				}
			});
		})
	});

	const copyNodes = document.querySelectorAll('.crm-features-list-item-copy[data-url]');
	[...copyNodes].forEach((node) => {
		BX.Event.bind(node, 'click', (e) => {
			const url = node.dataset.url;
			BX.clipboard.copy(url);
			BX.UI.Notification.Center.notify({
				content: BX.message('crmLinkCopiedToClipboard'),
			});
		});
	});
});
