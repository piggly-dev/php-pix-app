<?php
namespace App\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ListAccountCommand extends Command 
{
	protected static $defaultName = 'account:list';

	protected function configure ()
	{
		$this
			->setDescription('Lista todos as contas Pix.')
			->setHelp('Esse comando auxilia você a visualizar todas contas Pix.');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{		
		// $output->writeln(dirname(dirname(dirname(__FILE__))) . '/app/config/accounts.php');
		$accountsFile = dirname(dirname(dirname(__FILE__))) . '/app/config/accounts.php';

		if ( !is_file($accountsFile) )
		{ 
			$output->writeln('<error>O arquivo de configuração de contas não existe. Crie uma conta primeiro.</error>');
			return Command::SUCCESS;
		}

		// Get all accounts
		$accounts = include ( $accountsFile );
		$table = (new Table($output))->setHeaders(['Rótulo','Titular','Cidade','Chave Pix']);

		foreach ( $accounts as $account )
		{ $table->addRow([$account['label'],$account['merchantName'],$account['merchantCity'],sprintf('%s:%s',$account['keyType'],$account['key'])]); }

		$table->render();
		return Command::SUCCESS;
	}
}