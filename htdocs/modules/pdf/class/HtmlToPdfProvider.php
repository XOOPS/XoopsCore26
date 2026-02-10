<?php
/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

use Xoops\Core\Service\AbstractContract;
use Xoops\Core\Service\Contract\HtmlToPdfInterface;
use Xoops\Core\Service\Response;

/**
 * HtmlToPdf provider for service manager
 *
 * @category  ServiceProvider
 * @package   HtmlToPdfProvider
 * @author    Richard Griffith <richard@geekwright.com>
 * @copyright 2013-2014 XOOPS Project (http://xoops.org)
 * @license   GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @link      http://xoops.org
 * @since     2.6.0
 */
class HtmlToPdfProvider extends AbstractContract implements HtmlToPdfInterface
{
    /** @var string $moddir */
    private $moddir = 'pdf';

    /** @var TCPDF $pdfEngine */
    protected $pdfEngine;

    /** @var string $pageOrientation */
    protected $pageOrientation = 'P';

    /** @var string $pageSize */
    protected $pageSize = 'A4';

    /** @var string $unit */
    protected $unit = 'mm';

    /** @var float $leftMargin */
    protected $leftMargin;

    /** @var float $topMargin */
    protected $topMargin;

    /** @var float $rightMargin */
    protected $rightMargin;

    /** @var float $bottomMargin */
    protected $bottomMargin;

    /** @var string $fontFamily */
    protected $fontFamily;

    /** @var string $fontStyle  */
    protected $fontStyle;

    /** @var float $fontSize   */
    protected $fontSize;

    /** @var string $monoFontFamily */
    protected $monoFontFamily;

    /** @var string $pdfAuthor */
    protected $pdfAuthor;

    /** @var string $pdfTitle */
    protected $pdfTitle;

    /** @var string $pdfSubject */
    protected $pdfSubject;

    /** @var string[] $pdfKeywords */
    protected $pdfKeywords;

    /** @var string $pdfCreator */
    protected $pdfCreator;

    /** @var string[] $moduleConfigs */
    protected $moduleConfigs;

    /**
     * __construct
     */
    public function __construct()
    {
        $this->resetPdf();
    }

    /**
     * setFromConfigs - set property from config value or default
     *
     * @param string $name     config name
     * @param string $property property name
     * @param mixed  $default  default value
     *
     * @return void
     */
    private function setFromConfigs($name, $property, $default)
    {
        $this->$property = $default;
        if (isset($this->moduleConfigs[$name])) {
            $value = $this->moduleConfigs[$name];
            $this->$property = empty($value) ? $default : $value;
        }
    }
    /**
     * resetPdf - resets to default state
     *
     * @return void
     */
    protected function resetPdf()
    {
        unset($this->pdfEngine);

        unset($this->pdfAuthor);
        unset($this->pdfTitle);
        unset($this->pdfSubject);
        unset($this->pdfKeywords);

        $this->moduleConfigs = \Xoops::getInstance()->getModuleConfigs($this->moddir);

        $this->setFromConfigs('page_orientation', 'pageOrientation', 'P');
        $this->setFromConfigs('page_size', 'pageSize', 'A4');
        $this->setFromConfigs('pdf_creator', 'pdfCreator', 'XOOPS');

        $this->setFromConfigs('font_family', 'fontFamily', null);
        $this->setFromConfigs('font_style', 'fontStyle', '');
        $this->setFromConfigs('font_style', 'fontStyle', '');
        $this->setFromConfigs('font_size', 'fontSize', 10);
        $this->setFromConfigs('monofont_family', 'monoFontFamily', null);
        $this->setFromConfigs('size_unit', 'unit', 'mm');
        $this->setFromConfigs('margin_left', 'leftMargin', null);
        $this->setFromConfigs('margin_top', 'topMargin', null);
        $this->setFromConfigs('margin_right', 'rightMargin', null);
        $this->setFromConfigs('margin_bottom', 'bottomMargin', null);
    }

    /**
     * startPdf - start a new pdf
     *
     * @param Response $response \Xoops\Core\Service\Response object
     *
     * @return void
     */
    public function startPdf($response)
    {
        $this->resetPdf();
    }

    /**
     * getName - get a short name for this service provider. This should be unique within the
     * scope of the named service, so using module dirname is suggested.
     *
     * @return string - a unique name for the service provider
     */
    public function getName()
    {
        return 'pdf';
    }

    /**
     * getDescription - get human readable description of the service provider
     *
     * @return string
     */
    public function getDescription()
    {
        return 'Simple HTML to PDF using TCPDF.';
    }

    /**
     * setPageOrientation - set page orientation
     *
     * @param Response $response        \Xoops\Core\Service\Response object
     * @param string   $pageOrientation page orientation, 'P' for portrait, 'L' for landscape
     *
     * @return void
     */
    public function setPageOrientation($response, $pageOrientation)
    {
        $this->pageOrientation = $pageOrientation;
        if (isset($this->pdfEngine)) {
            $this->pdfEngine->setPageOrientation($this->pageOrientation, true, $this->bottomMargin);
        }
    }

