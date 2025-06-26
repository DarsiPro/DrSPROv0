





<!-- Ярлыки боковых вкладок с некоторыми классами всплывающих подсказок (подробнее смотрите в соответствующем разделе). Здесь показаны три стиля вкладок:

Первый из них активен, и виден фон вкладки
Второй - стандартный ярлык
Последний - отключенный ярлык
В комплекте с шаблоном есть несколько иконок, перейдите в раздел дополнительно, чтобы узнать, как создать свою собственную.

Начиная с версии 1.5, доступен стиль с легендами: просто добавьте класс with-legend к элементу #shortcuts 
и заключите текст ярлыка в промежуток с помощью класса shortcut-legend:-->
	<!-- Ярлыки на боковых вкладках с надписями -->
	<ul id="shortcuts" role="complementary" class="children-tooltip tooltip-right">
		<!-- Active shortcut -->
		<li class="current"><a href="./" title="Dashboard"><span class="icon-home"></span></a></li>
		<!-- Background shortcut -->
		<li><a href="#" title="Messages"><span class="icon-user"></span></a></li>
		<!-- Disabled shortcut -->
		<li><span class="shortcut-notes" title="Notes"><span class="icon-briefcase"></span></span></li>
		
		<li class="at-bottom"><a href="<?php echo WWW_ROOT ?>/admin/settings.php?m=__sys__" title="<?php echo __('Common settings'); ?>"><span class="icon-gear"></span></a></li>
		
				
	</ul>
	<!-- Sidebar/drop-down menu -->
	<section id="menu" role="complementary">
<!-- Панель навигационного меню. And the Aria role.-->

		<!-- Эта оболочка используется в нескольких адаптивных макетах -->
		<div id="menu-content">
<!-- Эта оболочка предназначена в основном для отображения на планшете, где содержимое будет прокручиваться в этом элементе.-->

			<header>
				Administrator
			</header>
<!-- Просто простой заголовок. Добавляйте сюда все, что хотите.-->

			<div id="profile">
				<img src="img/user.png" width="64" height="64" alt="User name" class="user-icon">
				Hello
				<span class="name"><?php echo h($_SESSION['user']['name']); ?></span>
			</div>
<!-- Блокировка профиля пользователя. Раздел-оболочка может быть заменен ссылкой, например, для указания на страницу профиля.-->

			<!-- По умолчанию этот раздел предназначен для 4 значков, смотрите документацию, чтобы узнать, как это изменить, в разделе "Объяснение базовой разметки". -->
			<ul id="access" class="children-tooltip">
				<!-- Icon with count -->
				<li><a href="#" title="Messages">
					<span class="icon-inbox"></span>
					<span class="count">2</span>
				</a></li>
				<!-- Simple icon -->
				<li><a href="#" title="Calendar">
					<span class="icon-calendar"></span></a>
				</li>
				<!-- Disabled icon -->
				<li class="disabled">
					<span class="icon-briefcase"></span>
				</li>
				
			</ul>
<!-- Панель быстрого доступа со значками. Чтобы отключить значки, используйте класс disabled.

Чтобы изменить количество видимых значков, отредактируйте файл style.css и найдите селектор #access > li, 
затем измените ширину на любую, какую вы хотите, чтобы она соответствовала желаемому количеству значков.-->

			<!-- Navigation menu goes here -->
<!-- Заполнитель для содержимого вашего навигационного меню, но он может располагаться в любом месте панели меню, даже над заголовком или блоками профиля.-->

<section class="navigable">
	<ul class="big-menu">
		<?php
		$section = pathinfo(basename($_SERVER['REQUEST_URI'], '?' . $_SERVER['QUERY_STRING']), PATHINFO_FILENAME);
		$modules = getAdmFrontMenuParams();
		// Активные модули
                        $i=0;
        foreach ($modules as $modKey => $modData): 
                            if (!empty($nsmods) && array_key_exists($modKey, $nsmods)) continue;
                            if (Config::read('active', $modKey) != 1) continue;
                            $i=1;
                    ?>
		
		
		
		<li class="with-right-arrow">
			<span>
			    <span class="list-count"><?php echo count($modData['pages']); ?></span>
			                    <?php if (isset($modData['icon_url'])) {?>
                                    <i><img src="<?php echo $modData['icon_url']; ?>" alt="icon" class="responsive-img"></i>
                                <?php } else { ?>
                                    <i class="<?php echo isset($modData['icon_class']) ? $modData['icon_class'] : 'mdi-action-extension'; ?>"></i>
                                <?php } ?>
			        
			        <?php echo $modData['title']; ?>
			    </span>
			<?php if (count($modData['pages']) > 1) { ?>
			<ul class="big-menu">
				
				<?php foreach ($modData['pages'] as $url => $ankor): ?>
				
				    <li><a href="<?php echo $url; ?>"<?php if ($modKey == $Register['module'] && $section == pathinfo($url)['filename']) {echo ' class="navigable-current current"';} ?>><?php echo $ankor;?></a></li>
				<?php endforeach; ?>
				
			</ul>
			<?php } ?>
			
		</li>
		<?php endforeach;
		            // Неактивные модули
                     $i=0;
                    foreach ($modules as $modKey => $modData):
                        if (!empty($nsmods) && array_key_exists($modKey, $nsmods)) continue;
                        if (Config::read('active', $modKey) == 1) continue;
                        $i=1;
                    ?>
                   <li class="with-right-arrow anthracite-gradient">
			<span>
			    <span class="list-count"><?php echo count($modData['pages']); ?></span>
			                    <i class="<?php echo isset($modData['icon_class']) ? $modData['icon_class'] : 'mdi-action-extension'; ?>"></i>
                                <?php if (count($modData['pages']) < 2) { ?>
                                    <a href="<?php echo key($modData['pages']); ?>"><?php echo $modData['title']; ?></a>
                                <?php } else { ?>
                                    <?php echo $modData['title']; ?>
                                    <i class="mdi-navigation-arrow-drop-down right"></i>
                                <?php } ?>
			    </span>
			<?php if (count($modData['pages']) > 1) { ?>
			<ul class="big-menu">
				<?php foreach ($modData['pages'] as $url => $ankor): ?>
				    <li><a href="<?php echo $url; ?>"<?php if ($modKey == $Register['module'] && $section == pathinfo($url)['filename']) {echo ' class="navigable-current current"';} ?>><?php echo $ankor; ?></a></li>
				<?php endforeach; ?>
			</ul>
			<?php } ?>
			
		</li>
		<?php endforeach;  ?>
	</ul>
</section>






		</div>
		<!-- End content wrapper -->

		<!-- This is optional -->
		<footer id="menu-footer">
			<!-- Any content -->
		</footer>
<!-- Это необязательный блок, в который вы можете поместить некоторое дополнительное содержимое, например, 
некоторые переключатели опций. Он всегда будет виден в режиме планшета, так как находится за пределами оболочки прокрутки.-->
	</section>
	<!-- End sidebar/drop-down menu -->
<!-- То же самое, что и для основного содержимого: использование элемента section - это чистая семантика, вы также можете использовать div.-->






<!-- Main content -->
	<section role="main" id="main">
<!-- Основной блок контента с соответствующей Aria role-->

		<!-- Visible only to browsers without javascript -->
		<noscript class="message black-gradient simpler">Your browser does not support JavaScript! Some features won't work as expected...</noscript>
<!-- Простой элемент noscript для браузеров с отключенным javascript. В целом шаблон отлично работает и без него, 
но большинство функций будут отключены.-->





