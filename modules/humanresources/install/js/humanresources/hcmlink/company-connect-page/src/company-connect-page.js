import { Tag, Loc } from 'main.core';

import './style.css';

const appUrl = '/marketplace/detail/bitrix.1chrm/?ver=1&install=Y&hash=c2dddb1ce87267585b1dcfde4893cc77&check_hash=61c01468e268f41c6d1aba8a13b34e84&install_hash=445915b49fdf3c6b801bbb6c6b70e848';

export class CompanyConnectPage
{
	static openSlider()
	{
		BX.SidePanel.Instance.open('humanresources:hcmlink1c-slider', {
			contentCallback: () => {
				return BX.UI.SidePanel.Layout.createContent({
					extensions: ['humanresources.hcmlink.company-connect-page'],
					design: {
						section: false,
						margin: true,
					},
					content: () => {
						return (new CompanyConnectPage()).getLayout();
					},
					buttons(): Array<Button> {
						return [];
					},
				});
			},
			animationDuration: 200,
			width: 920,
		});
	}

	getLayout()
	{
		return Tag.render`
			<div class="integration-slider-content">
				<div class="integration-slider-text">
				    <h2>${Loc.getMessage('HUMANRESOURCES_HCMLINK_CONNECTOR_PAGE_TITLE')}</h2>
				    <p>${Loc.getMessage('HUMANRESOURCES_HCMLINK_CONNECTOR_PAGE_HEAD_DESCRIPTION')}</p>
				    <ul style="padding-bottom: 20px">
				        <li>${Loc.getMessage('HUMANRESOURCES_HCMLINK_CONNECTOR_PAGE_PARAGRAPH_1')}</li>
				        <li>${Loc.getMessage('HUMANRESOURCES_HCMLINK_CONNECTOR_PAGE_PARAGRAPH_2')}</li>
				        <li>${Loc.getMessage('HUMANRESOURCES_HCMLINK_CONNECTOR_PAGE_PARAGRAPH_3')}</li>
				        <li>${Loc.getMessage('HUMANRESOURCES_HCMLINK_CONNECTOR_PAGE_PARAGRAPH_4')}</li>
					</ul>
					<div>
						<button class="ui-btn ui-btn-success ui-btn-md ui-btn-round" 
								onclick="${this.#openAppPage}">
							${Loc.getMessage('HUMANRESOURCES_HCMLINK_CONNECTOR_PAGE_ACTION_INSTALL')}	
					   </button>
		           </div>
				</div>
			</div>
		`;
	}

	#openAppPage(): void
	{
		BX.SidePanel.Instance.open(appUrl);
	}
}
