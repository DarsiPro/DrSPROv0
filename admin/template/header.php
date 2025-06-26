<!DOCTYPE html>
<?php
    if (!empty($_SESSION['user']['name'])) {
        $ava_path = getAvatar($_SESSION['user']['id'], $_SESSION['user']['email']);
    }
    @ini_set('default_socket_timeout', 5);
?>
<!--Описание базовой разметки
В этом разделе вы подробно ознакомитесь с разметкой шаблона по умолчанию. 
Вы найдете объяснения, альтернативные варианты и советы, которые помогут вам создать ее так, как вы хотите. 
Для получения более подробной версии этого кода перейдите к разделу Краткое руководство пользователя/Базовая разметка.
-->


<!--[if IEMobile 7]><html class="no-js iem7 oldie"><![endif]-->
<!--[if (IE 7)&!(IEMobile)]><html class="no-js ie7 oldie" lang="en"><![endif]-->
<!--[if (IE 8)&!(IEMobile)]><html class="no-js ie8 oldie" lang="en"><![endif]-->
<!--[if (IE 9)&!(IEMobile)]><html class="no-js ie9" lang="en"><![endif]-->
<!--[if (gt IE 9)|(gt IEMobile 7)]><!--><html class="no-js" lang="en"><!--<![endif]-->
<!-- Немного перегруженный, но действительно полезный: в IE условные комментарии будут выводить только узел <html>,
соответствующий версии Internet Explorer. А для всех остальных браузеров будет использоваться последний. 
Таким образом, вы можете настроить таргетинг на любую из этих версий IE в своем CSS-коде, используя соответствующий 
селектор: .ie7, .ie8, .ie9, .iem7 (для Internet Explorer Mobile 7) и .oldie (для всех сразу).
-->
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
<!-- Эта последняя строка предназначена для принудительного использования Google Chrome Frame,
если он установлен в Internet Explorer. Если он не установлен, пользователям IE6 будет предложено
ввести строку с условным комментарием прямо под <body>.
-->
	<title><?php echo $pageTitle ?></title>
	<meta name="description" content="">
	<meta name="author" content="">

	<!-- http://davidbcalhoun.com/2010/viewport-metatag -->
	<meta name="HandheldFriendly" content="True">
	<meta name="MobileOptimized" content="320">

	<!-- http://www.kylejlarson.com/blog/2012/iphone-5-web-design/ -->
	<meta name="viewport" content="user-scalable=0, initial-scale=1.0">
<!-- В этом последнем разделе описаны несколько способов отображения контента в мобильных браузерах. 
Если вы не знакомы с этим, вам следует прочитать две статьи в комментариях выше, они действительно полезны.
-->
	<!-- For all browsers -->
	<link rel="stylesheet" href="/admin/template/css/reset.css?v=<?php echo rand();?>">
	<link rel="stylesheet" href="/admin/template/css/style.css?v=<?php echo rand();?>">
	<link rel="stylesheet" href="/admin/template/css/colors.css?v=<?php echo rand();?>">
	<link rel="stylesheet" media="print" href="/admin/template/css/print.css?v=<?php echo rand();?>">
	<!-- For progressively larger displays -->
	<link rel="stylesheet" media="only all and (min-width: 480px)" href="/admin/template/css/480.css?v=<?php echo rand();?>">
	<link rel="stylesheet" media="only all and (min-width: 768px)" href="/admin/template/css/768.css?v=<?php echo rand();?>">
	<link rel="stylesheet" media="only all and (min-width: 992px)" href="/admin/template/css/992.css?v=<?php echo rand();?>">
	<link rel="stylesheet" media="only all and (min-width: 1200px)" href="/admin/template/css/1200.css?v=<?php echo rand();?>">
	<!-- For Retina displays -->
	<link rel="stylesheet" media="only all and (-webkit-min-device-pixel-ratio: 1.5), only screen and (-o-min-device-pixel-ratio: 3/2), only screen and (min-device-pixel-ratio: 1.5)" href="/admin/template/css/2x.css?v=<?php echo rand();?>">
<!-- Загружаются стандартные таблицы стилей, иногда используются медиазапросы.-->

	<!-- Webfonts -->
	<link href='http://fonts.googleapis.com/css?family=Open+Sans:300' rel='stylesheet' type='text/css'>
