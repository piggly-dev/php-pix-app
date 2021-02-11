<?php
use App\Core\Tools\Hook;
use Piggly\Pix\Payload as PixPayload;

Hook::bind( 'header', 
	Hook::generateCallbackFunction
	(
		'importCss', 
		[
			'assets/css',
			'signin.css'
		]
	)
);

$accounts = include ( ABSPATH.'/config/accounts.php' );

$inputs = [ 
	'account', 
	'amount', 
	'tid', 
	'description' 
];

$required = [
	'merchantName',
	'merchantCity',
	'key',
	'keyType'
];

$output = [];
$pix    = new PixPayload();

foreach ( $inputs as $input )
{ 
	$value = filter_input( INPUT_POST, $input, FILTER_SANITIZE_STRING );
	
	if ( !empty( $value ) )
	{ $output[$input] = $value; }
}

if ( !empty($output) ) 
{
	$account = $accounts[$output['account']];
	
	$allSet = true;

	if ( count( array_intersect($required,array_keys($account)) ) !== count($required) )
	{ $allSet = false; }

	if ( $allSet )
	{
		foreach ( $account as $key => $value )
		{
			if ( in_array($key, $required) )
			{ 
				if ( empty($value) )
				{
					$allSet = false;
					break;
				}
			}
		}
	}	

	if ( $allSet )
	{
		try
		{
		$pix
			->setPixKey($account['keyType'], $account['key'])
			->setMerchantName($account['merchantName'])
			->setMerchantCity($account['merchantCity'])
			->setAmount($output['amount'])
			->setTid($output['tid'])
			->setDescription($output['description'])
			->setAsReusable(false);
		}
		catch ( \Exception $e )
		{ $allSet = false; }
	}
}
else 
{ redirect('/create'); }

?>

<?php $this->getTemplate( 'header' ); ?>
<div class="container mb-5" style="max-width: 420px;">
	<main>
		<div class="py-5 text-center">
		<img class="d-block mx-auto mb-4" style="width: 50px;" src="<?=getUrl("assets/svg/logo.svg")?>">
		<h2>Código Pix</h2>
		</div>

		<?php if ( !$allSet ) : ?>
		<div class="py-5 text-center">
			<p class="lead">A sua conta <?=sprintf('`<code>%s</code>`', $account['label']);?> está mal configurada.</p>
		</div>
		<?php else : ?>
		<div class="row mb-5">
			<div class="col order-md-last text-center">
				<ul class="list-group mb-3">
					<li class="list-group-item d-flex justify-content-between lh-sm">
						<div><h6 class="my-0">Valor</h6></div>
						<span class="text-muted">R$ <?=$output['amount']?></span>
					</li>
					<li class="list-group-item d-flex justify-content-between lh-sm">
						<div><h6 class="my-0">Identificador</h6></div>
						<span class="text-muted"><?=$output['tid']?></span>
					</li>
				</ul>
				
				<img style="margin:12px auto" src="<?=$pix->getQRCode(PixPayload::OUTPUT_PNG);?>" alt="QR Code de Pagamento" />
				<div style="display: table"></div>
				<p>Compartilhe o código abaixo para enviar o Pix:</p>
				<input type="text" id="piggly_pix" class="form-control-sm mb-1" style="width: 100%; border: none; background: #CCC;" name="pix" value="<?=$pix->getPixCode();?>" readonly/>
				<button type="button" class="w-100 btn btn-primary btn-sm" onclick="pigglyCopyPix();">Copiar Pix</button>
			</div>
		</div>
		<?php endif; ?>
		<a href="/create" class="w-100 btn btn-primary btn-lg">Gerar Pix</a>
	</main>
</div>

<script>
	function pigglyCopyPix() {
		/* Get the text field */
		var copyText = document.getElementById("piggly_pix");

		/* Select the text field */
		copyText.select();
		copyText.setSelectionRange(0, 99999); /* For mobile devices */

		/* Copy the text inside the text field */
		document.execCommand("copy");
	}
</script>
<?php $this->getTemplate( 'footer' ); ?>