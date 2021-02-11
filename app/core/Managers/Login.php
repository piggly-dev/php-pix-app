<?php
namespace App\Core\Managers;

use App\Core\Tools\Session as Session;
use App\Core\Tools\Cookie as Cookie;

/**
 * Gerenciador de usuário.
 * 
 * @package \App\Core\Managers
 * @author Caique M Araujo <caique@piggly.com.br>
 * @since 1.0.0 Inicial version.
 * @version 1.0.0
 */
class Login
{     
	/** @var USER_RIGHT Usuário obteve sucesso no login. */
	const USER_RIGHT   = 0;
	/** @var USER_BLOCKED Usuário foi bloqueado no login. */
	const USER_BLOCKED = 1;
	/** @var USER_WRONG Usuário está errado no login. */
	const USER_WRONG   = 2;
	/** @var USER_WRONG Usuário está errado no login. */
	const USER_BLOCKED_MAIL = 3;
	/** @var LOGGIN_URI Url para fazer o LOGIN. */
	const LOGGIN_URI   = '';
	/** @var LOGGED_URI Url para quando está logado. */
	const LOGGED_URI   = 'create';
	
	/**
	 * @var array Usuário.
	 * @access private
	 * @since 1.0.0
	 */
	private $user;
	
	/**
	 * Faz a validação do e-mail e senha, tentando encontrar o usuário.
	 * Caso encontrado, cria a sessão e realiza o redirecionamento para a
	 * página LOGGED_URI. Do contrário, seta o erro e volta para a página
	 * LOGGIN_URI.
	 * 
	 * @return void
	 * @access public
	 * @since 1.0.0
	 */
	public function doLogin ()
	{
		// Captura o método de requisição
		$post = strtoupper( filter_input( INPUT_SERVER, 'REQUEST_METHOD', FILTER_SANITIZE_STRING ) );
		
		// Sendo POST
		if ( $post === 'POST' )
		{
			$username = filter_input( INPUT_POST, 'username', FILTER_SANITIZE_STRING );
			$password = filter_input( INPUT_POST, 'password', FILTER_SANITIZE_STRING );
			
			// Se os e-mail não estão setados, define o erro na sessão
			// e redireciona para a página de login
			if ( !isset ( $username, $password ) || $username === '' || $password === '' )
			{ return redirect( self::LOGGIN_URI, sprintf('?status=%s', \urlencode('Não foi possível fazer o login. Verifique o usuário e a senha.')) );	}

			// Tenta criar sessão com o e-mail e senha passados
			$status = $this->createSession( $username, $password );
			
			if ( $this->checkStatus( $status, self::USER_RIGHT ) )
			{ redirect( self::LOGGED_URI ); }
			else if ( $this->checkStatus( $status, self::USER_WRONG ) )
			{ return redirect( self::LOGGIN_URI, sprintf('?status=%s', \urlencode('Não foi possível fazer o login. Verifique o usuário e a senha.')) ); }
		}
		
		// Redireciona para a página principal
		return redirect( self::LOGGIN_URI );
	}
	
	/**
	 * Remove todas as sessões e os cookies que foram setados.
	 * 
	 * @return void
	 * @access public
	 * @since 1.0.0
	 */
	public function doLogout ()
	{ $this->removeSession( true ); }
		
	/**
	 * Cria uma nova sessão para o usuário.
	 * 
	 * @param string $username Username.
	 * @param string $password Senha em Hash.
	 * @return int USER_BLOCKED, USER_WRONG ou USER_RIGHT.
	 * @access public
	 * @since 1.0.0
	 */
	public function createSession ( $username, $password )
	{
		// Extrai o usuário e valida a criação do mesmo
		if ( $this->findUser( $username ) )
		{ return $this->setupUser( $password ); }
		
		return self::USER_WRONG;
	}
	