<!-- Этот шрифт используется для тонких заголовков. Если это не требуется, вы можете смело опустить эту строку.-->

	<!-- Additional styles -->
<!-- Разместите здесь дополнительные таблицы стилей. Это не обязательно, но это хорошая привычка - немного навести порядок.-->
<!-- Additional styles -->
	<link rel="stylesheet" href="css/styles/agenda.css?v=1">
	<link rel="stylesheet" href="/admin/template/css/dashboard.css?v=<?php echo rand();?>">
	<link rel="stylesheet" href="111/.s/scr/files.css?v=<?php echo rand();?>">
	<link rel="stylesheet" href="/.s/scr/form.css?v=<?php echo rand();?>">
	<link rel="stylesheet" href="/admin/template/css/modal.css?v=<?php echo rand();?>">
	<link rel="stylesheet" href="111css/styles/progress-slider.css?v=1">
	<link rel="stylesheet" href="/.s/scr/switches.css?v=<?php echo rand();?>">
	<link rel="stylesheet" href="/.s/scr/table.css?v=<?php echo rand();?>">



<script src="https://code.jquery.com/jquery-1.9.1.min.js"></script>
	<!-- JavaScript at bottom except for Modernizr -->
	<script src="/admin/js/modernizr.custom.js"></script>
<!-- Modernizr - единственный javascript, загружаемый в разделе <head>, потому что его следует запускать в первую очередь. 
Все остальные файлы загружаются прямо над </body>, поскольку это рекомендуемый способ, в основном для ускорения загрузки страницы. 
Но если вы хотите (или нуждаетесь), чтобы все ваши скрипты были здесь, делайте, что хотите!-->




	<!-- For Modern Browsers -->
	<link rel="shortcut icon" href="img/favicons/favicon.png">
	<!-- For everything else -->
	<link rel="shortcut icon" href="img/favicons/favicon.ico">
<!-- Существует множество размеров значков, каждый браузер выберет тот, который подходит лучше всего. 
Обратите внимание, что при использовании Tiny con будет использоваться первый значок.-->

	<!-- iOS web-app metas -->
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-status-bar-style" content="black">
<!-- Эта часть определяет, может ли ваше веб-приложение работать как отдельное приложение на iOS, 
и устанавливает цвет строки состояния. Если вам это не нужно (или вы не хотите), просто удалите эти строки.-->

	<!-- iPhone ICON -->
	<link rel="apple-touch-icon" href="img/favicons/apple-touch-icon.png" sizes="57x57">
	<!-- iPad ICON -->
	<link rel="apple-touch-icon" href="img/favicons/apple-touch-icon-ipad.png" sizes="72x72">
	<!-- iPhone (Retina) ICON -->
	<link rel="apple-touch-icon" href="img/favicons/apple-touch-icon-retina.png" sizes="114x114">
	<!-- iPad (Retina) ICON -->
	<link rel="apple-touch-icon" href="img/favicons/apple-touch-icon-ipad-retina.png" sizes="144x144">
<!-- Набор тегов iOS для определения различных размеров значков веб-сайтов, добавленных в качестве веб-приложения (на панели управления). 
Если вам это не нужно (или вы не хотите), просто удалите эти строки.-->

	<!-- iPhone SPLASHSCREEN (320x460) -->
	<link rel="apple-touch-startup-image" href="img/splash/iphone.png" media="(device-width: 320px)">
	<!-- iPhone (Retina) SPLASHSCREEN (640x960) -->
	<link rel="apple-touch-startup-image" href="img/splash/iphone-retina.png" media="(device-width: 320px) and (-webkit-device-pixel-ratio: 2)">
	<!-- iPhone 5 SPLASHSCREEN (640×1096) -->
	<link rel="apple-touch-startup-image" href="img/splash/iphone5.png" media="(device-height: 568px) and (-webkit-device-pixel-ratio: 2)">
	<!-- iPad (portrait) SPLASHSCREEN (748x1024) -->
	<link rel="apple-touch-startup-image" href="img/splash/ipad-portrait.png" media="(device-width: 768px) and (orientation: portrait)">
	<!-- iPad (landscape) SPLASHSCREEN (768x1004) -->
	<link rel="apple-touch-startup-image" href="img/splash/ipad-landscape.png" media="(device-width: 768px) and (orientation: landscape)">
	<!-- iPad (Retina, portrait) SPLASHSCREEN (2048x1496) -->
	<link rel="apple-touch-startup-image" href="img/splash/ipad-portrait-retina.png" media="(device-width: 1536px) and (orientation: portrait) and (-webkit-min-device-pixel-ratio: 2)">
	<!-- iPad (Retina, landscape) SPLASHSCREEN (1536x2008) -->
	<link rel="apple-touch-startup-image" href="img/splash/ipad-landscape-retina.png" media="(device-width: 1536px)  and (orientation: landscape) and (-webkit-min-device-pixel-ratio: 2)">
