<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks;
use Bitrix\Tasks\Integration\SocialNetwork;

Loc::loadMessages(__FILE__);

$this->setFrameMode(true);
$this->SetViewTarget("sidebar", 200);
?>

<div class="sidebar-widget sidebar-widget-tasks">
	<div class="sidebar-widget-top">
		<div class="sidebar-widget-top-title">
			<a href="<?= $arParams['PATH_TO_TASKS'] ?>"><?= Loc::getMessage('TASKS_FILTER_TITLE') ?></a>
		</div>
		<?php
			$path = (new Tasks\Slider\Path\TaskPathMaker(0, Tasks\Slider\Path\PathMaker::EDIT_ACTION))->makeEntityPath();
			$url = new \Bitrix\Main\Web\Uri($path);
			$url->addParams([
				'ta_sec' => Tasks\Helper\Analytics::SECTION['tasks'],
				'ta_el' => Tasks\Helper\Analytics::ELEMENT['widget_menu']
			]);
		?>
		<a class="plus-icon" href="<?= $url->getUri() ?>"></a>
	</div>
	<?php if (is_array($arResult['ROLES'])): ?>
		<div class="sidebar-widget-content">
			<?php foreach ($arResult['ROLES'] as $role): ?>
			<div class="sidebar-widget-item --with-separator">
				<a class="task-item" href="<?= $role['HREF'] ?>">
					<span class="task-item-text"><?= $role['TITLE'] ?></span>
					<span class="task-item-index-wrap">
						<span class="task-item-index"><?= $role['COUNTER'] ?></span>
						<span class="task-item-counter-wrap">
							<span class="task-item-counter"><?= $role['COUNTER_VIOLATIONS'] ?></span>
						</span>
					</span>
				</a>
			</div>
			<?php endforeach ?>
		</div>
	<?php endif ?>
</div>