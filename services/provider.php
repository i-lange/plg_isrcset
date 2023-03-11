<?php
/**
 * @package    plg_isrcset
 * @author     Pavel Lange <pavel@ilange.ru>
 * @link       https://github.com/i-lange/plg_isrcset
 * @copyright  (C) 2023 Pavel Lange <https://ilange.ru>
 * @license    GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Event\DispatcherInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Plugin\Content\Isrcset\Extension\Isrcset;

/**
 * Класс Service Provider для плагина plg_isrcset
 * @since 1.0.0
 */
return new class implements ServiceProviderInterface
{
    /**
     * Регистрируем с помощью контейнера внедрения зависимостей
     * @param Container $container Контейнер DI
     * @return void
     * @since 1.0.0
     */
    public function register(Container $container)
    {        
        $container->set(
            PluginInterface::class,
            function (Container $container) {
                $config  = (array) PluginHelper::getPlugin('content', 'isrcset');

                $plugin = new Isrcset(
                    $container->get(DispatcherInterface::class),
                    Factory::getApplication()->getDocument(),
                    $config);
                
                $plugin->setApplication(Factory::getApplication());

                return $plugin;
            }
        );
    }
};