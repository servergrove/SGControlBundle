<?php

namespace ServerGrove\Bundle\SGControlBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use ServerGrove\Bundle\SGControlBundle\APIClient;

class ShellCommand extends ContainerAwareCommand
{
    private $client;
    private $input;
    private $output;

    private $server;
    private $servers = array();

    private $domain;
    private $domains = array();

    private $app;
    private $apps = array();

    private $args;

    private $lastCommand = null;

    private $commands = array(
        //'list',
        '.',
        'x',
        'q',
        'help',
        'exit',
        'servers',
        'server',
        'domains',
        'domain',
        'apps',
        'app',
        'reboot',
        'shutdown',
        'bootup',
        'restart',
        'stop',
        'start',
        'exec',
    );

    protected function configure()
    {
        parent::configure();

        $this
                ->setName('sgc:shell')
                ->setDescription("Provides a shell for the ServerGrove Control Panel API. For more information visit https://control.servergrove.com/docs/api")
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
        $this->input = $input;
        $this->output = $output;

        // check command is valid
        $argStr = $input->getArgument('args');

        $this->options = $input->getOptions();

        parse_str($argStr, $this->args);

        if (empty($this->args['apiKey'])) {
            $this->args['apiKey'] = $this->getContainer()->getParameter('sgc_api.client.apiKey');
        }
        if (empty($this->args['apiSecret'])) {
            $this->args['apiSecret'] = $this->getContainer()->getParameter('sgc_api.client.apiSecret');
        }

        $this->client = $this->getContainer()->get('sgc_api_client');
        /* @var $apiclient \ServerGrove\Bundle\SGControlBundle\APIClient */
        if ($this->options['url']) {
            $this->client->setUrl($this->options['url']);
        }

        $this->runShell();
    }

    protected function runShell()
    {
        while (true) {
            $command = $this->readline($this->getPrompt());

            switch($command) {
                case 'help':
                case 'h':
                case '?':
                    $this->output->writeln('Help:');
                    foreach($this->commands as $cmd) {
                        $this->output->writeln('   '.$cmd );
                    }
                    break;
                case 'exit':
                case 'quit':
                    $this->output->writeln("Exiting, goodbye!");
                    break 2;
                default:
                    $this->processCommand($command);
            }
        }
    }

    protected function processCommand($command)
    {
        $command = trim($command);

        if (empty($command)) {
            return;
        }

        if ($command == '.' && $this->lastCommand) {
            $command = $this->lastCommand;
        }


        foreach($this->commands as $cmd) {
            if (strpos($command, $cmd) === 0) {
                $method = 'execute'.lcfirst(str_replace(' ', '', $cmd));
                if (false === $argStr = substr($command, strlen($cmd))) {
                    $args = array();
                } else {
                    $args = explode(' ', trim($argStr));
                }

                if (method_exists($this, $method)) {
                    $this->lastCommand = $command;
                    $this->$method($args);
                    return;
                }
            }
        }

        $this->error('Unrecognized command');
    }

    protected function getPrompt($msg = '$ ')
    {
        $prompt = '';

        if ($this->server) {
            $prompt .= $this->server['hostname'].' ';
        }

        if ($this->domain) {
            $prompt .= '> '.$this->domain['name'].' ';
        }

        if ($this->app) {
            $prompt .= '> '.$this->app['name'].' ';
        }

        return $prompt.$msg;
    }

    protected function executeList($args)
    {
        print_r($args);
    }

    protected function executeServer($args)
    {
        $this->selectServer($args[0]);
    }

    protected function executeDomain($args)
    {
        $this->selectDomain($args[0]);
    }

    protected function executeApp($args)
    {
        $this->selectApp($args[0]);
    }

    protected function selectServer($s = null) {
        if (!count($this->servers)) {
            if (!$this->loadServers()) {
                return false;
            }
        }

        if (count($this->servers) == 1) {
            return $this->setServer(1);
        }

        if (empty($s)) {
            return false;
        }
        if (is_numeric($s)) {
            if (isset($this->servers[$s])) {
                return $this->setServer($s);
            }
        } else {
            foreach($this->servers as $idx => $server) {
                if ($server['hostname'] == $s) {
                    return $this->setServer($idx);
                }
            }
            foreach($this->servers as $idx => $server) {
                if (stripos($server['hostname'], $s) !== false) {
                    return $this->setServer($idx);
                }
            }
        }

        $this->server = null;
        $this->error("Server not found");
        return false;
    }

