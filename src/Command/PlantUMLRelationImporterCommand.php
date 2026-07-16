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
    name: 'UML:make:relations',
    description: 'Generate relations with puml diagram'
)]
class PlantUMLRelationImporterCommand extends Command
{
    public function getStrStartLower($string): string
    {
        $ret = strtolower(substr($string, 0, 1)) . substr($string, 1);

        return preg_replace("/[^a-zA-Z0-9]/", "", $ret);
    }

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

        $command = $this->getApplication()->find('make:entity');

        foreach ($content as $line) {

            // Ignore les lignes qui ne sont pas des relations
            if (!str_contains($line, '--')) {
                continue;
            }

            /*
             Exemple :
             User "1..1" --* "0..n" UserTheme : themes
            */

            preg_match(
                '/(\w+)\s+"([^"]+)"\s+--\*?\s+"([^"]+)"\s+(\w+)(?:\s*:\s*(\w+))?/',
                trim($line),
                $matches
            );

            if (!$matches) {
                continue;
            }

            $currentClass = $matches[1];
            $currentCardinality = $matches[2];

            $targetCardinality = $matches[3];
            $targetClass = $matches[4];

            $fieldName = $matches[5] ?? $this->getStrStartLower($targetClass);


            /*
             Détermination de la relation Doctrine
            */

            if ($currentCardinality === '1..1' && $targetCardinality === '0..n') {
                $relation = 'OneToMany';
            }
            elseif ($currentCardinality === '0..n' && $targetCardinality === '1..1') {
                $relation = 'ManyToOne';
            }
            elseif ($currentCardinality === '1..1' && $targetCardinality === '1..1') {
                $relation = 'OneToOne';
            }
            elseif ($currentCardinality === '0..n' && $targetCardinality === '0..n') {
                $relation = 'ManyToMany';
            }
            else {
                $io->warning("Relation inconnue : $line");
                continue;
            }


            $io->section("$currentClass -> $targetClass");
            $io->text("Relation : $relation");
            $io->text("Champ : $fieldName");


            /*
             Construction des réponses make:entity
            */

            $inputs = [
                $currentClass,
                $fieldName,
                'relation',
                $targetClass,
                $relation,
            ];


            switch ($relation) {

                case 'ManyToOne':
                case 'OneToOne':

                    // nullable ?
                    $inputs[] = 'yes';

                    // relation inverse ?
                    $inputs[] = 'no';

                    $inputs[] = '';
                    break;


                case 'OneToMany':

                    $inverseFieldName = $this->getStrStartLower($currentClass);

                    if ($inverseFieldName === 'user') {
                        $inverseFieldName = 'userEntity';
                    }

                    // Nom du champ inverse
                    $inputs[] = $inverseFieldName;

                    // nullable
                    $inputs[] = 'no';

                    // Orphan removal
                    $inputs[] = 'yes';

                    // Terminer
                    $inputs[] = '';

                    break;

                case 'ManyToMany':

                    $inputs[] = 'no';

                    $inputs[] = '';
                    break;
            }


            $tester = new CommandTester($command);

            $tester->setInputs($inputs);
            $tester->execute([]);


            $io->write($tester->getDisplay());

            $io->success(
                "Relation {$currentClass} -> {$targetClass} créée"
            );
        }


        $io->success('Toutes les relations ont été créées.');

        return Command::SUCCESS;
    }
}
