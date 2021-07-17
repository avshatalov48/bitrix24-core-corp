import Item from './item';
import {Tag, Text, Loc} from "main.core";
import Utils from './utils';

export default class ItemActivity extends Item
{
	constructor(data) {
		super(data);
		this.id = this.data['ASSOCIATED_ENTITY']['ID'];
		console.log('this.data: ', this.data);

	}
	getOwnerId()
	{
		return [this.data['ASSOCIATED_ENTITY']['TYPE_ID'], this.getId()].join('_');
	}
	getNode()
	{
		return this.cache.remember('mainNode', () => {
			const direction = parseInt(this.data['ASSOCIATED_ENTITY']['DIRECTION']);
			const rawDuration = parseInt(this.data['ASSOCIATED_ENTITY']['SETTINGS']['DURATION']);
			const hasDuration = (rawDuration > 0);
			const duration = Utils.formatInterval(rawDuration);

			const deadline = Text.encode(this.data['ASSOCIATED_ENTITY']['DEADLINE']);
			const comment = direction === BX.CrmActivityDirection.incoming ?
				Loc.getMessage('MPL_MOBILE_INCOMING_CALL') : (direction === BX.CrmActivityDirection.outgoing ?
					Loc.getMessage('MPL_MOBILE_OUTBOUND_CALL') :
					Loc.getMessage('MPL_MOBILE_CALL'));
			const hasStatus = this.data['ASSOCIATED_ENTITY']['CALL_INFO'] ? this.data['ASSOCIATED_ENTITY']['CALL_INFO']['HAS_STATUS'] : false;
			const status = this.data['ASSOCIATED_ENTITY']['CALL_INFO'] ? this.data['ASSOCIATED_ENTITY']['CALL_INFO']['SUCCESSFUL'] : null;

			const iconClasses = [
				(direction === BX.CrmActivityDirection.incoming
					? 'ui-icon-service-call-in' : (
						direction === BX.CrmActivityDirection.outgoing
							? 'ui-icon-service-call-out' : 'ui-icon-service-callback')),
			];
			const render = Tag.render`
			<div class="feed-com-block-cover crm-calltracker-notification">
				<div class="post-comment-block post-comment-block-old post-comment-block-approved  mobile-longtap-menu ">
					<div class="ui-icon ${iconClasses.join(' ')} crm-phonetracker-icon">
						<i></i>
						${(hasStatus && status !== true) ? 
							`<div class="ui-icon-cross">
								<svg width="11" height="11" viewBox="0 0 11 11" fill="none" xmlns="http://www.w3.org/2000/svg">
									<path fill-rule="evenodd" clip-rule="evenodd" d="M7.19252 5.532L10.7451 9.08457L9.08463 10.745L5.53206 7.19246L1.91046 10.8141L0.25 9.15361L3.87161 5.532L0.319037 1.97943L1.97949 0.318976L5.53206 3.87155L9.15367 0.249939L10.8141 1.91039L7.19252 5.532Z" fill="#767C87"/>
								</svg>
							</div>` : ''
						}
						<div class="ui-icon-counter">1</div>
					</div>
					<div class="post-comment-detail">
						<div class="post-comment-balloon">
							<div class="post-comment-cont">
								<span class="post-comment-author    crm-phonetracker-event-name">
									${comment}
								</span>
								<div class="post-comment-time">${Text.encode(this.data['ASSOCIATED_ENTITY']['CREATED'])}</div>
							</div>
							<div class="post-comment-wrap-outer">
								${
									hasDuration
									? Tag.render`
										<div class="post-comment-wrap">
											<div class="post-comment-text">${duration}</div>
										</div>`
									: ''
								}
								<div class="post-label-wrap">
									<svg width="19" height="18" viewBox="0 0 19 18" fill="none" xmlns="http://www.w3.org/2000/svg">
										<path d="M8.26297 5.9911H9.76297V8.2411H12.013V9.7411H8.26297V5.9911Z" fill="white"/>
										<path fill-rule="evenodd" clip-rule="evenodd" d="M3.5682 11.4511C4.56845 13.6754 6.81991 15.0688 9.25682 14.9716C12.4898 14.9055 15.0579 12.2327 14.995 8.99971C14.9949 6.56087 13.5128 4.36679 11.2504 3.45607C8.98799 2.54536 6.39916 3.10075 4.70939 4.85933C3.01962 6.61792 2.56795 9.22685 3.5682 11.4511ZM4.95948 10.8255C5.70444 12.4821 7.38125 13.5198 9.19618 13.4475C11.604 13.3982 13.5167 11.4076 13.4698 8.99978C13.4697 7.18341 12.3659 5.54933 10.6809 4.87106C8.99597 4.19279 7.06789 4.60642 5.8094 5.91616C4.55092 7.2259 4.21453 9.16894 4.95948 10.8255Z" fill="white"/>
									</svg>
									<div class="post-label-text">${deadline}</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			`;
			return render;
		});
	}
	solve()
	{

	}

	static checkForPaternity(data)
	{
		return false;
	}
}