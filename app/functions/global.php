<?php

/**
 * Para adicionar funções locais de um projeto, utilize o arquivo:
 * functions/local.php
 */

/**
 * Jeito fácil de escrever URLs.
 * 
 * @author Caique M Araujo <caiqe@piggly.com.br>
 * @since 1.0.0 Versão Inicial.
 * @version 1.0.0
 * @param string $page Página para juntar com o HOME_PATH.
 * @access public
 */
function getUrl ( $page )
{ return HOME_URI . $page; }

/**
 * Faz a criptografia de um dado para descriptografar.
 * 
 * @param string $string String para criptografar ou descriptografar.
 * @param string $action 'e' para criptografar, 'd' para descriptografar.
 * @return mixed String quando válido, FALSE quando não.
 * @access public
 * @author Caique M Araujo <caiqe@piggly.com.br>
 * @version 1.0.0
 */
function icrypt ( $string, $action = 'e' ) 
{
	$method = "AES-256-CBC";
	$skey   = hash ( 'sha512', SECRET_KEY );
	
	if ( $action === 'e' )
	{
		$siv        = substr( hash( 'sha256', uniqid( openssl_random_pseudo_bytes(16) , true ) ), 0, 16 );
		$ciphertext = base64_encode( openssl_encrypt( $string, $method, $skey, 0, $siv ) );
		$hash       = substr( hash( 'sha512', $ciphertext.$skey ), 0, 32 );
		
		return $siv . $hash . $ciphertext;
	}
	else if ( $action === 'd' )
	{
		$siv        = substr( $string, 0, 16 );
		$hash       = substr( $string, 16, 32 );
		$ciphertext = substr( $string, 48 );
		$n_hash     = substr( hash( 'sha512', $ciphertext.$skey ), 0, 32 );
		
		if ( $n_hash !== $hash ) 
		{ return null; }
		
		return openssl_decrypt( base64_decode ( $ciphertext ), $method, $skey, 0, $siv );
	}
	
	return false;
}

/**
 * Checa se um valor existe dentro de um array a partir de uma chave.
 * Por exemplo: se você desejar saber se $json["name"] existe, chama a função.
 * Se existe irá retornar o valor para $json["name"], do contrário... irá retornar
 * null
 * 
 * @param array $array Array.
 * @param string|int $key Chave da Array ou indíce.
 * @return string|boolean Valor da Array ou falso.
 * @access public
 * @author Caique M Araujo <caiqe@piggly.com.br>
 * @version 1.0.0
 */
function hasKey ( $array , $key )
{
	if ( isset ( $array[$key] ) && !empty ( $array[$key] ) )
	{ return $array[$key]; }

	return false;
}

/**
 * Remove caracteres inválidos da string para obter um nome válido.
 * Troca "-" por "" e remove todos caracteres, menos {a-z, A-Z, 0-9, _}.
 * 
 * @param string $value Valor a ser ajustado.
 * @return string|boolean Valor ou falso.
 * @access public
 * @author Caique M Araujo <caiqe@piggly.com.br>
 * @version 1.0.0
 */
function filterUrlToMethod ( $value )
{
	if ( $value !== false )
	{ return preg_replace ( "/[^a-z\-]/i", '', str_replace ( '-', '', $value ) ); } 

	return false;
}

/**
 * Cria uma string única com caracteres aleatórios.
 * 
 * @param int $lenght Tamanho da string.
 * @param boolean $cstrong Força da string.
 * @return string Em hash.
 * @access public
 * @author Caique M Araujo <caiqe@piggly.com.br>
 * @version 1.0.0
 */
function getToken ( $lenght = 24, $cstrong = true )
{
	$bytes = openssl_random_pseudo_bytes( $lenght, $cstrong );
	return bin2hex( $bytes );
}

/**
 * Verifica se a string de data enviada está expirada em relação a atual data.
 * 
 * @param string $expires 
 * @return boolean
 * @access public
 * @author Caique M Araujo <caiqe@piggly.com.br>
 * @version 1.0.0
 */
function isExpired ( $expires )
{
	if ( new \DateTime() > $expires )
	{ return true; }

	return false;
}

/**
* Cria uma identidade única para o usuário atual, baseado em seu navegador
* e em seu endereço de IP.
* 
* @return string
* @access public
* @author Caique M Araujo <caiqe@piggly.com.br>
* @version 1.0.0
*/
function fingerprint ()
{
	$user_agent  = filter_input( INPUT_SERVER, 'HTTP_USER_AGENT', FILTER_SANITIZE_STRING );
	$remote_addr = filter_input( INPUT_SERVER, 'REMOTE_ADDR', FILTER_SANITIZE_STRING );

	// Cria uma assinatura para a sessão
	$hash = md5 ( $user_agent . ( ip2long( $remote_addr ) & ip2long( '255.255.0.0' ) ) );

	// Retorna a assinatura
	return $hash;
}

/**
* Checa se a identidade do usuário é a mesma que ele estava utilizando.
* 
* @return string
* @access public
* @author Caique M Araujo <caiqe@piggly.com.br>
* @version 1.0.0
*/
function checkFingerprint ( $value )
{ return $value === fingerprint(); }

/**
* Redireciona para um determinado URL.
* 
* @param string $url Url, menos o domínio.
* @param string $status Código HTTP da página.
* @access public
* @author Caique M Araujo <caiqe@piggly.com.br>
* @version 1.0.0
*/
function redirect ( $url = '', $query = '', $status = 302 )
{
	header ( 'Location:'.HOME_URI.trim($url, '/').$query, true, $status );
	exit;
}

/**
 * Importa um arquivo CSS para a página.
 * 
 * @param string $library_folder Pasta onde o arquivo está /public/{folder}.
 * @param string $file_name Nome do arquivo sem a extensão {file}.css.
 * @access public
 * @author Caique M Araujo <caiqe@piggly.com.br>
 * @version 1.0.0
 */
function importCSS ( $library_folder, $file_name )
{ echo '<link href="',HOME_URI,$library_folder,'/',$file_name,'.css" rel="stylesheet" type="text/css"/>',"\n\t\t"; }

/**
 * Importa um arquivo CSS externo para a página.
 * 
 * @param string $urls URL do arquivo.
 * @access public
 * @author Caique M Araujo <caiqe@piggly.com.br>
 * @version 1.0.0
 */
function importExternalCSS ( $urls = array() )
{ 
	$urls  = func_get_args();
	
	if ( !empty( $urls ) )
	{
		foreach ( $urls as $item )
		{ echo '<link href="',$item,'" rel="stylesheet"/>',"\n\t\t"; }
	}
	
}

/**
 * Importa um arquivo Javascript para a página.
 *
 * @param string $library_folder Pasta onde o arquivo está /public/{folder}.
 * @param string $file_name Nome do arquivo sem a extensão {file}.js.
 * @access public
 * @author Caique M Araujo <caiqe@piggly.com.br>
 * @version 1.0.0
 */
function importJS ( $library_folder, $file_name )
{ echo '<script src="',HOME_URI,$library_folder,'/',$file_name,'.js" type="text/javascript"></script>',"\n\t\t"; }

/**
 * Importa um arquivo Javascript externo para a página.
 * 
 * @param string $urls URL do arquivo.
 * @access public
 * @author Caique M Araujo <caiqe@piggly.com.br>
 * @version 1.0.0
 */
function importExternalJS ( $urls = array() )
{
	$urls  = func_get_args();
	
	if ( !empty( $urls ) )
	{
		foreach ( $urls as $item )
		{ echo '<script src="',$item,'" type="text/javascript"></script>',"\n\t\t"; } 
	}
}