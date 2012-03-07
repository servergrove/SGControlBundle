<?php

namespace ServerGrove\Bundle\SGControlBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class APIClientCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        parent::configure();

        $this
                ->setName('sg:api:client')
                ->setDescription("Executes a call to the ServerGrove Control Panel API. For more information visit https://control.servergrove.com/docs/api")
                ->addArgument('call', InputArgument::REQUIRED, 'API Call')
                ->addArgument('args', InputArgument::OPTIONAL, 'API Arguments')
                ->addOption('url', null, null, 'API URL')
        ;
    }

    /**
     * Executes the current command.
     *
     * @param InputInterface  $input  An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     *
     * @return integer 0 if everything went fine, or an error code
     *
     * @throws \LogicException When this abstract class is not implemented
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // check command is valid
        $call = strtolower($input->getArgument('call'));
        $argStr = $input->getArgument('args');

        $options = $input->getOptions();

        parse_str($argStr, $args);

        if (empty($args['apiKey'])) {
            $args['apiKey'] = $this->getContainer()->getParameter('sgc_api.client.apiKey');
        }
        if (empty($args['apiSecret'])) {
            $args['apiSecret'] = $this->getContainer()->getParameter('sgc_api.client.apiSecret');
        }

        $apiclient = $this->getContainer()->get('sgc_api_client');
        /* @var $apiclient \ServerGrove\Bundle\SGControlBundle\APIClient */
        if ($options['url']) {
            $apiclient->setUrl($options['url']);
        }

        if ($options['verbose']) {
            $output->writeln("Calling: <info>".$apiclient->getFullUrl($call, $args)."</info>");
        }

        if ($apiclient->call($call, $args)) {
            if ($options['verbose']) {
                $output->writeln("Response: <info>".print_r($apiclient->getResponse(), true)."</info>");
            } else {
                $output->writeln($apiclient->getRawResponse());
            }
            return 0;
        } else {
            if ($options['verbose']) {
                $output->writeln("<error>".$apiclient->getError()."</error>");
            } else {
                $output->writeln($apiclient->getRawResponse());
            }
            return 1;
        }
    }


}