    /**
     * setPageSize - set standard page size
     *
     * @param Response $response \Xoops\Core\Service\Response object
     * @param string   $pageSize standard named page size, i.e. 'LETTER', 'A4', etc.
     *
     * @return void
     */
    public function setPageSize($response, $pageSize)
    {
        $this->pageSize = $pageSize;
        if (isset($this->pdfEngine)) {
            $response->setSuccess(false);
            $response->addErrorMessage('Cannot change pagesize now.');
            //$this->pdfEngine->setPageFormat($this->pageSize, $this->pageOrientation);
        }
    }

    /**
     * setBaseUnit - set unit of measure for page size, margins, etc.
     *
     * @param Response $response \Xoops\Core\Service\Response object
     * @param string   $unit     unit used in page size, margins. Possible values include
     *                           'mm' = millimeter, "in" = inches, 'pt' = typographic points
     *
     * @return void
     */
    public function setBaseUnit($response, $unit)
    {
        $this->unit = $unit;
        if (isset($this->pdfEngine)) {
            $this->pdfEngine->setPageUnit($unit);
        }
    }

    /**
     * setMargins - set margin sizes
     *
     * @param Response $response     \Xoops\Core\Service\Response object
     * @param float    $leftMargin   left margin in base units, @see setBaseUnits()
     * @param float    $topMargin    top margin in base units
     * @param float    $rightMargin  right margin in base units
     * @param float    $bottomMargin bottom margin in base units
     *
     * @return void - response->value set to absolute URL to avatar image
     */
    public function setMargins($response, $leftMargin, $topMargin, $rightMargin, $bottomMargin)
    {
        $this->$leftMargin = $leftMargin;
        $this->$topMargin = $topMargin;
        $this->$rightMargin = $rightMargin;
        $this->$bottomMargin = $bottomMargin;
        if (isset($this->pdfEngine)) {
            $this->pdfEngine->SetMargins($this->leftMargin, $this->topMargin, $this->rightMargin);
            if (empty($this->bottomMargin)) {
                $this->bottomMargin = PDF_MARGIN_BOTTOM;
            }
            $this->pdfEngine->SetAutoPageBreak(true, $this->bottomMargin);
        }
    }

    /**
     * setBaseFont - set the base font used in rendering
     *
     * @param Response $response   \Xoops\Core\Service\Response object
     * @param string   $fontFamily font family
     * @param string   $fontStyle  font style ('bold', 'italic', etc.)
     * @param float    $fontSize   font size in points
     *
     * @return void
     */
    public function setBaseFont($response, $fontFamily, $fontStyle = '', $fontSize = null)
    {
        $this->fontFamily = $fontFamily;
        $this->fontStyle  = $fontStyle;
        $this->fontSize   = $fontSize;
        if (isset($this->pdfEngine)) {
            $this->pdfEngine->SetFont($this->fontFamily, $this->fontStyle, $this->fontSize);
        }
    }

    /**
     * setDefaultMonospacedFont - default monotype font used in rendering
     *
     * @param Response $response       \Xoops\Core\Service\Response object
     * @param string   $monoFontFamily font family
     *
     * @return void
     */
    public function setDefaultMonospacedFont($response, $monoFontFamily)
    {
        $this->monoFontFamily = $monoFontFamily;
        if (isset($this->pdfEngine)) {
            $this->pdfEngine->SetDefaultMonospacedFont($this->monoFontFamily);
        }
    }

    /**
     * setAuthor - set author in pdf meta data
     *
     * @param Response $response  \Xoops\Core\Service\Response object
     * @param string   $pdfAuthor author name
     *
     * @return void
     */
    public function setAuthor($response, $pdfAuthor)
    {
        $this->pdfAuthor = $this->decodeEntities($pdfAuthor);
        if (isset($this->pdfEngine)) {
            $this->pdfEngine->SetAuthor($this->pdfAuthor);
        }
    }

    /**
     * setTitle - set title in pdf meta data
     *
     * @param Response $response \Xoops\Core\Service\Response object
     * @param string   $pdfTitle document title
     *
     * @return void
     */
    public function setTitle($response, $pdfTitle)
    {
        $this->pdfTitle = $this->decodeEntities($pdfTitle);
        if (isset($this->pdfEngine)) {
            $this->pdfEngine->SetTitle($this->pdfTitle);
        }
    }

    /**
     * setSubject - set subject in pdf meta data
     *
     * @param Response $response   \Xoops\Core\Service\Response object
     * @param string   $pdfSubject document subject
     *
     * @return void
     */
    public function setSubject($response, $pdfSubject)
    {
        $this->pdfSubject = $this->decodeEntities($pdfSubject);
        if (isset($this->pdfEngine)) {
            $this->pdfEngine->SetSubject($this->pdfSubject);
        }
    }

