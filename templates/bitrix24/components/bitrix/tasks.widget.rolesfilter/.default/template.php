<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks;
use Bitrix\Tasks\Integration\SocialNetwork;
use Bitrix\UI\Counter\Counter;

Loc::loadMessages(__FILE__);

$this->setFrameMode(true);
$this->SetViewTarget("sidebar", 200);
?>

<div class="sidebar-widget sidebar-widget-tasks --ui-context-content-light">
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
				<a class="sidebar-widget-item task-item <?= $role['COUNTER'] == 0 ? '--zero' : '' ?>" href="<?= $role['HREF'] ?>">
					<span class="task-item-text"><?= $role['TITLE'] ?></span>
					<span class="task-item-counter-wrap <?= $role['COUNTER_VIOLATIONS'] == 0 ? '--zero' : '' ?>">
							<?php
								$value = $role['COUNTER_VIOLATIONS'] === '99+' ? 100 : (int)$role['COUNTER_VIOLATIONS'];
								$counter = new Counter(useAirDesign: true, value: $value);

								echo $counter->render();
							?>
						</span>
					<span class="task-item-index"><?= $role['COUNTER'] ?></span>
				</a>
			<?php endforeach ?>
		</div>
	<?php endif ?>
</div>
