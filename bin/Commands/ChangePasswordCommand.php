<?php
namespace App\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class ChangePasswordCommand extends Command 
{
	protected static $defaultName = 'user:change';

	protected function configure ()
	{
		$this
			->setDescription('Muda a senha do usuário.')
			->setHelp('Esse comando auxilia você a mudar a senha de um usuário.');
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

		$qPass    = (new Question('<info>Entre com a nova senha</info>: ', null))->setHidden(true)->setHiddenFallback(false);
		$password = $helper->ask($input, $output, $qPass);

		foreach ( $users as $index => $user )
		{
			if ( $user['username'] === $username )
			{ 
				$users[$index]['password'] = $password;
				break;
			}
		}

		$content = $this->parseArray($users);
		file_put_contents($usersFile, $content);			

		$output->writeln(sprintf('<info>Senha do usuário `%s` alterada com sucesso.</info>',$username));
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