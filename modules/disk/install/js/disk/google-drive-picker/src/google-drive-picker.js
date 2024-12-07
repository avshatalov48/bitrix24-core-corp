import { Dom, Extension, Loc } from 'main.core';

import './style.css';

type DocType = {
	modifyDateInt: number,
	sizeInt: number,
	modifyBy: string,
	size: number,
	modifyDate: string,
	provider: string,
	name: string,
	id: string,
	type: string
}

type PickerData = {
	action: string,
	docs: GoogleDocType[]
}

type GoogleDocType = {
	id: string;
	name: string;
	type: string;
	lastEditedUtc: string;
	sizeBytes: number;
}

export class GoogleDrivePicker
{
	scopes = Extension.getSettings('disk.google-drive-picker').get('scopes');
	provider = 'gdrive';

	constructor(clientId: string, appId: string, apiKey: string, oauthToken: string, saveCallback: string)
	{
		this.clientId = clientId;
		this.appId = appId;
		this.apiKey = apiKey;
		this.saveCallback = saveCallback;
		this.oauthToken = oauthToken;
	}

	async loadAndShowPicker(): Promise<void>
	{
		if (!this.oauthToken)
		{
			return;
		}

		try
		{
			await this.#loadGis();
			await this.#verifyGoogleOAuthToken(this.oauthToken);
			this.#createPicker();
		}
		catch (error)
		{
			// debug
			throw new Error(`Error: ${error}`);
		}
	}

	async #loadGis(): Promise<void>
	{
		const scripts = [
			'https://apis.google.com/js/api.js',
			'https://accounts.google.com/gsi/client',
		];

		await Promise.all(scripts.map((script) => this.#loadScript(script)));
	}

	#loadScript(url: string): Promise<void>
	{
		return new Promise((resolve, reject): void => {
			const script = document.createElement('script');
			script.src = url;
			script.onload = () => resolve(url);
			script.onerror = () => reject(new Error(`Failed to load script ${url}`));
			Dom.append(script, document.head);
		});
	}

	#createPicker(): void
	{
		window.gapi.load('client:auth2:picker', this.#showPicker);
	}

	#showPicker = (): void => {
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
				.setCallback((data) => this.#pickerCallback(data))
				.enableFeature(window.google.picker.Feature.MULTISELECT_ENABLED)
				.build();

			picker.setVisible(true);
		}
	};

	#pickerCallback(data: PickerData): void
	{
		if (data.action === window.google.picker.Action.PICKED)
		{
			const documents = data.docs.map((doc): DocType => this.#convertItem(doc));
			this.saveCallback.saveButton(this.provider, '/', documents);
		}

		switch (data.action)
		{
			case 'loaded':
				document.body.style.setProperty('overflow', 'hidden');
				break;
			case 'cancel':
			case 'picked':
				document.body.style.removeProperty('overflow');
				break;
		}
	}

	#convertItem(document: GoogleDocType): DocType
	{
		const modifyDate = new Date(document.lastEditedUtc);

		return {
			id: document.id,
			name: document.name,
			type: 'file',
			size: document.sizeBytes,
			sizeInt: document.sizeBytes,
			modifyBy: '',
			modifyDate: this.#formatDate(modifyDate),
			modifyDateInt: modifyDate.getTime(),
			provider: this.provider,
		};
	}

	#formatDate(date: Date): string
	{
		return `${date.getDay()}.${date.getMonth()}.${date.getFullYear()}`;
	}

	async #verifyGoogleOAuthToken(token: string): Promise<any>
	{
		try
		{
			const url = `https://oauth2.googleapis.com/tokeninfo?access_token=${token}`;
			const response = await fetch(url);

			if (!response.ok)
			{
				new Error(`Error verifying token: ${response.statusText}`);
			}

			return await response.json();
		}
		catch (error)
		{
			throw new Error(`Error verifying token: ${error.message}`);
		}
	}
}
