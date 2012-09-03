<?php
namespace Wlfrm\Bundle\TwitterBootstrapBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class LessPhpCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('assets:lessphp')
            ->setDescription('Compile current .less file from public/less dir into public/css dir')
            ->addOption('f', null, InputOption::VALUE_OPTIONAL, 'Set the output format, includes "default", "compressed"', 'default')
            ->addOption('c', null, InputOption::VALUE_OPTIONAL, 'Keep /* */ comments in output', false)
            ->addOption('in', null, InputOption::VALUE_OPTIONAL, 'optional source-filename to compile', 'bootstrap')
            ->addOption('out', null, InputOption::VALUE_OPTIONAL, 'optional result-filename', 'bootstrap')          
            ->addOption('bundlename', null, InputOption::VALUE_OPTIONAL, 'your bundle name, or just pick the vendor of bundle where .less files live', 'wlfrm')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $filesystem = $this->getContainer()->get('filesystem');
        $targetArg = 'web';
        $bundlesDir = $targetArg.'/bundles/';
        foreach ($this->getContainer()->get('kernel')->getBundles() as $bundle) {
            if (strpos(strtolower($bundle->getName()), $input->getOption('bundlename')) !== FALSE)
            {
              $sourceDir  = $bundlesDir.preg_replace('/bundle$/', '', strtolower($bundle->getName())).'/less';
              $targetDir  = $bundlesDir.preg_replace('/bundle$/', '', strtolower($bundle->getName())).'/css';
            }
            else {
                continue;
            }
            
            //cannot locate public/less directory into bundle
            if (!$filesystem->exists($sourceDir))
            {
              $output->writeln(sprintf('cannot locate %s directory into %s bundle', $sourceDir, $bundle->getName()));
              continue;
            }
            
            $output->writeln(sprintf('Compiling less file <comment>%2$s/%1$s.less</comment> into <comment>%3$s/%4$s.css</comment>', $input->getOption('in'), $sourceDir, $targetDir, $input->getOption('out')));
            
            $less = new \lessc();
            $less->addImportDir($sourceDir);
            
            if ($input->getOption('c'))
              $less->setPreserveComments(true);
            
            if ($input->getOption('f') !== 'default')
              $less->setFormatter($input->getOption('f'));
            
            $buffer = '';
            foreach (Finder::create()->in($sourceDir)->files()->name('/'.$input->getOption('in').'/') as $file)
            {
                $buffer = $file->getContents();
            }
            if (!$filesystem->exists($targetDir))
              $filesystem->mkdir($targetDir, 0777);
            file_put_contents($targetDir."/".$input->getOption('out').".css", $less->compile($buffer));
        }
    }
}