    protected function setServer($server) {
        $this->server = $this->servers[$server];
        $this->info("Selected server ".$this->server['hostname']);
        $this->reset(false);
        return true;
    }


    protected function selectDomain($s = null) {
        if (!count($this->domains)) {
            if (!$this->loadDomains()) {
                return false;
            }
        }

        if (count($this->domains) == 1) {
            return $this->setDomain(1);
        }

        if (empty($s)) {
            return false;
        }
        if (is_numeric($s)) {
            if (isset($this->domains[$s])) {
                return $this->setDomain($s);
            }
        } else {
            foreach($this->domains as $idx => $dom) {
                if ($dom['name'] == $s) {
                    return $this->setDomain($idx);
                }
            }
            foreach($this->domains as $idx => $dom) {
                if (stripos($dom['name'], $s) !== false) {
                    return $this->setDomain($idx);
                }
            }
        }

        $this->domain = null;
        $this->error("Domain not found");
        return false;
    }


    protected function setDomain($domain) {
        $this->domain = $this->domains[$domain];
        $this->info("Selected domain ".$this->domain['name']);
        return true;
    }

    protected function selectApp($s = null) {
           if (!count($this->apps)) {
               if (!$this->loadApps()) {
                   return false;
               }
           }

           if (count($this->apps) == 1) {
               return $this->setApp(1);
           }

           if (empty($s)) {
               return false;
           }
           if (is_numeric($s)) {
               if (isset($this->apps[$s])) {
                   return $this->setApp($s);
               }
           } else {
               foreach($this->apps as $idx => $app) {
                   if ($app['name'] == $s) {
                       return $this->setApp($idx);
                   }
               }
               foreach($this->apps as $idx => $app) {
                   if (stripos($app['name'], $s) !== false) {
                       return $this->setApp($idx);
                   }
               }
           }

           $this->app = null;
           $this->error("App not found");
           return false;
       }


    protected function setApp($app) {
        $this->app = $this->apps[$app];
        $this->info("Selected app ".$this->app['name']);
        return true;
    }

    protected function loadServers()
    {
        $this->servers = array();

        $this->info("Loading servers...");

        if (!$res = $this->call('server/list', array())) {
            return false;
        }

        if (false === $rsp = $this->client->getResponse(APIClient::FORMAT_ARRAY)) {
            return false;
        }

        $this->result = array();
        $i = 1;
        foreach($rsp['rsp'] as $server) {
            $this->servers[$i] = $server;
            $i++;
        }
        return true;
    }

    protected function loadDomains($serverId = null)
    {
        if (!$serverId) {
            $serverId = $this->server['id'];
        }

        $this->domains = array();

        $this->info("Loading domains...");
        if (!$res = $this->call('domain/list', array('serverId' => $serverId))) {
            return false;
        }

        if (false === $rsp = $this->client->getResponse(APIClient::FORMAT_ARRAY)) {
            return false;
        }

        $this->result = array();
        $i = 1;
        foreach($rsp['rsp'] as $domain) {
            $this->domains[$i] = $domain;
            $i++;
        }
        return true;
    }

    protected function loadApps($serverId = null)
    {
        if (!$serverId) {
            $serverId = $this->server['id'];
        }

        $this->apps = array();

        $this->info("Loading apps...");
        if (!$res = $this->call('app/list', array('serverId' => $serverId))) {
            return false;
        }

        if (false === $rsp = $this->client->getResponse(APIClient::FORMAT_ARRAY)) {
            return false;
        }

        $this->result = array();
        $i = 1;

        foreach($rsp['rsp'] as $app) {
            $this->apps[$i] = $app;
            $i++;
        }
        return true;
    }