	/**
	 * Remove os dados do usuário da sessão.
	 * Redireciona para a página inicial do site.
	 * 
	 * Essa função não limpa que possam estar setados na sessão.
	 * 
	 * @return void
	 * @access public
	 * @since 1.0.0
	 */
	public function removeSession ( $all = false )
	{ 
		// Deleta o cookie do usuário no banco de dados 
		// limpando as informações no cookie
		$this->deleteCookie();
		
		if ( $all )
		{
			// Remove a Sessão por Completo
			Session::destroy();
			// Remove o Cookie por Completo
			$cke = new Cookie();
			$cke->clear();
		}
		else
		{
			// Remove os dados de Usuário da Sessão
			Session::remove('user');
		}
		
		// Redireciona para a página inicial
		exit ( header ( 'Location: '.HOME_URI ) );
	}
	
	/**
	 * Valida a sessão do usuário.
	 * 
	 * Com as informações da sessão, extrai o usuário do banco de dados,
	 * verifica se o e-mail é compatível com o usuário da sessão e
	 * valida o hash da sessão para certificar que a sessão é valida.
	 * 
	 * @return boolean Se a sessão é válida ou não.
	 * @access public
	 * @since 1.0.0
	 */
	public function checkSession ()
	{
		$uid = Session::get ( "user.id" );

		// Verifica se a sessão está setada, então valida o usuário
		// pela sessão disponível
		if ( isset ( $uid ) )
		{
			// Obtém o usuário pelo ID e verifica se o HASH é compatível
			if ( $this->findUserById ( $uid ) )
			{ return $this->verifyHash(); }
		}
		else
		{
			// Verifica se o cookie existe e tenta capturar no banco de dados
			// retorna o ID de usuário encontrado ou null
			$uid = $this->verifyCookie();

			if ( !is_null ( $uid ) )
			{
				// Obtém o usuário pelo ID e cria a sessão
				if ( $this->findUserById ( $uid ) )
				{ return $this->setCookieSession(); }
			}
		}

		return false;
	}
	
	/**
	 * Retorna os dados do usuário ativo.
	 * 
	 * @return \Piggly\Models\Users\User Usuário ativo.
	 * @access public
	 * @since 1.0.0
	 */
	public function getUser ()
	{ return $this->user; }    
	
	/**
	 * Cria uma nova sessão para o usuário.
	 * 
	 * Primeiro, valida se a senha do usuário está setada.
	 * Então, verifica se o usuário atingiu um número de tentativas.
	 * Verifica se a senha bate com a do usuário setado.
	 * 
	 * Se as informações conferirem, verifica se o usuário solicitou lembrar a
	 * senha, então cria um cookie em modo seguro. Posteriormente, configura
	 * a sessão e retorna que está tudo certo.
	 * 
	 * Caso o usuário erre a senha, uma tentativa mal sucedida é adicionada
	 * ao banco de dados.
	 * 
	 * Para lembrar o login, coloque um checkbox com nome "remember".
	 * 
	 * @param string $password Senha em Hash.
	 * @return int USER_BLOCKED, USER_WRONG ou USER_RIGHT.
	 * @access public
	 * @since 1.0.0
	 */
	private function setupUser ( $password )
	{
		$user =& $this->user;
		
		// Verifica se a senha é a mesma do usuário e a enviada
		if ( $user['password'] === $password )
		{
			// Captura o valor do campo REMEMBER
			$check = filter_input( INPUT_POST, 'remember' );

			// Se não está vazio...
			if ( !empty ( $check ) )
			{
				// Cria um Cookie para o Usuário
				$this->setCookie( $user['_id'] );
			}

			// Forma a sessão
			$this->setSession();
			return self::USER_RIGHT;
		}
		
		return self::USER_WRONG;
	}
	
