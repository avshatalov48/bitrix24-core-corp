import {Item} from '../src/item/item';

describe('Tasks.Scrum.Item', () => {

	const itemParams = {
		itemId: 1,
		name: 'Item name',
		sort: 1,
		storyPoints: '3',
	};

	let item = null;
	before(() => {
		item = new Item(itemParams);
	})

	describe('Initialization', () => {
		it('Item must be a function', () => {
			assert(typeof Item === 'function');
		});
		it('Item must be initialized successfully', () => {
			assert(item.getItemId() === itemParams.itemId);
			assert(item.getName() === itemParams.name);
			assert(item.getSort() === itemParams.sort);
			assert(item.getStoryPoints().getPoints() === itemParams.storyPoints);
		});
	});

	describe('Correct behaviour', () => {
		it('Item must not be disabled', () => {
			assert(item.isDisabled() === false);
		});
		it('Item must be create a DOM element', () => {
			assert.equal(
				item.render().outerHTML.replace(/[\n\r\t]/g, ''),
				`
				<div data-item-id="${item.getItemId()}" data-sort="${item.getSort()}" class="tasks-scrum-item">
					<div class="tasks-scrum-item-inner">
						<div class="tasks-scrum-item-group-mode-container">
							<input type="checkbox">
						</div>
						<div class="tasks-scrum-item-name">
							<div class="tasks-scrum-item-name-field ui-ctl ui-ctl-xs ui-ctl-textbox ui-ctl-no-border">
								<div class="ui-ctl-element" contenteditable="false">
									${item.getName()}
								</div>
							</div>
						</div>
						<div class="tasks-scrum-item-params">
							<div class="ui-icon ui-icon-common-user tasks-scrum-item-responsible"><i></i></div>
							<div class="tasks-scrum-item-story-points">
								<div class="tasks-scrum-item-story-points-field ui-ctl ui-ctl-xs ui-ctl-textbox ui-ctl-auto ui-ctl-no-border">
									<div class="ui-ctl-element" contenteditable="false">
										${item.getStoryPoints().getPoints()}
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				`.replace(/[\n\r\t]/g, '')
			);
		});
		it('Item indicators must be hide', () => {
			assert(item.isShowIndicators() === false);
		});
		it('Item must be able to remove yourself', () => {
			item.removeYourself();
			assert(item.getItemNode() === null);
		});
	});
});