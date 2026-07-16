<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Tester\CommandTester;

#[AsCommand(
    name: 'UML:make:entities',
    description: 'Generate Entity with puml diagram'
)]
class PlantUMLImporterCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addArgument('file', InputArgument::REQUIRED, 'Path to UML file');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $file = $input->getArgument('file');
        $content = explode("\n", file_get_contents($file));

        $currentClass = '';
        $classList = [];
        $openedBracket = 0;

        foreach ($content as $line) {
            $line = trim($line);

            if (str_contains($line, '{')) {
                $openedBracket++;
            }

            if (str_starts_with($line, 'class ')) {
                $currentClass = trim(str_replace('{', '', explode(' ', $line)[1]));
                $classList[$currentClass] = [];
            } elseif (
                $openedBracket > 0 &&
                str_contains($line, '-') &&
                str_contains($line, ':')
            ) {
                [$field, $type] = explode(':', $line);

                $field = trim(str_replace('-', '', $field));
                $type = trim($type);

                // Conversion éventuelle des types UML -> Doctrine
                $type = match (strtolower($type)) {
                    'int' => 'integer',
                    'bool' => 'boolean',
                    'datetime' => 'datetime',
                    default => $type,
                };

                $classList[$currentClass][$field] = $type;
            }

            if (str_contains($line, '}')) {
                $openedBracket--;
            }
        }

        $command = $this->getApplication()->find('make:entity');

        foreach ($classList as $class => $fields) {
            $io->section("Création de l'entité $class");

            $tester = new CommandTester($command);

            $inputs = [];
            $inputs[] = $class;

            foreach ($fields as $field => $type) {
                $io->text("Ajout du champ $field ($type)");

                $inputs[] = $field;
                $inputs[] = $type;

                if ($type === 'string') {
                    $inputs[] = '255';
                }

                // Nullable ?
                $inputs[] = 'no';
            }

            // Fin de l'ajout des propriétés
            $inputs[] = '';

            $tester->setInputs($inputs);
            $tester->execute([]);

            $io->write($tester->getDisplay());
            $io->success("Entité $class créée");
        }

        $io->success('Toutes les entités ont été créées.');

        return Command::SUCCESS;
    }
}