	/**
	 * Seta as principais informações do usuário na sessão, entre elas:
	 * id, name, mail e hash.
	 * 
	 * @return void
	 * @access private
	 * @since 1.0.0
	 */
	private function setSession ()
	{
		Session::put( "user.id", $this->user['_id'] );
		Session::put( "user.name", $this->user['username'] );
		Session::put( "user.hash", $this->getHash() );
	}
	
	/**
	 * Seta a sessão com os dados de usuário nomais, acrescrentando a informação
	 * de que existe um cookie salvo.
	 * 
	 * @return boolean
	 * @access private
	 * @since 1.0.0
	 */
	private function setCookieSession ()
	{
		// Forma a sessão
		$this->setSession();
		// Salva a informação do cookie
		Session::put( "user.cookie", true );
		return true;
	}
			
	/**
	 * Cria os tokens, salva no banco de dados e salva o cookie.
	 * 
	 * @param int $user_id Id do usuário.
	 * @param int $selector Seletor do Cookie.
	 * @return boolean Sucesso ao criar o cookie.
	 * @access private
	 * @since 1.0.0
	 */
	private function setCookie ( $user_id, $selector = null )
	{        
		// Se não foi enviado um seletor, cria
		if ( is_null ( $selector ) )
		{ $selector = hash('sha512', $this->user['username']); }
		
		// Cria um Token
		$token = base64_encode(hash('sha512', $this->user['password']));
		
		// Armazena o selector, o token e a indentidade
		$tokenValue = $selector . ':' . $token . ':' . fingerprint();

		// Salva o Cookie
		$cke = new Cookie();
		$cke->put( 'utoken', $tokenValue );
		$cke->save();
	}
	
	/**
	 * Verifica a existência do cookie e valida o mesmo, renegenrando-o caso
	 * ele seja encontrado.
	 * 
	 * @return mixed ID do usuário encontrado, NULL quando não encontrado.
	 * @access private
	 * @since 1.0.0
	 */
	private function verifyCookie ()
	{
		$cke = new Cookie();
		$cookie = $cke->get( 'utoken' );
		
		// Tem o cookie
		if ( !is_null ( $cookie ) )
		{            
			$token_ = explode( ':', $cookie );
			
			// Busca o usuário
			$user = $this->findUserHash($token_[0]);
			
			// Encontrou o cookie
			if ( $user )
			{
				// Verifica se a identidade do usuário é compatível
				// caso não seja, limpa o seletor retorna null
				if ( !checkFingerprint( $token_[2] ) )
				{ $this->clearCookie(); }
				else
				{
					// Validar o token
					$token = $token_[1];

					if ( $token === base64_encode(hash('sha512', $this->user['password'])) )
					{ 
						// Cria um novo Cookie e retorna o ID do usuário
						$this->setCookie( $this->user['_id'], $token_[0] );
						return $this->user['_id']; 
					}
					else
					{ 
						// Remove todos os Cookies do Usuário
						$this->clearCookie();
					}
				}
			}
			else 
			{
				// Remove o Cookie
				$cke->remove( 'utoken' );
				$cke->save();
			}
		}
		
		return null;
	} 
	
	/**
	 * Deleta o cookie que está setado no valor UTOKEN.
	 * 
	 * @return boolean TRUE quando obteve sucesso, FALSE quando não.
	 * @access private
	 * @since 1.0.0
	 */
	private function deleteCookie ()
	{
		$cke = new Cookie();
		$cookie = $cke->get( 'utoken' );
		
		// Tem o cookie
		if ( !is_null ( $cookie ) )
		{ return $this->clearCookie(); }
		
		return false;
	}
	
	/**
	 * Se o token do cookie existe, então remove ele do Cookie e do banco de dados.
	 * 
	 * Se uma array for enviada com os dados do Token, então remove apenas
	 * o Token do cookie conforme as colunas. Do contrário, remove todos Tokens do usuário.
	 * 
	 * @param array $data Dados do Token preenchido.
	 * @param array $columns Por quais colunas deletar o Token.
	 * @return boolean TRUE quando obteve sucesso, FALSE quando não.
	 * @access private
	 * @since 1.0.0
	 */
	private function clearCookie ()
	{ 
		$cke = new Cookie();
		$cookie = $cke->get( 'utoken' );      
		
		if ( !is_null ( $cookie ) )
		{      
			$cke->remove( 'utoken' );
			$cke->save();
			
			return true;
		}

		return false;
	}
	
