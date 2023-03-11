<?php
/**
 * @package    plg_isrcset
 * @author     Pavel Lange <pavel@ilange.ru>
 * @link       https://github.com/i-lange/plg_isrcset
 * @copyright  (C) 2023 Pavel Lange <https://ilange.ru>
 * @license    GNU General Public License version 2 or later
 */

namespace Joomla\Plugin\Content\Isrcset\Helper;

defined('_JEXEC') or die;

use Exception;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Image\Image;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;

/**
 * Класс Helper с основной логикой обработки изображений
 * @since 1.0.0
 */
class IsrcsetHelper
{
    /**
     * @var array Массив атрибутов, которые необходимо обрабатывать
     * @since 1.0.0
     */
    private static $atributes = [
        'alt' => '',
        'class' => '',
        'sizes' => '(max-width: 1200px) 100vw, 3200px',
        'data' => ''
    ];

    /**
     * @var array Массив значений ширины изображений в px
     * @since 1.0.0
     */
    private static $widths = [
        4000,
        3600,
        3200,
        2800,
        2400,
        2000,
        1600,
        1200,
        800,
        600,
        400,
        300,
        200,
        100
    ];

    /**
     * @var array Матрица значений ширины и высоты для различных соотноений сторон
     * @since 1.0
     */
    private static $sizes = [
        '1x1' => [
            '3200x3200',
            '2800x2800',
            '2400x2400',
            '2000x2000',
            '1600x1600',
            '1200x1200',
            '1000x1000',
            '800x800',
            '600x600',
            '400x400',
            '200x200',
            '100x100'
        ],
        '1x2' => [
            '1600x3200',
            '1400x2800',
            '1200x2400',
            '1000x2000',
            '800x1600',
            '600x1200',
            '500x1000',
            '400x800',
            '300x600',
            '200x400',
            '100x200'
        ],
        '2x1' => [
            '3200x1600',
            '2800x1400',
            '2400x1200',
            '2000x1000',
            '1600x800',
            '1200x600',
            '1000x500',
            '800x400',
            '600x300',
            '400x200',
            '200x100'
        ],
        '1x3' => [
            '1000x3000',
            '900x2700',
            '800x2400',
            '700x2100',
            '600x1800',
            '500x1500',
            '400x1200',
            '300x9000',
            '250x750',
            '125x375'
        ],
        '3x1' => [
            '3000x1000',
            '2700x900',
            '2400x800',
            '2100x700',
            '1800x600',
            '1500x500',
            '1200x400',
            '900x400',
            '750x250',
            '375x125'
        ],
        '2x3' => [
            '2000x3000',
            '1800x2700',
            '1600x2400',
            '1200x1800',
            '1000x1500',
            '800x1200',
            '600x900',
            '500x750',
            '400x600',
            '200x300'
        ],
        '3x2' => [
            '3000x2000',
            '2700x1800',
            '2400x1600',
            '1800x1200',
            '1500x1000',
            '1200x800',
            '900x600',
            '750x500',
            '600x400',
            '300x200'
        ],
        '1x4' => [
            '1000x4000',
            '900x3600',
            '800x3200',
            '700x2800',
            '600x2400',
            '500x2000',
            '400x1600',
            '300x1200',
            '200x800',
            '100x400'
        ],
        '4x1' => [
            '4000x100',
            '3600x900',
            '3200x800',
            '2800x700',
            '2400x600',
            '2000x500',
            '1600x400',
            '1200x300',
            '800x200',
            '400x100'
        ],
        '3x4' => [
            '2400x3200',
            '2100x2800',
            '1800x2400',
            '1500x2000',
            '1200x1600',
            '900x1200',
            '750x1000',
            '600x800',
            '450x600',
            '300x400',
            '150x200'
        ],
        '4x3' => [
            '3200x2400',
            '2800x2100',
            '2400x1800',
            '2000x1500',
            '1600x1200',
            '1200x900',
            '1000x750',
            '800x600',
            '600x450',
            '400x300',
            '200x150'
        ],
        '16x9' => [
            '3200x1800',
            '2816x1584',
            '2560x1440',
            '2048x1152',
            '1600x900',
            '1280x720',
            '960x540',
            '800x450',
            '640x360',
            '400x225',
            '320x180'
        ],
        '9x16' => [
            '1800x3200',
            '1584x2816',
            '1440x2560',
            '1152x2048',
            '900x1600',
            '720x1280',
            '540x960',
            '450x800',
            '360x640',
            '225x400',
            '180x320'
        ],
        '25x10' => [
            '3200x1280',
            '2800x1120',
            '2500x1000',
            '2000x800',
            '1600x640',
            '1250x500',
            '800x320',
            '625x250',
            '400x160',
            '200x80'
        ],
        '10x25' => [
            '1280x3200',
            '1120x2800',
            '1000x2500',
            '800x2000',
            '640x1600',
            '500x1250',
            '320x800',
            '250x625',
            '160x400',
            '80x200'
        ]
    ];

