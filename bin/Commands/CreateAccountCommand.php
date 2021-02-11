<?php
namespace App\Console\Commands;

use Piggly\Pix\Parser;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ChoiceQuestion;

class CreateAccountCommand extends Command 
{
	protected static $defaultName = 'account:create';

	protected function configure ()
	{
		$this
			->setDescription('Cria uma nova conta Pix.')
			->setHelp('Esse comando auxilia você a criar uma nova conta Pix.');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$helper        = $this->getHelper('question');
		$qMerchantName = new Question('<info>Entre com o Nome do Titular da Conta</info>: ', null);
		$qMerchantCity = new Question('<info>Entre com a Cidade do Titular da Conta</info>: ', null);
		$qKeyType      = (new ChoiceQuestion('<info>Entre com o Tipo da Chave</info> [cpf]: ', ['cpf', 'cnpj', 'telefone', 'email', 'aleatoria'], 0))->setErrorMessage('A chave `%s` é inválida.');
		$qKey          = new Question('<info>Entre com a Chave Pix</info>: ', null);
		$qLabel        = new Question('<info>Rotule esta conta</info>: ', 'Conta Principal');
		
		$qMerchantName->setValidator( function ($answer) {
			if ( empty( $answer ) )
			{ throw new \RuntimeException('O Nome do Titular não pode ser vazio.'); }

			return $answer;
		});

		$qMerchantCity->setValidator( function ($answer) {
			if ( empty( $answer ) )
			{ throw new \RuntimeException('O Nome da Cidade não pode ser vazio.'); }

			return $answer;
		});

		$qKeyType->setValidator( function ($answer) {
			if ( empty( $answer ) )
			{ throw new \RuntimeException('O Tipo da Chave não pode ser vazio.'); }

			return $answer;
		});

		$qKey->setValidator( function ($answer) {
			if ( empty( $answer ) )
			{ throw new \RuntimeException('O Chave Pix não pode ser vazia.'); }

			return $answer;
		});

		$qMerchantName->setMaxAttempts(2);
		$qMerchantCity->setMaxAttempts(2);
		$qKeyType->setMaxAttempts(2);
		$qKey->setMaxAttempts(2);

		$merchantName = $helper->ask($input, $output, $qMerchantName);
		$merchantCity = $helper->ask($input, $output, $qMerchantCity);
		$keyType = $helper->ask($input, $output, $qKeyType);
		$key = $helper->ask($input, $output, $qKey);
		$label = $helper->ask($input, $output, $qLabel);

		switch ( $keyType )
		{
			case 0:
			case "0":
				$keyType = Parser::KEY_TYPE_DOCUMENT;
				break;
			case 1:
			case "1":
				$keyType = Parser::KEY_TYPE_DOCUMENT;
				break;
			case 2:
			case "2":
				$keyType = Parser::KEY_TYPE_PHONE;
				break;
			case 3:
			case "3":
				$keyType = Parser::KEY_TYPE_EMAIL;
				break;
			case 4:
			case "4":
				$keyType = Parser::KEY_TYPE_RANDOM;
				break;
		}

		$accountsFile = dirname(dirname(dirname(__FILE__))) . '/app/config/accounts.php';

		if ( !is_file($accountsFile) )
		{ 
			$output->writeln('<comment>Criando os arquivos de configuração de contas...</comment>');
			$content = $this->parseArray([uniqid(rand ()) => $this->createAccount($merchantName, $merchantCity, $keyType, $key, $label)]);
			file_put_contents($accountsFile, $content);

			$output->writeln(sprintf('<info>Conta `%s` criado com sucesso.</info>',$label));
			return Command::SUCCESS;
		}

		// Get all users
		$accounts = include ( $accountsFile );

		if ( $this->hasPixKey($accounts, $key) )
		{
			$output->writeln(sprintf('<error>A chave Pix `%s` já existe...</error>',$key));
			return Command::SUCCESS;
		}

		$accounts[uniqid(rand ())] = $this->createAccount($merchantName, $merchantCity, $keyType, $key, $label);

		$content = $this->parseArray($accounts);
		file_put_contents($accountsFile, $content);			

		$output->writeln(sprintf('<info>Conta `%s` criado com sucesso.</info>',$label));
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
	 * Create an account in array format.
	 * @param string $merchantName
	 * @param string $merchantCity
	 * @param string $keyType
	 * @param string $key
	 * @param string $label
	 * @return array
	 */
	private function createAccount ( $merchantName, $merchantCity, $keyType, $key, $label )
	{
		return [
			'label' => $label,
			'merchantName' => $merchantName,
			'merchantCity' => $merchantCity,
			'keyType' => $keyType,
			'key' => $key
		];
	}

	/**
	 * Parse accounts array to text.
	 * @param array $accounts
	 */
	private function parseArray ( array $accounts ) 
	{ return sprintf("<?php\n\nreturn %s;", var_export($accounts, true)); }
}