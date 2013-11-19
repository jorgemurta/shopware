<?php
/**
 * Shopware 4.0
 * Copyright © 2013 shopware AG
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "Shopware" is a registered trademark of shopware AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 */

namespace Shopware\Components\Console\Command;

use Shopware\Components\DependencyInjection\ResourceLoader;
use Shopware\Components\DependencyInjection\ResourceLoaderAwareInterface;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Plugin\Plugin;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @category  Shopware
 * @package   Shopware\Components\Console\Command
 * @copyright Copyright (c) 2013, shopware AG (http://www.shopware.de)
 */
class PluginListCommand extends Command implements ResourceLoaderAwareInterface
{
    /**
     * @var ResourceLoader
     */
    private $container;

    /**
     * @param ResourceLoader $resourceLoader
     */
    public function setResourceLoader(ResourceLoader $resourceLoader = null)
    {
        $this->container = $resourceLoader;
    }

    protected function configure()
    {
        $this
            ->setName('sw:plugin:list')
            ->setDescription('List plugins')
            ->addOption(
                'filter',
                null,
                InputOption::VALUE_OPTIONAL,
                'Filter Plugins (inactive, active)'
            )
            ->addOption(
                'namespace',
                null,
                InputOption::VALUE_IS_ARRAY|InputOption::VALUE_OPTIONAL,
                'Filter Plugins by namespace (core, frontend, backend)',
                array()
            )
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var ModelManager $em */
        $em = $this->container->get('models');

        $repository = $em->getRepository('Shopware\Models\Plugin\Plugin');
        $builder = $repository->createQueryBuilder('plugin');
        $builder->addOrderBy('plugin.name');

        $filter = strtolower($input->getOption('filter'));
        if ($filter === 'active') {
            $builder->where('plugin.active = true');
        }

        if ($filter === 'inactive') {
            $builder->where('plugin.active = false');
        }

        $namespace = $input->getOption('namespace');
        if (count($namespace)) {
            $builder->where('p.namespace IN (:namespace)');
            $builder->setParameter('namespace', $namespace);
        }

        $plugins = $builder->getQuery()->execute();

        $rows = array();

        /** @var Plugin $plugin */
        foreach ($plugins as $plugin) {
            $rows[] = array(
                $plugin->getName(),
                $plugin->getLabel(),
                $plugin->getVersion(),
                $plugin->getAuthor(),
                $plugin->getActive() ? 'Yes' : 'No'
            );
        }

        $table = $this->getHelperSet()->get('table');
        $table->setHeaders(array('Plugin', 'Label', 'Version', 'Author', 'Active'))
              ->setRows($rows);

        $table->render($output);
    }
}