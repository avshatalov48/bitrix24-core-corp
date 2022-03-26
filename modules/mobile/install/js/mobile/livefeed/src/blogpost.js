import {Loc, Type, Uri, Text} from 'main.core';
import {Ajax} from 'mobile.ajax';

import {Instance, PostFormManagerInstance, PostFormOldManagerInstance} from './feed';

class BlogPost
{
	static delete(params)
	{
		const context = (Type.isStringFilled(params.context) ? params.context : 'list');
		const postId = (!Type.isUndefined(params.postId) ? parseInt(params.postId) : 0);
		if (postId <= 0)
		{
			return false;
		}

		app.confirm({
			title: Loc.getMessage('MOBILE_EXT_LIVEFEED_DELETE_CONFIRM_TITLE'),
			text : Loc.getMessage('MOBILE_EXT_LIVEFEED_DELETE_CONFIRM_DESCRIPTION'),
			buttons : [
				Loc.getMessage('MOBILE_EXT_LIVEFEED_DELETE_CONFIRM_BUTTON_OK'),
				Loc.getMessage('MOBILE_EXT_LIVEFEED_DELETE_CONFIRM_BUTTON_CANCEL'),
			],
			callback : (btnNum) => {
				if (parseInt(btnNum) !== 1)
				{
					return false;
				}
				app.showPopupLoader({
					text: '',
				});

				const actionUrl = Uri.addParam(`${Loc.getMessage('MOBILE_EXT_LIVEFEED_SITE_DIR')}mobile/ajax.php`, {
					b24statAction: 'deleteBlogPost',
					b24statContext: 'mobile',
				});

				Ajax.wrap({
					type: 'json',
					method: 'POST',
					url: actionUrl,
					data: {
						action: 'delete_post',
						mobile_action: 'delete_post',
						sessid: Loc.getMessage('bitrix_sessid'),
						site: Loc.getMessage('SITE_ID'),
						lang: Loc.getMessage('LANGUAGE_ID'),
						post_id: postId,
					},
					processData: true,
					callback: (response) => {
						app.hidePopupLoader();

						if (
							!Type.isStringFilled(response.SUCCESS)
							|| response.SUCCESS !== 'Y'
						)
						{
							return;
						}

						BXMobileApp.onCustomEvent('onBlogPostDelete', {}, true, true);
						if (context === 'detail')
						{
							app.closeController({
								drop: true,
							});
						}
					},
					callback_failure: () => {
						app.hidePopupLoader();
					}
				});

				return false;
			}
		});
	}

	static edit(params)
	{
		const postId = (!Type.isUndefined(params.postId) ? parseInt(params.postId) : 0);
		if (postId <= 0)
		{
			return;
		}

		const pinnedContext = (!Type.isUndefined(params.pinnedContext) ? !!params.pinnedContext : false);

		if (Application.getApiVersion() >= Instance.getApiVersion('layoutPostForm'))
		{
			PostFormManagerInstance.show({
				pageId: Instance.getPageId(),
				postId: postId,
			});
		}
		else
		{
			this.getData({
				postId: postId,
				callback: (postData) => {
					PostFormOldManagerInstance.formParams = {};

					if (
						!Type.isUndefined(postData.PostPerm)
						&& postData.PostPerm >= 'W'
					)
					{
						const selectedDestinations = {
							a_users: [],
							b_groups: [],
						};

						PostFormOldManagerInstance.setExtraDataArray({
							postId: postId,
							postAuthorId: postData.post_user_id,
							logId: postData.log_id,
							pinnedContext: pinnedContext,
						});

						if (!Type.isUndefined(postData.PostDetailText))
						{
							PostFormOldManagerInstance.setParams({
								messageText: postData.PostDetailText,
							});
						}

						if (Type.isPlainObject(postData.PostDestination))
						{
							for (const [key, value] of Object.entries(postData.PostDestination))
							{
								if (
									Type.isStringFilled(postData.PostDestination[key].STYLE)
									&& postData.PostDestination[key].STYLE === 'all-users'
								)
								{
									PostFormOldManagerInstance.addDestination(
										selectedDestinations,
										{
											type: 'UA'
										}
									);
								}
								else if (
									Type.isStringFilled(postData.PostDestination[key].TYPE)
									&& [ 'U', 'SG' ].includes(postData.PostDestination[key].TYPE)
								)
								{
									PostFormOldManagerInstance.addDestination(
										selectedDestinations,
										{
											type: postData.PostDestination[key].TYPE,
											id: postData.PostDestination[key].ID,
											name: Text.decode(postData.PostDestination[key].TITLE)
										}
									);
								}
							}
						}

						if (!Type.isUndefined(postData.PostDestinationHidden))
						{
							PostFormOldManagerInstance.setExtraData({
								hiddenRecipients: postData.PostDestinationHidden,
							});
						}

						PostFormOldManagerInstance.setParams({
							selectedRecipients: selectedDestinations,
						});

						if (!Type.isUndefined(postData.PostFiles))
						{
							PostFormOldManagerInstance.setParams({
								messageFiles: postData.PostFiles,
							});
						}

						if (!Type.isUndefined(postData.PostUFCode))
						{
							PostFormOldManagerInstance.setExtraData({
								messageUFCode: postData.PostUFCode,
							});
						}

						app.exec('showPostForm', PostFormOldManagerInstance.show());
					}
				}

			});
		}
	}

