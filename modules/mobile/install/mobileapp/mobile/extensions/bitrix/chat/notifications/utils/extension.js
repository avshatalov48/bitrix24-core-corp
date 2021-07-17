(() => {

	this.Utils =
	{
		decodeBbCode(params = {})
		{
			let { text } = params;

			text = text.replace(/\[url=([^\]]+)\](.*?)\[\/url\]/ig, function(whole, link, text)
			{
				link = Utils.htmlspecialcharsback(link);
				text = Utils.htmlspecialcharsback(text);
				let allowList = [
					"http:",
					"https:",
					"ftp:",
					"file:",
					"tel:",
					"callto:",
					"mailto:",
					"skype:",
					"viber:",
				];

				let allowed = false;
				allowList.forEach((protocol) => {
					if (link.startsWith(protocol))
					{
						allowed = true;
					}
				})

				return allowed ? `<a href="${link}">${text}</a>` : whole;
			});

			text = text.replace(/\[LIKE\]/ig, BX.message('MOBILE_EXT_NOTIFICATION_BB_CODE_LIKE'));
			text = text.replace(/\[DISLIKE\]/ig, BX.message('MOBILE_EXT_NOTIFICATION_BB_CODE_DISLIKE'));

			text = text.replace(/\[BR\]/ig, '<br/>');
			text = text.replace(/\[([buis])\](.*?)\[(\/[buis])\]/ig, (whole, open, inner, close) => {
				return '<' + open + '>' + inner + '<' + close + '>';
			});

			text = text.replace(/\[CHAT=(imol\|)?([0-9]{1,})\](.*?)\[\/CHAT\]/ig, (whole, openlines, chatId, inner) => inner);
			text = text.replace(/\[USER=([0-9]{1,})\](.*?)\[\/USER\]/ig, (whole, userId, text) => text);
			text = text.replace(/\[PCH=([0-9]{1,})\](.*?)\[\/PCH\]/ig, (whole, historyId, text) => text);

			return text;
		},

		showError(title, message, color)
		{
			include("InAppNotifier");

			InAppNotifier.showNotification({
				title: title,
				message: message,
				backgroundColor: color,
			});
		},

		// from \CMobileHelper::createLink
		openLinkFromTag(tag)
		{
			const siteDir = env.siteDir ? env.siteDir : '/';
			const link = siteDir + 'mobile/log/?ACTION=CONVERT';
			let result = false;
			const params = tag.split("|");

			if (
				tag.startsWith('BLOG|POST|') ||
				tag.startsWith('BLOG|POST_MENTION|') ||
				tag.startsWith('BLOG|SHARE|') ||
				tag.startsWith('BLOG|SHARE2USERS|') ||
				tag.startsWith('RATING_MENTION|BLOG_POST|')
			)
			{
				result = `${link}&ENTITY_TYPE_ID=BLOG_POST&ENTITY_ID=${params[2]}`;
			}
			else if (tag.startsWith('BLOG|COMMENT|') || tag.startsWith('BLOG|COMMENT_MENTION|'))
			{
				if (params[3] !== undefined)
				{
					result = `${link}&ENTITY_TYPE_ID=BLOG_COMMENT&ENTITY_ID=${params[3]}#com${params[3]}`;
				}
				else
				{
					result = `${link}&ENTITY_TYPE_ID=BLOG_POST&ENTITY_ID=${params[2]}`;
				}
			}
			else if (tag.startsWith('RATING_MENTION|BLOG_COMMENT|'))
			{
				result = `${link}&ENTITY_TYPE_ID=BLOG_COMMENT&ENTITY_ID=${params[2]}`;
			}
			else if (tag.startsWith('RATING|IM|'))
			{
				const dialogId = (params[2] === 'P' ? params[3] : 'chat' + params[3]);

				BX.postComponentEvent("onOpenDialog", [{dialogId: dialogId}, true], 'im.recent');
			}
			else if (tag.startsWith('IM|MENTION|'))
			{
				const dialogId = 'chat' + params[2];

				BX.postComponentEvent("onOpenDialog", [{dialogId: dialogId}, true], 'im.recent');

			}
			else if (tag.startsWith('RATING|DL|'))
			{
				result = `${link}&ENTITY_TYPE_ID=${params[2]}&ENTITY_ID=${params[3]}`;
			}
			else if (
				tag.startsWith('FORUM|COMMENT') ||
				tag.startsWith('RATING_MENTION|FORUM_POST|') ||
				tag.startsWith('RATING|FORUM_POST|')
			)
			{
				BX.ajax({
					url: `/bitrix/services/main/ajax.php?action=socialnetwork.api.livefeed.mobileCreateNotificationLink`,
					data: { tag: tag },
					method: 'POST',
					dataType: 'json',
					timeout: 10,
					async: true,
					processData: true,
					start: true,
					onsuccess: function(result) {
						if (result.data.startsWith(link))
						{
							PageManager.openPage({'url': result.data});
						}
						else
						{
							try
							{
								const params = JSON.parse(result.data);
								BX.postComponentEvent('taskbackground::task::action', params);
							}
							catch (e)
							{
								console.error(e);
							}
						}
					},
					onfailure: function(result) {
						console.error(result);
					}
				});
			}
			else if (tag.startsWith('RATING|'))
			{
				if (params[1] === 'TASK')
				{
					if (params[2] !== undefined)
					{
						const taskId = params[2];

						BX.rest.callMethod('mobile.task.link.params.get', { taskId })
							.then(res => {
								const params = res.data();
								BX.postComponentEvent('taskbackground::task::action', JSON.parse(params));
							})
							.catch(error => console.log(error));
					}
				}
				else if (params[1] === 'BLOG_COMMENT')
				{
					result = `${link}&ENTITY_TYPE_ID=${params[1]}&ENTITY_ID=${params[2]}#com${params[2]}`;
				}
				else
				{
					result = `${link}&ENTITY_TYPE_ID=${params[1]}&ENTITY_ID=${params[2]}`;
				}
			}
			else if (
				tag.startsWith('CALENDAR|INVITE') ||
				tag.startsWith('CALENDAR|COMMENT') ||
				tag.startsWith('CALENDAR|STATUS')
			)
			{
				if (params.length >= 5 && params[4] === 'cancel')
				{
					result = false;
				}
				else
				{
					result = `${siteDir}mobile/calendar/view_event.php?event_id=${params[2]}`;
				}
			}
			else if (tag.startsWith('FORUM|COMMENT_MENTION'))
			{
				result = `${link}&ENTITY_TYPE_ID=LOG_COMMENT&ENTITY_ID=${params[2]}`;
			}
			else if (tag.startsWith('VOTING|'))
			{
				result = `${link}&ENTITY_TYPE_ID=VOTING&ENTITY_ID=${params[1]}`;
			}
			else if (tag.startsWith('PHOTO|COMMENT') || tag.startsWith('WIKI|COMMENT'))
			{
				result = `${link}&ENTITY_TYPE_ID=IBLOCK_ELEMENT&ENTITY_ID=${params[2]}`;
			}
			else if (
				tag.startsWith('INTRANET_NEW_USER|COMMENT_MENTION|') ||
				tag.startsWith('LISTS|COMMENT_MENTION|') ||
				tag.startsWith('RATING_MENTION|LOG_COMMENT|')
			)
			{
				result = `${link}&ENTITY_TYPE_ID=LOG_COMMENT&ENTITY_ID=${params[2]}`;
			}
			else if (tag.startsWith('SONET|EVENT|'))
			{
				result = `${link}&ENTITY_TYPE_ID=LOG_ENTRY&ENTITY_ID=${params[2]}`;
			}
			else if (tag.startsWith('TASKS|TASK|') || tag.startsWith('TASKS|COMMENT|'))
			{
				// the format is:
				// for task modifications:
				// TASKS|TASK|%task_id%|%user_id%
				// for task comments:
				// TASKS|TASK_COMMENT|%task_id%|%user_id%|%comment_id%

				if (params[2] !== undefined)
				{
					const taskId = params[2];
					//rest tasks
					BX.rest.callMethod('mobile.task.link.params.get', { taskId })
						.then(res => {
							const params = res.data();
							console.log(JSON.parse(params));
							BX.postComponentEvent('taskbackground::task::action', JSON.parse(params));
						})
						.catch(error => console.log(error));
				}

				// after task detail page supports reloading only by TASK_ID, use the following:
				//$result = SITE_DIR.'mobile/tasks/snmrouter/?routePage=__ROUTE_PAGE__&USER_ID='.intval($GLOBALS['USER']->GetId());
				//$uniqueParams = "{task_id:".intval($params[2]).", params_emitter: 'tasks_list'}";
				//$unique = true;
			}
			else if (tag.startsWith('ROBOT|'))
			{
				if (params[1] === 'CRM' && params[3] !== undefined)
				{
					let { entityTypeName, entityId } = params[3].split("_");
					entityTypeName = entityTypeName.toLowerCase();
					entityId = +entityId;

					if (entityTypeName === 'lead' || entityTypeName === 'deal')
					{
						result = `${siteDir}mobile/crm/${entityTypeName}/?page=view&${entityTypeName}_id=${entityId}`;
					}
				}
			}
			else if (tag.startsWith('BIZPROC|TASK|'))
			{
				if (params[2] !== undefined)
				{
					result = `${siteDir}mobile/bp/detail.php?task_id=${params[2]}`;
				}
			}

			if (result)
			{
				PageManager.openPage({'url': result});
			}
		},

		htmlspecialcharsback(text)
		{
			if (typeof text !== 'string')
			{
				return text;
			}

			return text.replace(/\&quot;/g, '"')
				.replace(/&#39;/g, "'")
				.replace(/\&lt;/g, '<')
				.replace(/\&gt;/g, '>')
				.replace(/\&amp;/g, '&')
				.replace(/\&nbsp;/g, ' ');
		},
	}

})();
