import { Tag } from 'main.core';
import { Circle, Line } from 'ui.system.skeleton';
import './task-card-skeleton.css';

export const Skeleton = (): HTMLElement => Tag.render`
	<div class="task-skeleton" style="padding: 24px">
		<div class="--title --row">
			${line(240, 18, 99)}
			<div class="--fire">${circle()}</div>
			${circle()}
		</div>
		<div class="--description">${line(80, 12, 99)}</div>
		<div style="margin-bottom: 17px">${FieldRow()}</div>
		${FieldRow()}
		<div class="--chips">${line(null, 32)}</div>
		<div class="--buttons --row">
			${line(84, 34)}
			<div class="--cancel">${line(84, 34)}</div>
			${line(97, 12, 99)}
		</div>
	</div>
`;

const FieldRow = (): HTMLElement => Tag.render`
	<div class="--row">
		${line(100, 12, 99)}
		<div style="margin: 0 8px 0 41px">${circle(22)}</div>
		${line(130, 12, 99)}
	</div>
`;

export const FullSkeleton = (): HTMLElement => Tag.render`
	<div class="task-skeleton --full">
		<div class="--main">
			<div style="padding: 28px 24px">
				<div class="--full-title --row">
					${line(350, 18)}
					${circle()}
				</div>
				<div class="--full-description">${line(260, 18)}</div>
				${line(null, 84)}
				<div style="margin: 12px 0">${line(null, 84)}</div>
				${line(null, 84)}
				<div class="--full-chips --row">
					${line(88, 32)}
					${line(88, 32)}
					${line(88, 32)}
				</div>
			</div>
			<div class="--row --footer">
				${line(85, 38)}
				<div class="--more">${line(38, 38)}</div>
				${line(131, 22)}
			</div>
		</div>
		<div class="--chat">
			<div class="--chat-title --row">
				${circle(40)}
				<div class="--chat-info">
					${line(110, 12)}
					${line(75, 10)}
				</div>
				${circle()}
				<div style="margin-left: 8px">${circle()}</div>
			</div>
			<div class="--chat-bg">
				<div class="--textarea --row">
					${line(null, 47)}
					${circle(44)}
				</div>
			</div>
		</div>
	</div>
`;

const line = Line;
const circle = Circle;
