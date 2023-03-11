<?php
/**
 * @package    plg_isrcset
 * @author     Pavel Lange <pavel@ilange.ru>
 * @link       https://github.com/i-lange/plg_isrcset
 * @copyright  (C) 2023 Pavel Lange <https://ilange.ru>
 * @license    GNU General Public License version 2 or later
 */

namespace Joomla\Plugin\Content\Isrcset\Extension;

use Exception;
use Joomla\CMS\Document\Document;
use Joomla\CMS\Document\HtmlDocument;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\DispatcherInterface;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;
use Joomla\Plugin\Content\Isrcset\Helper\IsrcsetHelper;

defined('_JEXEC') or die;

/**
 * Плагин Isrcset для оптимизации встраиваемых <img> и <iframe>
 * @since 1.0.0
 */
final class Isrcset extends CMSPlugin implements SubscriberInterface
{
    /**
     * Загрузка языкового файла при инстанцировании
     * @var bool
     * @since 1.0.0
     */
    protected $autoloadLanguage = true;

    /**
     * Искать и регистрировать старые слушатели событий, 
     * т.е. методы, которые принимают развёрнутые аргументы?
     * @var boolean
     * @since 1.0.0
     */
    protected $allowLegacyListeners = false;

    /**
     * Документ
     * @var Document
     * @since 1.0.0
     */
    private $document;

    /**
     * Конструктор
     * @param DispatcherInterface $subject Объект для наблюдения
     * @param Document $document Документ
     * @param array $config Необязательный ассоциативный массив настроек
     * Значения ключей включают 'name', 'group', 'params', 'language' и пр.
     * @since 1.0.0
     */
    public function __construct(DispatcherInterface $subject, Document $document, array $config = [])
    {
        parent::__construct($subject, $config);

        $this->document = $document;
    }

    /**
     * Возвращает массив событий, которые будут прослушиваться
     * Ключами массива являются имена событий, а значениями могут быть:
     * - Имя метода для вызова (приоритет по умолчанию равен 0)
     * - Массив, состоящий из имени вызываемого метода и приоритета.
     * @return string[]
     * @since 1.0.0
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onBeforeCompileHead' => 'initialize',
            'onContentBeforeSave' => 'saveContentProcess',
        ];
    }

    /**
     * Добавление скриптов и стилей
     * @return void
     * @throws Exception
     * @since 1.0.0
     */
    public function initialize()
    {
        // Проверяем, что мы именно на фронте сайта
        if (!$this->getApplication()->isClient('site')) {
            return;
        }

        // Проверяем тип документа, нам нужен только Html
        if (!($this->document instanceof HtmlDocument)) {
            return;
        }
        
        // Добавляем стили и скрипты с помощью Web Assets Manager
        $this->document
            ->getWebAssetManager()
            ->getRegistry()
            ->addExtensionRegistryFile('plg_content_isrcset');        
        
        if ($this->params->get('use_css')) {
            $this->document
                ->getWebAssetManager()
                ->useStyle('plg_content_isrcset.front.min');
        }
        
        if ($this->params->get('use_js')) {
            $this->document
                ->getWebAssetManager()
                ->useScript('plg_content_isrcset.front.min');
        }
    }

    /**
     * Метод обработки контента, замена тегов img и iframe
     * @param Event $event Объект события
     * @return void
     * @since 1.0.0
     */
    public function saveContentProcess(Event $event)
    {        
        /** @var string $context Контекст */
        /** @var object $table Объект таблицы */
        [$context, $table] = $event->getArguments();
        
        //JLog::add('Вывод: ' . $table->fulltext, JLog::ERROR, 'jerror');

        if (($context === 'com_content.article') && (strpos($table->fulltext, 'img') !== false)) {
            $text = $table->fulltext;

            if ($this->params->get('process_img')) {
                $text = $this->processImages($text);
            }

            if ($this->params->get('process_iframe')) {
                $text = $this->processIframes($text);
            }
            
            $table->fulltext = $text;
        }
    }

    /**
     * Метод ищет вхождения тегов img, обрабатывает
     * @param string $text Исходный текст
     * @return string Текст с обработанными изображениями
     * @since 1.0.0
     */
    public function processImages(string $text):string
    {
        $regex = '/<img\s(.*?)>/i';
        preg_match_all($regex, $text, $images, PREG_SET_ORDER);

        if ($images) {
            foreach ($images as $img) {
                $attrs = IsrcsetHelper::getAtributes($img[0], ['src', 'alt', 'class', 'sizes', 'data']);
                $image = array_shift($attrs);

                $src = JPATH_SITE . '/' . ltrim($image, '/');

                if (file_exists($src) && ($start = strpos($text, $img[0])) !== false) {
                    $text = substr_replace(
                        $text,
                        IsrcsetHelper::renderImage($image, $attrs),
                        $start,
                        strlen($img[0])
                    );
                }
            }
        }
        
        return $text;
    }

    /**
     * Метод ищет вхождения тегов iframe, обрабатывает
     * @param string $text Исходный текст
     * @return string Текст с обработанными iframe
     * @since 1.0.0
     */
    public function processIframes(string $text):string
    {
        $regex = '/<iframe\s(.*?)><\/iframe>/i';
        preg_match_all($regex, $text, $iframes, PREG_SET_ORDER);

        if ($iframes) {
            foreach ($iframes as $iframe) {
                $attrs = IsrcsetHelper::getAtributes($iframe[0], ['src', 'title', 'class', 'data']);
                $src = array_shift($attrs);

                if (($start = strpos($text, $iframe[0])) !== false) {
                    $text = substr_replace(
                        $text,
                        IsrcsetHelper::renderIframe($src, $attrs),
                        $start,
                        strlen($iframe[0])
                    );
                }
            }
        }

        return $text;
    }
    
}