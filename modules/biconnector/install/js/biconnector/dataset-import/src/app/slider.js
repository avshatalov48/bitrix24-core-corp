import { Uri } from 'main.core';
import { SidePanel } from 'ui.sidepanel';

export class Slider
{
	static open(sourceId: string, datasetId: ?number = 0, connection: ?Object = {}): void
	{
		const componentLink = '/bitrix/components/bitrix/biconnector.dataset.import/slider.php';

		const sliderLink = new Uri(componentLink);
		sliderLink.setQueryParam('sourceId', sourceId);
		if (datasetId)
		{
			sliderLink.setQueryParam('datasetId', datasetId);
		}

		if (Object.keys(connection).length > 0)
		{
			sliderLink.setQueryParam('connection', connection);
		}

		const options = {
			allowChangeHistory: false,
			cacheable: false,
			customLeftBoundary: 0,
		};

		SidePanel.Instance.open(
			sliderLink.toString(),
			options,
		);
	}
}
