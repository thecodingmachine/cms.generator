<?php
namespace Mouf\Cms\Generator\Controllers;

use Mouf\Cms\Generator\Model\Dao\Generated\DaoFactory;
use Mouf\Mvc\Splash\Controllers\Controller;
use Mouf\Html\Template\TemplateInterface;
use Mouf\Html\HtmlElement\HtmlBlock;
use Psr\Log\LoggerInterface;
use \Twig_Environment;
use Mouf\Html\Renderer\Twig\TwigTemplate;
use Mouf\Mvc\Splash\HtmlResponse;

/**
 * TODO: write controller comment
 */
class CmsGeneratorController extends Controller {

    /**
     * The template used by this controller.
     * @var TemplateInterface
     */
    private $template;

    /**
     * The main content block of the page.
     * @var HtmlBlock
     */
    private $content;

    protected $selfedit;

    /**
     * @Action
     * @Logged
     */
    public function index($selfedit = "false") {
        $this->selfedit = $selfedit;
        $this->content->addFile(__DIR__.'/../../../../views/cmsGenerator.php', $this);
        $this->template->toHtml();
    }
}
