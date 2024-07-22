<?php

namespace Polynds\ThinkphpSentry\Command;

use Symfony\Component\Console\Input\InputArgument;
use think\console\Command;

class SentryTest extends Command
{
    protected function configure()
    {
        $this->setName('factory:create')
            ->setDescription('Create a new model factory')
            ->addArgument('name', InputArgument::REQUIRED, 'What is the name of the model?');
    }

    public function handle()
    {


        $this->output->writeln('<info>created</info> .');
    }
}