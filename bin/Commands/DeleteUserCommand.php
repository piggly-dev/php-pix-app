<?php
namespace App\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Question\Question;

class DeleteUserCommand extends Command 
{
	protected static $defaultName = 'user:delete';

	protected function configure ()
	{
		$this
			->setDescription('Remove um usuário.')
			->setHelp('Esse comando auxilia você a remover um usuário.');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$helper = $this->getHelper('question');
		$qUser  = new Question('<info>Entre com o nome do usuário</info>: ', null);
		
		$username = $helper->ask($input, $output, $qUser);

		if ( empty($username) )
		{ 
			$output->writeln('<error>Nenhum usuário informado, saindo...</error>');
			return Command::SUCCESS;
		}

		// $output->writeln(dirname(dirname(dirname(__FILE__))) . '/app/config/users.php');
		$usersFile = dirname(dirname(dirname(__FILE__))) . '/app/config/users.php';

		if ( !is_file($usersFile) )
		{ 
			$output->writeln('<error>O arquivo de configuração de usuários não existe. Crie um usuário primeiro.</error>');
			return Command::SUCCESS;
		}

		// Get all users
		$users = include ( $usersFile );

		if ( !$this->hasUser($users, $username) )
		{
			$output->writeln(sprintf('<error>O usuário `%s` não existe...</error>',$username));
			return Command::SUCCESS;
		}

		foreach ( $users as $index => $user )
		{
			if ( $user['username'] === $username )
			{ 
				unset($users[$index]);
				break;
			}
		}

		$content = $this->parseArray($users);
		file_put_contents($usersFile, $content);			

		$output->writeln(sprintf('<info>Usuário `%s` removido com sucesso.</info>',$username));
		return Command::SUCCESS;
	}

	/**
	 * Check if user exists in array.
	 * @param array $users
	 * @param string $password
	 * @return bool
	 */
	private function hasUser ( $users, $username )
	{
		foreach ( $users as $user )
		{
			if ( $user['username'] === $username )
			{ return true; }
		}

		return false;
	}

	/**
	 * Parse users array to text.
	 * @param array $users
	 */
	private function parseArray ( array $users ) 
	{ return sprintf("<?php\n\nreturn %s;", var_export($users, true)); }
}