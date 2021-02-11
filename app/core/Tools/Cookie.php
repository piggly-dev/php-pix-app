<?php
namespace App\Core\Tools;

/** 
 * Gerencia os cookies da aplicação, sendo capaz de obter e setar.
 * Sempre que o método put for utilizado, é preciso salvar o cookie.
 * 
 *      Sempre adicionará em chaves dentro de um array, sendo a estrutura base
 *      da cookie como:
 * 
 *      $_COOKIE[cookie_name]:
 *          [categoria]:
 *              [elemento]: valor,
 *              [elemento]:
 *                  [subelemento]: valor
 *              [elemento]:
 *                  [subelemento]: valor,
 *                  [subelemento]:
 *                      [subelemento..n]: valor
 * 
 * @package \App\Core\Tools
 * @author Caique M Araujo <caique@piggly.com.br>
 * @version 1.0.0
 */
class Cookie
{    
	/**
	 * @var array Nome do cookie. 
	 * @access private
	 * @since 1.0.0
	 */
	private $name;
	
	/**
	 * @var array Dados do cookie. 
	 * @access private
	 * @since 1.0.0
	 */
	private $cookie;
	
	/**
	 * @var boolean Valida se está inserindo/excluindo dados.
	 * @access private
	 * @since 1.0.0
	 */
	private $editing = false;
	
	/**
	 * Define o nome do Cookie antes de adotar os procedimentos.
	 * 
	 * @param string $cookie_name Nome do cookie para carregar.
	 * @access public
	 * @since 1.0.0
	 */
	public function __construct ( $cookie_name = 'main' )
	{ $this->name = $this->getCookieName( $cookie_name ); }
	
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
	public function check ( $key )
	{
		$this->load();
		
		// Explode o nome solicitado
		$parsed = explode( '.', $key );
		
		if ( !empty ( $this->cookie ) )
		{
			// Captura o Json
			$json = $this->cookie;

			// Enquanto tiverem valores...
			while ( $parsed ) 
			{
					// Captura o primeiro elemento
					$next = array_shift( $parsed );

					// Se ele está setado, então o obtem
					if ( isset( $json[$next] ) ) 
					{ $json = $json[$next]; } 
					else 
					{ return false; }
			}
			
			return isset ( $json );
		}

		return false;
	}
			
	/**
	 * Obtem um parâmetro dentro dos dados do cookie baseado em padrão.
	 * 
	 *      Padrão: categoria.{elemento 1...N}
	 * 
	 * @param string $key Parâmetro em Padrão.
	 * @return string Valor do Parâmetro.
	 * @access public
	 * @since 1.0.0
	 */
	public function get( $key )
	{                
		$this->load();
		
		$parsed = explode( '.', $key );
					
		if ( !empty ( $this->cookie ) )
		{
			$json = $this->cookie;
			
			while ( $parsed ) 
			{
					$next = array_shift( $parsed );

					if ( isset( $json[$next] ) ) 
					{ $json = $json[$next]; } 
					else 
					{ return null; }
			}

			return $json;
		}
		
		return null;
	}
	
	/**
	 * Insere um parâmetro dentro dos dados do cookie baseado em padrão.
	 *      
	 *      Padrão: categoria.{elemento 1...N}
	 * 
	 * Os valores não serão salvos no cookie até que o método save()
	 * seja chamado.
	 * 
	 * @param string $key Parâmetro em Padrão.
	 * @param string $value Valor do Parâmetro.
	 * @access public
	 * @since 1.0.0
	 */
	public function put( $key, $value )
	{
		if ( !$this->editing )
		{
			$this->load();
			$this->editing = true;
		}
		
		$parsed = explode( '.', $key );
		$cookie =& $this->cookie;
		
		while ( count( $parsed ) > 1 ) 
		{
			$next = array_shift( $parsed );
			
			if ( !isset( $cookie[$next] ) || !is_array( $cookie[$next] ) ) 
			{ $cookie[$next] = []; }
			
			$cookie =& $cookie[$next];
		}
		
		$cookie[array_shift($parsed)] = filter_var ( $value, FILTER_SANITIZE_STRING );
	}
	
	/**
	 * Remove um parâmetro dentro dos dados do cookie baseado em padrão.
	 *      
	 *      Padrão: categoria.{elemento 1...N}
	 * 
	 * Os valores não serão salvos no cookie até que o método save()
	 * seja chamado.
	 * 
	 * @param string $key Parâmetro em Padrão.
	 * @return void
	 * @access public
	 * @since 1.0.0
	 */
	public function remove ( $key )
	{
		if ( !$this->editing )
		{
			$this->load();
			$this->editing = true;
		}
		
		$parsed = explode( '.', $key );
		$cookie =& $this->cookie;
		
		while ( count( $parsed ) > 1 ) 
		{
			$next = array_shift( $parsed );
			
			// Se está setado, então coloca o ponteiro na array
			if ( isset( $cookie[$next] ) ) 
			{ $cookie =& $cookie[$next]; }
			else
			{ return; }
		}
		
		unset ( $cookie[array_shift($parsed)] );
	}
	
	/**
	 * Inicia o cookie capturando todos os dados salvos e setando na
	 * array local. Se nenhum dado for encontrado, a array local é setada
	 * como null.
	 * 
	 * @return void
	 * @access private
	 * @since 1.0.0
	 */
	private function load ()
	{                
		$json = json_decode ( filter_input( INPUT_COOKIE, $this->name ) , true );
		
		if ( !empty ( $json ) )
		{
			$json = filter_var_array( $json, FILTER_SANITIZE_STRING );            
			$this->cookie = $json;
			return;
		}
		
		$this->cookie = array();
	}
	
	/**
	 * Salva o cookie em JSON encodado.
	 * 
	 * @param int $days Quantidade de dias para expirar.
	 * @return boolean
	 * @access public
	 * @since 1.0.0
	 */
	public function save ( $days = 15 )
	{
		$this->editing = false;
		
		$expires = new \DateTime('+'.$days.' days');
		$json    = json_encode( $this->cookie );
		
		$server_host = filter_input ( INPUT_SERVER , 'SERVER_NAME', FILTER_SANITIZE_STRING );
		$domain      = ($server_host !== 'localhost') ? $server_host : false;
			
		return setcookie( $this->name, $json, $expires->format('U'), '/', $domain, HTTPS, true );
	}
	
	/**
	 * Limpa completamente o cookie, quando necessário.
	 * 
	 * @return boolean
	 * @access public
	 * @since 1.0.0
	 */
	public function clear ()
	{        
		$server_host = filter_input ( INPUT_SERVER , 'SERVER_NAME', FILTER_SANITIZE_STRING );
		$domain      = ($server_host !== 'localhost') ? $server_host : false;
			
		return setcookie( $this->name, '', time() - 42000, '/', $domain, HTTPS, true ); 
	}
	
	/**
	 * Obtem o nome do Cookie formatado.
	 * 
	 * @param string $cookie_name Nome do cookie para carregar.
	 * @return string
	 * @access private
	 * @since 1.0.0
	 */
	private function getCookieName ( $cookie_name )
	{ return SESSION_NAME.'_'.$cookie_name; }
}