    protected function serverExec($cmd, $serverId = null)
    {
        if (!$serverId) {
            $serverId = $this->server['id'];
        }

        $this->info("Sending request...");
        if (!$res = $this->call('server/exec', array(
            'serverId' => $serverId,
            'cmd' => $cmd,
            'async' => 0,
            ))) {
            return false;
        }

        if (false === $rsp = $this->client->getResponse(APIClient::FORMAT_ARRAY)) {
            return;
        }

        $this->output->writeln($rsp['msg']);

        return true;
    }

    protected function appCall($method, $args = null, $appId = null)
    {
        if (!$appId) {
            $appId = $this->app['id'];
        }

        $this->info("Sending request...");
        if (!$res = $this->call('app/call', array(
            'serverId' => $this->server['id'],
            'appId' => $appId,
            'method' => $method,
            'async' => 0,
            ))) {
            return false;
        }

        if (false === $rsp = $this->client->getResponse(APIClient::FORMAT_ARRAY)) {
            return;
        }

        $this->output->writeln($rsp['msg']);

        return true;
    }

    protected function executeX($args)
    {
        return $this->reset();
    }

    protected function executeQ($args)
    {
        return $this->reset();
    }

    protected function reset($server = true, $domain = true, $app = true)
    {
        if ($server) {
            $this->server = null;
            $this->servers = array();
        }
        if ($domain) {
            $this->domain = null;
            $this->domains = array();
        }
        if ($app) {
            $this->app = null;
            $this->apps = array();
        }

        return true;
    }

    protected function executeServers($args)
    {
        if (!count($this->servers)) {
            if (!$this->loadServers()) {
                return false;
            }
        }
        foreach($this->servers as $i => $server) {
            $this->output->writeln(sprintf("$i. <info>%s</info> IP: <info>%s</info> Plan: <info>%s</info> %s",
                str_pad($server['hostname'], 40),
                str_pad($server['mainIpAddress'], 15),
                str_pad(isset($server['plan']) ? $server['plan'] : 'n/a', 8),
                $server['isActive'] ? '<info>Active</info>' : '<error>Offline</error>'
            ));
        }

        return true;
    }

    protected function executeDomains($args)
    {
        if (isset($args[0])) {
            if (!$this->selectServer($args[0])) {
                return false;
            }
        }

        if (!$this->server) {
            if (!$this->selectServer()) {
                return $this->error('No server selected');
            }
        }

        if (!count($this->domains)) {
            if (!$this->loadDomains()) {
                return false;
            }
        }
        foreach($this->domains as $i => $domain) {
            $this->output->writeln(sprintf("$i. <info>%s</info>",
                str_pad($domain['name'], 40)
            ));
        }

        return true;
    }

    protected function executeApps($args)
    {
        if (isset($args[0])) {
            if (!$this->selectServer($args[0])) {
                return false;
            }
        }

        if (!$this->server) {
            if (!$this->selectServer()) {
                return $this->error('No server selected');
            }
        }

        if (!count($this->apps)) {
            if (!$this->loadApps()) {
                return false;
            }
        }
        foreach($this->apps as $i => $app) {
            $this->output->writeln(sprintf("$i. <info>%s</info> <info>%s</info> %s",
                str_pad($app['name'], 15),
                str_pad($app['version'], 10),
                $app['isActive'] ? '<info>Active</info>' : '<error>Inactive</error>'
            ));
        }

        return true;
    }

    protected function executeExec($args)
    {
        if (!$this->server) {
            if (!$this->selectServer()) {
                return $this->error('No server selected');
            }
        }

        $this->serverExec(implode(' ', $args));

        return true;
    }

    protected function executeReboot($args)
    {
        if (count($args) == 1) {
            if (!$this->selectServer($args[0])) {
                return false;
            }
        }

        if (!$this->server) {
            if (!$this->selectServer()) {
                return $this->error('No server selected');
            }
        }

        $serverId = $this->server['id'];

        if ('y' !== $this->readline('Are you sure you want to <error>reboot</error> <info>'.$this->server['hostname'].'</info>? [y/N] ')) {
            return false;
        }

        $this->info("Sending request...");
        if (!$res = $this->call('server/restart', array(
            'serverId' => $serverId,
            'async' => 0,
            ))) {
            return false;
        }

        if (false === $rsp = $this->client->getResponse(APIClient::FORMAT_ARRAY)) {
            return false;
        }

        $this->output->writeln($rsp['msg']);

        return true;
    }

