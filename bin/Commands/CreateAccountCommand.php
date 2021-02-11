<?php
namespace App\Console\Commands;

use Exception;
use Piggly\Pix\Parser;
use Piggly\Pix\Reader;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

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
		$helper   = $this->getHelper('question');
		$qExtract = new ConfirmationQuestion('<info>Quer extrair os dados de um código pix</info>? <comment>[no]</comment> ', false);
		$extract  = $helper->ask($input, $output, $qExtract);

		if ( $extract )
		{
			$qPixCode = new Question('<info>Informe o código Pix</info>: ', null);

			$qPixCode->setValidator( function ($answer) {
				if ( empty( $answer ) )
				{ throw new \RuntimeException('O código Pix não pode ser vazio.'); }
	
				return $answer;
			});

			$qPixCode->setMaxAttempts(2);
			$pixCode = $helper->ask($input, $output, $qPixCode);

			$reader = new Reader($pixCode);

			$merchantName = $reader->getMerchantName();
			$merchantCity = $reader->getMerchantCity();
			$key = $reader->getPixKey();
			$keyType = Parser::getKeyType($key);

			$output->writeln('<comment>Os dados foram extraídos</comment>');
			$output->writeln(sprintf('<info>Nome do Titular</info>: %s', $merchantName));
			$output->writeln(sprintf('<info>Cidade</info>: %s', $merchantCity));
			$output->writeln(sprintf('<info>Tipo da Chave</info>: %s', Parser::getAlias($keyType)));
			$output->writeln(sprintf('<info>Chave</info>: %s', $key));
		}
		else 
		{
			$qMerchantName = new Question('<info>Entre com o Nome do Titular</info>: ', null);
			$qMerchantCity = new Question('<info>Entre com a Cidade associada a conta</info>: ', null);
			$qKeyType      = (new ChoiceQuestion('<info>Entre com o Tipo da Chave</info> [cpf]: ', ['cpf', 'cnpj', 'telefone', 'email', 'aleatoria'], 0))->setErrorMessage('A chave `%s` é inválida.');
			$qKey          = new Question('<info>Entre com a Chave Pix</info>: ', null);
			
			$qMerchantName->setValidator( function ($answer) {
				if ( empty( $answer ) )
				{ throw new RuntimeException('O Nome do Titular não pode ser vazio.'); }
	
				return $answer;
			});
	
			$qMerchantCity->setValidator( function ($answer) {
				if ( empty( $answer ) )
				{ throw new RuntimeException('O Nome da Cidade não pode ser vazio.'); }
	
				return $answer;
			});
	
			$qKey->setValidator( function ($answer) {
				if ( empty( $answer ) )
				{ throw new RuntimeException('A Chave Pix não pode ser vazia.'); }
	
				return $answer;
	
				return $answer;
			});
	
			$qMerchantName->setMaxAttempts(2);
			$qMerchantCity->setMaxAttempts(2);
			$qKeyType->setMaxAttempts(2);
			$qKey->setMaxAttempts(2);

			$merchantName = $helper->ask($input, $output, $qMerchantName);
			$merchantCity = $helper->ask($input, $output, $qMerchantCity);
			$keyType = $helper->ask($input, $output, $qKeyType);

			$output->writeln($keyType);
			switch ( $keyType )
			{
				case 'cpf':
				case 'cnpj':
					$keyType = Parser::KEY_TYPE_DOCUMENT;
					break;
				case 'telefone':
					$keyType = Parser::KEY_TYPE_PHONE;
					break;
				case 'email':
					$keyType = Parser::KEY_TYPE_EMAIL;
					break;
				case 'aleatoria':
					$keyType = Parser::KEY_TYPE_RANDOM;
					break;
			}

			$key = Parser::parse($keyType, $helper->ask($input, $output, $qKey));
			Parser::validate($keyType, $key);
		}
		
		$qLabel = new Question('<info>Rotule esta conta</info>: ', 'Conta Principal');
		$label  = $helper->ask($input, $output, $qLabel);

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