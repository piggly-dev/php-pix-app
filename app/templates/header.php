<?php header( 'Content-Type: text/html; charset=UTF-8' ); ?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="UTF-8">
		<meta name="theme-color" content="#000">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<meta name="author" content="Piggly">  
		<meta name="copyright" content="Copyright (c) Piggly">
		<title><?=SITE_NAME;?></title>
		<link rel="icon" type="image/png" sizes="192x192"  href="<?=getUrl("icon-192x192.png"); ?>">
		<link rel="icon" type="image/png" sizes="32x32" href="<?=getUrl("icon-32x32.png"); ?>">
		<link rel="icon" type="image/png" sizes="96x96" href="<?=getUrl("icon-96x96.png"); ?>">
		<link rel="icon" type="image/png" sizes="16x16" href="<?=getUrl("icon-16x16.png"); ?>">
		<link rel="icon" type="image/icon" href="<?=getUrl("favicon.ico"); ?>" />
		<meta name="robots" content="noindex,nofollow" />
		<!-- BEGIN HTML Includes here... -->
		<?php \App\Core\Tools\Hook::run( 'header' ); ?>
		<!-- END HTML Includes here... -->
		<!-- HTML5 Shiv and Respond.js IE8 support of HTML5 elements and media queries -->
		<!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
		<!--[if lt IE 9]>
		<script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
		<script src="https://oss.maxcdn.com/libs/respond.js/1.3.0/respond.min.js"></script>
		<![endif]-->
	</head>
	<body>