<!-- Заставки для веб-приложений на устройствах iOS. В пакете вы найдете шаблоны PSD, которые можно создать самостоятельно. 
Если вам это не нужно (или вы не хотите), просто удалите эти строки.-->

	<!-- Microsoft clear type rendering -->
	<meta http-equiv="cleartype" content="on">
<!-- Некоторые типы Internet Explorer смягчают отображение.-->

	<!-- IE9 Pinned Sites: http://msdn.microsoft.com/en-us/library/gg131029.aspx -->
	<meta name="application-name" content="Developr Admin Skin">
	<meta name="msapplication-tooltip" content="Cross-platform admin template.">
	<meta name="msapplication-starturl" content="http://www.display-inline.fr/demo/developr">
	<!-- Эти пользовательские задачи являются примерами, вам нужно отредактировать их, чтобы показать реальные страницы -->
	<meta name="msapplication-task" content="name=Agenda;action-uri=http://www.display-inline.fr/demo/developr/agenda.html;icon-uri=http://www.display-inline.fr/demo/developr/img/favicons/favicon.ico">
	<meta name="msapplication-task" content="name=My profile;action-uri=http://www.display-inline.fr/demo/developr/profile.html;icon-uri=http://www.display-inline.fr/demo/developr/img/favicons/favicon.ico">
<!-- Internet Explorer 9 закрепил демонстрацию сайта. Это всего лишь пример, вам нужно определить некоторые реальные тексты и задачи.-->

</head>

<body class="clearfix with-menu with-shortcuts">
<!--Узел <body> может иметь несколько специальных классов:

with-menu
Используйте этот класс, если на вашей странице есть панель навигационного меню. Это добавит необходимое дополнение к основному содержимому.
with-shortcuts
Используйте этот класс, если на вашей странице есть ярлыки боковых вкладок. Это также добавит необходимые отступы к основному содержимому.
reversed
Этот класс изменит расположение меню и ярлыков. Также работает, если используется только один из них.
fixed-title-bar
Строка заголовка будет иметь фиксированное положение в мобильном макете, прокручиваясь вниз вместе со страницей (в браузерах с требуемой поддержкой другие будут видеть статичную строку заголовка)
menu-hidden
Меню навигации по умолчанию будет закрыто-->
<!-- Предложить пользователям IE 6 установить Chrome Frame -->
	<!--[если это IE 7]><p class="сообщение с красным градиентом проще">Ваш браузер <em>древний!</em> <a href="http://browsehappy.com/">Перейдите на другой браузер</a> или <a href="http://www.google.com/chromeframe/?redirect=true">установите Google Chrome Frame</a>, чтобы пользоваться этим сайтом.</p><![endif]-->
<!-- Как упоминалось выше, пользователям IE6 будет предложено установить Google Chrome Frame или более новый браузер. 
Узнайте больше здесь : http://chromium.org/developers/how-tos/chrome-frame-getting-started.-->

	<!-- Title bar -->
	<header role="banner" id="title-bar">
		<h2><a class="white" href="<?php echo (used_https() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . '/' ?>"><?php echo strtoupper(basename($_SERVER['HTTP_HOST'])); ?></a></h2>
	</header>
<!-- Строка заголовка с названием вашего приложения. На мобильных устройствах она также используется в качестве кнопки для отображения панели быстрого доступа.-->

	<!-- Button to open/hide menu -->
	<a href="#" id="open-menu"><span>Menu</span></a>

	<!-- Button to open/hide shortcuts -->
	<a href="#" id="open-shortcuts"><span class="icon-thumbs"></span></a>
<!-- Эти кнопки могут быть расположены где угодно, поскольку они расположены абсолютно точно, но здесь хорошее место, не так ли?-->

<?php include_once ROOT . '/admin/template/menu.php'; ?>