    /**
     * Рендер изображения по его адресу и набору аттрибутов
     * @param string $image URL изображения
     * @param array $attrs Массив дополнительных атрибутов (alt, class, sizes, data и т.п.)
     * @return string
     * @since 1.0.0
     */
    public static function renderImage(
        string $image,
        array $attrs
    ): string {
        $image = HTMLHelper::cleanImageURL($image);
        $path = JPATH_SITE . '/' . ltrim($image->url, '/');

        if (file_exists($path)) {
            return self::getImage(new Image($path), $attrs);
        }

        return '<br/>[' . Text::_('PLG_ISRCSET_ERROR_IMAGE_LOAD') . ']<br/>[' . $path . ']<br/>';
    }

    /**
     * Рендер Iframe по адресу содержимого и набору аттрибутов
     * @param string $src URL содержимого
     * @param array $attrs Массив дополнительных атрибутов (class, data и т.п.)
     * @return string
     * @since 1.0.0
     */
    public static function renderIframe(
        string $src,
        array $attrs
    ): string {
        $path = ltrim($src, '/');

        return self::getIframe($path, $attrs);
    }

    /**
     * Парсинг аттрибутов из строки
     * @param string $string Строка для разбора
     * @param array $attrs Массив атрибутов (alt, class, sizes, data и т.п.)
     * @return array Ассоциативный массив найденных атрибутов
     * @since 1.0.0
     */
    public static function getAtributes(
        string $string,
        array $attrs = []
    ): array {
        $attrs = (!empty($attrs)) ? $attrs : self::$atributes;

        // Собираем массив атрибутов (alt, class, sizes, data и т.п.)
        $attrsArr = [];
        foreach ($attrs as $name) {
            switch ($name) {
                case 'data':
                    $tempAttr = self::parseDataAttribute($string);
                    break;
                default:
                    $tempAttr = self::parseAttribute($string, $name);
            }
            
            if ($tempAttr !== '' || $name === 'alt') {
                $attrsArr[$name] = $tempAttr;
            }
        }

        return $attrsArr;
    }

    /**
     * Получение значения атрибута по имени
     * @param string $string
     * @param string $name
     * @return string
     * @since @since 1.0.0
     */
    private static function parseAttribute(string $string, string $name): string
    {
        $result = '';
        $regex = '/' . $name . '="(.*?)"/i';

        if (preg_match($regex, $string, $matches)) {
            $result = $matches[1];
        }

        return $result;
    }

    /**
     * Получение строки значений data-атрибутов
     * @param string $string
     * @return string
     * @since 1.0.0
     */
    private static function parseDataAttribute(string $string): string
    {
        $result = '';
        $regex = '/(data-[[:alnum:]]*?)[ \t\/>]/i';

        if (preg_match_all($regex, $string, $matches)) {
            $result = implode(' ', $matches[1]);
        }

        return $result;
    }

    /**
     * Получение строки тега img, готового для встраивания
     * @param Image $image Объект изображения
     * @param array $attrs Ассоциативный массив атрибутов ('alt', 'class', 'sizes', 'data' и т.п.)
     * @return string
     * @since 1.0.0
     */
    private static function getImage(
        Image $image,
        array $attrs = []
    ): string {
        // Получаем соотношение сторон и массив этих двух значений
        $ratio = self::getRatio($image);
        $ratioArr = explode('x', $ratio);

        return '<img' .
            ' width="' . $image->getWidth() . '"' .
            ' height="' . $image->getHeight() . '"' .
            ' src="' . str_replace(JPATH_SITE, Uri::root(true), $image->getPath()) . '"' .
            ' srcset="' . self::generatePlaceholder($ratioArr[0], $ratioArr[1]) . '"' .
            ' ' . self::generateAttrString($attrs) .
            ' data-srcset="' . self::generateSrcset($image, $ratio, $ratioArr[0], $ratioArr[1]) . '"' .
            ' decoding="async"' .
            ' itemprop="image" />';
    }

    /**
     * Получение строки тега iframe, готового для встраивания
     * @param string $src URL содержимого
     * @param array $attrs Ассоциативный массив атрибутов ('title', 'class', 'data' и т.п.)
     * @return string
     * @since 1.0.0
     */
    private static function getIframe(
        string $src,
        array $attrs = []
    ): string {

        return '<iframe src' .
            ' width="3200"' .
            ' height="1800"' .
            ' ' . self::generateAttrString($attrs) .
            ' data-src="' . $src . '"' .
            ' allowfullscreen>' .
            '</iframe>';
    }

    /**
     * Получение соотношения сторон изображения
     * @param Image $image Объект изображения
     * @return string
     * @since 1.0.0
     */
    protected static function getRatio(Image $image): string
    {
        $width = $image->getWidth();
        $height = $image->getHeight();

        $greatestCommonDivisor = static function ($width, $height) use (&$greatestCommonDivisor) {
            return ($width % $height) ? $greatestCommonDivisor($height, $width % $height) : $height;
        };

        $divisor = $greatestCommonDivisor($width, $height);
        return $width / $divisor . 'x' . $height / $divisor;
    }

