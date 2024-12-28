(() => {
	const require = (ext) => jn.require(ext);
	const { SignOpener } = require('sign/opener');
	const { Push } = require('native/app');

	const EVENT_NAME_FOUND_DOCUMENT_FOR_SIGNING = 'SIGN_MOBILE_FOUND_DOCUMENT_FOR_SIGNING';
	const EVENT_NAME_REQUEST_FOR_SIGN_CONFIRMATION = 'SIGN_MOBILE_REQUEST_FOR_SIGN_CONFIRMATION';

	class SignBackground
	{
		constructor()
		{
			SignOpener.clearCacheSomeBannerIsAlreadyOpen();
			this.bindEvents();
		}

		unpackJsonEventParams(params)
		{
			let result = null;

			const jsonParams = JSON.parse(params);

			if (jsonParams.message)
			{
				const message = JSON.parse(jsonParams.message);
				result = this.unpackEventParams(message);
			}

			return result;
		}

		unpackEventParams(message)
		{
			let result = null;
			const payload = message.payload;

			if (payload)
			{
				const {
					role,
					memberId = 0,
					forcedBannerOpening = false,
					isGoskey = false,
					isExternal = false,
					initiatedByType,
				} = payload;

				result = {
					type: message.type,
					role,
					memberId: Number(memberId),
					forcedBannerOpening,
					title: '',
					url: '',
					isGoskey,
					isExternal,
					initiatedByType,
				};

				const document = payload.document;

				if (document)
				{
					const {
						title = '',
						url = '',
					} = document;

					result.title = title;
					result.url = url;
				}
			}

			return result;
		}

		processEvent(data)
		{
			if (data !== null)
			{
				const {
					url,
					title,
					memberId,
					role,
					forcedBannerOpening,
					isGoskey,
					isExternal,
					initiatedByType,
				} = data;

				switch (data.type)
				{
					case EVENT_NAME_FOUND_DOCUMENT_FOR_SIGNING:
						SignOpener.openSigning({
							url,
							title,
							memberId,
							role,
							goWithoutConfirmation: false,
							isGoskey,
							isExternal,
							initiatedByType,
						});
						break;
					case EVENT_NAME_REQUEST_FOR_SIGN_CONFIRMATION:
						SignOpener.openConfirmation({
							role,
							memberId,
							title,
							forcedBannerOpening,
							initiatedByType,
						});
						break;
					default:
						break;
				}
			}
		}

		bindEvents()
		{
			Push.on('receivedPush', ({ params }) => {
				this.processEvent(this.unpackJsonEventParams(params));
			});

			PushListener.subscribe(EVENT_NAME_FOUND_DOCUMENT_FOR_SIGNING, (message) => {
				this.processEvent(this.unpackEventParams(message));
			});

			PushListener.subscribe(EVENT_NAME_REQUEST_FOR_SIGN_CONFIRMATION, (message) => {
				this.processEvent(this.unpackEventParams(message));
			});

			BX.addCustomEvent('signbackground::router', (memberId) => {
				SignOpener.openSigning({
					memberId,
				});
			});
		}
	}

	this.SignBackground = new SignBackground();
})();
