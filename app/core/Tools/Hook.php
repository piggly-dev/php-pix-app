<?php
namespace App\Core\Tools;

use InvalidArgumentException;

/** 
 * Responsável por controlar os hooks a serem executados quando uma TAG
 * específica for chamada. Ele opera tanto no formato de função quanto no formato
 * de função dentro de um determinado objeto passado por referência.
 * 
 * A execução é bem simples. Suponhamos que exista uma função test() para
 * adicioná-la dentro de uma TAG fazemos o seguinte procedimento:
 * 
 *      Hook::bind( 'header', Hook::generateCallbackFunction( 'test' ) );
 * 
 * Agora, supondo a função testParams( $param1, $param2, ... ) para
 * adicioná-la dentro de uma TAG fazemos o seguinte procedimento:
 * 
 *      Hook::bind( 'header', Hook::generateCallbackFunction( 'testParams', $param1, $param2, ... ) );
 * 
 * Por fim, supondo que tenhamos um objeto Test com a função start() para
 * adicioná-lo dentro de uma TAG fazemos o seguinte procedimento:
 * 
 *      $test = new Test();
 *      Hook::bind( 'header', Hook::generateCallbackObject( $test, 'start' ) );
 * 
 *      static Session...
 *      Hook::bind( 'header', Hook::generateCallbackStaticObject( 'Session', 'start' ) );
 * 
 * Com parâmetros na função do objeto:
 * 
 *      $test = new Test();
 *      Hook::bind( 'header', Hook::generateCallbackObject( $test, 'start', $param1, $param2, ... ) );
 * 
 *      static Session...
 *      Hook::bind( 'header', Hook::generateCallbackStaticObject( 'Session', 'start', $param1, $param2, ... ) );
 * 
 * Para finalizar, basta executar o hook e todos os callbacks serão executados,
 * conforme uma ordem de prioridade.
 * 
 *      Hook::run( 'header' );
 * 
 * @package \App\Core\Tools
 * @author Caique M Araujo <caique@piggly.com.br>
 * @version 1.0.0
 */
class Hook
{
	/**
	 * @var array Chamados para serem realizados.
	 * @access private
	 * @since 1.0.0
	 */
	private static $callables = [];
	
	/**
	 * @var int Hooks anexados para execução. 
	 * @access private
	 * @since 1.0.0
	 */
	private static $hooksAttached = 0;
	
	/**
	 * @var array Array com os erros que aconteceram durante a execução. 
	 * @access private
	 * @since 1.0.0
	 */
	public static $errors;
	
	const CALL_OBJECT = 0;
	const CALL_SOBJECT = 1;
	const CALL_FUNCTION = 2;
	
	/**
	 * Metodo padrão para criação de um callback, existem três tipos de criação:
	 * 
	 *      CALL_OBJECT     0       Cria um callback com um objeto instanciado.
	 *          $obj        object  Instância do objeto.
	 *          $function   string  Método a ser executado.
	 *          $params...  mixed   Parâmetros a serem enviados para o método.
	 * 
	 *      CALL_SOBJECT    1       Cria um callback com um objeto estático.
	 *          $obj        string  Nome do objeto.
	 *          $function   string  Método a ser executado.
	 *          $params...  mixed   Parâmetros a serem enviados para o método.
	 * 
	 *      CALL_FUNCTION   2       Cria um callback com uma função.
	 *          $function   string  Função a ser executada.
	 *          $params...  mixed   Parâmetros a serem enviados para o método.
	 * 
	 * @param int $type Tipo do Callback.
	 * @param array $params Parâmetros ou argumentos a serem capturados.
	 * @return array Callback preenchido.
	 * @throws InvalidArgumentException Alguns dos argumentos enviados são inválidos.
	 * @access public
	 * @since 1.0.0
	 */
	public static function generateCallback ( $type, $params = array() )
	{
		// Se o tipo for inválido, encerra
		if ( !is_int( $type ) )
		{ throw new InvalidArgumentException('O tipo de callback "'.$type.'" é inválido.'); }
		
		// Array para gerar o callback
		$callback = array();
		
		// Se os parâmetros enviados não é um array, então captura os argumentos
		if ( !is_array( $params ) )
		{ $callback['params'] = func_get_args(); }
		else
		{ $callback['params'] = $params; }
		
		if ( $type === self::CALL_OBJECT )
		{            
			// Se a quantidade de parâmetros não é suficiente...
			if ( count( $callback['params'] ) < 2 )
			{ throw new InvalidArgumentException('A quantidade de parâmetros para o callback de objeto é inválida.'); }

			// Obtém o objeto
			$callback['obj'] = $callback['params'][0];

			// Verifica se o objeto é válido
			if ( !is_object( $callback['obj'] ) )
			{ throw new InvalidArgumentException('O objeto enviado não é um objeto válido.'); }

			// Obtém a função
			$callback['function'] = $callback['params'][1];

			// Verifica se o método existe no objeto
			if ( !method_exists( $callback['obj'], $callback['function'] ) )
			{ throw new InvalidArgumentException('O método "'.$callback['function'].'" não existe no objeto.'); }

			// Remove o parâmetro $obj
			array_shift($callback['params']);
			// Remove o parâmetro $function
			array_shift($callback['params']);
		}
		else if ( $type === self::CALL_SOBJECT )
		{            
			// Se a quantidade de parâmetros não é suficiente...
			if ( count( $callback['params'] ) < 2 )
			{ throw new InvalidArgumentException('A quantidade de parâmetros para o callback de objeto estático é inválida.'); }

			// Obtém o objeto
			$callback['obj'] = $callback['params'][0];

			// Verifica se o objeto é válido
			if ( !class_exists( $callback['obj'] ) )
			{ throw new InvalidArgumentException('O objeto "'.$callback['obj'].'" enviado não é um objeto válido.'); }

			// Obtém a função
			$callback['function'] = $callback['params'][1];

			// Verifica se o método existe no objeto
			if ( !method_exists( $callback['obj'], $callback['function'] ) )
			{ throw new InvalidArgumentException('O método "'.$callback['function'].'" não existe no objeto "'.$callback['obj'].'".'); }

			// Remove o parâmetro $obj
			array_shift($callback['params']);
			// Remove o parâmetro $function
			array_shift($callback['params']);
		}
		else if ( $type === self::CALL_FUNCTION )
		{            
			// Se a quantidade de parâmetros não é suficiente...
			if ( count( $callback['params'] ) < 1 )
			{ throw new InvalidArgumentException('A quantidade de parâmetros para o callback de função é inválida.'); }

			// Obtém o objeto
			$callback['function'] = $callback['params'][0];

			// Verifica se a função é válido
			if ( !function_exists( $callback['function'] ) )
			{ throw new InvalidArgumentException('A função "'.$callback['function'].'" é inválida.'); }
			
			// Remove o parâmetro $function
			array_shift($callback['params']);
		}
		else
		{ throw new InvalidArgumentException('O tipo de callback "'.$type.'" é inválido.'); }
		
		return $callback;
	}
	
