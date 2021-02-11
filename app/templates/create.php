<?php
use App\Core\Tools\Hook;
use App\Core\Tools\Session;

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
?>

<?php $this->getTemplate( 'header' ); ?>
<div class="container mb-5">
	<main>
		<div class="py-5 text-center">
			<img class="d-block mx-auto mb-4" style="width: 50px;" src="<?=getUrl("assets/svg/logo.svg")?>">
			<h2>Código Pix</h2>
			<p style="font-size: 14px; margin: 0;">Você está logado como <code>@<?=Session::get('user.name');?></code>, <a href="/logout">clique aqui</a> para sair</p>
			<p class="lead">Entre com os dados abaixo para gerar um código pix para pagamentos.</p>
		</div>

		<?php if ( empty($accounts) ) : ?>
		<div class="py-5 text-center">
			<p class="lead">Adicione uma conta Pix antes de continuar.</p>
		</div>
		<?php else : ?>
		<div class="row g-3">
			<div class="col">
				<h4 class="mb-3">Informações de Pagamento</h4>
				<form action="/pix" method="POST" class="needs-validation" novalidate>
					<div class="row g-3">
						<div class="col-12">
							<label for="account" class="form-label">Conta Pix</label>
							<select class="form-select" id="account" name="account" required>
								<?php
								$first = true;
								foreach ( $accounts as $id => $account )
								{
									if ( $first )
									{ echo sprintf('<option value="%s" selected>%s</option>', $id, $account['label']); $first = false; }
									else 
									{ echo sprintf('<option value="%s">%s</option>', $id, $account['label']); }
								}
								?>
							</select>
							<div class="invalid-feedback">Selecione uma conta válida.</div>
						</div>

						<div class="col-sm-6">
							<label for="amount" class="form-label">Valor do Pagamento</label>
							<input type="text" class="form-control" id="amount" name="amount" required>
							<div class="invalid-feedback">O valor do pagamento é obrigatório.</div>
							<div id="amountHelp" class="form-text">Ao preencher o valor utilize sempre a vírgula <code>,</code> e coloque o valor integral do pagamento.</div>
						</div>

						<div class="col-sm-6">
							<label for="tid" class="form-label">Identificador do Pagamento</label>
							<input type="text" class="form-control" id="tid" name="tid" required>
							<div class="invalid-feedback">O identificador do pagamento é obrigatório.</div>
							<div id="tidHelp" class="form-text">Insira no identificador o número do pedido ou algum dado para identificar o pagamento recebido.</div>
						</div>

						<div class="col-12">
							<label for="description" class="form-label">Descrição do Pagamento</label>
							<input type="text" class="form-control" id="description" name="description">
							<div id="tidHelp" class="form-text">A descrição do Pix será visualizada apenas pelo cliente ao pagar. Utilize frases curtas como <code>Compra na Loja</code>.</div>
						</div>
					</div>

					<hr class="my-4">

					<button class="w-100 btn btn-primary btn-lg" type="submit">Gerar Pix</button>
				</form>
			</div>
		</div>
		<?php endif; ?>
	</main>
</div>

<script>
// Example starter JavaScript for disabling form submissions if there are invalid fields
(function () {
  'use strict'

  // Fetch all the forms we want to apply custom Bootstrap validation styles to
  var forms = document.querySelectorAll('.needs-validation')

  // Loop over them and prevent submission
  Array.prototype.slice.call(forms)
    .forEach(function (form) {
      form.addEventListener('submit', function (event) {
        if (!form.checkValidity()) {
          event.preventDefault()
          event.stopPropagation()
        }

        form.classList.add('was-validated')
      }, false)
    })
})()
</script>
<?php $this->getTemplate( 'footer' ); ?>