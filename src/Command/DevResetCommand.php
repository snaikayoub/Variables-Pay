<?php

namespace App\Command;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;

#[AsCommand(
    name: 'app:dev:reset',
    description: 'Recree la base (schema/migrations) et recharge des donnees de dev.'
)]
/**
 * One-stop command for local development resets.
 *
 * Safety:
 * - Requires `--force`
 * - Refuses to run in `prod`
 *
 * Modes:
 * - `--migrate` (default): run doctrine migrations
 * - `--schema`: drop/create schema via doctrine:schema:* (faster, but bypasses migrations)
 */
final class DevResetCommand extends Command
{
    public function __construct(private readonly KernelInterface $kernel)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('force', null, InputOption::VALUE_NONE, 'Obligatoire. Confirme une operation destructive (drop/reset).')
            ->addOption('recreate-db', null, InputOption::VALUE_NONE, 'Drop + create database via Doctrine (necessite les droits).')
            ->addOption('migrate', null, InputOption::VALUE_NONE, 'Applique les migrations (par defaut si aucun mode schema selectionne).')
            ->addOption('schema', null, InputOption::VALUE_NONE, 'Drop + create schema via doctrine:schema:* (alternative aux migrations).')
            ->addOption('no-seed', null, InputOption::VALUE_NONE, 'Ne pas charger app:load-test-data')
            ->addOption('no-periodes', null, InputOption::VALUE_NONE, 'Ne pas charger app:load-periode-paie')
            ->addOption('year', null, InputOption::VALUE_REQUIRED, 'Annee pour app:load-periode-paie', (new \DateTimeImmutable())->format('Y'))
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$input->getOption('force')) {
            $output->writeln('<error>Refus: operation destructive.</error>');
            $output->writeln('Relance avec: <comment>--force</comment>');
            $output->writeln('Ex: <comment>php bin/console app:dev:reset --force --migrate</comment>');

            return Command::FAILURE;
        }

        $env = $this->kernel->getEnvironment();
        if ('prod' === $env) {
            $output->writeln('<error>Refus: ce reset ne doit pas etre execute en prod.</error>');
            return Command::FAILURE;
        }

        $app = $this->getApplication();
        if (!$app instanceof Application) {
            $output->writeln('<error>Console application not available.</error>');
            return Command::FAILURE;
        }

        $console = new Application($this->kernel);
        $console->setAutoExit(false);

        $useSchema = (bool) $input->getOption('schema');
        $useMigrate = (bool) $input->getOption('migrate');
        if (!$useSchema && !$useMigrate) {
            $useMigrate = true;
        }

        $output->writeln(sprintf('<info>Env: %s</info>', $env));

        if ($input->getOption('recreate-db')) {
            $output->writeln('<info>Recreate database...</info>');
            $code = $this->runCommand($console, 'doctrine:database:drop', [
                '--if-exists' => true,
                '--force' => true,
                '--no-interaction' => true,
            ], $output);
            if (0 !== $code) {
                return $code;
            }

            $code = $this->runCommand($console, 'doctrine:database:create', [
                '--if-not-exists' => true,
                '--no-interaction' => true,
            ], $output);
            if (0 !== $code) {
                return $code;
            }
        }

        if ($useSchema) {
            $output->writeln('<info>Reset schema...</info>');
            $code = $this->runCommand($console, 'doctrine:schema:drop', [
                '--force' => true,
                '--full-database' => true,
                '--no-interaction' => true,
            ], $output);
            if (0 !== $code) {
                return $code;
            }

            $code = $this->runCommand($console, 'doctrine:schema:create', [
                '--no-interaction' => true,
            ], $output);
            if (0 !== $code) {
                return $code;
            }
        }

        if ($useMigrate) {
            $output->writeln('<info>Run migrations...</info>');
            $code = $this->runCommand($console, 'doctrine:migrations:migrate', [
                '--no-interaction' => true,
            ], $output);
            if (0 !== $code) {
                return $code;
            }
        }

        if (!$input->getOption('no-seed')) {
            $output->writeln('<info>Seed test/dev data...</info>');
            $code = $this->runCommand($console, 'app:load-test-data', [], $output);
            if (0 !== $code) {
                return $code;
            }
        }

        if (!$input->getOption('no-periodes')) {
            $year = (int) $input->getOption('year');
            $output->writeln(sprintf('<info>Seed periodes (year=%d)...</info>', $year));
            $code = $this->runCommand($console, 'app:load-periode-paie', [
                'year' => $year,
            ], $output);
            if (0 !== $code) {
                return $code;
            }
        }

        $output->writeln('<info>Done.</info>');

        return Command::SUCCESS;
    }

    /**
     * @param array<string, mixed> $args
     */
    private function runCommand(Application $app, string $command, array $args, OutputInterface $output): int
    {
        $input = new ArrayInput(array_merge(['command' => $command], $args));
        $input->setInteractive(false);

        return $app->run($input, $output);
    }
}
