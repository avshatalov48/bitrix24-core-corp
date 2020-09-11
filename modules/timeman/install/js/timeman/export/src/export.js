import {ajax, Type} from 'main.core';
import {ExportState} from './export-state';
import {ExportPopup} from './export-popup';

export class Export
{
	constructor(options)
	{
		options = {...{
			signedParameters: '',
			componentName: '',
			siteId: '',
			stExportId: '',
			managerId: '',
			sToken: '',
		}, ...options};

		this.signedParameters = options.signedParameters;
		this.componentName = options.componentName;
		this.siteId = options.siteId;
		this.stExportId = options.stExportId;
		this.managerId = options.managerId;

		this.sToken = options.sToken;
		this.cToken = 'c';

		this.exportState = new ExportState();
		this.exportPopup = new ExportPopup({
			exportManager: this,
			exportState: this.exportState
		});

		this.availableTypes = ['excel', 'csv'];
	}

	startExport(exportType)
	{
		if (!this.availableTypes.includes(exportType))
		{
			throw 'Export: parameter "exportType" has invalid value';
		}

		this.exportType = exportType;

		this.exportPopup.createPopup();

		this.exportPopup.showPopup();

		this.startRequest();
	}

	getExcelExportType()
	{
		return 'excel';
	}

	getCsvExportType()
	{
		return 'csv';
	}

	startRequest()
	{
		this.cToken += Date.now();
		this.request('timeman.api.export.dispatcher');
	}

	nextRequest()
	{
		this.request('timeman.api.export.dispatcher');
	}

	stopRequest()
	{
		this.request('timeman.api.export.cancel');
	}

	clearRequest()
	{
		this.request('timeman.api.export.clear');
	}

	request(action)
	{
		this.exportState.setRunning();

		ajax.runAction(action, {
			data: {
				'SITE_ID': this.siteId,
				'PROCESS_TOKEN': this.sToken + this.cToken,
				'EXPORT_TYPE': this.exportType,
				'COMPONENT_NAME': this.componentName,
				'signedParameters': this.signedParameters
			}
		}).then((response) => {
			this.handleResponse(response);
		}).catch((response) => {
			this.handleResponse(response);
		});
	}

	handleResponse(response)
	{
		if (response.errors.length)
		{
			this.exportPopup.setPopupContent(response.errors.shift().message);
			this.exportState.setError();
		}
		else if (response.status === 'success')
		{
			const data = response.data;
			switch (data['STATUS'])
			{
				case 'COMPLETED':
				case 'NOT_REQUIRED':
					this.exportState.setCompleted();
					break;
				case 'PROGRESS':
					const processedItems = (Type.isInteger(data['PROCESSED_ITEMS']) ? data['PROCESSED_ITEMS'] : 0);
					const totalItems = (Type.isInteger(data['TOTAL_ITEMS']) ? data['TOTAL_ITEMS'] : 0);
					this.exportPopup.setProgressBar(processedItems, totalItems);
					setTimeout(() => this.nextRequest(), 200);
					break;
			}
			this.exportPopup.setPopupContent(data);
		}
		else
		{
			this.exportState.setError();
		}

		this.exportPopup.adjustPosition();
	}
}