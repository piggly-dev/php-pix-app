<?php
/** 
 * Inicia a aplicação com uma sessão segura, garante a prevenção do modo DEBUG
 * e inicializa a classe principal da aplicação.
 * 
 * @author Caique M Araujo <caique@piggly.com.br>
 * @version 1.0.0
 */

use App\Core\Tools\Session;
use App\Core\Tools\UrlBuilder;
use App\Core\Main;

/** @var string Caminho absoluto baseado nesse arquivo. Um nível acima da pasta pública. */
define ( 'ABSPATH' , dirname( __FILE__ ) );

/**
 * 
 * AMBIENTE
 */
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Configurações globais
require_once 'config/master.php';

/**
 * ##############################
 * FUNÇÕES
 * ##############################
 */

// Carrega as Funções Globais
require_once ( 'functions/global.php' );
// Carrega as Funções Locais
require_once ( 'functions/local.php' );

/**
 * ##############################
 * SESSÃO
 * ##############################
 */

// Inicia uma sessão segura
Session::startSession();

/**
 * ##############################
 * Tratamento de erros.
 * ##############################
 */

// Reporta os erros encontrados
error_reporting( E_ALL );

// Verifica o modo debug
if ( DEBUG === false )
{
	// Oculta todos os erros 
	ini_set( "display_startup_errors", 0);
	ini_set( "display_erros", 0 );
}
else
{
	// Mostra todos os erros
	ini_set( "display_startup_errors", 1);
	ini_set( "display_erros", 1 );
}

/**
 * ##############################
 * ROTAS DE URL
 * ##############################
 */

// Todas as páginas
UrlBuilder::addRoute( '([a-z0-9\-]+)', 'Pages', 'loader' );


/**
 * ##############################
 * APLICAÇÃO
 * ##############################
 */

/** @var app Classe principal que inicia a aplicação */
$app = new Main();