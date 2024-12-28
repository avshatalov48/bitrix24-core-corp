import { Api } from 'humanresources.hcmlink.api';
import { HcmlinkCompanyConfig } from 'humanresources.hcmlink.company-config';
import { Type } from 'main.core';
import { Guide } from 'ui.tour';

export class MappedPerson
{
	static async deleteLinkMappedPerson()
	{
		const grid = BX.Main.gridManager.getInstanceById('hcmlink_mapped_users');
		if (!grid)
		{
			return;
		}

		const api = new Api();
		const mappingIds = grid.getRows().getSelectedIds();
		await api.removeLinkMapped({ mappingIds });
		top.BX.SidePanel.Instance.getSliderByWindow(window)?.reload();
	}

	static showGuide(config)
	{
		const guide = new Guide({
			id: 'hr-guide-hcmlink-mapped-person',
			steps: [{
				target: config.selector,
				title: config.title,
				text: config.text,
				article: '23264608',
				position: 'bottom',
			}],
			autoSave: true,
			onEvents: true,
		});

		if (Type.isNull(config.lastShowGuideDate))
		{
			guide.start();
		}
	}

	static openCompanyConfigSlider(options)
	{
		HcmlinkCompanyConfig.openSlider(options);
	}
}
