<?php
namespace App\Core\Managers;

use App\Core\Controllers\Base as BaseController;

/** 
 * Gerenciador padrão para armazenar o controlador.
 * 
 * @package \App\Core\Managers
 * @author Caique M Araujo <caique@piggly.com.br>
 * @version 1.0.0 
 */
class Base
{
	/**
	 * @var BaseController Controlador que instânciou o gerenciador.
	 * @access public
	 * @since 1.0.0
	 */ 
	public $controller;
		
	/**
	 * Vincula o controlador de origem ao gerente.
	 * 
	 * @param BaseController $controller Controlador pai.
	 * @access public
	 * @since 1.0.0
	 */
	public function __construct( &$controller = null ) 
	{ $this->controller = $controller; }
}