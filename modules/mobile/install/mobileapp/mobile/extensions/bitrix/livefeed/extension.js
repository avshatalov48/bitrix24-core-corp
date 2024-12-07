(() => {
	class Livefeed
	{
		/**
		 * @param params
		 */
		constructor(params = {})
		{
			this.init();
		}

		init()
		{
			BX.addCustomEvent('Livefeed::getWorkgroupData', this.getWorkgroupData.bind(this));
			BX.addCustomEvent('Livefeed::getPostFullData', this.getPostFullData.bind(this));
		}

		getWorkgroupData(params)
		{
			const groupId = (params.groupId ? parseInt(params.groupId, 10) : 0);
			const returnEventName = (params.returnEventName ? params.returnEventName : '');

			if (
				returnEventName
				&& groupId
			)
			{
				BX.ajax.runAction('socialnetwork.api.workgroup.get', {
					data: {
						params: {
							groupId,
						},
					},
				}).then((response) => {
					const errors = response.errors;
					if (errors && errors.length > 0)
					{
						BX.postWebEvent(returnEventName, {
							success: false,
						});
					}
					else
					{
						BX.postWebEvent(returnEventName, {
							success: true,
							groupData: response.data
						});
					}
				}).catch((response) => {
					BX.postWebEvent(returnEventName, {
						success: false,
					});
				});
			}
			else
			{
				BX.postWebEvent(returnEventName, {
					success: false,
				});
			}
		}

		getPostFullData(params)
		{
			const postId = (params.postId ? parseInt(params.postId, 10) : 0);
			const returnEventName = (params.returnEventName ? params.returnEventName : '');

			if (
				returnEventName
				&& postId
			)
			{
				BX.ajax.runAction('socialnetwork.api.livefeed.blogpost.getBlogPostMobileFullData', {
					data: {
						params: {
							postId,
							showLogin: 'Y',
							htmlEncode: 'N',
							previewImageSize: 250,
							getAdditionalData: 'Y',
						},
					},
				}).then((response) => {
					const errors = response.errors;
					if (errors && errors.length > 0)
					{
						BX.postWebEvent(returnEventName, {
							success: false,
						});
					}
					else
					{
						BX.postWebEvent(returnEventName, {
							success: true,
							postData: response.data
						});
					}
				}).catch((response) => {
					BX.postWebEvent(returnEventName, {
						success: false,
					});
				});
			}
			else
			{
				BX.postWebEvent(returnEventName, {
					success: false,
				});
			}
		}
	}

	this.Livefeed = new Livefeed();
})();