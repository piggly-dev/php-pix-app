<?php
namespace App\Core\Tools;

/**
 * O URLBuilder é responsável por montar a URL de acordo com o acesso realizado.
 * Ao iniciar a classe é preciso setar todas as regras de URL da aplicação.
 * Todas serão validadas através da função preg_match com expressões regulares.
 * 
 * Automáticamente, em todas as regras, serão inseridas as instruções de inicio
 * e fim da string, para limitar URL não compatível com as regras: /^{rule}$/i
 * 
 * Ao adicionar uma rota também é possível definir qual será o controlador
 * e o método a serem acessados pela regra definida.
 * 
 * Os parâmetros capturados serão os grupos da expressão regular. 
 * 
 * Assim que as rotas forem adicionadas em addRoutes() executar o build()
 * para construir a URL que foi executada.
 * 
 * @author Caique M Araujo <caique@piggly.com.br>
 * @package \App\Core\Tools
 * @version 1.0.0
 */
class UrlBuilder
{
	/**
	 * @var array Rotas com as regras a serem validadas.
	 * @access private
	 * @since 1.0.0
	 */
	private static $routes = array();
		
	/**
	 * Constrói a URL seguindos as instruções de regras definidas. Caso a URL
	 * seja compatível, então define um callback contendo os parâmetros, o controlador
	 * e o método a ser chamado. Conforme aplicável.
	 * 
	 * Os grupos setados na expressão regular serão retornados como parâmetros.
	 * 
	 * @return mixed Array quando o Callback existe, ou null quando não.
	 * @param string $filter Parte da string para retirar do url.
	 * @access public
	 * @since 1.0.0
	 */
	public static function build ( $filter = null )
	{
		$query   = filter_input( INPUT_GET, 'url', FILTER_SANITIZE_STRING );
		$urls    = trim ( $query, '/' );
		
		if ( !is_null ( $filter ) )
		{ 
			$filter = trim ( $filter, '/' ) . '/';
			$urls   = str_replace( $filter, '', $urls ); 
		}
		// Se não existe nenhuma query de URL, então, consideramos
		// que é a homepage
		if ( !isset ( $query ) )
		{ return self::buildHomepage(); }
		
		foreach ( self::$routes as &$rule )
		{
			if ( preg_match( $rule['rule'], $urls, $matches ) )
			{ 
					$callback = $rule;
					unset( $callback['rule'] );
					self::ajustMatches ( $matches, $callback );
					return $callback;
			}
		}
		
		return null;
	}
	
	/**
	 * Cria uma rota para validar as URLs do sistema.
	 * 
	 * A regra de uma rota é utilizando expressões regulares com a URL
	 * a ser validada. Qualquer informação incompatível será ignorada
	 * e a rota não será aplicada aquela URL.
	 * 
	 * Se nenhum controlador for enviado, então o controlador padrão
	 * a ser chamado será o \Piggly\Controllers\Pages.
	 * 
	 * Se nenhum método for enviado, então o método padrão a ser chamado
	 * no controlador será o loader().
	 * 
	 * Os grupos na expressão regular serão capturados como parâmetros.
	 * 
	 * @param string $rule Expressão regular para validar a URL.
	 * @param string $controller Nome do controlador a ser iniciado.
	 * @param string $method Nome do método a ser chamado no controlador.
	 * @param array $options Envia alguma opções adicionais para o callback.
	 *                          block_start Bloqueia a expressão regular no início com ^.
	 *                              Por padrão, true.
	 *                          block_end   Bloqueia a expressão regular no final com $.
	 *                              Por padrão, true.
	 * @return void
	 * @access public
	 * @since 1.0.0
	 */
	public static function addRoute ( $rule, $controller = null, $method = null, $options = array() )
	{ self::$routes[] = self::buildConfig( $rule, $controller, $method, $options ); }
	
	/**
	 * Limpa todas as rotas que foram setadas.
	 * 
	 * @return void
	 * @access public
	 * @since 1.0.0
	 */
	public static function cleanRoutes ()
	{ self::$routes = array(); }
	
	/**
	 * Formata o resultado encontrado pela função preg_match, eliminando
	 * a string completa e definindo os parâmetros.
	 * 
	 * @param array $matches Resultado da função preg_match
	 * @param array $callback Callback para configurar
	 * @return void
	 * @access private
	 * @since 1.0.0
	 */
	private static function ajustMatches ( &$matches, &$callback )
	{
		$callback['url'] = $matches[0];
		unset( $matches[0] );
		$callback['params'] =& $matches;
	}
			
	/**
	 * Constrói a configuração, uma array com a regra, o nome do controlador e o
	 * nome do método para executar assim que necessário.
	 * 
	 * Se nenhum controlador for enviado, então o controlador padrão
	 * a ser chamado será o \Piggly\Controllers\Pages.
	 * 
	 * Se nenhum método for enviado, então o método padrão a ser chamado
	 * no controlador será o loader().
	 * 
	 * @param string $rule Expressão regular para validar a URL.
	 * @param string $controller Nome do controlador a ser iniciado.
	 * @param string $method Nome do método a ser chamado no controlador.
	 * @param array $options Envia alguma opções adicionais para o callback.
	 *                          block_start Bloqueia a expressão regular no início com ^.
	 *                              Por padrão, true.
	 *                          block_end   Bloqueia a expressão regular no final com $.
	 *                              Por padrão, true.
	 * @return array Configuração completa.
	 * @access private
	 * @since 1.0.0
	 */
	private static function buildConfig ( $rule, $controller = null, $method = null, $options = array() )
	{
		$config = array();
		
		// Normaliza a array de opções com os valores padrões
		$options = array_merge( array ( 'block_start' => true, 'block_end' => true ), $options );
		
		// Começa a criação da regra
		$config['rule'] = '/';
		
		// Se a expressão regular está bloqueada no início, adiciona ^
		if ( isset ( $options['block_start'] ) && $options['block_start'] )
		{ $config['rule'] .= '^'; }
		
		// Coleta a expressão regular
		$config['rule'] .= trim ( $rule, '/' );
		
		// Se a expressão regular está bloqueada no final, adiciona $
		if ( isset ( $options['block_end'] ) && $options['block_end'] )
		{ $config['rule'] .= '$'; }
		
		// Encerra a expressão regular com a bandeira para ser insensitiva
		$config['rule'] .= '/i';
		
		// Obtém o controlador
		$config['controller'] = !is_null ( $controller ) ? $controller : 'Pages';
		
		// Obtém o método
		if ( !is_null( $method ) )
		{ $config['method'] = $method; }
		
		// Envia as configurações
		if ( isset ( $options ) )
		{ $config['options'] = $options; }
		
		return $config;
	}
	
	/**
	 * Constrói um callback para a página principal. Somente contendo o nome
	 * do controlador e nenhum parâmetro setado.
	 * 
	 * @return array Callback configurado.
	 * @access private
	 * @since 1.0.0
	 */
	private static function buildHomepage ()
	{
		$callback               = array();
		$callback['url']        = '';
		$callback['controller'] = 'Pages';
		
		return $callback;
	}
}