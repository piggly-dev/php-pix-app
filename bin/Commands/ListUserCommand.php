<?php
namespace App\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ListUserCommand extends Command 
{
	protected static $defaultName = 'user:list';

	protected function configure ()
	{
		$this
			->setDescription('Lista todos os usuários.')
			->setHelp('Esse comando auxilia você a visualizar todos os usuários.');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{		
		// $output->writeln(dirname(dirname(dirname(__FILE__))) . '/app/config/users.php');
		$usersFile = dirname(dirname(dirname(__FILE__))) . '/app/config/users.php';

		if ( !is_file($usersFile) )
		{ 
			$output->writeln('<error>O arquivo de configuração de usuários não existe. Crie um usuário primeiro.</error>');
			return Command::SUCCESS;
		}

		// Get all users
		$users = include ( $usersFile );
		$table = (new Table($output))->setHeaders(['Usuário']);

		foreach ( $users as $user )
		{ $table->addRow([$user['username']]); }

		$table->render();
		return Command::SUCCESS;
	}
}