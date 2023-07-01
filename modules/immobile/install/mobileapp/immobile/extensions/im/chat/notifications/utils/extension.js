(() => {

	this.Utils =
	{
		decodeCustomBbCode(text)
		{
			text = text.replace(/\[LIKE\]/ig, () => {
				return `[IMG width="15" height="15"]${currentDomain}/bitrix/js/im/images/hires/like.png[/IMG]`;
			});
			text = text.replace(/\[DISLIKE\]/ig, () => {
				return `[IMG width="15" height="15"]${currentDomain}/bitrix/js/im/images/hires/dislike.png[/IMG]`;
			});
			text = text.replace(/\[CHAT=(imol\|)?([0-9]{1,})\](.*?)\[\/CHAT\]/ig, (whole, openlines, chatId, inner) => inner);
			text = text.replace(/\[context=(chat\d+|\d+:\d+)\/(\d+)](.*?)\[\/context]/gi, (whole, dialogId, messageId, message) => message);
			text = text.replace(/\[USER=([0-9]{1,})\](.*?)\[\/USER\]/ig, (whole, userId, text) => text);
			text = text.replace(/\[PCH=([0-9]{1,})\](.*?)\[\/PCH\]/ig, (whole, historyId, text) => text);
			text = text.replace(/\[RATING\=([1-5]{1})\]/ig, (whole, rating) => {
				const ratingUrl = `${currentDomain}/bitrix/js/im/images/hires/stars${rating}.png`;

				return `[IMG width="105" height="15"]${ratingUrl}[/IMG]`;
			});

			// links style (bold, fontSize 14, color)
			text = text.replace(/\[url=([^\]]+)](.*?)\[\/url]/ig, (whole, src, text) => {
				return `[B][COLOR=#1d54a2][URL=${src}]${text}[/URL][/COLOR][/B]`;
			});

			// not supported #br# or [br] code.
			text = text.replace(/( *#BR# *)/ig, '\n');
			text = text.replace(/\[BR]/ig, '\n');

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
								BX.postComponentEvent('taskbackground::task::open', params);
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
								BX.postComponentEvent('taskbackground::task::open', JSON.parse(params));
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
							BX.postComponentEvent('taskbackground::task::open', JSON.parse(params));
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
					const entity = params[3].split("_");
					const entityTypeName = entity[0].toLowerCase();
					const entityId = parseInt(entity[1]);

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
				.replace(/&#039;/g, "'")
				.replace(/\&lt;/g, '<')
				.replace(/\&gt;/g, '>')
				.replace(/\&amp;/g, '&')
				.replace(/\&nbsp;/g, ' ');
		},

		getFormattedDateTime(timestamp)
		{
			const nowDate = new Date();
			const nowYear = nowDate.getFullYear();
			const nowMonth = nowDate.getMonth();
			const nowDay = nowDate.getDate();

			const timestampShort = timestamp / 1000;

			const todayStart = new Date(Date.UTC(nowYear, nowMonth, nowDay, 0, 0, 0, 0));
			const todayEnd = new Date(Date.UTC(nowYear, nowMonth, nowDay+1, 0, 0, 0, 0));

			const yesterdayStart = new Date(Date.UTC(nowYear, nowMonth, nowDay-1, 0, 0, 0, 0));
			const yesterdayEnd = new Date(Date.UTC(nowYear, nowMonth, nowDay, 0, 0, 0, 0));

			const targetDate = new Date(timestamp);
			if (targetDate >= todayStart && targetDate < todayEnd)
			{
				return BX.message('MOBILE_EXT_NOTIFICATION_TODAY_DATETIME').replace(
					'#datetime#',
					dateFormatter.get(timestampShort, dateFormatter.formats.shortTime)
				);
			}
			else if (targetDate >= yesterdayStart && targetDate < yesterdayEnd)
			{
				return BX.message('MOBILE_EXT_NOTIFICATION_YESTERDAY_DATETIME').replace(
					'#datetime#',
					dateFormatter.get(timestampShort, dateFormatter.formats.shortTime)
				);
			}
			else if (targetDate.getFullYear() === nowYear)
			{
				return BX.message('MOBILE_EXT_NOTIFICATION_FULL_DATETIME').replace(
					'#time#',
					dateFormatter.get(timestampShort, dateFormatter.formats.shortTime)
				).replace(
					'#date#',
					dateFormatter.get(timestampShort, dateFormatter.formats.dayMonth)
				);
			}

			return BX.message('MOBILE_EXT_NOTIFICATION_FULL_DATETIME').replace(
				'#time#',
				dateFormatter.get(timestampShort, dateFormatter.formats.shortTime)
			).replace(
				'#date#',
				dateFormatter.get(timestampShort, dateFormatter.formats.longDate)
			);
		},

		getListItemTypeSuffix(attach)
		{
			if (attach.USER) return "u"
			if (attach.LINK) return "l"
			if (attach.MESSAGE) return "m"
			if (attach.HTML) return "h"
			if (attach.FILE) return "f"
			if (attach.DELIMITER) return "d"
			if (attach.IMAGE) return "i"
			if (attach.GRID) return "g"
			return ""
		},

		getListItemType(item)
		{
			return !!item.params && !!item.params.ATTACH ? Array.from(item.params.ATTACH).reduce(
				(acc, attachBlock, index, arr) => (
					acc + Array.from(attachBlock.BLOCKS).reduce(
						(acc, attach, index, arr) => (acc + this.getListItemTypeSuffix(attach)),
						""
					)
				),
				"attach"
			) : (item.notify_type === 1 ? 'confirm' : 'notification');
		},

		openUrl(url)
		{
			const rewritedUrl = UrlRewriter.get(url);
			PageManager.openPage({url: rewritedUrl});
		},

		sortByType(a, b)
		{
			if (a.commonType === Const.NotificationTypes.confirm && b.commonType !== Const.NotificationTypes.confirm)
			{
				return -1;
			}
			else if (a.commonType !== Const.NotificationTypes.confirm && b.commonType === Const.NotificationTypes.confirm)
			{
				return 1;
			}
			else
			{
				return b.id - a.id;
			}
		},

		getAvatarUrl(params)
		{
			if (params.userAvatar && params.userAvatar !== '/bitrix/js/im/images/blank.gif')
			{
				return currentDomain + params.userAvatar;
			}

			return '';
		}
	}

})();
