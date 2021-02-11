<?php
namespace App\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class InstallCommand extends Command 
{
	protected static $defaultName = 'app:install';

	protected function configure ()
	{
		$this
			->setDescription('Realiza todas as configurações iniciais do app.')
			->setHelp('Esse comando auxilia você a configurar o app de um jeito simples e fácil.');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{		
		$output->writeln('<comment>A partir de agora você irá configurar o Pix por Piggly!</comment>');

		// DOMAIN=https://pix.piggly.lab
		// HTTPS=true
		// SITE_NAME=Pix por Piggly
		// SESSION_NAME=pix_piggly_lab
		// SECRET_KEY=L^y_nxLG7Eee2a6*
		// BOOTSTRAP_CDN=false
		$helper    = $this->getHelper('question');
		$qDomain   = new Question('<info>Qual o domínio do website (sem http(s))</info>? <comment>[localhost]</comment> ', 'localhost');
		$qHttps    = new ConfirmationQuestion('<info>Deseja habilitar o HTTPS</info>? <comment>[no]</comment> ', false);
		$qSiteN    = new Question('<info>Dê um nome para o seu website</info>: <comment>[Pix por Piggly]</comment> ', 'Pix por Piggly');
		$qSessionN = new Question('<info>Dê um nome para a sessão (sem espaços)</info>: <comment>[pix_piggly_lab]</comment> ', 'pix_piggly_lab');
		$qBootCDN  = new ConfirmationQuestion('<info>Você quer utilizar o CDN da Bootstrap</info>? <comment>[yes]</comment> ', true);

		$https  = $helper->ask($input, $output, $qHttps);
		$domain = $helper->ask($input, $output, $qDomain);
		$domain = $https ? sprintf('https://%s', $domain) : sprintf('http://%s', $domain);
		
		$env  = sprintf("DOMAIN=%s\n", $domain);
		$env .= sprintf("HTTPS=%s\n", var_export($https,true));
		$env .= sprintf("SITE_NAME=\"%s\"\n", $helper->ask($input, $output, $qSiteN));
		$env .= sprintf("SESSION_NAME=%s\n", $helper->ask($input, $output, $qSessionN));
		$env .= sprintf("SECRET_KEY=\"%s\"\n", base64_encode(random_bytes(32)));
		$env .= sprintf("BOOTSTRAP_CDN=%s\n", var_export($helper->ask($input, $output, $qBootCDN),true));

		// $output->writeln(dirname(dirname(dirname(__FILE__))) . '/app/config/users.php');
		$envFile = dirname(dirname(dirname(__FILE__))) . '/app/.env';
		file_put_contents($envFile, $env);

		$output->writeln('<comment>Vamos configurar o primeiro usuário!</comment>');
		($this->getApplication()->find('user:create'))->run($input, $output);

		$output->writeln('<comment>Vamos configurar a sua primeira conta Pix!</comment>');
		($this->getApplication()->find('account:create'))->run($input, $output);

		$output->writeln('<comment>Não esqueça de configurar o domínio em seu servidor Web apontando para a pasta `/public`</comment>');
		$output->writeln(sprintf('<comment>Seu app foi configurado com sucesso! Acesse %s para usar.</comment>',$domain));
		return Command::SUCCESS;
	}
}