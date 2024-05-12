import { Dom, Extension, Loc } from 'main.core';
export class GoogleDrivePicker
{
	scopes = Extension.getSettings('disk.google-drive-picker').get('scopes');
	provider = 'gdrive';

	constructor(clientId, appId, apiKey, oauthToken, saveCallback)
	{
		this.clientId = clientId;
		this.appId = appId;
		this.apiKey = apiKey;
		this.saveCallback = saveCallback;
		this.oauthToken = oauthToken;
	}

	async loadAndOpenPicker()
	{
		if (!this.oauthToken)
		{
			return;
		}

		try
		{
			await this.loadGis();
			await this.verifyGoogleOAuthToken(this.oauthToken);
			this.createPicker();
		}
		catch (error)
		{
			// debug
			throw new Error(`Error: ${error}`);
		}
	}

	async loadGis() {
		const scripts = [
			'https://apis.google.com/js/api.js',
			'https://accounts.google.com/gsi/client',
		];

		await Promise.all(scripts.map((script) => this.loadScript(script)));
	}

	loadScript(url) {
		return new Promise((resolve, reject) => {
			const script = document.createElement('script');
			script.src = url;
			script.onload = () => resolve(url);
			script.onerror = () => reject(new Error(`Failed to load script ${url}`));
			Dom.append(script, document.head);
		});
	}

	createPicker()
	{
		window.gapi.load('client:auth2:picker', this.showPicker);
	}

	showPicker = () => {
		if (this.oauthToken)
		{
			const googleViewId = window.google.picker.ViewId.DOCS;
			const docsView = new window.google.picker.DocsView(googleViewId)
				.setParent('root')
				.setMode(window.google.picker.DocsViewMode.LIST)
				.setIncludeFolders(true);

			const picker = new window.google.picker.PickerBuilder()
				.enableFeature(window.google.picker.Feature.NAV_HIDDEN)
				.setLocale(Loc.getMessage('LANGUAGE_ID'))
				.addView(docsView)
				.setOAuthToken(this.oauthToken)
				.setDeveloperKey(this.apiKey)
				.setAppId(this.appId)
				.setCallback((data) => this.pickerCallback(data))
				.enableFeature(window.google.picker.Feature.MULTISELECT_ENABLED)
				.build();

			picker.setVisible(true);
		}
	};

	pickerCallback(data)
	{
		if (data.action === window.google.picker.Action.PICKED)
		{
			const documents = data.docs.map((doc) => this.convertItem(doc));
			this.saveCallback.saveButton(this.provider, '/', documents);
		}
	}

	convertItem(document)
	{
		const modifyDate = new Date(document.lastEditedUtc);

		return {
			id: document.id,
			name: document.name,
			type: 'file',
			size: document.sizeBytes,
			sizeInt: document.sizeBytes,
			modifyBy: '',
			modifyDate: this.formatDate(modifyDate),
			modifyDateInt: modifyDate.getTime(),
			provider: this.provider,
		};
	}

	formatDate(date)
	{
		return `${date.getDay()}.${date.getMonth()}.${date.getFullYear()}`;
	}

	async verifyGoogleOAuthToken(token)
	{
		try
		{
			const url = `https://oauth2.googleapis.com/tokeninfo?access_token=${token}`;
			const response = await fetch(url);

			if (!response.ok)
			{
				throw new Error(`Error verifying token: ${response.statusText}`);
			}

			return await response.json();
		}
		catch (error)
		{
			throw new Error(`Error verifying token: ${error.message}`);
		}
	}
}