	/**
	 * Cria um callback com objeto para realizar determinada função.
	 * 
	 * @param Object $obj Objeto onde está a função.
	 * @param string $function Função a ser executada.
	 * @param array $params Parâmetros para enviar na função.
	 * @return array Callback preenchido.
	 * @access public
	 * @since 1.0.0
	 */
	public static function generateCallbackObject ( &$obj, $function, $params = array() )
	{
		$callback = array();
		
		$callback['obj']      = $obj;
		$callback['function'] = $function;
		
		// Se os parâmetros existem, então captura todos enviados na função
		// removendo os dois primeros parâmetros representando $obj e $function
		if ( !is_array( $params ) )
		{
			$callback['params'] = func_get_args();
			// Remove o parâmetro $obj
			array_shift($callback['params']);
			// Remove o parâmetro $function
			array_shift($callback['params']);
		}
		else
		{ $callback['params'] = $params; }
		
		return $callback;
	}
	
	/**
	 * Cria um callback com objeto estático para realizar determinada função.
	 * 
	 * @param Object $obj Objeto onde está a função.
	 * @param string $function Função a ser executada.
	 * @param array $params Parâmetros para enviar na função.
	 * @return array Callback preenchido.
	 * @access public
	 * @since 1.0.0
	 */
	public static function generateCallbackStaticObject ( $obj, $function, $params = array() )
	{
		$callback = array();
		
		$callback['obj']      = $obj;
		$callback['function'] = $function;
		
		// Se os parâmetros existem, então captura todos enviados na função
		// removendo os dois primeros parâmetros representando $obj e $function
		if ( !is_array( $params ) )
		{
			$callback['params'] = func_get_args();
			// Remove o parâmetro $obj
			array_shift($callback['params']);
			// Remove o parâmetro $function
			array_shift($callback['params']);
		}
		else
		{ $callback['params'] = $params; }
		
		return $callback;
	}
	
	/**
	 * Cria um callback para realizar determinada função. Sem objeto.
	 * 
	 * @param string $function Função a ser executada.
	 * @param array $params Parâmetros para enviar na função.
	 * @return array Callback preenchido.
	 * @access public
	 * @since 1.0.0
	 */
	public static function generateCallbackFunction ( $function, $params = array() )
	{
		$callback = array();
		
		$callback['function'] = $function;
		
		// Se os parâmetros existe, então captura todos enviados na função
		// removendo o primero parâmetro representando $function
		if ( !is_array( $params ) )
		{
			$callback['params'] = func_get_args();
			// Remove o parâmetro $function
			array_shift( $callback['params'] );
		}
		else
		{ $callback['params'] = $params; }
		
		return $callback;
	}
	
