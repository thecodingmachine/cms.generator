<?php
namespace Mouf\Cms\Generator\Controllers;

use Mouf\Mvc\Splash\Controllers\Controller;
use Mouf\Html\Template\TemplateInterface;
use Mouf\Html\HtmlElement\HtmlBlock;
use Psr\Log\LoggerInterface;
use \Twig_Environment;
use Mouf\Html\Renderer\Twig\TwigTemplate;

/**
 * TODO: write controller comment
 */
class CmsGeneratorController extends Controller {

    /**
     * The template used by this controller.
     * @var TemplateInterface
     */
    public $template;

    /**
     * The main content block of the page.
     * @var HtmlBlock
     */
    public $content;

    protected $selfedit;

    /**
     * @Action
     * @Logged
     */
    public function index($selfedit = "false") {
        $this->selfedit = $selfedit;
        $this->content->addFile(__DIR__.'../views/cmsGenerator.php', $this);
        $this->template->toHtml();
    }
}