    /**
     * setKeywords - set keywords in pdf meta data
     *
     * @param Response $response    \Xoops\Core\Service\Response object
     * @param string[] $pdfKeywords array of keywords pertaining to document
     *
     * @return void
     */
    public function setKeywords($response, $pdfKeywords)
    {
        $this->pdfKeywords = $pdfKeywords;
        if (isset($this->pdfEngine)) {
            $keywords =
                is_array($this->pdfKeywords) ? implode(', ', $this->pdfKeywords) : (string) $this->pdfKeywords;
            $this->pdfEngine->SetKeywords($keywords);
        }
    }

    /**
     * addHtml - add HTML formatted text to document. This may be called multiple times
     *
     * @param Response $response \Xoops\Core\Service\Response object
     * @param string   $html     HTML formated text to include in document
     *                           array     user info, 'uid', 'uname' and 'email' required
     *
     * @return void
     */
    public function addHtml($response, $html)
    {
        $this->initPdf();
        $this->pdfEngine->AddPage();
        $this->pdfEngine->writeHTML($html, true, false, true, false, '');
    }

    /**
     * outputPdfInline - output a named pdf document file inline
     *
     * @param Response $response \Xoops\Core\Service\Response object
     * @param string   $name     filename for file
     *
     * @return void
     */
    public function outputPdfInline($response, $name)
    {
        $this->initPdf();
        if (empty($name)) {
            $name = 'requested.pdf';
        }
        $this->pdfEngine->lastPage();
        $this->pdfEngine->Output($name, 'I');
    }

    /**
     * outputPdfDownload - output a named pdf document file for download
     *
     * @param Response $response \Xoops\Core\Service\Response object
     * @param string   $name     filename for file
     *
     * @return void
     */
    public function outputPdfDownload($response, $name)
    {
        $this->initPdf();
        if (empty($name)) {
            $name = 'requested.pdf';
        }
        $this->pdfEngine->lastPage();
        $this->pdfEngine->Output($name, 'D');
    }

    /**
     * fetchPdf - fetch rendered document as a string
     *
     * @param Response $response \Xoops\Core\Service\Response object
     *
     * @return void - response->value set to string containing document
     */
    public function fetchPdf($response)
    {
        $this->initPdf();
        $this->pdfEngine->lastPage();
        $response->setValue($this->pdfEngine->Output('', 'S'));
    }

    /**
     * initPdf - initialize TCPDF with current setting
     *
     * @return void
     */
    private function initPdf()
    {
        if (empty($this->pdfEngine)) {
            $this->pdfEngine = new TCPDF($this->pageOrientation, $this->unit, $this->pageSize, true, 'UTF-8', false);
            if (isset($this->pdfAuthor)) {
                $this->pdfEngine->SetAuthor($this->pdfAuthor);
            }
            if (isset($this->pdfTitle)) {
                $this->pdfEngine->SetTitle($this->pdfTitle);
            }
            if (isset($this->pdfSubject)) {
                $this->pdfEngine->SetSubject($this->pdfSubject);
            }
            if (isset($this->pdfKeywords)) {
                $keywords =
                    is_array($this->pdfKeywords) ? implode(', ', $this->pdfKeywords) : (string) $this->pdfKeywords;
                $this->pdfEngine->SetKeywords($keywords);
            }
            if (!empty($this->pdfCreator)) {
                $this->pdfEngine->SetCreator($this->pdfCreator);
            }
            if (!empty($this->fontFamily)) {
                $this->pdfEngine->SetFont($this->fontFamily, $this->fontStyle, $this->fontSize);
            }
            if (!empty($this->monoFontFamily)) {
                $this->pdfEngine->SetDefaultMonospacedFont($this->monoFontFamily);
            }
            if (!empty($this->leftMargin)) {
                $this->pdfEngine->SetMargins($this->leftMargin, $this->topMargin, $this->rightMargin);
            }
            if (empty($this->bottomMargin)) {
                $this->bottomMargin = PDF_MARGIN_BOTTOM;
            }
            $this->pdfEngine->SetAutoPageBreak(true, $this->bottomMargin);
        }
    }

    /**
     * decodeEntities - handles numeric entities
     *
     * @param string $text text to decode
     *
     * @return string decoded string
     */
    private function decodeEntities($text)
    {
        $text= html_entity_decode($text, ENT_QUOTES, "UTF-8");
        $text= preg_replace_callback(
            '/&#(\d+);/m',
            function ($m) {
                return mb_convert_encoding(chr($m[1]), 'UTF-8', 'ISO-8859-1');
            },
            $text
        ); // decimal notation
        $text= preg_replace_callback(
            '/&#x([a-f0-9]+);/mi',
            function ($m) {
                return mb_convert_encoding(chr(hexdec($m[1])), 'UTF-8', 'ISO-8859-1');
            },
            $text
        );  //hex notation
        return $text;
    }
}
