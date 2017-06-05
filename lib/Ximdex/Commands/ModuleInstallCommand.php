<?php

namespace Ximdex\Commands;


use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Ximdex\Modules\Manager;

class ModuleInstallCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('module:install')
            ->setDescription('Install a Module')
            ->addArgument('module', InputArgument::REQUIRED, "Module to install")
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $module = $input->getArgument('module');

        $manager = new Manager;

        if ( Manager::isModule($module) ){
            $output->writeln("<error>Module {$module} doesn't exist</error>");
            return;
        }

        $status = $manager->checkModule( $module );
        if ( $status === Manager::get_module_state_installed()){
            $output->writeln("<error>Module {$module} is already installed</error>");
            return;
        }

        $installed = $manager->installModule($module);

         if ( !$installed ){
            $output->writeln("<error>Error during installation of module {$module}</error>");
            return;
        }

        $manager->enableModule($module);

        $output->writeln("<info>Module {$module} installed!</info>");


    }
}