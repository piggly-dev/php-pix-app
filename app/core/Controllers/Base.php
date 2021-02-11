<?php
namespace App\Core\Controllers;

use App\Core\Managers\Template as Template;
use App\Core\Managers\Login as LoginManager;

/** 
 * Controlador padrão, contendo o banco de dados, o gerente principal do
 * banco de dados e o usuário, quando o mesmo for verificado na sessão
 * ativa.
 * 
 * @package \App\Core\Controllers
 * @author Caique M Araujo <caique@piggly.com.br>
 * @version 1.0.0
 */
class Base
{    
	/**
	 * @var Template Template para carregar uma página. 
	 * @access protected 
	 * @since 1.0.0
	 */
	protected $template;
	
	/**
	 * @var string Base url que chamou o controlador. 
	 * @access public 
	 * @since 1.0.0
	 */
	public $base_url;
							
	/**
	 * @var array Parâmetros da Página. 
	 * @access public
	 * @since 1.0.0
	 */
	public $params;    
	
	/**
	 * @var array Usuário logado. 
	 * @access protected
	 * @since 1.0.0
	 */
	protected $user_logged;
			
	/**
	 * Configura o controlador setando seus parâmetros padrões.
	 * 
	 * @param string $url Base URL que chamou o controlador. 
	 * @param array $params Parâmetros da URL.
	 * @return void
	 * @access public
	 * @since 1.0.0
	 */
	public function __construct( &$url = null, &$params = null ) 
	{ 
		$this->params   = $params; 
		$this->base_url = $url;   
		$this->template = new Template ( $this );
	}
	
	/**
	 * Verifica a sessão ativa do usuário e redireciona, validando conforme
	 * as seguintes condições:
	 * 
	 *      Se $hasToLogin é verdeiro, então se não tiver logado redireciona
	 *      a página HAS_TOLOGIN_URI, para fazer o login.
	 * 
	 *      Se $hasToLogin é falso, então faz o redirecionamento para a página
	 *      principal de quando se está logado LOGGED_URI.
	 * 
	 * @param boolean $hasToLogin É uma página com login necessário.
	 * @return void
	 * @access public
	 * @since 1.0.0
	 */
	public function verifySession ( $hasToLogin = true )
	{
		$session = $this->checkSession();
		
		if ( $hasToLogin )
		{ 
			// Redireciona para fazer o login, se não existe sessão
			if ( !$session )
			{ redirect( LoginManager::LOGGIN_URI ); }
		}
		else
		{
			// Redireciona para a página logada, se existe sessão
			if ( $session )
			{ redirect( LoginManager::LOGGED_URI ); }
		}
	}
	
	/**
	 * Apenas faz o check do usuário, se o usuário existe então 
	 * salva seus dados no objeto.
	 * 
	 * @return boolean
	 * @access public
	 * @since 1.0.0
	 */
	public function checkSession ()
	{
		$login = new LoginManager();
		
		$session = $login->checkSession();
		
		if ( $session )
		{ $this->user_logged = $login->getUser(); }
		
		unset ( $login );
		
		return $session;
	}
	
	/**
	 * Retorna se existe um usuário salvo no objeto.
	 * Ideal para controlar áreas exibidas apenas se o usuário está ativo.
	 * 
	 * @return boolean
	 * @access public
	 * @since 1.0.0
	 */
	public function hasUser ()
	{ return isset ( $this->user_logged ); }
		
	/**
	 * Conteúdo padrão para exibir quando uma página não for encontrada.
	 * 
	 * @access public
	 */
	public function notFound ()
	{ $this->template->getTemplate('404'); }
}