    protected function executeShutdown($args)
    {
        if (count($args) == 1) {
            if (!$this->selectServer($args[0])) {
                return false;
            }
        }

        if (!$this->server) {
            if (!$this->selectServer()) {
                return $this->error('No server selected');
            }
        }

        $serverId = $this->server['id'];

        if ('y' !== $this->readline('Are you sure you want to <error>shutdown</error> <info>'.$this->server['hostname'].'</info>? [y/N] ')) {
            return false;
        }

        $this->info("Sending request...");
        if (!$res = $this->call('server/stop', array(
            'serverId' => $serverId,
            'async' => 0,
            ))) {
            return false;
        }

        if (false === $rsp = $this->client->getResponse(APIClient::FORMAT_ARRAY)) {
            return false;
        }

        $this->output->writeln($rsp['msg']);

        return true;
    }


    protected function executeBootup($args)
    {
        if (count($args) == 1) {
            if (!$this->selectServer($args[0])) {
                return false;
            }
        }

        if (!$this->server) {
            if (!$this->selectServer()) {
                return $this->error('No server selected');
            }
        }

        $serverId = $this->server['id'];


        $this->info("Sending request...");
        if (!$res = $this->call('server/start', array(
            'serverId' => $serverId,
            'async' => 0,
            ))) {
            return false;
        }

        if (false === $rsp = $this->client->getResponse(APIClient::FORMAT_ARRAY)) {
            return false;
        }

        $this->output->writeln($rsp['msg']);

        return true;
    }


    protected function executeRestart($args)
    {
        if (count($args) == 1) {
            if (!$this->selectApp($args[0])) {
                return false;
            }
        }

        if (!$this->server) {
            if (!$this->selectServer()) {
                return $this->error('No server selected');
            }
        }

        if ('y' !== $this->readline('Are you sure you want to restart <info>'.$this->app['name'].'</info> on <info>'.$this->server['hostname'].'</info>? [y/N] ')) {
            return false;
        }

        return $this->appCall('svcRestart');
    }

    protected function executeStop($args)
    {
        if (count($args) == 1) {
            if (!$this->selectApp($args[0])) {
                return false;
            }
        }

        if (!$this->server) {
            if (!$this->selectServer()) {
                return $this->error('No server selected');
            }
        }

        if ('y' !== $this->readline('Are you sure you want to stop <info>'.$this->app['name'].'</info> on <info>'.$this->server['hostname'].'</info>? [y/N] ')) {
            return false;
        }

        return $this->appCall('svcStop');
    }

    protected function executeStart($args)
    {
        if (count($args) == 1) {
            if (!$this->selectApp($args[0])) {
                return false;
            }
        }

        if (!$this->server) {
            if (!$this->selectServer()) {
                return $this->error('No server selected');
            }
        }

        return $this->appCall('svcStart');
    }




    function readline($prompt="")
    {
       $this->output->write($prompt);
       $out = "";
       $key = "";
       $key = fgetc(STDIN);        //read from standard input (keyboard)
       while ($key!="\n")        //if the newline character has not yet arrived read another
       {
           $out.= $key;
           $key = fread(STDIN, 1);
       }
       return $out;
    }

    protected function error($msg)
    {
        $this->output->writeln("Error: <error>".$msg."</error>\n");
        return false;
    }

    protected function info($msg)
    {
        $this->output->writeln("<info>".$msg."</info>");
        return true;
    }

    protected function call($call, $args)
    {
        $args = array_merge($this->args, $args);

        if ($this->options['verbose']) {
            $this->output->writeln("Calling: <info>".$this->client->getFullUrl($call, $args)."</info>");
        }

        $res =  $this->client->call($call, $args);

        if (!$res) {
            $this->error($this->client->getError());
        }

        return $res;
    }

}
