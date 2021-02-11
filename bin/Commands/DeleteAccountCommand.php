<?php
namespace App\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Question\Question;

class DeleteAccountCommand extends Command 
{
	protected static $defaultName = 'account:delete';

	protected function configure ()
	{
		$this
			->setDescription('Remove uma conta Pix.')
			->setHelp('Esse comando auxilia você a remover uma conta Pix.');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$helper = $this->getHelper('question');
		$qKey   = new Question('<info>Entre com a chave Pix para remover</info>: ', null);
		
		$key = $helper->ask($input, $output, $qKey);

		if ( empty($key) )
		{ 
			$output->writeln('<error>Nenhuma chave informada, saindo...</error>');
			return Command::SUCCESS;
		}

		$accountsFile = dirname(dirname(dirname(__FILE__))) . '/app/config/accounts.php';

		if ( !is_file($accountsFile) )
		{ 
			$output->writeln('<error>O arquivo de configuração de contas não existe. Crie uma conta primeiro.</error>');
			return Command::SUCCESS;
		}

		// Get all users
		$accounts = include ( $accountsFile );

		if ( !$this->hasPixKey($accounts, $key) )
		{
			$output->writeln(sprintf('<error>A chave Pix `%s` não existe...</error>',$username));
			return Command::SUCCESS;
		}

		foreach ( $accounts as $index => $account )
		{
			if ( $account['key'] === $key )
			{ 
				unset($accounts[$index]);
				break;
			}
		}

		$content = $this->parseArray($accounts);
		file_put_contents($accountsFile, $content);			

		$output->writeln(sprintf('<info>Chave Pix `%s` removida com sucesso.</info>',$key));
		return Command::SUCCESS;
	}

	/**
	 * Check if pix key exists in array.
	 * @param array $accounts
	 * @param string $key
	 * @return bool
	 */
	private function hasPixKey ( $accounts, $key )
	{
		foreach ( $accounts as $account )
		{
			if ( $account['key'] === $key )
			{ return true; }
		}

		return false;
	}

	/**
	 * Parse accounts array to text.
	 * @param array $accounts
	 */
	private function parseArray ( array $accounts ) 
	{ return sprintf("<?php\n\nreturn %s;", var_export($accounts, true)); }
}