	static getData(params)
	{
		const postId = (!Type.isUndefined(params.postId) ? parseInt(params.postId) : 0);
		if (postId <= 0)
		{
			return;
		}

		const callbackFunction = (Type.isFunction(params.callback) ? params.callback : null);
		if (Type.isNull(callbackFunction))
		{
			return;
		}

		const result = {};

		if (postId > 0)
		{
			app.showPopupLoader();

			Ajax.wrap({
				type: 'json',
				method: 'POST',
				url: `${Loc.getMessage('MOBILE_EXT_LIVEFEED_SITE_DIR')}mobile/ajax.php`,
				processData: true,
				data: {
					action: 'get_blog_post_data',
					mobile_action: 'get_blog_post_data',
					sessid: Loc.getMessage('bitrix_sessid'),
					site: Loc.getMessage('SITE_ID'),
					lang: Loc.getMessage('LANGUAGE_ID'),
					post_id: postId,
					nt: Loc.getMessage('MSLNameTemplate'),
					sl: Loc.getMessage('MSLShowLogin')
				},
				callback: (data) => {
					app.hidePopupLoader();

					result.id = postId;

					if (
						!Type.isUndefined(data.log_id)
						&& parseInt(data.log_id) > 0
					)
					{
						result.log_id = data.log_id;
					}

					if (
						!Type.isUndefined(data.post_user_id)
						&& parseInt(data.post_user_id) > 0
					)
					{
						result.post_user_id = data.post_user_id;
					}

					if (!Type.isUndefined(data.PostPerm))
					{
						result.PostPerm = data.PostPerm;
					}

					if (!Type.isUndefined(data.PostDestination))
					{
						result.PostDestination = data.PostDestination;
					}

					if (!Type.isUndefined(data.PostDestinationHidden))
					{
						result.PostDestinationHidden = data.PostDestinationHidden;
					}

					if (!Type.isUndefined(data.PostDetailText))
					{
						result.PostDetailText = data.PostDetailText;
					}

					if (Type.isUndefined(data.PostFiles))
					{
						result.PostFiles = data.PostFiles;
					}

					if (!Type.isUndefined(data.PostBackgroundCode))
					{
						result.PostBackgroundCode = data.PostBackgroundCode;
					}

					if (!Type.isUndefined(data.PostUFCode))
					{
						result.PostUFCode = data.PostUFCode;
					}

					callbackFunction(result);
				},
				callback_failure: () => {
					app.hidePopupLoader();
				},
			});
		}
	}
}

export {
	BlogPost
}