<?php
/**
 * @package    plg_isrcset
 * @author     Pavel Lange <pavel@ilange.ru>
 * @link       https://github.com/i-lange/plg_isrcset
 * @copyright  (C) 2023 Pavel Lange <https://ilange.ru>
 * @license    GNU General Public License version 2 or later
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Installer\InstallerScript;
use Joomla\CMS\Language\Text;

defined('_JEXEC') or die;

class PlgContentIsrcsetInstallerScript extends InstallerScript
{  
    /**
     * Минимальная версия PHP, необходимая для установки модуля
     * @var string
     * @since 1.0.0
     */
    protected $minimumPhp = '7.2';

    /**
     * Минимальная версия Joomla, необходимая для установки модуля
     * @var string
     * @since 1.0.0
     */
    protected $minimumJoomla = '4.2.0';

    /**
     * Список файлов, которые необходимо удалить
     * @var array
     * @since 1.0.0
     */
    protected $deleteFiles = [];

    /**
     * Список папок, которые необходимо удалить
     * @var array
     * @since 1.0.0
     */
    protected $deleteFolders = [];

    /**
     * Объект приложения
     * @var object
     * @since 1.0.0
     */
    protected $app = null;

    /**
     * DBO
     * @var object
     * @since 1.0.0
     */
    protected $db = null;

    /**
     * Конструктор
     * @throws Exception
     * @since 1.0.0
     */
    public function __construct()
    {
        // Получаем объект приложения
        $this->app = Factory::getApplication();

        // Получаем DBO
        $this->db = Factory::getContainer()->get('DatabaseDriver');
    }


    /**
     * Метод запускается непосредственно перед установкой/обновлением/удалением плагина
     * @param string $type Тип действия, которое выполняется (install|uninstall|discover_install|update)
     * @param InstallerAdapter $parent Класс, вызывающий этот метод.
     * @return bool Возвращает True для продолжения, False для отмены установки/обновления/удаления
     * @throws Exception
     * @since 1.0.0
     */
    public function preflight($type, $parent): bool
    {
        if (!parent::preflight($type, $parent)) {
            return false;
        }

        return true;
    }

    /**
     * Метод запускается непосредственно после установки/обновления/удаления плагина
     * @param string $type Тип действия, которое выполняется (install|uninstall|discover_install|update)
     * @param InstallerAdapter $parent Класс, вызывающий этот метод.
     * @return bool True при успешном выполнении
     * @throws Exception
     * @since 1.0.0
     */
    public function postflight(string $type, InstallerAdapter $parent): bool
    {
        if ($type === 'uninstall') {
            $this->app->enqueueMessage(Text::_('PLG_ISRCSET_XML_UNINSTALL_OK'), 'warning');
            return true;
        }

        // Удаляем файлы и папки, в которых больше нет необходимости
        $this->removeFiles();

        // Активация плагина
        if ($type === 'install') {
            $query = $this->db->getQuery(true);
            $query->select('extension_id');
            $query->from('#__extensions');
            $query->where($this->db->quoteName('element') . ' = ' . $this->db->quote($parent->getElement()));
            $query->where($this->db->quoteName('type') . ' = ' . $this->db->quote('plugin'));
            $query->where($this->db->quoteName('folder') . ' = ' . $this->db->quote('content'));
            $result = $this->db->setQuery($query)->loadResult();

            if ($result) {
                $this->enablePlugin($parent);
            }

            return true;
        }

        if ($type === 'update') {
            // Получаем данные из xml файла модуля
            $xml = $parent->getManifest();

            // Пишем сообщение со ссылками на сайт автора и на репозиторий
            $message[] = '<p class="fs-2 mb-2">' . Text::_('ISRCSET') . ' [' . $parent->getElement() . ']</p>';
            $message[] = '<ul>';
            $message[] = '<li>' . Text::_('PLG_ISRCSET_VERSION') . ': ' . $xml->version . '</li>';
            $message[] = '<li>' . Text::_('PLG_ISRCSET_AUTHOR') . ': ' . $xml->author . '</li>';
            $message[] = "<li><a href='https://ilange.ru' target='_blank'>https://ilange.ru</a></li>";
            $message[] = "<li><a href='https://github.com/i-lange/plg_" . $parent->getElement() . "' target='_blank'>GitHub</a></li>";
            $message[] = '</ul>';
            $message[] = '<p class="mb-2">' . Text::_('PLG_ISRCSET_DONATE') . ': </p>';
            $message[] = "<a href='" . Text::_('PLG_ISRCSET_DONATE_URL')
                . "' target='_blank' class='btn btn-primary'>" . Text::_('PLG_ISRCSET_DONATE_BTN') . "</a>";
            $msgStr = implode($message);

            // Показываем сообщение
            echo $msgStr;
        }

        return true;
    }

    /**
     * Активируем плагин
     * @param InstallerAdapter $parent
     * @since 1.0.0
     */
    protected function enablePlugin(InstallerAdapter $parent)
    {
        $plugin = new stdClass();
        $plugin->type = 'plugin';
        $plugin->element = $parent->getElement();
        $plugin->folder = (string)$parent->getParent()->manifest->attributes()['group'];
        $plugin->enabled = 1;

        $this->db->updateObject('#__extensions', $plugin, ['type', 'element', 'folder']);
    }
}