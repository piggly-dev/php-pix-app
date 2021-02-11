<?php
namespace App\Core\Managers;

/** 
 * Reponsavel por controlar todos os templates para carregar a view corretamente.
 * Seta as meta-tag base da página.
 * Chama o cabeçalho, a view da página e o footer.
 * Adiciona os arquivos css e javascript para cada página.
 * Configura o estilo padrão da view.
 * Obtém o template da página 404 se necessário.
 * 
 * @package \App\Core\Managers
 * @author Caique M Araujo <caique@piggly.com.br>
 * @version 1.0.0
 */
class Template extends Base
{                	
	/**
	 * @var string Urls de acesso. Por padrão {main}, {list} e {ajax}.
	 * @access public
	 * @since 1.0.0
	 */
	public $urls = array();
					
	/**
	 * @var array Modelos a serem carregados.
	 * @access private 
	 * @since 1.0.0
	 */
	private $models = array();
	
	/**
	 * Ao construir o Template, iniciamos sem uma página setada.
	 *  
	 * @param \Piggly\Controllers\Base $controller Controlador pai.
	 * @return void
	 * @access public 
	 * @since 1.0.0
	 */
	public function __construct( &$controller = null ) 
	{ 
		parent::__construct( $controller );
		
		$this->addUrl( 'main', $this->controller->base_url );
		$this->addUrl( 'ajax', 'ajax' );
	}
			
	/**
	 * Requere um arquivo de template. Quando nenhum nome é enviado como
	 * parâmetro então faz a captura do template da página carregada
	 * do banco de dados.
	 * 
	 * Se não tiver nem o parâmetro, nem a página carregada, simplesmente
	 * deixa de executar.
	 * 
	 * @param string $file Nome do arquivo com pasta, se aplicável, dentro dos
	 *                     templates.
	 * @return void
	 * @access public 
	 * @since 1.0.0
	 */
	public function getTemplate ( $file = null )
	{
		$file = TEMPLATE_PATH . $file . '.php';

		if ( !file_exists ( $file ) )
		{ return $this->controller->notFound(); }
		
		require ( $file );
	}	
		
	/**
	 * Seta uma URL de destino.
	 * 
	 * @param string $name Nome da URL.
	 * @param string $url URL de destino.
	 * @return void
	 * @access public
	 * @since 1.0.0
	 */
	public function addUrl ( $name, $url = '' )
	{ 
		$url = trim( $url, '/' );
		$this->urls[$name] = HOME_URI . $url; 
	}
	
	/**
	 * Retorna uma URL de destino.
	 * 
	 * @param type $name Nome da URL.
	 * @return string URL específica quando existir, ou página inicial.
	 * @access public
	 * @since 1.0.0
	 */
	public function getUrl ( $name )
	{ return isset( $this->urls[$name] ) ? $this->urls[$name] : HOME_URI; }
			
	/**
	 * Retorna se existe um usuário salvo no objeto.
	 * Ideal para controlar áreas exibidas apenas se o usuário está ativo.
	 * 
	 * @return boolean
	 * @access public
	 * @since 1.0.0
	 */
	public function hasUser ()
	{ return $this->controller->hasUser(); }
}