	/**
	 * Adiciona um novo callback para se executado quanto a TAG for acionada.
	 * 
	 * O callback sempre representa uma array contendo um objeto, uma função
	 * e os parâmetros. O único termo obrigatório dentro do callback é a função.
	 * 
	 * Conforme a prioridade, o callback será executado.
	 * 
	 * @param string $tag TAG do Hook a ser adicionado.
	 * @param array $callback Callback para adicionar no hook.
	 * @param int $priority Prioridade de execução do callback.
	 * @return void
	 * @access public
	 * @since 1.0.0
	 */
	public static function bind ( $tag, $callback, $priority = 10 )
	{
		// Gera o ID único para o callback a ser adicionado
		$idx = self::generateID( $tag, $priority );
		
		// Se a TAG ainda não foi setada, seta...
		if ( !isset ( self::$callables[$tag] ) )
		{ self::$callables[$tag] = []; }

		// Se a PRIORIDADE ainda não foi setada, seta...
		if ( !isset ( self::$callables[$tag][$priority] ) )
		{ self::$callables[$tag][$priority] = []; }

		// Adiciona o ID gerado conforme a TAG e a PRIORIDADE
		self::$callables[$tag][$priority][$idx] = $callback;
	}
	
	/**
	 * Executa as funções presentes em um TAG conforme uma ordem de prioridade.
	 * 
	 * @param string $tag TAG do Hook a ser executado.
	 * @return boolean TRUE quando todos os callbacks foram executados com sucesso, FALSE quando não.
	 *                 Todos os callback com erros, serão setados na array de erros.
	 * @access public
	 * @since 1.0.0
	 */
	public static function run ( $tag )
	{
		self::$errors = array();
		// Obtem o hook a ser executado, ordenado pelas chaves de prioridade 
		$hook = isset ( self::$callables[$tag] ) ? self::sortHooks($tag) : null;
		
		if ( !is_null( $hook ) )
		{
			// Para cada uma das prioridades, coleta os ID do callback
			foreach ( $hook as $priority => $idx )
			{
					// Para cada ID existente na prioridade, executa um callback
					foreach ( $idx as $id => $cb )
					{ 
						// Verifica se o callback é um array válido com função e parâmetros
						if ( !is_array( $cb ) || !isset ( $cb['function'], $cb['params'] ) )
						{ 
							self::$errors[] = 'O Callback '.$id.' não é um array válido.'; 
							continue;
						}
						
						// Captura a função
						$function = $cb['function'];
						// Captura os parâmetro
						$params   = $cb['params'];

						// Se existe um objeto no callback, substitue a função
						if ( isset ( $cb['obj'] ) )
						{                        
							$function = array( $cb['obj'], $cb['function'] ); 
							
							// Se o objeto setado, não for um objeto referência
							// por tanto, é estático
							if ( !is_object( $cb['obj'] ) )
							{
									// Verfica se o objeto existe
									if ( !class_exists( $cb['obj'] ) )
									{ 
										self::$errors[] = 'O objeto "'.$cb['obj'].'" no Callback '.$id.' não existe.'; 
										continue;
									}
							}
							
							// Verifica se a função existe no objeto
							if ( !method_exists( $cb['obj'], $cb['function'] ) )
							{ 
									self::$errors[] = 'O método "'.get_class($cb['obj']).'->'.$cb['function'].'" no Callback '.$id.' não existe.'; 
									continue;
							}
						}
						else
						{
							// Verifica se a função existe
							if ( !function_exists( $cb['function'] ) )
							{ 
									self::$errors[] = 'A função "'.$cb['function'].'" no Callback '.$id.' não existe.'; 
									continue;
							}
						}
						
						// Executa a função
						\call_user_func_array( $function, $params ); 
					}
			}
		}
				
		// Retorna se houve ou não erro
		return count( self::$errors ) === 0;
	}
	
	/**
	 * Gera um ID único para o callback a ser adicionado.
	 * 
	 * @param string $tag TAG do Hook a ser adicionado.
	 * @param int $priority Prioridade de execução do callback.
	 * @return string ID único para o callback.
	 * @access public
	 * @since 1.0.0
	 */
	private static function generateID ( $tag, $priority )
	{
		self::$hooksAttached++;
		return $tag . ':' . $priority . self::$hooksAttached;
	}
	
	/**
	 * Ordena as prioridades de uma TAG específica, caso a TAG não seja setada,
	 * então ordena todas.
	 * 
	 * @param string $tag TAG do Hook a ser ordenado.
	 * @return array Array de chamadas ordenadas.
	 * @access public
	 * @since 1.0.0
	 */
	private static function sortHooks ( $tag = null )
	{
		if ( $tag === null )
		{
			foreach ( self::$callables as $key => $value )
			{ self::sortHooks($key); }
		}
		else
		{
			if ( isset( self::$callables[$tag] ) )
			{ ksort ( self::$callables[$tag] ); }
			else
			{ return array(); }
			
			return self::$callables[$tag];
		}
	}
}