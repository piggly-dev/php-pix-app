<?php
namespace App\Core\Tools;

/** 
 * Gerencia as sessões e os seus comportamentos.
 * 
 * Inicia a sessão adotando um padrão de segurança, no processo faz
 * constantemente a validação da sessão para ver se a assinatura é compatível,
 * evitando que a sessão seja roubada.
 * 
 * Para garantia a integridade da sessão, ela é regenerada automaticamente em
 * uma chance de 1 para 20. Mantendo ela em constante manutenção.
 * 
 *      Sempre adicionará em chaves dentro de um array, sendo a estrutura base
 *      da sessão PHP como:
 * 
 *      [categoria]:
 *          [elemento]: valor,
 *          [elemento]:
 *              [subelemento]: valor
 *          [elemento]:
 *              [subelemento]: valor,
 *              [subelemento]:
 *                  [subelemento..n]: valor
 * 
 * @package \App\Core\Tools
 * @author Caique M Araujo <caique@piggly.com.br>
 * @version 1.0.0
 */
class Session
{
	/**
	 * @var string Nome da sessão. 
	 * @access private
	 * @since 1.0.0
	 */
	private static $name;
	
	/**
	 * Inicia uma nova sessão de modo seguro, então valida as sessões conforme
	 * as regras definidas.
	 * 
	 * Se a sessão não for válida, sendo obsoleta ou expirada, então destrói.
	 * Se a sessão não tiver a identidade original, então destrói.
	 * Se a última atividade expirou, então destrói.
	 * 
	 * @param string $name Nome da Sessão. Por padrão, nome do site.
	 * @return void
	 * @access public
	 * @since 1.0.0
	 */
	public static function startSession ( $name = SESSION_NAME )
	{
		// Seta o nome estático da sessão
		self::$name = $name; 
		
		ini_set( 'session.use_cookies', 1 );
		ini_set( 'session.use_only_cookies', 1 );
		
		session_set_cookie_params
		(
			0,
			ini_get( 'session.cookie_path' ),
			ini_get( 'session.cookie_domain' ),
			HTTPS,
			true
		);
		
		session_name( self::$name );
		session_start();
					
		// Verifica se a sessão está obsoleta
		if ( self::isValid() )
		{
			// Verifica se a assinatura é válida
			// regenerando a sessão em uma chance de 1 pra 20
			if ( !self::fingerprint() )
			{ self::destroy(); }
			else if ( rand ( 1, 20 ) == 1 )
			{ self::regenerate(); }
		}
		else
		{ self::destroy(); }
		
	}
	
	/**
	 * Cria uma identidade única para o usuário atual, baseado em seu navegador
	 * e em seu endereço de IP. A identididade não será válida se essas informações
	 * forem alteradas.
	 * 
	 * @return boolean TRUE quando é válida, FALSE quando não.
	 * @access private
	 * @since 1.0.0
	 */
	private static function fingerprint ()
	{
		$user_agent  = filter_input( INPUT_SERVER, 'HTTP_USER_AGENT', FILTER_SANITIZE_STRING );
		$remote_addr = filter_input( INPUT_SERVER, 'REMOTE_ADDR', FILTER_SANITIZE_STRING );
		
		// Cria uma assisnatura para a sessão
		$hash = md5 ( $user_agent . ( ip2long( $remote_addr ) & ip2long( '255.255.0.0' ) ) );
		
		// Se a assinatura já está setada, verifica se ela é igual ao hash
		if ( isset( $_SESSION['_fingerprint'] ) ) 
		{ return $_SESSION['_fingerprint'] === $hash; }
		
		// Seta a assinatura
		$_SESSION['_fingerprint'] = $hash;        
		return true;
	}
	
	/**
	 * Regenera a sessão, mantendo a cópia da antiga por 
	 * um determinado espaço de tempo.
	 * 
	 * @param int $delay Tempo em segundos para expirar.
	 * @return void
	 * @access private
	 * @since 1.0.0
	 */
	private static function regenerate ( $delay = 10 )
	{ 
		// Se a sessão atual é obsoleta então ignora
		if ( isset ( $_SESSION['_obsolete'] ) )
		{ return; }
		
		// Define a sessão atual como obsoleta
		$_SESSION['_obsolete'] = true;
		// Define a expiração em um delay a partir de agora
		$_SESSION['_expires'] = time() + $delay;
		
		// Não generera o ID da sessão
		session_regenerate_id( false );
		
		// Óbtem o ID a sessão atual e fecha ela para escrita
		$new_session = session_id();
		session_write_close();
		
		// Inicia uma nova sessão com o ID antigo
		session_id( $new_session );
		session_start();
		
		// Remove as tags de obsoleta e expiração
		unset ( $_SESSION['_obsolete'] );
		unset ( $_SESSION['_expires'] );
	}
	
