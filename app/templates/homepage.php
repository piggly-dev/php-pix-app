<?php
use App\Core\Tools\Hook;

Hook::bind( 'header', 
	Hook::generateCallbackFunction
	(
		'importCss', 
		[
			'assets/css',
			'signin'
		]
	)
);

$status = filter_input( INPUT_GET, 'status', FILTER_SANITIZE_STRING );
?>

<?php $this->getTemplate( 'header' ); ?>
<main class="form-signin">
	<form method="POST" action="/login">
		<img class="mb-4" style="width: 50px;" src="<?=getUrl("assets/svg/logo.svg")?>">
		<h1 class="h3 mb-3 fw-normal">Faça o <strong>Login</strong></h1>
		<?php if ( !empty($status) ) : ?>
		<div class="alert alert-primary" role="alert"><?=urldecode($status);?></div>
		<?php endif; ?>
		<label for="username" name="username" class="visually-hidden">Usuário</label>
		<input type="text" id="username" name="username" class="form-control" placeholder="Usuário" required autofocus>
		<label for="password" name="password" class="visually-hidden">Senha</label>
		<input type="password" id="password" name="password" class="form-control" placeholder="Senha" required>
		<div class="checkbox mb-3"> 
			<label><input type="checkbox" name="remember" value="remember-me"> Lembrar-me</label>
		</div>
		<button class="w-100 btn btn-lg btn-primary" type="submit">Entrar</button>
	</form>
</main>
<?php $this->getTemplate( 'footer' ); ?>