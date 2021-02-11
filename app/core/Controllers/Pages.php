<?php
namespace App\Core\Controllers;

use App\Core\Managers\Login as LoginManager;

/** 
 * Responsável por obter as informações da páginas, como título, descrição
 * e thumbnail. Também tem a array com URLS e identifica o sucesso/erro em GET.
 * 
 * @package \App\Core\Controllers
 * @author Caique M Araujo <caique@piggly.com.br>
 * @version 1.0.0
 */
class Pages extends Base
{        
	/**
	 * Carrega a página principal.
	 * 
	 * @return void
	 * @access public
	 * @since 1.0.0
	 */
	public function homepage()
	{        
		// Verifica a sessão informando que não precisa estar logado
		// para que, se estiver, redirecione.
		$this->verifySession( false );
		$this->template->getTemplate('homepage');
	}
	
	/**
	 * Carrega a página de LOGIN para iniciar a sessão do usuário.
	 * 
	 * @return void
	 * @access public
	 * @since 1.0.0
	 */
	public function login ()
	{
		// Verifica a sessão informando que não precisa estar logado
		// para que, se estiver, redirecione.
		$this->verifySession( false );
		
		// Processa as informações de login
		$login = new LoginManager();
		$login->doLogin();
	}
	
	/**
	 * Carrega a página de LOGOUT para encerrar a sessão do usuário.
	 * 
	 * @return void
	 * @access public
	 * @since 1.0.0
	 */
	public function logout ()
	{
		// Verifica a sessão informando que não precisa estar logado
		// para que, se estiver, redirecione.
		$this->verifySession();
		
		// Processa as informações de login
		$login = new LoginManager();
		$login->doLogout();
	}

	public function create ()
	{
		// Verifica a sessão informando que não precisa estar logado
		// para que, se estiver, redirecione.
		$this->verifySession();
		$this->template->getTemplate('create');
	}

	public function pix ()
	{
		// Verifica a sessão informando que não precisa estar logado
		// para que, se estiver, redirecione.
		$this->verifySession();
		$this->template->getTemplate('pix');
	}
}