	/**
	 * Verifica se a sessão é válida, ou sera, diferente de obsoleta ou expirada.
	 * 
	 * @return boolean TRUE quando é valida, FALSE quando não.
	 * @access private
	 * @since 1.0.0
	 */
	private static function isValid ()
	{
		if ( isset ( $_SESSION['_obsolete'] ) && !isset( $_SESSION['_expires'] ) )
		{ return false; }
		
		if ( isset ( $_SESSION['_expires'] ) && $_SESSION['_expires'] < time() )
		{ return false; }
		
		return true;
	}

	/**
	 * Destrói a sessão.
	 * 
	 * @param boolean $logout Executou o Logout.
	 * @return boolean TRUE quando funcionou, FALSE quando não.
	 * @access public
	 * @since 1.0.0
	 */
	public static function destroy ()
	{
		$_SESSION = [];
		
		setcookie
		(
			self::$name,
			'',
			time() - 42000,
			ini_get( 'session.cookie_path' ),
			ini_get( 'session.cookie_domain' ),
			HTTPS,
			true
		);
		
		return session_destroy();
	}
	
	/**
	 * Verifica se o parâmetro existe para obter.
	 * 
	 *      Padrão: categoria.{elemento 1...N}
	 * 
	 * @param string $key Parâmetro em Padrão.
	 * @return boolean TRUE quando existe, FALSE quando não.
	 * @access public
	 * @since 1.0.0
	 */
	public static function check ( $key )
	{
		// Explode o nome solicitado
		$parsed = explode( '.', $key );
		// Captura a sessão
		$result = $_SESSION;
		
		// Enquanto tiverem valores...
		while ( $parsed ) 
		{
			// Captura o primeiro elemento
			$next = array_shift( $parsed );
			
			// Se ele está setado, então o obtem
			if ( isset( $result[$next] ) ) 
			{ $result = $result[$next]; } 
			else 
			{ return false; }
		}
		
		return isset ( $result );
	}
	
	/**
	 * Obtem um parâmetro dentro da sessão baseado em padrão.
	 * 
	 *      Padrão: categoria.{elemento 1...N}
	 * 
	 * @param string $key Parâmetro em Padrão.
	 * @return mixed Valor quando existe o parâmetro, NULL quando não.
	 * @access public
	 * @since 1.0.0
	 */
	public static function get( $key )
	{
		// Explode o nome solicitado
		$parsed = explode( '.', $key );
		// Captura a sessão
		$result = $_SESSION;
		
		// Enquanto tiverem valores...
		while ( $parsed ) 
		{
			// Captura o primeiro elemento
			$next = array_shift( $parsed );
			
			// Se ele está setado, então o obtem
			if ( isset( $result[$next] ) ) 
			{ $result = $result[$next]; } 
			else 
			{ return null; }
		}
		
		return $result;
	}
	
	/**
	 * Insere um parâmetro dentro da sessão baseado em padrão.
	 *      
	 *      Padrão: categoria.{elemento 1...N}
	 * 
	 * @param string $key Parâmetro em Padrão.
	 * @param string $value Valor do Parâmetro.
	 * @return void
	 * @access public
	 * @since 1.0.0
	 */
	public static function put( $key, $value )
	{
		// Explode o nome solicitado
		$parsed = explode( '.', $key );
		// Captura a sessão
		$session =& $_SESSION;
		
		while ( count( $parsed ) > 1 ) 
		{
			// Captura o primeiro elemento
			$next = array_shift( $parsed );
			
			// Se não está setado e não é uma array, cria uma array
			if ( !isset( $session[$next] ) || !is_array( $session[$next] ) ) 
			{ $session[$next] = []; }
			
			// Coloca o ponteiro na array criada
			$session =& $session[$next];
		}
		
		// Adiciona a chave que sobrou o valor
		$session[array_shift($parsed)] = filter_var( $value, FILTER_SANITIZE_STRING );
	}
	
	/**
	 * Remove um parâmetro dentro da sessão baseado em padrão.
	 *      
	 *      Padrão: categoria.{elemento 1...N}
	 * 
	 * @param string $key Parâmetro em Padrão.
	 * @return void
	 * @access public
	 * @since 1.0.0
	 */
	public static function remove ( $key )
	{
		// Explode o nome solicitado
		$parsed = explode( '.', $key );
		// Captura a sessão
		$session =& $_SESSION;
		
		while ( count( $parsed ) > 1 ) 
		{
			// Captura o primeiro elemento
			$next = array_shift( $parsed );
			
			// Se está setado, então coloca o ponteiro na array
			if ( isset( $session[$next] ) ) 
			{ $session =& $session[$next]; }
			else
			{ return; }
		}
		
		// Remove o valor da sessão
		unset ( $session[array_shift($parsed)] );
	}
}