	/**
	 * Valida se o e-mail da sessão é o mesmo do usuário logado e
	 * certifica que o hash gerado pelo usuário também continua sendo
	 * o mesmo.
	 * 
	 * @return private
	 * @access public
	 * @since 1.0.0
	 */
	private function verifyHash ()
	{
		$user_name = Session::get ( "user.name" );
		$user_hash = Session::get ( "user.hash" );
		
		if ( $this->user['username'] === $user_name )
		{ return $this->checkHash( $user_hash ); }
		
		return false;
	}
	
	/**
	 * Verifica se o hash recebido é igual o do usuário obtido do banco.
	 * 
	 * @param string $hash
	 * @return boolean Se é igual ou não.
	 * @access public
	 * @since 1.0.0
	 */
	private function checkHash ( $hash )
	{ 
		if ( !is_null ( $hash ) )
		{ return $this->getHash() === $hash; }
		
		return false;
	}
	
	/**
	 * Obtém o hash atual para o usuário.
	 * 
	 * O hash do user é gerado pela codificação em sha512 das seguintes informações:
	 * 
	 *      SECRET_KEY                      string      Chave de Segurança.
	 *      $_SERVER[HTTP_USER_AGENT]       string      Agente do usuário.
	 *      SENHA                           string      Senha do usuário em hash.
	 * 
	 * @return string Hash do Usuário.
	 * @access public
	 * @since 1.0.0
	 */
	private function getHash ()
	{
		$user_browser = filter_input ( INPUT_SERVER , 'HTTP_USER_AGENT', FILTER_SANITIZE_STRING );
		return hash ( 'sha512', SECRET_KEY . $user_browser . $this->user['password'] );
	}
		
	/**
	 * Verifica se o restado recebido é compatível com o resultado esperado.
	 * 
	 * @param int $status Status Recebido.
	 * @param int $expected Status Esperado.
	 * @return boolean TRUE quando obteve sucesso, FALSE quando não.
	 * @access private
	 * @since 1.0.0
	 */
	private function checkStatus ( $status, $expected )
	{ return $status === $expected; }

	/**
	 * Find an user in users array.
	 * 
	 * @param int $username Usuário Esperado.
	 * @return bool
	 * @access private
	 * @since 1.0.0
	 */
	private function findUser ( $username ) : bool
	{
		$users = include ( ABSPATH.'/config/users.php' );

		foreach ( $users as $user )
		{
			if ( $user['username'] === $username )
			{ $this->user = $user; return true; }
		}
		
		$this->user = null;
		return false;
	}

	/**
	 * Find an user in users array.
	 * 
	 * @param int $username Usuário Esperado.
	 * @return bool
	 * @access private
	 * @since 1.0.0
	 */
	private function findUserById ( $id ) : bool
	{
		$users = include ( ABSPATH.'/config/users.php' );

		foreach ( $users as $user )
		{
			if ( $user['_id'] === $id )
			{ $this->user = $user; return true; }
		}

		$this->user = null;
		return false;
	}

	/**
	 * Find an user hash in users array.
	 * 
	 * @param int $username Usuário Esperado.
	 * @return bool
	 * @access private
	 * @since 1.0.0
	 */
	private function findUserHash ( $username ) : bool
	{
		$users = include ( ABSPATH.'/config/users.php' );
 
		foreach ( $users as $user )
		{
			if ( hash('sha512', $user['username']) === $username )
			{ $this->user = $user; return true; }
		}

		$this->user = null;
		return false;
	}
}