<?php
namespace App\Core;

use App\Core\Tools\URLBuilder as URLBuilder;
use App\Core\Controllers\Pages as Pages;

/** 
 * Classe principal da aplicação.
 * 
 * Gerencia os controladores e métodos para chamar, tal como os parâmetros para enviar.
 * Abstrai as informações necessárias para carregar, da URL.
 * 
 * @author Caique M Araujo <caique@piggly.com.br>
 * @package \App\Core\Main
 * @version 1.0.0
 */
class Main
{            
	/**
	 * Construtor da classe que obtém os valores advindos da URL.
	 * Então configura o objeto controlador a ser chamado.
	 * 
	 * @param string $controller_base Base do namespace de controladores.
	 * @return void
	 * @access public
	 * @since 1.0.0
	 */
	public function __construct( $controller_base = 'App\Core\Controllers' ) 
	{
		// Constrói a URL e obtém o callback
		$callback = URLBuilder::build();
		
		// Se não obteve um callback, então a página não existe
		if ( is_null ( $callback ) )
		{
			$this->notFound();
			return;
		}

		// Se não foi setado nenhum parâmetro então
		// chama a página inicial
		if ( !isset ( $callback['params'] ) )
		{
			$this->controller = new Pages();
			$this->controller->homepage();
			return;
		}
		
		// Nome do controlador a ser instanciado
		$controller_name = '\\' 
									. trim ( $controller_base, '\\' ) 
									. '\\' 
									. $callback['controller'];
		
		// Se o controlador não existe, então a página não existe
		if ( !class_exists( $controller_name ) )
		{
			$this->notFound();
			return;
		}
		
		// Cria uma nova instância do controlador, enviando os parâmetros
		$this->controller = new $controller_name( $callback['url'], $callback['params'] );

		// Valida se existe ou não um método a ser chamado dentro do
		// callback.
		if ( !isset ( $callback['method'] ) )
		{
			// Quando existem parâmetros, tenta encontrar o método
			// compatível com o nome do primeiro parâmetro
			if ( !empty ( $callback['params'] ) )
			{
					$method = filterUrlToMethod ( $callback['params'][0] );

					if ( method_exists( $this->controller, $method ) )
					{
						$this->controller->{$method}();
						return;
					}
			}
			
			// Do contrário, então carrega o método padrão loader
			if ( method_exists( $this->controller, 'loader' ) )
			{
					$this->controller->loader();
					return;
			}
		}
		else
		{
			// Verifica se o método existe e executa
			if ( method_exists( $this->controller, filterUrlToMethod ( $callback['url'] ) ) )
			{
					$this->controller->{filterUrlToMethod ( $callback['url'] )}();
					return;
			}
			
			// Verifica se o método existe e executa
			if ( method_exists( $this->controller, $callback['method'] ) )
			{
					$this->controller->{$callback['method']}();
					return;
			}
		}
			
		// Se não encontrou nada, executa o erro de página não encontrada.
		$this->notFound();
		return;
	}
			
	/**
	 * Solicita a página 404 do controlador padrão de páginas.
	 * 
	 * @access private
	 * @since 1.0.0
	 */
	private function notFound ()
	{
		$this->controller = new Pages();
		$this->controller->notFound();
		return;
	}
}
