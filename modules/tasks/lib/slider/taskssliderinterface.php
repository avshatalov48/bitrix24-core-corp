<?php

namespace Bitrix\Tasks\Slider;

interface TasksSliderInterface
{
	public function open(): void;
	public function getJs(): string;
	public function getOpenUrl(): string;
	public function getCloseUrl(): string;
}