<?php

/*
 * This file is part of the Formicula package.
 *
 * Copyright Formicula Development Team
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zikula\FormiculaModule\Twig;

use Zikula\FormiculaModule\Helper\CaptchaHelper;

/**
 * Twig extension class.
 */
class TwigExtension extends \Twig_Extension
{
    /**
     * @var CaptchaHelper
     */
    private $captchaHelper;

    /**
     * TwigExtension constructor.
     *
     * @param CaptchaHelper $captchaHelper CaptchaHelper service instance
     */
    public function __construct(CaptchaHelper $captchaHelper)
    {
        $this->captchaHelper = $captchaHelper;
    }

    /**
     * Returns a list of custom Twig functions.
     *
     * @return array
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('zikulaformiculamodule_simpleCaptcha', [$this, 'simpleCaptcha'], ['is_safe' => ['html']])
        ];
    }

    /**
     * The zikulaformiculamodule_simpleCaptcha function adds a simplecaptcha image to a form.
     *
     * Example:
     *     {{ zikulaformiculamodule_simpleCaptcha(font='quikhand', size=14, bgColour='ffffff', fgColour='000000') }}
     *
     * @param string $font     Name of font to use (arial, freesans, quickhand, vera)
     * @param int    $size     Font size
     * @param string $bgColour Background colour (hex code without the # char)
     * @param string $fgColour Foreground colour (hex code without the # char)
     *
     * @return string The image markup
     */
    public function simpleCaptcha($font = 'quickhand', $size = 14, $bgColour = 'ffffff', $fgColour = '000000')
    {
        return $this->captchaHelper->createCaptcha($font, $size, $bgColour, $fgColour);
    }
}