    /**
     * Получение готовой строки атрибутов для тега
     * @param array $attrs Ассоциативный массив атрибутов ('alt', 'title', 'class', 'sizes', 'data' и т.п.)
     * @return string
     * @since 1.0.0
     */
    private static function generateAttrString(array $attrs = []): string
    {
        if (empty($attrs)) {
            return '';
        }
        
        $attrsArr = [];
        
        foreach ($attrs as $name => $value) {
            switch ($name) {
                case 'data':
                    $tempValue = ($value) ?: self::$atributes[$name];
                    break;
                default:
                    $tempValue = $name . '="' . (($value) ?: self::$atributes[$name]) . '"';
            }

            if ($tempValue !== '' || $name === 'alt') {
                $attrsArr[] = $tempValue;
            }            
        }

        return implode(' ', $attrsArr);
    }    

    /**
     * Генерация заглушки в формате base64 на основе соотношения сторон изображения
     * @param int $width
     * @param int $height
     * @return string
     * @since 1.0.0
     */
    private static function generatePlaceholder(int $width, int $height): string
    {
        $image = imagecreatetruecolor($width, $height);
        $color = imagecolorallocate($image, 0, 0, 0);
        imagecolortransparent($image, $color);

        ob_start();
        imagegif($image);
        $data = ob_get_contents();
        ob_end_clean();

        imagedestroy($image);

        return 'data:image/gif;base64,' . base64_encode($data) . ' ' . $width . 'w';
    }

    /**
     * Генерация уменьшенных копий и сборка значения атрибута srcset
     * @param Image $image Объект изображения
     * @param string $ratio Соотношение сторон (например '16x9')
     * @param int $ratioWidth Ширина в соотношении сторон
     * @param int $ratioHeight Высота в соотношении сторон
     * @return string значение атрибута srcset
     * @since 1.0.0
     */
    protected static function generateSrcset(Image $image, string $ratio, int $ratioWidth, int $ratioHeight): string
    {
        $sizes = [];
        $imgWidth = $image->getWidth();
        $imgPath = $image->getPath();
        $imgExt = pathinfo($imgPath, PATHINFO_EXTENSION);

        switch ($imgExt) {
            case 'webp':
                $imgType = IMAGETYPE_WEBP;
                break;
            case 'png':
                $imgType = IMAGETYPE_PNG;
                break;
            case 'gif':
                $imgType = IMAGETYPE_GIF;
                break;
            default:
                $imgType = IMAGETYPE_JPEG;
        }
        $dir = dirname($imgPath) . '/thumbs';
        $name = pathinfo($imgPath, PATHINFO_FILENAME);
        $result[] = str_replace(JPATH_SITE, Uri::root(true), $imgPath) . ' ' . $imgWidth . 'w';

        try {
            if (array_key_exists($ratio, self::$sizes)) {
                [$res, $sizes] = self::prepareDataSet(self::$sizes[$ratio], $dir, $name, $imgExt, $imgWidth);
            } else {
                $newSizes = [];
                foreach (self::$widths as $width) {
                    if ($imgWidth > $width) {
                        $height = $width / $ratioWidth * $ratioHeight;
                        if ($height % 10 === 0) {
                            $newSizes[] = $width . 'x' . $height;
                        }
                    }
                }

                [$res, $sizes] = self::prepareDataSet($newSizes, $dir, $name, $imgExt, $imgWidth);
            }
            
            $result += $res;
            $thumbs = $image->generateThumbs($sizes, Image::CROP_RESIZE);
        } catch (Exception $e) {
            $thumbs = [];
        }

        if (!$thumbs) {
            return implode(", ", $result);
        }

        foreach ($thumbs as $i => $thumb) {
            $thumbPath = $dir . '/' . $sizes[$i] . '/' . $name . '.' . $imgExt;

            if (!is_dir(dirname($thumbPath)) && !mkdir(dirname($thumbPath), 0755, true)) {
                continue;
            }

            try {
                $thumb->toFile($thumbPath, $imgType);
                $result[] = str_replace(JPATH_SITE, Uri::root(true), $thumbPath) . ' ' . $thumb->getWidth() . 'w';
            } catch (Exception $e) {
                continue;
            }
        }

        return implode(", ", $result);
    }

    /**
     * Подготовка списка разрешений, которые нужно сгенерировать
     * @param array $list Список разрешений
     * @param string $dir Папка с уменьшенными копиями
     * @param string $name Имя исходного файла
     * @param string $imgExt Расширение исходного файла
     * @param int $imgWidth Ширина исходного изображения
     * @return array $result уже сгенерированы, $sizes необходимо сгенерировать
     * @since 1.0.0
     */
    private static function prepareDataSet(
        array $list,
        string $dir,
        string $name,
        string $imgExt,
        int $imgWidth
    ): array {
        $result = $sizes = [];
        foreach ($list as $size) {
            $thumbPath = $dir . '/' . $size . '/' . $name . '.' . $imgExt;
            $sizeWidth = intval(explode('x', $size)[0]);
            if ($imgWidth > $sizeWidth) {
                if (file_exists($thumbPath)) {
                    $result[] = str_replace(JPATH_SITE, Uri::root(true), $thumbPath) . ' ' . $sizeWidth . 'w';
                } else {
                    $sizes[] = $size;
                }
            }
        }
        
        return [$result, $sizes];
    }
}