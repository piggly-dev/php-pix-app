<?php
/**
 * Configurações gerais da aplicação. Setando as principais configurações
 * antes de iniciar.
 * 
 * @author Caique M Araujo <caique@piggly.com.br>
 * @version 1.0.0
 */

/**
 * ##############################
 * TIMEZONE
 * ##############################
 */

/** Define a timezone padrão */
date_default_timezone_set( 'America/Sao_Paulo' );

/** Define o local */
setlocale( LC_ALL, 'pt_BR.utf8', 'pt_BR.utf8', 'pt_BR.utf8', 'portuguese' );

/**
 * ##############################
 * ENCODING
 * ##############################
 */

/** Habilita o encoding para UTF-8 */
mb_internal_encoding( 'UTF-8' );
mb_http_output( 'UTF-8' );
mb_regex_encoding( 'UTF-8' );

/**
 * ##############################
 * PATHS
 * ##############################
 */

/** @var string Pasta de template. */
define ( 'TEMPLATE_PATH' , ABSPATH . '/templates/' );

/**
 * ##############################
 * SITE SETTINGS
 * ##############################
 */

/** @var string Endereço da Web. URL que aponta para pasta pública. */
define ( 'DOMAIN' , $_ENV['DOMAIN'] ?? 'http://localhost' );

/** @var boolean Habilita o protocolo HTTPs. Por padrão, não está habilitado. */
define ( 'HTTPS', boolval($_ENV['HTTPS']) );

/** @var string Nome do site. */
define ( 'SITE_NAME', $_ENV['SITE_NAME'] ?? 'Pix por Piggly' );

/** @var string Nome do sessão. */
define ( 'SESSION_NAME', $_ENV['SESSION_NAME'] ?? 'pix_localhost' );

/** @var string Chave do Site para utilizar em níveis de segurança. */
define ( 'SECRET_KEY', $_ENV['SECRET_KEY'] );

/**
 * ##############################
 * URI
 * ##############################
 */

/** @var string Endereço da Web. URL que aponta para pasta pública. */
define ( 'HOME_URI' , sprintf('%s/', DOMAIN) );

/** @var string Endereço da Web. URL para quando estiver logado. */
define ( 'LOGGED_URI' , sprintf('%s/pix', DOMAIN) );

/** @var string Endereço da Web. URL para realizar o login. */
define ( 'HAS_TOLOGIN_URI' , sprintf('%s/pix', DOMAIN) );

/** @var boolean Habilita o modo DEBUG.
 * Se você estiver desenvolvendo, sete como true para ver os erros PHP.
 * Por padrão, não deve ser habilitado.
 */
define ( 'DEBUG' , false );

/** Não faça mudanças após aqui... */