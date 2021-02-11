<?php
use App\Core\Tools\Hook as Hook;

/**
 * Coloque aqui todas as funções locais do projeto.
 */
if ( boolval($_ENV['BOOTSTRAP_CDN']) )
{
	Hook::bind( 'header', 
		Hook::generateCallbackFunction
		(
			'importExternalCSS', 
			['https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta2/dist/css/bootstrap.min.css']
		)
	);
}
else 
{
	Hook::bind( 'header', 
		Hook::generateCallbackFunction
		(
			'importCss', 
			'assets/css',
			'bootstrap.min'
		)
	);
}