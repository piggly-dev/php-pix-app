<?php
namespace App\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class CreateUserCommand extends Command 
{
	protected static $defaultName = 'user:create';

	protected function configure ()
	{
		$this
			->setDescription('Cria um novo usuário.')
			->setHelp('Esse comando auxilia você a criar um novo usuário.');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$helper = $this->getHelper('question');
		$qUser  = new Question('<info>Entre com o nome do usuário</info>: ', null);
		$qPass  = (new Question('<info>Entre com a senha</info>: ', null))->setHidden(true)->setHiddenFallback(false);

		$qUser->setValidator( function ($answer) {
			if ( empty( $answer ) )
			{ throw new \RuntimeException('O usuário não pode ser vazio.'); }

			return $answer;
		});

		$qPass->setValidator( function ($answer) {
			if ( empty( $answer ) )
			{ throw new \RuntimeException('A senha não pode ser vazia.'); }

			return $answer;
		});
		
		$qUser->setMaxAttempts(2);
		$qPass->setMaxAttempts(2);

		$username = $helper->ask($input, $output, $qUser);
		$password = $helper->ask($input, $output, $qPass);

		// $output->writeln(dirname(dirname(dirname(__FILE__))) . '/app/config/users.php');
		$usersFile = dirname(dirname(dirname(__FILE__))) . '/app/config/users.php';

		if ( !is_file($usersFile) )
		{ 
			$output->writeln('<comment>Criando os arquivos de configuração de usuário...</comment>');
			$content = $this->parseArray([$this->createUser($username,$password)]);
			file_put_contents($usersFile, $content);

			$output->writeln(sprintf('<info>Usuário `%s` criado com sucesso.</info>',$username));
			return Command::SUCCESS;
		}

		// Get all users
		$users = include ( $usersFile );

		if ( $this->hasUser($users, $username) )
		{
			$output->writeln(sprintf('<error>O usuário `%s` já existe...</error>',$username));
			return Command::SUCCESS;
		}

		$users[] = $this->createUser($username,$password);

		$content = $this->parseArray($users);
		file_put_contents($usersFile, $content);			

		$output->writeln(sprintf('<info>Usuário `%s` criado com sucesso.</info>',$username));
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
	 * Create an user in array format.
	 * @param string $username
	 * @param string $password
	 * @return array
	 */
	private function createUser ( $username, $password )
	{
		return [
			'_id' => uniqid(rand ()),
			'username' => $username,
			'password' => $password
		];
	}

	/**
	 * Parse users array to text.
	 * @param array $users
	 */
	private function parseArray ( array $users ) 
	{ return sprintf("<?php\n\nreturn %s;", var_export($users, true)); }
}